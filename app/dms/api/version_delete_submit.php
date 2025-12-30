<?php
/**
 * DMS Archive System - Delete Version API
 * Soft delete version (mark as deleted)
 * IMPORTANT: Cannot delete the current version
 */

defined('DMS_ENTRY') or exit;

// Require admin role for deletion
dms_require_role('admin');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    dms_json_response(false, null, 'Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Get user info
$user_id = dms_get_current_user_id();

try {
    // Get version_id
    $version_id = isset($_POST['version_id']) ? (int)$_POST['version_id'] : 0;

    if ($version_id <= 0) {
        dms_json_response(false, null, 'Invalid version ID', 'INVALID_VERSION_ID', 400);
    }

    // Verify version exists
    $version = dms_db_get_version($version_id);
    if (!$version) {
        dms_json_response(false, null, 'Version not found', 'VERSION_NOT_FOUND', 404);
    }

    // Check if this is the current version
    if ($version['is_current']) {
        dms_json_response(false, null, 'Cannot delete the current version', 'CANNOT_DELETE_CURRENT', 400);
    }

    // Soft delete: mark as deleted
    dms_db_delete_version($version_id);

    // Log audit
    dms_db_audit_log($user_id, 'delete', 'version', (string)$version_id, $version['doc_id'], $version_id, [
        'filename' => $version['original_file_name'],
        'version_no' => $version['version_no'],
    ]);

    // Success response
    dms_json_response(true, ['version_id' => $version_id], 'Version deleted successfully');

} catch (Exception $e) {
    error_log('[DMS] Delete version error: ' . $e->getMessage());
    dms_json_response(false, null, $DMS_CONFIG['app_debug'] ? $e->getMessage() : 'Delete failed', 'DELETE_ERROR', 500);
}
