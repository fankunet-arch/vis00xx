<?php
/**
 * DMS Archive System - Save Project API
 * Create or update project
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
    $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $status = trim($_POST['status'] ?? 'active');

    // Validate required fields
    if (empty($name)) {
        dms_json_response(false, null, '项目名称不能为空', 'VALIDATION_ERROR', 400);
    }

    if (!dms_validate_string_length($name, 1, 100)) {
        dms_json_response(false, null, '项目名称长度必须在1-100个字符之间', 'VALIDATION_ERROR', 400);
    }

    if (!empty($code) && !dms_validate_string_length($code, 1, 50)) {
        dms_json_response(false, null, '项目代码长度不能超过50个字符', 'VALIDATION_ERROR', 400);
    }

    // Validate status
    $valid_statuses = ['active', 'archived', 'closed'];
    if (!in_array($status, $valid_statuses)) {
        dms_json_response(false, null, '无效的项目状态', 'VALIDATION_ERROR', 400);
    }

    // Save project
    $saved_id = dms_db_save_project([
        'project_id' => $project_id,
        'org_id' => 1,
        'name' => $name,
        'code' => !empty($code) ? $code : null,
        'description' => !empty($description) ? $description : null,
        'status' => $status,
        'created_by' => $user_id,
    ]);

    // Log audit
    dms_db_audit_log($user_id, $project_id ? 'update' : 'create', 'project', (string)$saved_id, null, null, [
        'name' => $name,
        'code' => $code,
        'status' => $status,
    ]);

    // Success response
    dms_json_response(true, [
        'project_id' => $saved_id
    ], $project_id ? '项目更新成功' : '项目创建成功');

} catch (Exception $e) {
    error_log('[DMS] Save project error: ' . $e->getMessage());
    dms_json_response(false, null, $DMS_CONFIG['app_debug'] ? $e->getMessage() : '保存失败', 'SAVE_ERROR', 500);
}
