<?php
/**
 * VIS 强制缓存清理 + 代码验证
 */
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>VIS 强制缓存清理</title>
    <style>
        body { font-family: monospace; background: #0e1014; color: #eff2f5; padding: 20px; line-height: 1.6; }
        .result { background: #1b1f26; border: 1px solid #2b303b; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .success { border-left: 4px solid #10b981; }
        .error { border-left: 4px solid #ef4444; }
        .warning { border-left: 4px solid #f59e0b; }
        h1 { color: #ff6b4a; }
        pre { background: #0e1014; padding: 10px; border-radius: 4px; overflow: auto; }
        code { color: #ff6b4a; }
    </style>
</head>
<body>
<h1>🧹 VIS 强制缓存清理 + 代码验证</h1>

<?php
$root = dirname(dirname(dirname(__DIR__)));

// 1. 清理OPcache
echo "<div class='result success'>";
echo "<h3>1. 清理 OPcache</h3>";
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color:#10b981;'>✅ OPcache 已清除</p>";
    } else {
        echo "<p style='color:#ef4444;'>❌ OPcache 清除失败</p>";
    }

    // 获取opcache状态
    if (function_exists('opcache_get_status')) {
        $status = opcache_get_status(false);
        if ($status) {
            echo "<pre>";
            echo "OPcache 已启用: " . ($status['opcache_enabled'] ? 'Yes' : 'No') . "\n";
            echo "缓存满: " . ($status['cache_full'] ? 'Yes' : 'No') . "\n";
            echo "重启次数: " . $status['opcache_statistics']['oom_restarts'] . "\n";
            echo "</pre>";
        }
    }
} else {
    echo "<p style='color:#f59e0b;'>⚠️ OPcache 未安装</p>";
}
echo "</div>";

// 2. 清理文件状态缓存
echo "<div class='result success'>";
echo "<h3>2. 清理文件状态缓存</h3>";
clearstatcache(true);
echo "<p style='color:#10b981;'>✅ Realpath 缓存已清除</p>";
echo "</div>";

// 3. 验证VIS代码中的跳转
echo "<div class='result'>";
echo "<h3>3. 验证VIS登录跳转代码</h3>";

$vis_lib = $root . '/app/vis/lib/vis_lib.php';
if (file_exists($vis_lib)) {
    $content = file_get_contents($vis_lib);

    // 检查vis_require_login函数
    if (preg_match("/function vis_require_login.*?\{(.*?)\n\}/s", $content, $matches)) {
        $func = $matches[0];

        if (strpos($func, "/vis/ap/index.php?action=login") !== false) {
            echo "<p style='color:#10b981;'>✅ vis_require_login() 正确跳转到 VIS</p>";
            echo "<pre>";
            echo htmlspecialchars($func);
            echo "</pre>";
        } else if (strpos($func, "/mrs/") !== false) {
            echo "<p style='color:#ef4444;'>❌ vis_require_login() 仍然跳转到 MRS！</p>";
            echo "<pre>";
            echo htmlspecialchars($func);
            echo "</pre>";
        }
    }
} else {
    echo "<p style='color:#ef4444;'>❌ vis_lib.php 不存在</p>";
}
echo "</div>";

// 4. 验证logout代码
echo "<div class='result'>";
echo "<h3>4. 验证VIS登出跳转代码</h3>";

$logout = $root . '/app/vis/api/logout.php';
if (file_exists($logout)) {
    $content = file_get_contents($logout);

    if (strpos($content, "/vis/ap/index.php?action=login") !== false) {
        echo "<p style='color:#10b981;'>✅ logout.php 正确跳转到 VIS</p>";
    } else if (strpos($content, "/mrs/") !== false) {
        echo "<p style='color:#ef4444;'>❌ logout.php 仍然跳转到 MRS！</p>";
    }

    echo "<pre>";
    echo htmlspecialchars($content);
    echo "</pre>";
} else {
    echo "<p style='color:#ef4444;'>❌ logout.php 不存在</p>";
}
echo "</div>";

// 5. 检查当前会话
echo "<div class='result'>";
echo "<h3>5. 会话信息</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "<pre>";
echo "会话状态: " . (session_status() === PHP_SESSION_ACTIVE ? '已启动' : '未启动') . "\n";
echo "会话名称: " . session_name() . "\n";
echo "会话ID: " . (session_id() ?: '(无)') . "\n";
echo "\n会话数据:\n";
print_r($_SESSION);
echo "</pre>";
echo "</div>";

// 6. 文件修改时间
echo "<div class='result'>";
echo "<h3>6. 关键文件修改时间</h3>";
$files = [
    'app/vis/lib/vis_lib.php',
    'app/vis/api/do_login.php',
    'app/vis/api/logout.php',
    'app/vis/config_vis/env_vis.php',
];

echo "<pre>";
foreach ($files as $file) {
    $path = $root . '/' . $file;
    if (file_exists($path)) {
        $mtime = filemtime($path);
        echo basename($file) . ": " . date('Y-m-d H:i:s', $mtime) . "\n";
    } else {
        echo basename($file) . ": 不存在\n";
    }
}
echo "</pre>";
echo "</div>";
?>

<hr style="border-color: #2b303b; margin: 30px 0;">
<h2>✅ 缓存已清理</h2>
<p><strong>如果上面显示代码仍然跳转到MRS，请立即报告！</strong></p>
<p><strong>否则，现在应该可以正常使用VIS登录了。</strong></p>
<p><a href="/vis/ap/index.php?action=login" style="color:#ff6b4a;">→ 测试VIS登录</a></p>
<p><a href="/vis/ap/index.php" style="color:#ff6b4a;">→ 测试VIS后台（会自动跳转登录）</a></p>
</body>
</html>
