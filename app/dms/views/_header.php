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
            <a href="index.php?action=doc_list">DMS 档案系统</a>
        </div>

        <nav class="header-nav">
            <a href="index.php?action=doc_list">文档列表</a>
            <?php if ($is_admin): ?>
                <div class="nav-dropdown">
                    <a href="#" class="nav-dropdown-toggle">设置 ▾</a>
                    <div class="nav-dropdown-menu">
                        <a href="index.php?action=category_list">分类管理</a>
                        <a href="index.php?action=project_list">项目管理</a>
                    </div>
                </div>
            <?php endif; ?>
        </nav>

        <div class="header-user">
            <span class="user-name">
                <?= dms_escape($current_user['full_name'] ?? $current_user['username']) ?>
                <small>(<?php
                    $role_map = ['admin' => '管理员', 'user' => '用户', 'viewer' => '访客'];
                    echo $role_map[$current_user['role']] ?? $current_user['role'];
                ?>)</small>
            </span>
            <a href="index.php?action=do_logout" class="btn btn-sm">退出</a>
        </div>
    </div>
</header>
