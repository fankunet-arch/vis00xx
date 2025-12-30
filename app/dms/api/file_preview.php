<?php
/**
 * DMS Archive System - File Preview API (Proxy Mode)
 * CRITICAL: Files are NEVER directly accessible via URL
 * Supports inline preview for PDF, images, and text files
 * Supports Range requests for large PDFs
 */

defined('DMS_ENTRY') or exit;

// Require preview permission
dms_require_permission('preview');

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

    // Check if file type is previewable
    $preview_type = dms_get_preview_type($version['mime_type']);

    if ($preview_type === false) {
        http_response_code(400);
        die('This file type cannot be previewed. <a href="index.php?action=file_download&version_id=' . $version_id . '">Download instead</a>');
    }

    // Handle text preview specially (size limit)
    if ($preview_type === 'text') {
        global $DMS_CONFIG;
        $max_size = $DMS_CONFIG['preview_text_max_bytes'];

        if ($version['file_size'] > $max_size) {
            http_response_code(400);
            die('Text file too large to preview (max ' . dms_format_bytes($max_size) . ')');
        }
    }

    // Parse Range header (for PDF and images)
    $range_start = null;
    $range_end = null;

    if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d+)-(\d*)/i', $_SERVER['HTTP_RANGE'], $matches)) {
        $range_start = (int)$matches[1];
        $range_end = !empty($matches[2]) ? (int)$matches[2] : null;

        // Validate range
        if ($range_start >= $version['file_size']) {
            http_response_code(416); // Range Not Satisfiable
            header('Content-Range: bytes */' . $version['file_size']);
            die('Invalid range');
        }

        if ($range_end !== null && $range_end >= $version['file_size']) {
            $range_end = $version['file_size'] - 1;
        }
    }

    // Get file from S3 storage (with optional range)
    $s3_result = dms_s3_get_object($version['storage_key'], $range_start, $range_end);

    if (!$s3_result['success']) {
        error_log('[DMS] Preview failed for version ' . $version_id . ': ' . $s3_result['error']);
        http_response_code(500);
        die('Failed to retrieve file from storage');
    }

    // Log preview audit (only on first request, not on Range requests)
    if ($range_start === null || $range_start === 0) {
        dms_db_audit_log(
            $user_id,
            'preview',
            'version',
            (string)$version_id,
            $version['doc_id'],
            $version_id,
            [
                'filename' => $version['original_file_name'],
                'size' => $version['file_size'],
                'preview_type' => $preview_type,
            ]
        );
    }

    // Stream file to user (inline mode = preview in browser)
    dms_stream_output(
        $s3_result['stream'],
        $version['file_size'],
        $version['mime_type'],
        $version['original_file_name'],
        true  // true = inline (preview in browser), not attachment
    );

} catch (Exception $e) {
    error_log('[DMS] Preview error: ' . $e->getMessage());
    http_response_code(500);
    die('Preview failed');
}
