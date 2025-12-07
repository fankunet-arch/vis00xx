<?php
/**
 * VIS Video Inspiration System - Login API
 * 文件路径: app/vis/api/do_login.php
 * 说明: VIS独立登录API（完全独立，不依赖其他系统）
 */

if (!defined('VIS_ENTRY')) {
    http_response_code(403);
    die('Direct access denied');
}

// 只接受POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /vis/ap/index.php?action=login');
    exit;
}

// 获取表单数据
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// 输入验证
if (empty($username)) {
    $_SESSION['login_error'] = '请输入用户名';
    header('Location: /vis/ap/index.php?action=login');
    exit;
}

if (empty($password)) {
    $_SESSION['login_error'] = '请输入密码';
    header('Location: /vis/ap/index.php?action=login');
    exit;
}

if (strlen($username) < 2) {
    $_SESSION['login_error'] = '用户名至少2个字符';
    header('Location: /vis/ap/index.php?action=login');
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['login_error'] = '密码至少6个字符';
    header('Location: /vis/ap/index.php?action=login');
    exit;
}

// 验证用户
$user = vis_authenticate_user($pdo, $username, $password);

if ($user === false) {
    $_SESSION['login_error'] = '用户名或密码错误';
    vis_log("登录失败: {$username} - 凭据无效", 'WARNING');
    header('Location: /vis/ap/index.php?action=login');
    exit;
}

// 创建会话
vis_create_user_session($user);

vis_log("用户登录成功: {$username} (ID: {$user['user_id']})", 'INFO');

// 登录成功，跳转到后台首页
header('Location: /vis/ap/index.php?action=admin_list');
exit;
