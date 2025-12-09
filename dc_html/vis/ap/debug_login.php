<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIS登录调试</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #0e1014;
            color: #eff2f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 {
            font-size: 24px;
            margin-bottom: 20px;
            color: #ff6b4a;
        }
        h2 {
            font-size: 18px;
            margin: 30px 0 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #2b303b;
        }
        .section {
            background: #1b1f26;
            border: 1px solid #2b303b;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            margin-left: 10px;
        }
        .status.ok { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .status.error { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
        code {
            background: #14171c;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            color: #60a5fa;
        }
        .test-form {
            background: #14171c;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #2b303b;
        }
        .form-group { margin-bottom: 15px; }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #9ca3af;
        }
        input {
            width: 100%;
            padding: 10px 12px;
            background: #0e1014;
            border: 1px solid #2b303b;
            border-radius: 6px;
            color: #eff2f5;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #ff6b4a;
        }
        button {
            background: #ff6b4a;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        button:hover { background: #e85a3a; }
        .error-box {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 VIS登录系统诊断</h1>

        <div class="section">
            <h2>1. 当前Session状态</h2>
            <?php
            session_start();
            echo "<ul>";
            echo "<li>Session ID: <code>" . session_id() . "</code></li>";
            echo "<li>Session Name: <code>" . session_name() . "</code></li>";
            echo "<li>登录状态: ";
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                echo '<span class="status ok">已登录</span>';
            } else {
                echo '<span class="status error">未登录</span>';
            }
            echo "</li>";
            echo "</ul>";
            ?>
        </div>

        <div class="section">
            <h2>2. 登录JavaScript问题</h2>
            <div class="error-box">
                <p style="color: #ef4444; font-weight: 600;">⚠️ 发现表单提交死循环问题</p>
                <p style="color: #9ca3af; font-size: 14px; margin-top: 10px;">
                    <code>login.js</code> 第41-56行存在逻辑错误：<br>
                    • <code>e.preventDefault()</code> 阻止表单提交<br>
                    • 然后调用 <code>loginForm.submit()</code><br>
                    • 但preventDefault已阻止提交，表单无法发送到服务器
                </p>
            </div>
        </div>

        <div class="section">
            <h2>3. 测试登录（绕过JavaScript）</h2>
            <p style="color: #9ca3af; margin-bottom: 15px;">
                此表单直接提交到服务器，不经过JavaScript验证。
            </p>

            <div class="test-form">
                <form method="POST" action="/vis/ap/index.php?action=do_login">
                    <div class="form-group">
                        <label>用户名</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>密码</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit">直接登录（无JS验证）</button>
                </form>
            </div>
        </div>

        <div class="section">
            <h2>4. 快速操作</h2>
            <p><a href="/vis/ap/index.php?action=login" style="color: #60a5fa;">→ 返回登录页面</a></p>
            <p><a href="/vis/ap/test_layout.html" style="color: #60a5fa;">→ 查看布局测试页面</a></p>
        </div>
    </div>
</body>
</html>
