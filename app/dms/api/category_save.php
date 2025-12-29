<?php
/**
 * DMS Archive System - Save Category API
 * Create or update category with schema
 */

defined('DMS_ENTRY') or exit;

// Require admin role
dms_require_role('admin');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    dms_json_response(false, null, 'Method not allowed', 'METHOD_NOT_ALLOWED', 405);
}

// Get user info
$user_id = dms_get_current_user_id();

try {
    // Get form data
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $schema_json = !empty($_POST['schema_json']) ? $_POST['schema_json'] : null;

    // Validate required fields
    if (empty($name)) {
        dms_json_response(false, null, 'Category name is required', 'VALIDATION_ERROR', 400);
    }

    if (!dms_validate_string_length($name, 1, 100)) {
        dms_json_response(false, null, 'Category name must be 1-100 characters', 'VALIDATION_ERROR', 400);
    }

    // Validate schema if provided
    $schema_array = null;
    if ($schema_json) {
        try {
            $schema_array = dms_json_decode($schema_json);
            $schema_validation = dms_validate_category_schema($schema_array);
            if (!$schema_validation['valid']) {
                dms_json_response(false, null, $schema_validation['error'], 'SCHEMA_VALIDATION_ERROR', 400);
            }
        } catch (Exception $e) {
            dms_json_response(false, null, 'Invalid JSON in schema', 'INVALID_JSON', 400);
        }
    }

    // Save category
    $saved_id = dms_db_save_category([
        'category_id' => $category_id,
        'org_id' => 1,
        'name' => $name,
        'description' => $description,
        'schema_json' => $schema_array,
    ]);

    // Log audit
    dms_db_audit_log($user_id, $category_id ? 'update' : 'create', 'category', (string)$saved_id, null, null, [
        'name' => $name,
    ]);

    // Success response
    dms_json_response(true, [
        'category_id' => $saved_id
    ], $category_id ? 'Category updated successfully' : 'Category created successfully');

} catch (Exception $e) {
    error_log('[DMS] Save category error: ' . $e->getMessage());
    dms_json_response(false, null, $DMS_CONFIG['app_debug'] ? $e->getMessage() : 'Save failed', 'SAVE_ERROR', 500);
}
