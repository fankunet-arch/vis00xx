<?php
/**
 * DMS Archive System - Delete Document API
 * Soft delete (mark as deleted, don't remove from storage)
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
    // Get doc_id
    $doc_id = trim($_POST['doc_id'] ?? '');

    if (empty($doc_id) || !dms_validate_uuid($doc_id)) {
        dms_json_response(false, null, 'Invalid document ID', 'INVALID_DOC_ID', 400);
    }

    // Verify document exists
    $doc = dms_db_get_document($doc_id);
    if (!$doc) {
        dms_json_response(false, null, 'Document not found', 'DOC_NOT_FOUND', 404);
    }

    // Soft delete: mark as deleted
    dms_db_update_document($doc_id, ['status' => 'deleted']);

    // Log audit
    dms_db_audit_log($user_id, 'delete', 'document', $doc_id, $doc_id, null, [
        'title' => $doc['title'],
    ]);

    // Success response
    dms_json_response(true, ['doc_id' => $doc_id], 'Document deleted successfully');

} catch (Exception $e) {
    error_log('[DMS] Delete document error: ' . $e->getMessage());
    dms_json_response(false, null, $DMS_CONFIG['app_debug'] ? $e->getMessage() : 'Delete failed', 'DELETE_ERROR', 500);
}
