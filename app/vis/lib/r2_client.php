<?php
/**
 * Cloudflare R2 Storage Client (S3-Compatible)
 * 文件路径: app/vis/lib/r2_client.php
 * 说明: 轻量级R2存储客户端，实现S3协议的核心功能
 *
 * ============================================
 * 技术参考文档（维护时必读）
 * ============================================
 *
 * 1. AWS Signature Version 4 签名算法（核心）
 *    https://docs.aws.amazon.com/general/latest/gr/signature-version-4.html
 *    https://docs.aws.amazon.com/general/latest/gr/sigv4-create-canonical-request.html
 *
 * 2. Cloudflare R2 官方文档
 *    https://developers.cloudflare.com/r2/
 *    https://developers.cloudflare.com/r2/api/s3/
 *
 * 3. S3 API 参考
 *    PutObject: https://docs.aws.amazon.com/AmazonS3/latest/API/API_PutObject.html
 *    DeleteObject: https://docs.aws.amazon.com/AmazonS3/latest/API/API_DeleteObject.html
 *    Presigned URLs: https://docs.aws.amazon.com/AmazonS3/latest/API/sigv4-query-string-auth.html
 *
 * ============================================
 * 维护注意事项
 * ============================================
 *
 * - R2 完全兼容 AWS S3 API（Signature V4）
 * - 端点格式: https://{accountId}.r2.cloudflarestorage.com
 * - 区域代码固定为 'auto'（R2特有）
 * - 如遇签名错误，请检查：
 *   1. 时间戳格式（必须是 UTC 时间）
 *   2. Canonical Request 构建顺序
 *   3. 字符串编码问题（URL编码）
 *
 * ============================================
 */

class R2Client {
    private $accountId;
    private $accessKeyId;
    private $secretAccessKey;
    private $bucketName;
    private $endpoint;
    private $region;

    /**
     * 构造函数
     */
    public function __construct($accountId, $accessKeyId, $secretAccessKey, $bucketName, $region = 'auto') {
        $this->accountId = $accountId;
        $this->accessKeyId = $accessKeyId;
        $this->secretAccessKey = $secretAccessKey;
        $this->bucketName = $bucketName;
        $this->region = $region;
        $this->endpoint = "https://{$accountId}.r2.cloudflarestorage.com";
    }

    /**
     * 上传文件到R2
     * @param string $key 对象键（路径）
     * @param string $filePath 本地文件路径
     * @param string $contentType MIME类型
     * @return array ['success' => bool, 'message' => string, 'etag' => string|null]
     */
    public function putObject($key, $filePath, $contentType = 'application/octet-stream') {
        try {
            if (!file_exists($filePath)) {
                return ['success' => false, 'message' => 'File not found', 'etag' => null];
            }

            $fileContent = file_get_contents($filePath);
            $contentLength = strlen($fileContent);
            $contentMD5 = base64_encode(md5($fileContent, true));

            $url = "{$this->endpoint}/{$this->bucketName}/{$key}";
            $timestamp = gmdate('Ymd\THis\Z');
            $date = gmdate('Ymd');

            // 构建请求头
            $headers = [
                'Content-Type' => $contentType,
                'Content-Length' => $contentLength,
                'Content-MD5' => $contentMD5,
                'x-amz-date' => $timestamp,
                'x-amz-content-sha256' => hash('sha256', $fileContent),
            ];

            // AWS Signature V4
            $signature = $this->signRequest('PUT', $key, $headers, $fileContent, $timestamp);
            $headers['Authorization'] = $signature;

            // 发送请求
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => $fileContent,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return [
                    'success' => true,
                    'message' => 'Upload successful',
                    'etag' => isset($response['ETag']) ? $response['ETag'] : null
                ];
            } else {
                vis_log("R2 upload failed: HTTP $httpCode - $response", 'ERROR');
                return [
                    'success' => false,
                    'message' => "Upload failed: HTTP $httpCode",
                    'etag' => null,
                    'response' => $response
                ];
            }
        } catch (Exception $e) {
            vis_log("R2 upload exception: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'message' => $e->getMessage(), 'etag' => null];
        }
    }

