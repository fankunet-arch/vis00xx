<?php
/**
 * DMS Archive System - Document List View
 */

defined('DMS_ENTRY') or exit;

$current_user = dms_get_current_user();
$can_upload = dms_can('upload');
$is_admin = dms_has_role('admin');

// Get filters
$filters = [];
if (!empty($_GET['category_id'])) {
    $filters['category_id'] = (int)$_GET['category_id'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = trim($_GET['search']);
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = $DMS_CONFIG['per_page_default'];
$offset = ($page - 1) * $per_page;

// Get documents
$documents = dms_db_get_documents($filters, $per_page, $offset);
$total = dms_db_count_documents($filters);
$total_pages = ceil($total / $per_page);

// Get categories for filter
$categories = dms_db_get_categories();

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文档列表 - DMS 档案管理系统</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include __DIR__ . '/_header.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>文档列表</h1>
                <div class="page-actions">
                    <?php if ($can_upload): ?>
                        <a href="index.php?action=doc_upload" class="btn btn-primary">上传新文档</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 筛选表单 -->
            <form method="GET" action="index.php" class="filter-form">
                <input type="hidden" name="action" value="doc_list">

                <div class="filter-row">
                    <div class="filter-group">
                        <label>搜索</label>
                        <input type="text" name="search" placeholder="搜索标题、描述、标签..." value="<?= dms_escape($_GET['search'] ?? '') ?>">
                    </div>

                    <div class="filter-group">
                        <label>分类</label>
                        <select name="category_id">
                            <option value="">全部分类</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= ($filters['category_id'] ?? 0) == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= dms_escape($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">筛选</button>
                    <a href="index.php?action=doc_list" class="btn">清空</a>
                </div>
            </form>

            <!-- 文档表格 -->
            <?php if (empty($documents)): ?>
                <div class="empty-state">
                    <p>未找到文档。</p>
                    <?php if ($can_upload): ?>
                        <a href="index.php?action=doc_upload" class="btn btn-primary">上传第一个文档</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>标题</th>
                            <th>分类</th>
                            <th>项目</th>
                            <th>标签</th>
                            <th>创建者</th>
                            <th>创建时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td>
                                    <a href="index.php?action=doc_view&doc_id=<?= dms_escape($doc['doc_id']) ?>" class="doc-title">
                                        <?= dms_escape($doc['title']) ?>
                                    </a>
                                    <?php if ($doc['description']): ?>
                                        <div class="doc-desc"><?= dms_escape(mb_substr($doc['description'], 0, 100)) ?><?= mb_strlen($doc['description']) > 100 ? '...' : '' ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= dms_escape($doc['category_name'] ?? '-') ?></td>
                                <td><?= dms_escape($doc['project_name'] ?? '-') ?></td>
                                <td><?= dms_escape($doc['tags'] ?? '') ?></td>
                                <td><?= dms_escape($doc['created_by_name'] ?? '未知') ?></td>
                                <td><?= dms_format_datetime($doc['created_at'], 'Y-m-d H:i') ?></td>
                                <td class="actions">
                                    <a href="index.php?action=doc_view&doc_id=<?= dms_escape($doc['doc_id']) ?>" class="btn btn-sm">查看</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- 分页 -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?action=doc_list&page=<?= $page - 1 ?><?= !empty($filters) ? '&' . http_build_query($filters) : '' ?>" class="btn btn-sm">上一页</a>
                        <?php endif; ?>

                        <span>第 <?= $page ?> 页 / 共 <?= $total_pages ?> 页</span>

                        <?php if ($page < $total_pages): ?>
                            <a href="?action=doc_list&page=<?= $page + 1 ?><?= !empty($filters) ? '&' . http_build_query($filters) : '' ?>" class="btn btn-sm">下一页</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>

        <?php include __DIR__ . '/_footer.php'; ?>
    </div>
</body>
</html>
