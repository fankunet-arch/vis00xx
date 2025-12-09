<?php
/**
 * VIS 路由测试文件
 */

define('VIS_ENTRY', true);
define('PROJECT_ROOT', dirname(dirname(dirname(__DIR__))));

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>VIS Route Test</title></head><body>";
echo "<h1>VIS 路由测试</h1>";
echo "<pre>";

echo "1. PROJECT_ROOT: " . PROJECT_ROOT . "\n";
echo "2. Bootstrap path: " . PROJECT_ROOT . '/app/vis/bootstrap.php' . "\n";
echo "3. Bootstrap exists: " . (file_exists(PROJECT_ROOT . '/app/vis/bootstrap.php') ? 'YES' : 'NO') . "\n\n";

require_once PROJECT_ROOT . '/app/vis/bootstrap.php';

echo "4. VIS_VIEW_PATH: " . VIS_VIEW_PATH . "\n";
echo "5. Login view path: " . VIS_VIEW_PATH . '/login.php' . "\n";
echo "6. Login view exists: " . (file_exists(VIS_VIEW_PATH . '/login.php') ? 'YES' : 'NO') . "\n\n";

$test_action = 'login';
echo "7. Test action: {$test_action}\n";
echo "8. Is in public_actions: " . (in_array($test_action, ['login', 'do_login']) ? 'YES' : 'NO') . "\n";
echo "9. Should call vis_require_login: " . (!in_array($test_action, ['login', 'do_login']) ? 'YES' : 'NO') . "\n\n";

echo "10. Session status: " . session_status() . " (1=disabled, 2=active)\n";
echo "11. Session name: " . session_name() . "\n";
echo "12. Is logged in: " . (vis_is_user_logged_in() ? 'YES' : 'NO') . "\n\n";

if (file_exists(VIS_VIEW_PATH . '/login.php')) {
    echo "13. Login.php first 5 lines:\n";
    $lines = file(VIS_VIEW_PATH . '/login.php');
    for ($i = 0; $i < min(5, count($lines)); $i++) {
        echo "    " . htmlspecialchars($lines[$i]);
    }
}

echo "</pre>";
echo "<hr>";
echo "<p><a href='/vis/ap/index.php?action=login'>Test actual /vis/ap/index.php?action=login</a></p>";
echo "</body></html>";
