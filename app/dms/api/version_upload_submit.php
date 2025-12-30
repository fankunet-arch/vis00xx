<?php
/**
 * DMS Archive System - Version Upload API
 * Add new version to existing document
 */

defined('DMS_ENTRY') or exit;

// Require upload permission
dms_require_permission('upload');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    dms_json_response(false, null, 'Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Get user info
$user_id = dms_get_current_user_id();

try {
    // Get form data
    $doc_id = trim($_POST['doc_id'] ?? '');
    $upload_mode = $_POST['upload_mode'] ?? 'append';
    $notes = trim($_POST['notes'] ?? '');

    // Validate doc_id
    if (empty($doc_id) || !dms_validate_uuid($doc_id)) {
        dms_json_response(false, null, 'Invalid document ID', 'INVALID_DOC_ID', 400);
    }

    // Verify document exists
    $doc = dms_db_get_document($doc_id);
    if (!$doc) {
        dms_json_response(false, null, 'Document not found', 'DOC_NOT_FOUND', 404);
    }

    if (!dms_validate_upload_mode($upload_mode)) {
        dms_json_response(false, null, 'Invalid upload mode', 'VALIDATION_ERROR', 400);
    }

    // Validate file upload
    if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
        dms_json_response(false, null, 'No file uploaded', 'NO_FILE', 400);
    }

    $file_validation = dms_validate_uploaded_file($_FILES['file']);
    if (!$file_validation['valid']) {
        dms_json_response(false, null, $file_validation['error'], 'FILE_VALIDATION_ERROR', 400);
    }

    $file_info = $file_validation['info'];

    // Start transaction
    $DMS_DB->beginTransaction();

    try {
        // Generate next version number
        $version_no = dms_db_get_next_version_no($doc_id);

        // Prepare storage
        $stored_file_name = dms_clean_filename($file_info['original_name']);
        $storage_key = dms_build_storage_key(1, $doc_id, $version_no, $stored_file_name);
        $storage_bucket = $DMS_CONFIG['s3_bucket'];

        // Upload to S3
        $s3_result = dms_s3_put_object(
            $file_info['tmp_path'],
            $storage_key,
            $file_info['mime_type']
        );

        if (!$s3_result['success']) {
            throw new Exception('Failed to upload file to storage: ' . $s3_result['error']);
        }

        // Create version record
        $version_id = dms_db_create_version([
            'doc_id' => $doc_id,
            'version_no' => $version_no,
            'upload_mode' => $upload_mode,
            'storage_bucket' => $storage_bucket,
            'storage_key' => $storage_key,
            'original_file_name' => $file_info['original_name'],
            'stored_file_name' => $stored_file_name,
            'file_ext' => $file_info['ext'],
            'mime_type' => $file_info['mime_type'],
            'file_size' => $file_info['size'],
            'sha256' => $file_info['sha256'],
            'etag' => $s3_result['etag'],
            'is_current' => 1,
            'uploaded_by' => $user_id,
            'notes' => $notes,
        ]);

        // Set as current version
        dms_db_set_current_version($doc_id, $version_id);

        // Commit transaction
        $DMS_DB->commit();

        // Log audit
        dms_db_audit_log($user_id, 'upload', 'version', $version_id, $doc_id, $version_id, [
            'filename' => $file_info['original_name'],
            'size' => $file_info['size'],
            'upload_mode' => $upload_mode,
        ]);

        // Success response
        dms_json_response(true, [
            'doc_id' => $doc_id,
            'version_id' => $version_id,
            'version_no' => $version_no,
        ], 'Version uploaded successfully');

    } catch (Exception $e) {
        $DMS_DB->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('[DMS] Version upload error: ' . $e->getMessage());
    dms_json_response(false, null, $DMS_CONFIG['app_debug'] ? $e->getMessage() : 'Upload failed', 'UPLOAD_ERROR', 500);
}
