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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents - DMS Archive System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include __DIR__ . '/_header.php'; ?>

        <main class="main-content">
            <div class="page-header">
                <h1>Documents</h1>
                <div class="page-actions">
                    <?php if ($can_upload): ?>
                        <a href="index.php?action=doc_upload" class="btn btn-primary">Upload New Document</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" action="index.php" class="filter-form">
                <input type="hidden" name="action" value="doc_list">

                <div class="filter-row">
                    <div class="filter-group">
                        <input type="text" name="search" placeholder="Search title, description, tags..." value="<?= dms_escape($_GET['search'] ?? '') ?>">
                    </div>

                    <div class="filter-group">
                        <select name="category_id">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>" <?= ($filters['category_id'] ?? 0) == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= dms_escape($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn">Filter</button>
                    <a href="index.php?action=doc_list" class="btn">Clear</a>
                </div>
            </form>

            <!-- Documents Table -->
            <?php if (empty($documents)): ?>
                <div class="empty-state">
                    <p>No documents found.</p>
                    <?php if ($can_upload): ?>
                        <a href="index.php?action=doc_upload" class="btn btn-primary">Upload First Document</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Tags</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Actions</th>
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
                                <td><?= dms_escape($doc['tags'] ?? '') ?></td>
                                <td><?= dms_escape($doc['created_by_name'] ?? 'Unknown') ?></td>
                                <td><?= dms_format_datetime($doc['created_at'], 'Y-m-d H:i') ?></td>
                                <td class="actions">
                                    <a href="index.php?action=doc_view&doc_id=<?= dms_escape($doc['doc_id']) ?>" class="btn-sm">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?action=doc_list&page=<?= $page - 1 ?><?= !empty($filters) ? '&' . http_build_query($filters) : '' ?>" class="btn-sm">Previous</a>
                        <?php endif; ?>

                        <span>Page <?= $page ?> of <?= $total_pages ?></span>

                        <?php if ($page < $total_pages): ?>
                            <a href="?action=doc_list&page=<?= $page + 1 ?><?= !empty($filters) ? '&' . http_build_query($filters) : '' ?>" class="btn-sm">Next</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>

        <?php include __DIR__ . '/_footer.php'; ?>
    </div>
</body>
</html>
