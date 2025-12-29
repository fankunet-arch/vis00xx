<?php
/**
 * DMS Archive System - Environment Configuration
 * CRITICAL: This file contains sensitive credentials - NEVER commit real values to git
 * Keep this file outside web root and set proper permissions (chmod 600)
 */

defined('DMS_ENTRY') or exit;

return [
    // =====================================================
    // Application Settings
    // =====================================================
    'app_env' => 'development', // development, production
    'app_debug' => true, // Set to false in production
    'timezone_display' => 'Europe/Madrid',
    'timezone_storage' => 'UTC', // Database always uses UTC

    // =====================================================
    // Database Configuration (MariaDB 10)
    // =====================================================
    'db_dsn' => 'mysql:host=localhost;dbname=vis00xx_dms;charset=utf8mb4',
    'db_user' => 'root',
    'db_pass' => '',
    'db_options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '+00:00'",
    ],

    // =====================================================
    // Session Configuration
    // =====================================================
    'session_name' => 'DMS_SESSION',
    'session_lifetime' => 28800, // 8 hours in seconds
    'session_cookie_secure' => false, // Set to true when using HTTPS
    'session_cookie_httponly' => true,
    'session_cookie_samesite' => 'Lax',

    // =====================================================
    // Upload Configuration
    // =====================================================
    'upload_max_mb' => 100, // Maximum file size in MB
    'upload_tmp_dir' => '/home/user/vis00xx/app/dms/tmp/upload',

    // Allowed file extensions (lowercase)
    'allowed_exts' => [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'txt', 'csv', 'rtf', 'odt',
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp',
        'zip', 'rar', '7z', 'tar', 'gz',
        'mp4', 'avi', 'mov', 'wmv', 'flv',
        'mp3', 'wav', 'ogg', 'm4a',
    ],

    // Allowed MIME types
    'allowed_mimes' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'text/rtf',
        'application/rtf',
        'application/vnd.oasis.opendocument.text',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/webp',
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        'application/x-tar',
        'application/gzip',
        'video/mp4',
        'video/x-msvideo',
        'video/quicktime',
        'video/x-ms-wmv',
        'video/x-flv',
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'audio/mp4',
        'application/octet-stream', // Generic binary
    ],

    // =====================================================
    // Preview Configuration
    // =====================================================
    'preview_text_max_bytes' => 2097152, // 2MB max for text preview
    'preview_buffer_size' => 8192, // 8KB chunks for streaming

    // Previewable types
    'preview_types' => [
        'pdf' => ['application/pdf'],
        'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'],
        'text' => ['text/plain', 'text/csv'],
    ],

    // =====================================================
    // S3-Compatible Storage (QNAP QuObjects)
    // =====================================================
    's3_endpoint' => 'https://qnap.example.com:8080', // CHANGE THIS
    's3_access_key' => 'YOUR_ACCESS_KEY_HERE', // CHANGE THIS
    's3_secret_key' => 'YOUR_SECRET_KEY_HERE', // CHANGE THIS
    's3_region' => 'us-east-1', // Default region
    's3_bucket' => 'abcabc-docs-dev', // Production: abcabc-docs-prod
    's3_use_path_style' => true, // QuObjects typically uses path-style
    's3_verify_ssl' => true, // Set to false for self-signed certs in dev
    's3_timeout' => 30, // Connection timeout in seconds
    's3_retry_count' => 2, // Number of retries on failure

    // =====================================================
    // Security Settings
    // =====================================================
    'bcrypt_cost' => 10, // Password hashing cost
    'csrf_token_name' => '_dms_token',
    'max_login_attempts' => 5,
    'login_lockout_minutes' => 15,

    // =====================================================
    // Pagination
    // =====================================================
    'per_page_default' => 25,
    'per_page_options' => [10, 25, 50, 100],

    // =====================================================
    // Paths (Auto-calculated, don't modify)
    // =====================================================
    'path_app' => '/home/user/vis00xx/app/dms',
    'path_public' => '/home/user/vis00xx/dc_html/dms',
    'url_base' => '/dc_html/dms/ap', // Adjust based on web server config
];
