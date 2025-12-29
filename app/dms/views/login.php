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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - DMS Archive System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <h1>DMS Archive System</h1>
            <p class="subtitle">Document Management & Archive</p>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?= dms_escape($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?action=do_login" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>

            <div class="login-help">
                <p><small>Default credentials: <code>admin</code> / <code>admin123</code></small></p>
                <p><small>Please change password after first login.</small></p>
            </div>
        </div>
    </div>
</body>
</html>
