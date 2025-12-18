<?php
/**
 * Generic S3-Compatible Storage Client
 * 文件路径: app/vis/lib/r2_client.php
 * 说明: 通用S3客户端，支持 QNAP QuObjects, MinIO, AWS S3, Cloudflare R2
 * 已针对 QNAP 优化路径风格 (Path Style) 和 SSL 验证
 */

class R2Client {
    private $accessKeyId;
    private $secretAccessKey;
    private $bucketName;
    private $endpoint;
    private $region;

    /**
     * 构造函数
     * @param string $endpoint 完整的S3端点URL
     * @param string $accessKeyId
     * @param string $secretAccessKey
     * @param string $bucketName
     * @param string $region
     */
    public function __construct($endpoint, $accessKeyId, $secretAccessKey, $bucketName, $region = 'us-east-1') {
        // 移除末尾斜杠，确保路径拼接正确
        $this->endpoint = rtrim($endpoint, '/');
        $this->accessKeyId = $accessKeyId;
        $this->secretAccessKey = $secretAccessKey;
        $this->bucketName = $bucketName;
        $this->region = $region;
    }

    /**
     * 上传文件 (使用高效流式上传)
     */
    public function putObject($key, $filePath, $contentType = 'application/octet-stream') {
        try {
            if (!file_exists($filePath)) {
                return ['success' => false, 'message' => 'Local file not found'];
            }

            $fileSize = filesize($filePath);
            // 计算文件哈希
            $contentSha256 = hash_file('sha256', $filePath);
            $timestamp = gmdate('Ymd\THis\Z');
            $date = gmdate('Ymd');

            // 构建资源路径: endpoint/bucket/key (Path Style)
            // 注意：key 开头不应带 /
            $key = ltrim($key, '/');
            $uri = "/{$this->bucketName}/{$key}";
            $url = "{$this->endpoint}{$uri}";
            
            // 准备请求头
            $headers = [
                'Content-Type' => $contentType,
                'Content-Length' => $fileSize,
                'x-amz-date' => $timestamp,
                'x-amz-content-sha256' => $contentSha256,
                // Host 头必须包含在签名中
                'Host' => parse_url($this->endpoint, PHP_URL_HOST) . (parse_url($this->endpoint, PHP_URL_PORT) ? ':' . parse_url($this->endpoint, PHP_URL_PORT) : '')
            ];

            // 计算签名
            $signature = $this->signRequest('PUT', $uri, $headers, $contentSha256, $timestamp, $date);
            $headers['Authorization'] = $signature;

            // 打开文件句柄
            $fp = fopen($filePath, 'r');
            if (!$fp) {
                return ['success' => false, 'message' => 'Unable to open local file'];
            }

            // 发送请求
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_PUT => true,
                CURLOPT_INFILE => $fp,
                CURLOPT_INFILESIZE => $fileSize,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
                // 关键：QNAP 自签名证书通常需要关闭验证
                CURLOPT_SSL_VERIFYPEER => false, 
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 3600 // 大文件上传允许较长时间
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            fclose($fp);

            if ($httpCode >= 200 && $httpCode < 300) {
                return ['success' => true, 'message' => 'Upload successful'];
            } else {
                return ['success' => false, 'message' => "Upload failed: HTTP $httpCode. Error: $error. Response: $response"];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 删除对象
     */
    public function deleteObject($key) {
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        $contentSha256 = hash('sha256', ''); // 空负载的哈希
        
        $key = ltrim($key, '/');
        $uri = "/{$this->bucketName}/{$key}";
        $url = "{$this->endpoint}{$uri}";

        $headers = [
            'x-amz-date' => $timestamp,
            'x-amz-content-sha256' => $contentSha256,
            'Host' => parse_url($this->endpoint, PHP_URL_HOST) . (parse_url($this->endpoint, PHP_URL_PORT) ? ':' . parse_url($this->endpoint, PHP_URL_PORT) : '')
        ];

        $signature = $this->signRequest('DELETE', $uri, $headers, $contentSha256, $timestamp, $date);
        $headers['Authorization'] = $signature;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 204 No Content 是删除成功的标准响应，200 也可以接受
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => "Delete failed HTTP $httpCode"];
    }

    /**
     * 获取签名URL (用于前端播放)
     */
    public function getPresignedUrl($key, $expiresIn = 300) {
        $timestamp = time();
        $amzDate = gmdate('Ymd\THis\Z', $timestamp);
        $date = gmdate('Ymd', $timestamp);
        
        $key = ltrim($key, '/');
        $host = parse_url($this->endpoint, PHP_URL_HOST) . (parse_url($this->endpoint, PHP_URL_PORT) ? ':' . parse_url($this->endpoint, PHP_URL_PORT) : '');
        $uri = "/{$this->bucketName}/{$key}";
        
        $queryParams = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => "{$this->accessKeyId}/{$date}/{$this->region}/s3/aws4_request",
            'X-Amz-Date' => $amzDate,
            'X-Amz-Expires' => $expiresIn,
            'X-Amz-SignedHeaders' => 'host',
        ];
        
        $canonicalUri = $uri;
        $canonicalQuery = [];
        ksort($queryParams);
        foreach ($queryParams as $k => $v) {
            $canonicalQuery[] = rawurlencode($k) . '=' . rawurlencode($v);
        }
        $canonicalQueryStr = implode('&', $canonicalQuery);
        
        $canonicalHeaders = "host:{$host}\n";
        $signedHeaders = "host";
        $payloadHash = "UNSIGNED-PAYLOAD";
        
        $canonicalRequest = "GET\n{$canonicalUri}\n{$canonicalQueryStr}\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        
        $scope = "{$date}/{$this->region}/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$scope}\n" . hash('sha256', $canonicalRequest);
        
        $signingKey = $this->getSigningKey($date, $this->region, 's3', $this->secretAccessKey);
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);
        
        return "{$this->endpoint}{$uri}?{$canonicalQueryStr}&X-Amz-Signature={$signature}";
    }

    // --- 内部辅助函数 ---

    private function signRequest($method, $uri, $headers, $payloadHash, $amzDate, $dateStr) {
        $canonicalHeaders = "";
        $signedHeadersList = [];
        ksort($headers);
        foreach ($headers as $k => $v) {
            $lowerK = strtolower($k);
            $canonicalHeaders .= "{$lowerK}:{$v}\n";
            $signedHeadersList[] = $lowerK;
        }
        $signedHeaders = implode(';', $signedHeadersList);
        
        $canonicalRequest = "{$method}\n{$uri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        
        $scope = "{$dateStr}/{$this->region}/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$amzDate}\n{$scope}\n" . hash('sha256', $canonicalRequest);
        
        $key = $this->getSigningKey($dateStr, $this->region, 's3', $this->secretAccessKey);
        return "AWS4-HMAC-SHA256 Credential={$this->accessKeyId}/{$scope}, SignedHeaders={$signedHeaders}, Signature=" . hash_hmac('sha256', $stringToSign, $key);
    }

    private function getSigningKey($date, $region, $service, $secretKey) {
        $kDate = hash_hmac('sha256', $date, 'AWS4' . $secretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }
    
    private function formatHeaders($headers) {
        $res = [];
        foreach($headers as $k => $v) $res[] = "$k: $v";
        return $res;
    }
}