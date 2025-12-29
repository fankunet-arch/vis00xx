<?php
/**
 * DMS Archive System - S3-Compatible Storage Client
 * Lightweight S3 client for QNAP QuObjects
 * All functions prefixed with 'dms_s3_'
 */

defined('DMS_ENTRY') or exit;

/**
 * Sign S3 request using AWS Signature Version 4
 * @param string $method HTTP method
 * @param string $uri Request URI
 * @param array $headers Request headers
 * @param string $payload Request body
 * @return array Headers with Authorization
 */
function dms_s3_sign_request(string $method, string $uri, array $headers, string $payload = ''): array {
    global $DMS_CONFIG;

    $access_key = $DMS_CONFIG['s3_access_key'];
    $secret_key = $DMS_CONFIG['s3_secret_key'];
    $region = $DMS_CONFIG['s3_region'];
    $service = 's3';

    // Parse endpoint
    $endpoint_parts = parse_url($DMS_CONFIG['s3_endpoint']);
    $host = $endpoint_parts['host'] . (isset($endpoint_parts['port']) ? ':' . $endpoint_parts['port'] : '');

    // Get current datetime
    $datetime = gmdate('Ymd\THis\Z');
    $date = substr($datetime, 0, 8);

    // Add required headers
    $headers['Host'] = $host;
    $headers['X-Amz-Date'] = $datetime;
    $headers['X-Amz-Content-Sha256'] = hash('sha256', $payload);

    // Sort headers
    ksort($headers);

    // Create canonical request
    $canonical_headers = '';
    $signed_headers = [];
    foreach ($headers as $key => $value) {
        $canonical_headers .= strtolower($key) . ':' . trim($value) . "\n";
        $signed_headers[] = strtolower($key);
    }
    sort($signed_headers);
    $signed_headers_str = implode(';', $signed_headers);

    $canonical_request = implode("\n", [
        $method,
        $uri,
        '', // Query string (empty for now)
        $canonical_headers,
        $signed_headers_str,
        hash('sha256', $payload)
    ]);

    // Create string to sign
    $credential_scope = "{$date}/{$region}/{$service}/aws4_request";
    $string_to_sign = implode("\n", [
        'AWS4-HMAC-SHA256',
        $datetime,
        $credential_scope,
        hash('sha256', $canonical_request)
    ]);

    // Calculate signature
    $k_date = hash_hmac('sha256', $date, 'AWS4' . $secret_key, true);
    $k_region = hash_hmac('sha256', $region, $k_date, true);
    $k_service = hash_hmac('sha256', $service, $k_region, true);
    $k_signing = hash_hmac('sha256', 'aws4_request', $k_service, true);
    $signature = hash_hmac('sha256', $string_to_sign, $k_signing);

    // Build authorization header
    $headers['Authorization'] = "AWS4-HMAC-SHA256 Credential={$access_key}/{$credential_scope}, SignedHeaders={$signed_headers_str}, Signature={$signature}";

    return $headers;
}

/**
 * Build S3 URL
 * @param string $key Object key
 * @return string Full URL
 */
function dms_s3_build_url(string $key): string {
    global $DMS_CONFIG;

    $endpoint = rtrim($DMS_CONFIG['s3_endpoint'], '/');
    $bucket = $DMS_CONFIG['s3_bucket'];

    if ($DMS_CONFIG['s3_use_path_style']) {
        return "{$endpoint}/{$bucket}/{$key}";
    } else {
        // Virtual-hosted style
        return str_replace('://', '://' . $bucket . '.', $endpoint) . '/' . $key;
    }
}

/**
 * Upload file to S3
 * @param string $local_path Local file path
 * @param string $key S3 object key
 * @param string $mime_type Content type
 * @return array ['success' => bool, 'etag' => string|null, 'error' => string|null]
 */
function dms_s3_put_object(string $local_path, string $key, string $mime_type): array {
    global $DMS_CONFIG;

    try {
        if (!file_exists($local_path)) {
            return ['success' => false, 'etag' => null, 'error' => 'Local file not found'];
        }

        $file_content = file_get_contents($local_path);
        if ($file_content === false) {
            return ['success' => false, 'etag' => null, 'error' => 'Failed to read local file'];
        }

        $url = dms_s3_build_url($key);
        $uri = '/' . $DMS_CONFIG['s3_bucket'] . '/' . $key;

        $headers = [
            'Content-Type' => $mime_type,
            'Content-Length' => strlen($file_content),
        ];

        $headers = dms_s3_sign_request('PUT', $uri, $headers, $file_content);

        // Build cURL headers
        $curl_headers = [];
        foreach ($headers as $k => $v) {
            $curl_headers[] = "{$k}: {$v}";
        }

        // Execute request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_content);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $DMS_CONFIG['s3_timeout']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $DMS_CONFIG['s3_verify_ssl']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $DMS_CONFIG['s3_verify_ssl'] ? 2 : 0);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $http_code >= 400) {
            return [
                'success' => false,
                'etag' => null,
                'error' => "S3 PUT failed (HTTP {$http_code}): {$curl_error}"
            ];
        }

        // Extract ETag from response headers
        $etag = null;
        if (preg_match('/ETag:\s*"?([a-f0-9]+)"?/i', $response, $matches)) {
            $etag = $matches[1];
        }

        return ['success' => true, 'etag' => $etag, 'error' => null];

    } catch (Exception $e) {
        return ['success' => false, 'etag' => null, 'error' => $e->getMessage()];
    }
}

