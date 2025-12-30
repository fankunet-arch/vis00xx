<?php
/**
 * DMS Archive System - Delete Project API
 * Delete project (with protection for projects containing documents)
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
    // Get project_id
    $project_id = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

    if ($project_id <= 0) {
        dms_json_response(false, null, '无效的项目ID', 'VALIDATION_ERROR', 400);
    }

    // Check if project exists
    $project = dms_db_get_project($project_id);
    if (!$project) {
        dms_json_response(false, null, '项目不存在', 'NOT_FOUND', 404);
    }

    // Check if project has active documents (deletion protection)
    $doc_count = dms_db_count_project_documents($project_id);
    if ($doc_count > 0) {
        dms_json_response(false, null, "该项目下有 {$doc_count} 个有效文档，无法删除。请先将这些文档移除或删除。", 'HAS_DOCUMENTS', 400);
    }

    // Delete project (soft delete)
    dms_db_delete_project($project_id);

    // Log audit
    dms_db_audit_log($user_id, 'delete', 'project', (string)$project_id, null, null, [
        'name' => $project['name'],
    ]);

    // Success response
    dms_json_response(true, [
        'project_id' => $project_id
    ], '项目删除成功');

} catch (Exception $e) {
    error_log('[DMS] Delete project error: ' . $e->getMessage());
    dms_json_response(false, null, $DMS_CONFIG['app_debug'] ? $e->getMessage() : '删除失败', 'DELETE_ERROR', 500);
}
