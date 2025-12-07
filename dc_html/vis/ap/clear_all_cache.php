<?php
/**
 * VIS 缓存清理工具
 */
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>清理缓存</title>";
echo "<style>body{font-family:monospace;background:#0e1014;color:#eff2f5;padding:20px;}";
echo ".result{background:#1b1f26;border:1px solid #2b303b;padding:15px;margin:10px 0;border-radius:8px;}";
echo ".success{border-left:4px solid #10b981;} .warning{border-left:4px solid #f59e0b;}";
echo "h1{color:#ff6b4a;}</style></head><body>";

echo "<h1>🧹 VIS 缓存清理</h1>";

// 1. 清理OPcache
echo "<div class='result success'>";
echo "<h3>1. OPcache</h3>";
if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color:#10b981;'>✅ OPcache 已清除</p>";
    } else {
        echo "<p style='color:#f59e0b;'>⚠️ OPcache 清除失败</p>";
    }
    echo "<pre>";
    echo "OPcache 状态: " . (opcache_get_status() ? 'enabled' : 'disabled') . "\n";
    echo "</pre>";
} else {
    echo "<p style='color:#f59e0b;'>⚠️ OPcache 未安装或未启用</p>";
}
echo "</div>";

// 2. 清理文件状态缓存
echo "<div class='result success'>";
echo "<h3>2. 文件状态缓存</h3>";
clearstatcache(true);
echo "<p style='color:#10b981;'>✅ 文件状态缓存已清除</p>";
echo "</div>";

// 3. 清理会话
echo "<div class='result success'>";
echo "<h3>3. 当前会话</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
    echo "<p style='color:#10b981;'>✅ 会话已销毁</p>";
} else {
    echo "<p>ℹ️ 无活动会话</p>";
}
echo "</div>";

// 4. 验证关键文件
echo "<div class='result'>";
echo "<h3>4. 验证关键文件</h3>";
$root = dirname(dirname(dirname(__DIR__)));
$files_to_check = [
    'app/vis/lib/vis_lib.php' => '/vis/ap/index.php',
    'app/vis/config_vis/env_vis.php' => 'VIS_SESSID',
];

foreach ($files_to_check as $file => $expected) {
    $path = $root . '/' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        if (strpos($content, $expected) !== false) {
            echo "<p style='color:#10b981;'>✅ {$file} 包含正确配置</p>";
        } else {
            echo "<p style='color:#ef4444;'>❌ {$file} 可能需要更新</p>";
        }
    } else {
        echo "<p style='color:#ef4444;'>❌ {$file} 不存在</p>";
    }
}
echo "</div>";

// 5. 检查VIS登录重定向
echo "<div class='result'>";
echo "<h3>5. 检查登录重定向配置</h3>";
$vis_lib = $root . '/app/vis/lib/vis_lib.php';
if (file_exists($vis_lib)) {
    $content = file_get_contents($vis_lib);
    if (preg_match("/header\(['\"]Location:\s*([^'\"]+)['\"].*vis_require_login/s", $content, $matches)) {
        $redirect_url = trim($matches[1]);
        if (strpos($redirect_url, '/vis/') !== false) {
            echo "<p style='color:#10b981;'>✅ 登录重定向正确: {$redirect_url}</p>";
        } else {
            echo "<p style='color:#ef4444;'>❌ 登录重定向错误: {$redirect_url}</p>";
        }
    } else {
        echo "<p style='color:#f59e0b;'>⚠️ 未找到登录重定向配置</p>";
    }
} else {
    echo "<p style='color:#ef4444;'>❌ vis_lib.php 不存在</p>";
}
echo "</div>";

echo "<hr style='border-color:#2b303b;margin:30px 0;'>";
echo "<h2>✨ 缓存清理完成</h2>";
echo "<p>建议操作：</p>";
echo "<ol>";
echo "<li><a href='test_login.php' style='color:#ff6b4a;'>运行登录诊断</a></li>";
echo "<li><a href='/vis/ap/index.php?action=login' style='color:#ff6b4a;'>访问登录页面</a></li>";
echo "<li><a href='/vis/ap/index.php' style='color:#ff6b4a;'>测试默认跳转</a></li>";
echo "</ol>";
echo "</body></html>";
