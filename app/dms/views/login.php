<?php
/**
 * DMS Archive System - Login View
 */

defined('DMS_ENTRY') or exit;

// If already logged in, redirect to doc_list
if (dms_is_logged_in()) {
    dms_redirect('doc_list');
}

// Get error message if any
$error = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - DMS 档案管理系统</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <h1>DMS 档案系统</h1>
            <p class="subtitle">文档管理与存档平台</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= dms_escape($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?action=do_login" class="login-form">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" placeholder="请输入用户名" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">密码</label>
                    <input type="password" id="password" name="password" placeholder="请输入密码" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">登 录</button>
            </form>

            <div class="login-help">
                <p><small>默认账号：<code>admin</code> / <code>admin123</code></small></p>
                <p><small>首次登录后请及时修改密码</small></p>
            </div>
        </div>
    </div>
</body>
</html>
