<?php
/**
 * DMS Archive System - Core Library Functions
 * All functions prefixed with 'dms_'
 */

defined('DMS_ENTRY') or exit;

/**
 * Generate UUID v4
 * @return string UUID in format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
 */
function dms_generate_uuid(): string {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Generate random hash (6 chars)
 * @return string
 */
function dms_generate_short_hash(): string {
    return substr(bin2hex(random_bytes(3)), 0, 6);
}

/**
 * Clean filename for storage (lowercase, safe chars only)
 * @param string $filename Original filename
 * @return string Cleaned filename with short hash
 */
function dms_clean_filename(string $filename): string {
    // Get extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $name = pathinfo($filename, PATHINFO_FILENAME);

    // Convert to lowercase and replace unsafe chars with underscore
    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9._-]/', '_', $name);
    $name = preg_replace('/_+/', '_', $name); // Collapse multiple underscores
    $name = trim($name, '_');

    // Limit length
    if (strlen($name) > 100) {
        $name = substr($name, 0, 100);
    }

    // Add short hash to prevent collisions
    $hash = dms_generate_short_hash();

    return $name . '__' . $hash . ($ext ? '.' . $ext : '');
}

/**
 * Build S3 storage key for document version
 * Format: org/{org_id}/doc/{doc_id}/v/{version_no}/{stored_file_name}
 *
 * @param int $org_id
 * @param string $doc_id UUID
 * @param int $version_no
 * @param string $stored_file_name
 * @return string
 */
function dms_build_storage_key(int $org_id, string $doc_id, int $version_no, string $stored_file_name): string {
    return sprintf('org/%d/doc/%s/v/%d/%s', $org_id, $doc_id, $version_no, $stored_file_name);
}

/**
 * Calculate SHA256 hash of file
 * @param string $file_path
 * @return string|false
 */
function dms_hash_file(string $file_path) {
    if (!file_exists($file_path)) {
        return false;
    }
    return hash_file('sha256', $file_path);
}

/**
 * Format bytes to human-readable size
 * @param int $bytes
 * @param int $precision
 * @return string
 */
function dms_format_bytes(int $bytes, int $precision = 2): string {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Sanitize HTML output (prevent XSS)
 * @param string|null $text
 * @return string
 */
function dms_escape(?string $text): string {
    if ($text === null) {
        return '';
    }
    return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * JSON encode with error handling
 * @param mixed $data
 * @return string
 */
function dms_json_encode($data): string {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        throw new RuntimeException('JSON encoding failed: ' . json_last_error_msg());
    }
    return $json;
}

/**
 * JSON decode with error handling
 * @param string $json
 * @param bool $assoc
 * @return mixed
 */
function dms_json_decode(string $json, bool $assoc = true) {
    $data = json_decode($json, $assoc);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException('JSON decoding failed: ' . json_last_error_msg());
    }
    return $data;
}

/**
 * Get client IP address
 * @return string
 */
