<?php
/**
 * DMS Archive System - Category List View
 */

defined('DMS_ENTRY') or exit;

dms_require_role('admin');

$current_user = dms_get_current_user();
$categories = dms_db_get_categories();

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - DMS 档案管理系统</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include __DIR__ . '/_header.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>分类管理</h1>
                <div class="page-actions">
                    <a href="index.php?action=category_edit" class="btn btn-primary">创建新分类</a>
                </div>
            </div>

            <?php if (empty($categories)): ?>
                <div class="empty-state">
                    <p>未找到分类。</p>
                    <a href="index.php?action=category_edit" class="btn btn-primary">创建第一个分类</a>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>名称</th>
                            <th>描述</th>
                            <th>字段数量</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <?php
                            $schema = !empty($cat['schema_json']) ? dms_json_decode($cat['schema_json']) : null;
                            $field_count = $schema ? count($schema['fields'] ?? []) : 0;
                            ?>
                            <tr>
                                <td><strong><?= dms_escape($cat['name']) ?></strong></td>
                                <td><?= dms_escape($cat['description'] ?? '') ?></td>
                                <td><?= $field_count ?> 个字段</td>
                                <td><?= dms_format_datetime($cat['created_at'], 'Y-m-d H:i') ?></td>
                                <td class="actions">
                                    <a href="index.php?action=category_edit&category_id=<?= $cat['category_id'] ?>" class="btn btn-sm">编辑</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </main>

        <?php include __DIR__ . '/_footer.php'; ?>
    </div>
</body>
</html>
