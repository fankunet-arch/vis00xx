<?php
/**
 * 清除 OPcache 和其他缓存
 */
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Clear Cache</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f0f0f0;}";
echo ".result{background:#fff;padding:15px;margin:10px 0;border-left:4px solid #007bff;}</style></head><body>";

echo "<h1>清除缓存</h1>";

// 清除 OPcache
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    echo "<div class='result'>";
    echo "OPcache: " . ($result ? "✅ 已清除" : "❌ 清除失败") . "<br>";
    if (function_exists('opcache_get_status')) {
        $status = opcache_get_status();
        echo "OPcache 状态: " . ($status ? "启用" : "禁用") . "<br>";
        if ($status) {
            echo "缓存的脚本数: " . $status['opcache_statistics']['num_cached_scripts'] . "<br>";
        }
    }
    echo "</div>";
} else {
    echo "<div class='result'>OPcache: 未安装</div>";
}

// 清除 Realpath cache
clearstatcache(true);
echo "<div class='result'>Realpath cache: ✅ 已清除</div>";

// 显示当前文件路径
echo "<div class='result'>";
echo "当前文件路径: " . __FILE__ . "<br>";
echo "当前目录: " . __DIR__ . "<br>";
echo "</div>";

// 测试 vis_lib.php 路径
$vis_lib_path = dirname(dirname(__DIR__)) . '/app/vis/lib/vis_lib.php';
echo "<div class='result'>";
echo "VIS lib 路径: " . $vis_lib_path . "<br>";
echo "文件存在: " . (file_exists($vis_lib_path) ? "✅ YES" : "❌ NO") . "<br>";
if (file_exists($vis_lib_path)) {
    echo "修改时间: " . date('Y-m-d H:i:s', filemtime($vis_lib_path)) . "<br>";
    echo "文件大小: " . filesize($vis_lib_path) . " bytes<br>";
}
echo "</div>";

echo "<hr>";
echo "<p><a href='/vis/ap/debug_login.php'>返回调试页面</a></p>";
echo "<p><a href='/vis/ap/index.php?action=login'>测试登录页面</a></p>";
echo "</body></html>";
