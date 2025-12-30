<?php
/**
 * DMS Archive System - Update Document Metadata API
 * Update title, description, category, tags, attributes
 */

defined('DMS_ENTRY') or exit;

// Require edit permission
dms_require_permission('edit');

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

    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $tags = trim($_POST['tags'] ?? '');
    $attributes = !empty($_POST['attributes']) ? json_decode($_POST['attributes'], true) : [];

    // Validate required fields
    if (empty($title)) {
        dms_json_response(false, null, 'Title is required', 'VALIDATION_ERROR', 400);
    }

    // Validate category and attributes if category specified
    if ($category_id) {
        $category = dms_db_get_category($category_id);
        if (!$category) {
            dms_json_response(false, null, 'Invalid category', 'INVALID_CATEGORY', 400);
        }

        // Validate attributes against schema
        if (!empty($category['schema_json'])) {
            $schema = dms_json_decode($category['schema_json']);
            $attr_validation = dms_validate_attributes($attributes, $schema);
            if (!$attr_validation['valid']) {
                dms_json_response(false, null, $attr_validation['error'], 'ATTRIBUTE_VALIDATION_ERROR', 400);
            }
        }
    }

    // Update document
    dms_db_update_document($doc_id, [
        'title' => $title,
        'description' => $description,
        'category_id' => $category_id,
        'tags' => $tags,
        'attributes' => $attributes,
    ]);

    // Log audit
    dms_db_audit_log($user_id, 'update_meta', 'document', $doc_id, $doc_id, null, [
        'title' => $title,
        'category_id' => $category_id,
    ]);

    // Success response
    dms_json_response(true, ['doc_id' => $doc_id], 'Document updated successfully');

} catch (Exception $e) {
    error_log('[DMS] Update metadata error: ' . $e->getMessage());
    dms_json_response(false, null, $DMS_CONFIG['app_debug'] ? $e->getMessage() : 'Update failed', 'UPDATE_ERROR', 500);
}
