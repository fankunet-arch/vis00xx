<?php
/**
 * VIS Video Inspiration System - Backend Admin Router
 * 文件路径: dc_html/vis/ap/index.php
 * 说明: 后台管理入口（需要登录验证）
 */

// 定义系统入口标识
define('VIS_ENTRY', true);

// 定义项目根目录 (dc_html的上级目录)
define('PROJECT_ROOT', dirname(dirname(dirname(__DIR__))));

// 加载bootstrap (在app目录中)
require_once PROJECT_ROOT . '/app/vis/bootstrap.php';

// 获取action参数
$action = $_GET['action'] ?? 'admin_list';
$action = basename($action); // 防止路径遍历

// 后台管理允许的action列表
$allowed_actions = [
    'login',                // 登录页面（无需登录）
    'do_login',             // 登录处理（无需登录）
    'logout',               // 登出（需要登录）
    'admin_list',           // 后台视频列表
    'admin_upload',         // 后台上传页面
    'video_upload',         // 处理上传
    'video_save',           // 保存编辑
    'video_delete',         // 删除视频
    'admin_categories',     // 分类管理（预留）
    'category_save',        // 保存分类（预留）
    'product_quick_create', // 产品快速创建/搜索
    'series_quick_create',  // 系列快速创建/搜索
];

// 无需登录的action列表
$public_actions = ['login', 'do_login'];

// 除了公开action，其他都需要登录验证
if (!in_array($action, $public_actions)) {
    vis_require_login();
}

// 验证action是否允许
if (!in_array($action, $allowed_actions)) {
    $accepts_json = isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    $is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($accepts_json || $is_ajax) {
        vis_json_response(false, null, 'Invalid action');
    }

    http_response_code(404);
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>404 - Page Not Found</title>';
    echo '<style>body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:40px;}';
    echo '.card{max-width:520px;margin:0 auto;background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,0.08);}';
    echo '.card h1{margin-top:0;font-size:22px;color:#c62828;} .card p{color:#444;line-height:1.6;} .card a{color:#1565c0;text-decoration:none;font-weight:600;}</style>';
    echo '</head><body><div class="card"><h1>404 - 无效的后台入口</h1><p>请求的操作未被允许或链接已失效。</p>';
    echo '<p><a href="/vis/ap/index.php?action=admin_list">返回后台首页</a></p></div></body></html>';
    exit;
}

// API action（执行操作后重定向，不返回JSON）
$api_actions = [
    'do_login',             // 登录处理
    'logout',               // 登出处理
    'video_upload',         // 上传视频
    'video_save',           // 保存编辑
    'video_delete',         // 删除视频
    'category_save',        // 保存分类
    'product_quick_create', // 产品快速创建/搜索
    'series_quick_create',  // 系列快速创建/搜索
];

// 路由到对应的action或API文件 (在app目录中)
if (in_array($action, $api_actions)) {
    // API路由
    $api_file = VIS_API_PATH . '/' . $action . '.php';
    if (file_exists($api_file)) {
        require_once $api_file;
    } else {
        vis_json_response(false, null, 'API not found');
    }
} else {
    // 页面路由
    $view_file = VIS_VIEW_PATH . '/' . $action . '.php';
    if (file_exists($view_file)) {
        require_once $view_file;
    } else {
        http_response_code(404);
        die('Page not found');
    }
}
