<?php
/**
 * DMS Archive System - Project Edit View
 */

defined('DMS_ENTRY') or exit;

dms_require_role('admin');

$current_user = dms_get_current_user();

// Get project_id if editing
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$is_edit = $project_id > 0;

$project = null;
$doc_count = 0;
if ($is_edit) {
    $project = dms_db_get_project($project_id);
    if (!$project) {
        die('<h1>项目未找到</h1>');
    }
    $doc_count = dms_db_count_project_documents($project_id);
}

$name = $project['name'] ?? '';
$code = $project['code'] ?? '';
$description = $project['description'] ?? '';
$status = $project['status'] ?? 'active';

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? '编辑' : '创建' ?> 项目 - DMS 档案管理系统</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include __DIR__ . '/_header.php'; ?>

        <main class="main-content">
            <div class="breadcrumb">
                <a href="index.php?action=project_list">项目列表</a> / <?= $is_edit ? '编辑' : '创建' ?>
            </div>

            <div class="page-header">
                <h1><?= $is_edit ? '编辑项目' : '创建新项目' ?></h1>
            </div>

            <?php if ($is_edit && $doc_count > 0): ?>
                <div class="alert alert-info">
                    <strong>提示：</strong>该项目下有 <?= $doc_count ?> 个文档，无法删除此项目。
                </div>
            <?php endif; ?>

            <form id="projectForm" class="project-form" onsubmit="handleSubmit(event)">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="project_id" value="<?= $project_id ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">项目名称 *</label>
                    <input type="text" id="name" name="name" value="<?= dms_escape($name) ?>" required maxlength="100" placeholder="例如：A项目、B项目">
                    <p class="form-help">项目的显示名称，用于在文档中区分不同项目</p>
                </div>

                <div class="form-group">
                    <label for="code">项目代码</label>
                    <input type="text" id="code" name="code" value="<?= dms_escape($code) ?>" maxlength="50" placeholder="例如：PROJ-A、PROJ-B">
                    <p class="form-help">可选的项目编号或简称</p>
                </div>

                <div class="form-group">
                    <label for="description">项目描述</label>
                    <textarea id="description" name="description" rows="4"><?= dms_escape($description) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="status">项目状态 *</label>
                    <select id="status" name="status" required>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>进行中</option>
                        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>已归档</option>
                        <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>已关闭</option>
                    </select>
                    <p class="form-help">已归档或已关闭的项目在新建文档时不会显示在列表中</p>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">保存项目</button>
                    <a href="index.php?action=project_list" class="btn">取消</a>
                </div>
            </form>
        </main>

        <?php include __DIR__ . '/_footer.php'; ?>
    </div>

    <script>
    function handleSubmit(event) {
        event.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = '保存中...';

        const formData = new FormData(event.target);

        fetch('api/project_save.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('项目保存成功');
                window.location.href = 'index.php?action=project_list';
            } else {
                alert('保存失败：' + (data.message || '未知错误'));
                submitBtn.disabled = false;
                submitBtn.textContent = '保存项目';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('保存失败：网络错误');
            submitBtn.disabled = false;
            submitBtn.textContent = '保存项目';
        });

        return false;
    }
    </script>
</body>
</html>
