<?php
/**
 * VIS Video Inspiration System - Logout API
 * 文件路径: app/vis/api/logout.php
 * 说明: VIS独立登出API（完全独立，不依赖其他系统）
 */

if (!defined('VIS_ENTRY')) {
    http_response_code(403);
    die('Direct access denied');
}

// 记录登出日志
if (isset($_SESSION['user_login'])) {
    vis_log("用户登出: {$_SESSION['user_login']}", 'INFO');
}

// 销毁会话
vis_destroy_user_session();

// 重定向到登录页面
header('Location: /vis/ap/index.php?action=login');
exit;
