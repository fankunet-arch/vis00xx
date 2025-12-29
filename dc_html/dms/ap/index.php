<?php
/**
 * DMS Archive System - Front Controller (Single Entry Point)
 * This is the ONLY public entry point for the DMS application
 */

// Define entry constant to prevent direct access to other files
define('DMS_ENTRY', 1);

// Load bootstrap
require_once __DIR__ . '/../../../app/dms/bootstrap.php';

// =====================================================
// Action Whitelist (Security: Only allowed actions)
// =====================================================

$public_actions = [
    'login',        // View: Login page
    'do_login',     // API: Process login
];

$view_actions = [
    'doc_list',         // View: Document list (default)
    'doc_view',         // View: Document details & versions
    'doc_upload',       // View: Upload document
    'category_list',    // View: Category management
    'category_edit',    // View: Edit category
];

$api_actions = [
    'do_logout',                // API: Logout
    'doc_upload_submit',        // API: Upload document
    'version_upload_submit',    // API: Upload new version
    'doc_update_meta_submit',   // API: Update document metadata
    'doc_delete_submit',        // API: Delete document
    'version_delete_submit',    // API: Delete version
    'file_download',            // API: Download file (proxy)
    'file_preview',             // API: Preview file (proxy)
    'category_save',            // API: Save category
];

$allowed_actions = array_merge($public_actions, $view_actions, $api_actions);

// =====================================================
// Get requested action
// =====================================================

$action = $_GET['action'] ?? 'doc_list';

// Validate action
if (!in_array($action, $allowed_actions, true)) {
    http_response_code(404);
    die('<h1>404 Not Found</h1><p>The requested action does not exist.</p>');
}

// =====================================================
// Check authentication for non-public actions
// =====================================================

if (!in_array($action, $public_actions, true)) {
    dms_require_login();
}

// =====================================================
// Route to appropriate handler
// =====================================================

// API Actions
if (in_array($action, $api_actions, true)) {
    $api_file = DMS_PATH_API . '/' . $action . '.php';
    if (file_exists($api_file)) {
        require $api_file;
    } else {
        http_response_code(500);
        dms_json_response(false, null, 'API handler not found', 'HANDLER_MISSING', 500);
    }
    exit;
}

// View Actions (including public)
$view_map = [
    'login' => 'login',
    'doc_list' => 'doc_list',
    'doc_view' => 'doc_view',
    'doc_upload' => 'doc_upload',
    'category_list' => 'category_list',
    'category_edit' => 'category_edit',
];

if (isset($view_map[$action])) {
    $view_file = DMS_PATH_VIEWS . '/' . $view_map[$action] . '.php';
    if (file_exists($view_file)) {
        require $view_file;
    } else {
        http_response_code(500);
        die('<h1>500 Internal Error</h1><p>View template not found.</p>');
    }
    exit;
}

// This should never be reached due to whitelist check above
http_response_code(500);
die('<h1>500 Internal Error</h1><p>Action handler not configured.</p>');
