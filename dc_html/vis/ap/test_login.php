<?php
/**
 * VIS登录诊断工具
 */
define('VIS_ENTRY', true);
define('PROJECT_ROOT', dirname(dirname(dirname(__DIR__))));
require_once PROJECT_ROOT . '/app/vis/bootstrap.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>VIS登录诊断</title>";
echo "<style>body{font-family:monospace;background:#0e1014;color:#eff2f5;padding:20px;}";
echo ".section{background:#1b1f26;border:1px solid #2b303b;padding:20px;margin:20px 0;border-radius:8px;}";
echo "h2{color:#ff6b4a;}pre{background:#0e1014;padding:10px;border-radius:4px;overflow:auto;}</style></head><body>";

echo "<h1>🔍 VIS登录系统诊断</h1>";

// 1. 检查会话
echo "<div class='section'>";
echo "<h2>1. 会话状态</h2>";
echo "<pre>";
echo "会话状态: " . (session_status() === PHP_SESSION_ACTIVE ? '✅ 已启动' : '❌ 未启动') . "\n";
echo "会话名称: " . session_name() . "\n";
echo "会话ID: " . (session_id() ?: '无') . "\n";
echo "VIS_SESSION_NAME常量: " . VIS_SESSION_NAME . "\n";
echo "</pre>";
echo "</div>";

// 2. 检查POST数据（如果有）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='section'>";
    echo "<h2>2. POST数据</h2>";
    echo "<pre>";
    echo "用户名: " . htmlspecialchars($_POST['username'] ?? '(空)') . "\n";
    echo "密码: " . (isset($_POST['password']) && !empty($_POST['password']) ? '[已提供]' : '(空)') . "\n";
    echo "</pre>";
    echo "</div>";

    echo "<div class='section'>";
    echo "<h2>3. 登录测试</h2>";
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        try {
            $user = vis_authenticate_user($pdo, $username, $password);
            if ($user) {
                echo "<pre style='color:#10b981;'>✅ 认证成功！\n";
                echo "用户ID: " . $user['user_id'] . "\n";
                echo "用户名: " . $user['user_login'] . "\n";
                echo "显示名: " . $user['user_display_name'] . "\n";
                echo "</pre>";

                // 尝试创建会话
                vis_create_user_session($user);
                echo "<pre style='color:#10b981;'>✅ 会话已创建</pre>";

                // 检查会话数据
                echo "<pre>";
                echo "会话数据:\n";
                print_r($_SESSION);
                echo "</pre>";
            } else {
                echo "<pre style='color:#ef4444;'>❌ 认证失败：用户名或密码错误</pre>";
            }
        } catch (Exception $e) {
            echo "<pre style='color:#ef4444;'>❌ 错误: " . htmlspecialchars($e->getMessage()) . "</pre>";
        }
    } else {
        echo "<pre style='color:#f59e0b;'>⚠️ 请提供用户名和密码</pre>";
    }
    echo "</div>";
}

// 4. 测试表单
echo "<div class='section'>";
echo "<h2>4. 登录测试表单</h2>";
echo "<form method='POST' action='test_login.php'>";
echo "<div style='margin:10px 0;'>";
echo "<label style='display:block;margin-bottom:5px;'>用户名:</label>";
echo "<input type='text' name='username' style='padding:8px;width:300px;background:#0e1014;border:1px solid #2b303b;color:#eff2f5;border-radius:4px;'>";
echo "</div>";
echo "<div style='margin:10px 0;'>";
echo "<label style='display:block;margin-bottom:5px;'>密码:</label>";
echo "<input type='password' name='password' style='padding:8px;width:300px;background:#0e1014;border:1px solid #2b303b;color:#eff2f5;border-radius:4px;'>";
echo "</div>";
echo "<button type='submit' style='padding:10px 20px;background:#ff6b4a;color:#fff;border:none;border-radius:4px;cursor:pointer;font-weight:600;'>测试登录</button>";
echo "</form>";
echo "</div>";

// 5. 数据库连接
echo "<div class='section'>";
echo "<h2>5. 数据库连接</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sys_users WHERE user_status = 'active'");
    $result = $stmt->fetch();
    echo "<pre style='color:#10b981;'>✅ 数据库连接正常\n";
    echo "活跃用户数: " . $result['count'] . "</pre>";
} catch (Exception $e) {
    echo "<pre style='color:#ef4444;'>❌ 数据库错误: " . htmlspecialchars($e->getMessage()) . "</pre>";
}
echo "</div>";

// 6. 路径检查
echo "<div class='section'>";
echo "<h2>6. 系统路径</h2>";
echo "<pre>";
echo "PROJECT_ROOT: " . PROJECT_ROOT . "\n";
echo "VIS_APP_PATH: " . VIS_APP_PATH . "\n";
echo "VIS_VIEW_PATH: " . VIS_VIEW_PATH . "\n";
echo "VIS_API_PATH: " . VIS_API_PATH . "\n";
echo "\n登录文件:\n";
echo "  登录视图: " . (file_exists(VIS_VIEW_PATH . '/login.php') ? '✅' : '❌') . " " . VIS_VIEW_PATH . '/login.php' . "\n";
echo "  登录API: " . (file_exists(VIS_API_PATH . '/do_login.php') ? '✅' : '❌') . " " . VIS_API_PATH . '/do_login.php' . "\n";
echo "</pre>";
echo "</div>";

echo "<hr style='border-color:#2b303b;margin:30px 0;'>";
echo "<p><a href='/vis/ap/index.php?action=login' style='color:#ff6b4a;'>← 返回登录页面</a></p>";
echo "</body></html>";
