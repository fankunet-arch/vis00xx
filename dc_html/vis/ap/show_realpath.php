<?php
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Real Paths</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f0f0f0;white-space:pre-wrap;}</style></head><body>";

echo "<h1>实际文件路径信息</h1>\n\n";

echo "__FILE__: " . __FILE__ . "\n";
echo "realpath(__FILE__): " . realpath(__FILE__) . "\n\n";

echo "__DIR__: " . __DIR__ . "\n";
echo "realpath(__DIR__): " . realpath(__DIR__) . "\n\n";

$parent = dirname(__DIR__);
echo "Parent dir: " . $parent . "\n";
echo "realpath(Parent): " . realpath($parent) . "\n\n";

$root = dirname(dirname(dirname(__DIR__)));
echo "PROJECT_ROOT (3 levels up): " . $root . "\n";
echo "realpath(PROJECT_ROOT): " . realpath($root) . "\n\n";

$vis_lib = $root . '/app/vis/lib/vis_lib.php';
echo "VIS lib path: " . $vis_lib . "\n";
echo "realpath(VIS lib): " . realpath($vis_lib) . "\n";
echo "Exists: " . (file_exists($vis_lib) ? "YES" : "NO") . "\n";
if (file_exists($vis_lib)) {
    echo "Modified: " . date('Y-m-d H:i:s', filemtime($vis_lib)) . "\n";
    echo "Size: " . filesize($vis_lib) . " bytes\n";
}

echo "\n<hr>\n";
echo "<a href='/vis/ap/clear_cache.php'>清除缓存</a> | ";
echo "<a href='/vis/ap/debug_login.php'>调试登录</a>";
echo "</body></html>";
