<?php
/**
 * DMS Archive System - Project List View
 */

defined('DMS_ENTRY') or exit;

dms_require_role('admin');

$current_user = dms_get_current_user();
$projects = dms_db_get_projects();

// Count documents for each project
foreach ($projects as &$project) {
    $project['doc_count'] = dms_db_count_project_documents($project['project_id']);
}
unset($project);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>项目管理 - DMS 档案管理系统</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include __DIR__ . '/_header.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>项目管理</h1>
                <div class="page-actions">
                    <a href="index.php?action=project_edit" class="btn btn-primary">创建新项目</a>
                </div>
            </div>

            <?php if (empty($projects)): ?>
                <div class="empty-state">
                    <p>未找到项目。</p>
                    <a href="index.php?action=project_edit" class="btn btn-primary">创建第一个项目</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>项目名称</th>
                            <th>项目代码</th>
                            <th>描述</th>
                            <th>状态</th>
                            <th>文档数量</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $proj): ?>
                            <tr>
                                <td><strong><?= dms_escape($proj['name']) ?></strong></td>
                                <td><?= dms_escape($proj['code'] ?? '-') ?></td>
                                <td><?= dms_escape($proj['description'] ?? '') ?></td>
                                <td>
                                    <?php
                                    $status_labels = [
                                        'active' => '进行中',
                                        'archived' => '已归档',
                                        'closed' => '已关闭'
                                    ];
                                    echo $status_labels[$proj['status']] ?? $proj['status'];
                                    ?>
                                </td>
                                <td><?= $proj['doc_count'] ?> 个文档</td>
                                <td><?= dms_format_datetime($proj['created_at'], 'Y-m-d H:i') ?></td>
                                <td class="actions">
                                    <a href="index.php?action=project_edit&project_id=<?= $proj['project_id'] ?>" class="btn btn-sm">编辑</a>
                                    <?php if ($proj['doc_count'] == 0): ?>
                                        <button onclick="deleteProject(<?= $proj['project_id'] ?>, '<?= dms_escape($proj['name']) ?>')" class="btn btn-sm btn-danger">删除</button>
                                    <?php else: ?>
                                        <button class="btn btn-sm" disabled title="该项目下有 <?= $proj['doc_count'] ?> 个文档，无法删除">删除</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>

        <?php include __DIR__ . '/_footer.php'; ?>
    </div>

    <script>
    function deleteProject(projectId, projectName) {
        if (!confirm('确定要删除项目 "' + projectName + '" 吗？此操作不可恢复。')) {
            return;
        }

        const formData = new FormData();
        formData.append('project_id', projectId);

        fetch('api/project_delete.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('项目删除成功');
                location.reload();
            } else {
                alert('删除失败：' + (data.message || '未知错误'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('删除失败：网络错误');
        });
    }
    </script>
</body>
</html>
