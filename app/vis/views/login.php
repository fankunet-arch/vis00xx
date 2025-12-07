<?php
/**
 * VIS Video Inspiration System - Login Page
 * 文件路径: app/vis/views/login.php
 * 说明: VIS独立登录页面
 */

if (!defined('VIS_ENTRY')) {
    http_response_code(403);
    die('Direct access denied');
}

$errorMessage = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIS登录 - Video Inspiration System</title>
    <link rel="stylesheet" href="/vis/ap/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>VIS 视频灵感库</h1>
                <p class="subtitle">Video Inspiration System</p>
            </div>

            <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span><?php echo htmlspecialchars($errorMessage); ?></span>
            </div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="/vis/ap/index.php?action=do_login">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="请输入用户名"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password">密码</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="请输入密码"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                    <span class="btn-text">登录</span>
                    <span class="btn-loading" style="display: none;">
                        <svg class="spinner" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="3" fill="none"></circle>
                        </svg>
                        登录中...
                    </span>
                </button>
            </form>

            <div class="login-footer">
                <p class="footer-text">VIS视频灵感库管理系统</p>
                <p class="footer-hint">请使用系统管理员分配的账户登录</p>
            </div>
        </div>
    </div>

    <script src="/vis/ap/js/login.js"></script>
</body>
</html>