/**
 * Get object from S3 (returns stream resource)
 * @param string $key S3 object key
 * @param int|null $range_start
 * @param int|null $range_end
 * @return array ['success' => bool, 'stream' => resource|null, 'size' => int, 'error' => string|null]
 */
function dms_s3_get_object(string $key, ?int $range_start = null, ?int $range_end = null): array {
    global $DMS_CONFIG;

    try {
        $url = dms_s3_build_url($key);
        $uri = '/' . $DMS_CONFIG['s3_bucket'] . '/' . $key;

        $headers = [];

        // Add Range header if specified
        if ($range_start !== null) {
            $range_header = "bytes={$range_start}-";
            if ($range_end !== null) {
                $range_header .= $range_end;
            }
            $headers['Range'] = $range_header;
        }

        $headers = dms_s3_sign_request('GET', $uri, $headers);

        // Build cURL headers
        $curl_headers = [];
        foreach ($headers as $k => $v) {
            $curl_headers[] = "{$k}: {$v}";
        }

        // Create temporary stream to store response
        $temp_stream = fopen('php://temp', 'r+');
        if (!$temp_stream) {
            return ['success' => false, 'stream' => null, 'size' => 0, 'error' => 'Failed to create temp stream'];
        }

        // Execute request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($ch, CURLOPT_FILE, $temp_stream);
        curl_setopt($ch, CURLOPT_TIMEOUT, $DMS_CONFIG['s3_timeout']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $DMS_CONFIG['s3_verify_ssl']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $DMS_CONFIG['s3_verify_ssl'] ? 2 : 0);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $size = curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code >= 400) {
            fclose($temp_stream);
            return [
                'success' => false,
                'stream' => null,
                'size' => 0,
                'error' => "S3 GET failed (HTTP {$http_code}): {$curl_error}"
            ];
        }

        // Rewind stream to beginning
        rewind($temp_stream);

        return ['success' => true, 'stream' => $temp_stream, 'size' => (int)$size, 'error' => null];

    } catch (Exception $e) {
        return ['success' => false, 'stream' => null, 'size' => 0, 'error' => $e->getMessage()];
    }
}

/**
 * Delete object from S3
 * @param string $key S3 object key
 * @return array ['success' => bool, 'error' => string|null]
 */
function dms_s3_delete_object(string $key): array {
    global $DMS_CONFIG;

    try {
        $url = dms_s3_build_url($key);
        $uri = '/' . $DMS_CONFIG['s3_bucket'] . '/' . $key;

        $headers = dms_s3_sign_request('DELETE', $uri, []);

        // Build cURL headers
        $curl_headers = [];
        foreach ($headers as $k => $v) {
            $curl_headers[] = "{$k}: {$v}";
        }

        // Execute request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $DMS_CONFIG['s3_timeout']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $DMS_CONFIG['s3_verify_ssl']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $DMS_CONFIG['s3_verify_ssl'] ? 2 : 0);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // 204 = successfully deleted, 404 = already gone (both OK)
        if ($http_code !== 204 && $http_code !== 404) {
            return ['success' => false, 'error' => "S3 DELETE failed (HTTP {$http_code}): {$curl_error}"];
        }

        return ['success' => true, 'error' => null];

    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Check if object exists in S3
 * @param string $key S3 object key
 * @return bool
 */
function dms_s3_object_exists(string $key): bool {
    global $DMS_CONFIG;

    try {
        $url = dms_s3_build_url($key);
        $uri = '/' . $DMS_CONFIG['s3_bucket'] . '/' . $key;

        $headers = dms_s3_sign_request('HEAD', $uri, []);

        // Build cURL headers
        $curl_headers = [];
        foreach ($headers as $k => $v) {
            $curl_headers[] = "{$k}: {$v}";
        }

        // Execute request
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $DMS_CONFIG['s3_timeout']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $DMS_CONFIG['s3_verify_ssl']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $DMS_CONFIG['s3_verify_ssl'] ? 2 : 0);

        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $http_code === 200;

    } catch (Exception $e) {
        return false;
    }
}
