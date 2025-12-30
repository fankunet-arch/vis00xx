<?php
/**
 * DMS Archive System - Header Component
 */

defined('DMS_ENTRY') or exit;

$current_user = dms_get_current_user();
$is_admin = dms_has_role('admin');
?>

<header class="site-header">
    <div class="header-container">
        <div class="header-logo">
            <a href="index.php?action=doc_list">DMS Archive</a>
        </div>

        <nav class="header-nav">
            <a href="index.php?action=doc_list">Documents</a>
            <?php if ($is_admin): ?>
                <a href="index.php?action=category_list">Categories</a>
            <?php endif; ?>
        </nav>

        <div class="header-user">
            <span class="user-name">
                <?= dms_escape($current_user['full_name'] ?? $current_user['username']) ?>
                <small>(<?= dms_escape($current_user['role']) ?>)</small>
            </span>
            <a href="index.php?action=do_logout" class="btn-sm">Logout</a>
        </div>
    </div>
</header>
