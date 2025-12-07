<?php
/**
 * VIS 部署脚本 - 将修复后的文件写入生产环境
 */

// 需要更新的文件内容
$files_to_update = [
    // 1. vis_lib.php - 修复登录跳转路径
    'app/vis/lib/vis_lib.php' => [
        'search' => "header('Location: /mrs/ap/index.php?action=login');",
        'replace' => "header('Location: /vis/ap/index.php?action=login');",
    ],

    // 2. env_vis.php - 修复会话名称
    'app/vis/config_vis/env_vis.php' => [
        'search' => "define('VIS_SESSION_NAME', ini_get('session.name') ?: 'PHPSESSID');",
        'replace' => "define('VIS_SESSION_NAME', 'VIS_SESSID');",
    ],
];

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>VIS Deploy</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f0f0f0;}";
echo ".result{background:#fff;padding:15px;margin:10px 0;border-left:4px solid #007bff;}";
echo ".success{border-left-color:#28a745;background:#f5fff5;}";
echo ".error{border-left-color:#dc3545;background:#fff5f5;}</style></head><body>";

echo "<h1>VIS 紧急部署修复</h1>";

// 获取生产环境的实际路径
$prod_root = dirname(dirname(dirname(__DIR__)));
echo "<div class='result'>";
echo "<h3>生产环境路径</h3>";
echo "PROJECT_ROOT: " . $prod_root . "<br>";
echo "Real path: " . realpath($prod_root) . "<br>";
echo "</div>";

// 执行更新
foreach ($files_to_update as $rel_path => $replacement) {
    echo "<div class='result'>";
    echo "<h3>更新文件: {$rel_path}</h3>";

    $file_path = $prod_root . '/' . $rel_path;
    echo "完整路径: {$file_path}<br>";

    if (!file_exists($file_path)) {
        echo "<span style='color:red'>❌ 文件不存在</span><br>";
        echo "</div>";
        continue;
    }

    // 读取文件
    $content = file_get_contents($file_path);
    $original_size = strlen($content);

    // 检查是否需要修改
    if (strpos($content, $replacement['search']) === false) {
        echo "<span style='color:orange'>⚠️  未找到需要替换的内容（可能已修复）</span><br>";
        echo "查找: " . htmlspecialchars($replacement['search']) . "<br>";
        echo "</div>";
        continue;
    }

    // 执行替换
    $new_content = str_replace($replacement['search'], $replacement['replace'], $content);
    $new_size = strlen($new_content);

    // 写入文件
    $backup_path = $file_path . '.backup.' . date('YmdHis');
    if (file_put_contents($backup_path, $content) === false) {
        echo "<span style='color:red'>❌ 备份失败</span><br>";
        echo "</div>";
        continue;
    }

    if (file_put_contents($file_path, $new_content) === false) {
        echo "<span style='color:red'>❌ 写入失败</span><br>";
        echo "</div>";
        continue;
    }

    echo "<span style='color:green'>✅ 更新成功</span><br>";
    echo "原始大小: {$original_size} bytes<br>";
    echo "新大小: {$new_size} bytes<br>";
    echo "备份位置: {$backup_path}<br>";
    echo "替换内容:<br>";
    echo "  FROM: <code>" . htmlspecialchars($replacement['search']) . "</code><br>";
    echo "  TO: <code>" . htmlspecialchars($replacement['replace']) . "</code><br>";

    echo "</div>";
}

// 清除OPcache
echo "<div class='result'>";
echo "<h3>清除缓存</h3>";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache 已清除<br>";
} else {
    echo "⚠️  OPcache 未安装<br>";
}
clearstatcache(true);
echo "✅ Realpath cache 已清除<br>";
echo "</div>";

echo "<hr>";
echo "<p><strong>⚠️ 重要：请立即测试</strong></p>";
echo "<p><a href='/vis/ap/debug_login.php' style='padding:10px 20px;background:#007bff;color:#fff;text-decoration:none;border-radius:4px;display:inline-block;'>1. 查看调试信息</a></p>";
echo "<p><a href='/vis/ap/index.php?action=login' style='padding:10px 20px;background:#28a745;color:#fff;text-decoration:none;border-radius:4px;display:inline-block;'>2. 测试登录页面</a></p>";
echo "</body></html>";
