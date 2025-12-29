<?php
/**
 * DMS Archive System - File Download API (Proxy Mode)
 * CRITICAL: Files are NEVER directly accessible via URL
 * All downloads are proxied through this script after authentication
 */

defined('DMS_ENTRY') or exit;

// Require download permission
dms_require_permission('download');

// Get user info
$user_id = dms_get_current_user_id();

try {
    // Get version_id from request
    $version_id = isset($_GET['version_id']) ? (int)$_GET['version_id'] : 0;

    if ($version_id <= 0) {
        http_response_code(400);
        die('Invalid version ID');
    }

    // Get version metadata from database
    $version = dms_db_get_version($version_id);

    if (!$version) {
        http_response_code(404);
        die('File not found');
    }

    // Check if version is deleted
    if ($version['is_deleted']) {
        http_response_code(404);
        die('File has been deleted');
    }

    // Verify document exists and is accessible
    $doc = dms_db_get_document($version['doc_id']);
    if (!$doc || $doc['status'] === 'deleted') {
        http_response_code(404);
        die('Document not found');
    }

    // Get file from S3 storage
    $s3_result = dms_s3_get_object($version['storage_key']);

    if (!$s3_result['success']) {
        error_log('[DMS] Download failed for version ' . $version_id . ': ' . $s3_result['error']);
        http_response_code(500);
        die('Failed to retrieve file from storage');
    }

    // Log download audit
    dms_db_audit_log(
        $user_id,
        'download',
        'version',
        (string)$version_id,
        $version['doc_id'],
        $version_id,
        [
            'filename' => $version['original_file_name'],
            'size' => $version['file_size'],
        ]
    );

    // Stream file to user (attachment mode = download)
    dms_stream_output(
        $s3_result['stream'],
        $version['file_size'],
        $version['mime_type'],
        $version['original_file_name'],
        false  // false = attachment (download), not inline
    );

} catch (Exception $e) {
    error_log('[DMS] Download error: ' . $e->getMessage());
    http_response_code(500);
    die('Download failed');
}
