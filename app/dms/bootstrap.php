<?php
/**
 * DMS Archive System - Bootstrap & Initialization
 * This file MUST be included after defining DMS_ENTRY constant
 */

defined('DMS_ENTRY') or exit;

// =====================================================
// Error Reporting (Adjust based on environment)
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// =====================================================
// Load Configuration
// =====================================================
$DMS_CONFIG = require __DIR__ . '/config_dms/env_dms.php';

// =====================================================
// Define Path Constants
// =====================================================
define('DMS_PATH_APP', $DMS_CONFIG['path_app']);
define('DMS_PATH_PUBLIC', $DMS_CONFIG['path_public']);
define('DMS_PATH_CONFIG', DMS_PATH_APP . '/config_dms');
define('DMS_PATH_API', DMS_PATH_APP . '/api');
define('DMS_PATH_VIEWS', DMS_PATH_APP . '/views');
define('DMS_PATH_LIB', DMS_PATH_APP . '/lib');
define('DMS_PATH_TMP', DMS_PATH_APP . '/tmp');
define('DMS_PATH_UPLOAD', $DMS_CONFIG['upload_tmp_dir']);

// =====================================================
// Timezone Settings
// =====================================================
date_default_timezone_set($DMS_CONFIG['timezone_storage']);

// =====================================================
// Session Configuration
// =====================================================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', $DMS_CONFIG['session_cookie_httponly'] ? '1' : '0');
    ini_set('session.cookie_samesite', $DMS_CONFIG['session_cookie_samesite']);
    if ($DMS_CONFIG['session_cookie_secure']) {
        ini_set('session.cookie_secure', '1');
    }
    ini_set('session.gc_maxlifetime', (string)$DMS_CONFIG['session_lifetime']);

    session_name($DMS_CONFIG['session_name']);
    session_start();
}

// =====================================================
// Database Connection (PDO with strict settings)
// =====================================================
try {
    $DMS_DB = new PDO(
        $DMS_CONFIG['db_dsn'],
        $DMS_CONFIG['db_user'],
        $DMS_CONFIG['db_pass'],
        $DMS_CONFIG['db_options']
    );
} catch (PDOException $e) {
    // Never expose DB credentials in error messages
    if ($DMS_CONFIG['app_debug']) {
        die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
    } else {
        die('Database connection failed. Please contact system administrator.');
    }
}

// =====================================================
// Load Core Libraries (Order matters)
// =====================================================
require_once DMS_PATH_LIB . '/dms_lib.php';
require_once DMS_PATH_LIB . '/dms_db.php';
require_once DMS_PATH_LIB . '/dms_auth.php';
require_once DMS_PATH_LIB . '/dms_validator.php';
require_once DMS_PATH_LIB . '/dms_s3_client.php';

// =====================================================
// Global Exception Handler (Production-safe)
// =====================================================
set_exception_handler(function($exception) use ($DMS_CONFIG) {
    // Log the error (implement proper logging in production)
    error_log('[DMS] Uncaught Exception: ' . $exception->getMessage());
    error_log('[DMS] Stack Trace: ' . $exception->getTraceAsString());

    // Determine if this is an API request
    $is_api = isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($is_api) {
        // API response
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $DMS_CONFIG['app_debug']
                ? $exception->getMessage()
                : 'An internal error occurred',
            'code' => 'INTERNAL_ERROR'
        ]);
    } else {
        // HTML response
        http_response_code(500);
        if ($DMS_CONFIG['app_debug']) {
            echo '<h1>Error</h1>';
            echo '<p>' . htmlspecialchars($exception->getMessage()) . '</p>';
            echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
        } else {
            echo '<h1>System Error</h1>';
            echo '<p>An unexpected error occurred. Please try again later.</p>';
        }
    }
    exit;
});

// =====================================================
// Bootstrap Complete
// =====================================================
// All required libraries are now loaded and ready
// Database connection is established in $DMS_DB
// Configuration is available in $DMS_CONFIG