    /**
     * 删除R2中的对象
     * @param string $key 对象键
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteObject($key) {
        try {
            $url = "{$this->endpoint}/{$this->bucketName}/{$key}";
            $timestamp = gmdate('Ymd\THis\Z');

            $headers = [
                'x-amz-date' => $timestamp,
                'x-amz-content-sha256' => hash('sha256', ''),
            ];

            $signature = $this->signRequest('DELETE', $key, $headers, '', $timestamp);
            $headers['Authorization'] = $signature;

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 204 || $httpCode === 200) {
                return ['success' => true, 'message' => 'Delete successful'];
            } else {
                vis_log("R2 delete failed: HTTP $httpCode - $response", 'ERROR');
                return ['success' => false, 'message' => "Delete failed: HTTP $httpCode"];
            }
        } catch (Exception $e) {
            vis_log("R2 delete exception: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * 生成预签名URL（用于临时访问）
     * @param string $key 对象键
     * @param int $expiresIn 有效期（秒）
     * @return string|null 签名URL
     */
    public function getPresignedUrl($key, $expiresIn = 300) {
        try {
            $timestamp = time();
            $expiration = $timestamp + $expiresIn;
            $date = gmdate('Ymd', $timestamp);
            $isoDate = gmdate('Ymd\THis\Z', $timestamp);

            $scope = "{$date}/{$this->region}/s3/aws4_request";
            $credentialParam = "{$this->accessKeyId}/{$scope}";

            $url = "{$this->endpoint}/{$this->bucketName}/{$key}";

            $queryParams = [
                'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
                'X-Amz-Credential' => $credentialParam,
                'X-Amz-Date' => $isoDate,
                'X-Amz-Expires' => $expiresIn,
                'X-Amz-SignedHeaders' => 'host',
            ];

            ksort($queryParams);
            $canonicalQueryString = http_build_query($queryParams);

            $canonicalRequest = implode("\n", [
                'GET',
                "/{$this->bucketName}/{$key}",
                $canonicalQueryString,
                "host:{$this->accountId}.r2.cloudflarestorage.com",
                '',
                'host',
                'UNSIGNED-PAYLOAD'
            ]);

            $stringToSign = implode("\n", [
                'AWS4-HMAC-SHA256',
                $isoDate,
                $scope,
                hash('sha256', $canonicalRequest)
            ]);

            $signingKey = $this->getSigningKey($date, $this->region, 's3');
            $signature = hash_hmac('sha256', $stringToSign, $signingKey);

            return "{$url}?{$canonicalQueryString}&X-Amz-Signature={$signature}";
        } catch (Exception $e) {
            vis_log("R2 presigned URL exception: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }

    /**
     * AWS Signature V4 签名
     */
    private function signRequest($method, $key, &$headers, $payload, $timestamp) {
        $date = substr($timestamp, 0, 8);
        $scope = "{$date}/{$this->region}/s3/aws4_request";

        $canonicalHeaders = '';
        $signedHeaders = '';
        ksort($headers);
        foreach ($headers as $name => $value) {
            $lowerName = strtolower($name);
            $canonicalHeaders .= "{$lowerName}:{$value}\n";
            $signedHeaders .= ($signedHeaders ? ';' : '') . $lowerName;
        }

        $canonicalRequest = implode("\n", [
            $method,
            "/{$this->bucketName}/{$key}",
            '',
            $canonicalHeaders,
            $signedHeaders,
            hash('sha256', $payload)
        ]);

        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $timestamp,
            $scope,
            hash('sha256', $canonicalRequest)
        ]);

        $signingKey = $this->getSigningKey($date, $this->region, 's3');
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);

        return "AWS4-HMAC-SHA256 Credential={$this->accessKeyId}/{$scope}, SignedHeaders={$signedHeaders}, Signature={$signature}";
    }

    /**
     * 生成签名密钥
     */
    private function getSigningKey($date, $region, $service) {
        $kDate = hash_hmac('sha256', $date, 'AWS4' . $this->secretAccessKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        return hash_hmac('sha256', 'aws4_request', $kService, true);
    }

    /**
     * 格式化请求头
     */
    private function formatHeaders($headers) {
        $formatted = [];
        foreach ($headers as $name => $value) {
            $formatted[] = "{$name}: {$value}";
        }
        return $formatted;
    }
}