function dms_get_client_ip(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Get user agent string
 * @return string
 */
function dms_get_user_agent(): string {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

/**
 * Redirect to another page
 * @param string $action
 * @param array $params
 */
function dms_redirect(string $action, array $params = []): void {
    $query = http_build_query(array_merge(['action' => $action], $params));
    header('Location: index.php?' . $query);
    exit;
}

/**
 * Send JSON response (for API endpoints)
 * @param bool $success
 * @param mixed $data
 * @param string|null $message
 * @param string|null $code
 * @param int $http_code
 */
function dms_json_response(bool $success, $data = null, ?string $message = null, ?string $code = null, int $http_code = 200): void {
    http_response_code($http_code);
    header('Content-Type: application/json; charset=utf-8');

    $response = ['success' => $success];
    if ($message !== null) {
        $response['message'] = $message;
    }
    if ($code !== null) {
        $response['code'] = $code;
    }
    if ($data !== null) {
        $response['data'] = $data;
    }

    echo dms_json_encode($response);
    exit;
}

/**
 * Check if file extension is allowed
 * @param string $ext
 * @return bool
 */
function dms_is_allowed_ext(string $ext): bool {
    global $DMS_CONFIG;
    return in_array(strtolower($ext), $DMS_CONFIG['allowed_exts'], true);
}

/**
 * Check if MIME type is allowed
 * @param string $mime
 * @return bool
 */
function dms_is_allowed_mime(string $mime): bool {
    global $DMS_CONFIG;
    return in_array(strtolower($mime), $DMS_CONFIG['allowed_mimes'], true);
}

/**
 * Determine if file type is previewable
 * @param string $mime_type
 * @return string|false Returns preview type (pdf, image, text) or false
 */
function dms_get_preview_type(string $mime_type) {
    global $DMS_CONFIG;
    $mime = strtolower($mime_type);

    foreach ($DMS_CONFIG['preview_types'] as $type => $mimes) {
        if (in_array($mime, $mimes, true)) {
            return $type;
        }
    }
    return false;
}

/**
 * Stream file output (for download/preview)
 * Supports Range requests for large files
 *
 * @param resource $stream Input stream
 * @param int $total_size Total file size
 * @param string $mime_type
 * @param string $filename
 * @param bool $inline true for preview, false for download
 */
function dms_stream_output($stream, int $total_size, string $mime_type, string $filename, bool $inline = false): void {
    global $DMS_CONFIG;

    // Disable output buffering
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Check for Range request
    $range_header = $_SERVER['HTTP_RANGE'] ?? '';
    $supports_range = !empty($range_header) && preg_match('/bytes=(\d+)-(\d*)/i', $range_header, $matches);

    if ($supports_range) {
        $start = (int)$matches[1];
        $end = !empty($matches[2]) ? (int)$matches[2] : $total_size - 1;
        $end = min($end, $total_size - 1);
        $length = $end - $start + 1;

        // Send 206 Partial Content
        http_response_code(206);
        header('Content-Range: bytes ' . $start . '-' . $end . '/' . $total_size);
        header('Content-Length: ' . $length);
    } else {
        // Normal response
        http_response_code(200);
        header('Content-Length: ' . $total_size);
        $start = 0;
        $end = $total_size - 1;
        $length = $total_size;
    }

    header('Content-Type: ' . $mime_type);
    header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . addslashes($filename) . '"');
    header('Accept-Ranges: bytes');
    header('Cache-Control: private, must-revalidate');
    header('Pragma: private');

    // Stream the content
    if ($start > 0 && is_resource($stream)) {
        fseek($stream, $start);
    }

    $buffer_size = $DMS_CONFIG['preview_buffer_size'] ?? 8192;
    $bytes_sent = 0;

    while (!feof($stream) && $bytes_sent < $length) {
        $chunk_size = min($buffer_size, $length - $bytes_sent);
        $chunk = fread($stream, $chunk_size);
        if ($chunk === false) {
            break;
        }
        echo $chunk;
        $bytes_sent += strlen($chunk);
        flush();
    }

    if (is_resource($stream)) {
        fclose($stream);
    }
}

/**
 * Render a view file
 * @param string $view_name View filename without .php
 * @param array $data Variables to extract into view scope
 */
function dms_render_view(string $view_name, array $data = []): void {
    extract($data, EXTR_SKIP);
    $view_file = DMS_PATH_VIEWS . '/' . $view_name . '.php';

    if (!file_exists($view_file)) {
        throw new RuntimeException('View not found: ' . $view_name);
    }

    require $view_file;
    exit;
}

/**
 * Get current timestamp in UTC (for DB storage)
 * @return string Format: Y-m-d H:i:s.u
 */
function dms_now_utc(): string {
    return (new DateTime('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s.u');
}

/**
 * Format datetime for display (convert UTC to display timezone)
 * @param string $utc_datetime
 * @param string $format
 * @return string
 */
function dms_format_datetime(string $utc_datetime, string $format = 'Y-m-d H:i:s'): string {
    global $DMS_CONFIG;
    try {
        $dt = new DateTime($utc_datetime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone($DMS_CONFIG['timezone_display']));
        return $dt->format($format);
    } catch (Exception $e) {
        return $utc_datetime;
    }
}
