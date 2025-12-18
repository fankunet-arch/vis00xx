<?php
// 开启所有错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 VIS 代码语法检查</h1>";

$root = dirname(dirname(dirname(__DIR__))); // 回到项目根目录
$files_to_check = [
    'app/vis/config_vis/env_vis.php',
    'app/vis/lib/r2_client.php',
    'app/vis/lib/vis_lib.php',
    'app/vis/bootstrap.php'
];

foreach ($files_to_check as $file) {
    $path = $root . '/' . $file;
    echo "<hr><strong>检查文件:</strong> {$file}<br>";
    
    if (!file_exists($path)) {
        echo "<span style='color:red'>❌ 文件不存在: $path</span>";
        continue;
    }

    // 使用 PHP 内置的 lint 检查 (需要 exec 权限，如果不行则尝试 include)
    $output = [];
    $return_var = 0;
    // 尝试包含文件（如果在 try-catch 中，可以捕获部分错误，但语法错误通常会直接终止）
    // 所以我们用一种安全的方法：读取内容简单的检查一下 <?php 标签
    
    $content = file_get_contents($path);
    if (strpos($content, '<?php') === false) {
        echo "<span style='color:red'>❌ 文件缺少 &lt;?php 头部标签</span>";
    } else {
        echo "<span style='color:green'>✅ 文件头检查通过</span>";
    }
    
    // 尝试执行语法检查命令 (仅在 Linux/CLI 环境有效)
    if (function_exists('exec')) {
        exec("php -l " . escapeshellarg($path), $output, $return_var);
        if ($return_var === 0) {
            echo "<br><span style='color:green'>✅ 语法检查通过 (php -l)</span>";
        } else {
            echo "<br><span style='color:red'>❌ 语法错误:</span><br>" . implode("<br>", $output);
        }
    } else {
        echo "<br>⚠️ 无法执行 exec('php -l')，尝试直接 include...";
        try {
            // 这种方法不完美，因为如果 env_vis.php 有定义常量，重复 include 会报错
            // 但对于调试 500 错误很有用
            // 注意：不要 include bootstrap，因为它会执行连接数据库
            if ($file !== 'app/vis/bootstrap.php') {
               // 仅仅检查文件是否可读，不做 include 防止污染环境或二次报错
               echo "<br>ℹ️ 文件可读，但无法深入检查语法（exec 被禁用）。请手动检查 PHP 错误日志。";
            }
        } catch (Throwable $e) {
            echo "<br><span style='color:red'>❌ 加载异常: " . $e->getMessage() . "</span>";
        }
    }
}

echo "<hr>";
echo "<h3>尝试加载 Bootstrap (如果是配置问题，这里会报错)</h3>";
try {
    require_once $root . '/app/vis/bootstrap.php';
    echo "<span style='color:green'>✅ 系统启动成功 (Bootstrap loaded)</span>";
} catch (Throwable $e) {
    echo "<span style='color:red'>❌ 系统启动失败: " . $e->getMessage() . "</span>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>