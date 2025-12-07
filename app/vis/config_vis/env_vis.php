<?php
/**
 * VIS System Configuration
 * 文件路径: app/vis/config_vis/env_vis.php
 * 说明: VIS 系统配置文件
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

// ============================================
// 数据库配置（复用MRS的数据库）
// ============================================
define('VIS_DB_HOST', 'mhdlmskp2kpxguj.mysql.db');
define('VIS_DB_PORT', '3306');
define('VIS_DB_NAME', 'mhdlmskp2kpxguj');
define('VIS_DB_USER', 'mhdlmskp2kpxguj');
define('VIS_DB_PASS', 'BWNrmksqMEqgbX37r3QNDJLGRrUka');
define('VIS_DB_CHARSET', 'utf8mb4');

// ============================================
// Cloudflare R2 配置
// ============================================
define('VIS_R2_ACCOUNT_ID', 'YOUR_CLOUDFLARE_ACCOUNT_ID');  // TODO: 填写实际的Account ID
define('VIS_R2_ACCESS_KEY_ID', 'YOUR_R2_ACCESS_KEY_ID');    // TODO: 填写实际的Access Key ID
define('VIS_R2_SECRET_ACCESS_KEY', 'YOUR_R2_SECRET_KEY');   // TODO: 填写实际的Secret Access Key
define('VIS_R2_BUCKET_NAME', 'vis-videos');                  // R2存储桶名称
define('VIS_R2_ENDPOINT', 'https://' . VIS_R2_ACCOUNT_ID . '.r2.cloudflarestorage.com'); // R2端点
define('VIS_R2_REGION', 'auto');                             // R2区域（通常为auto）

// R2公共访问域名（如果配置了Custom Domain）
define('VIS_R2_PUBLIC_URL', 'https://vis.dc.abcabc.net');   // TODO: 配置R2的自定义域名

// 签名URL有效期（秒）
define('VIS_SIGNED_URL_EXPIRES', 300); // 5分钟

// ============================================
// 路径常量
// ============================================
define('VIS_APP_PATH', PROJECT_ROOT . '/app/vis');
define('VIS_CONFIG_PATH', VIS_APP_PATH . '/config_vis');
define('VIS_LIB_PATH', VIS_APP_PATH . '/lib');
define('VIS_VIEW_PATH', VIS_APP_PATH . '/views');
define('VIS_API_PATH', VIS_APP_PATH . '/api');

// 临时文件上传目录
define('VIS_UPLOAD_TEMP_DIR', '/tmp/vis_uploads');

// ============================================
// 会话配置（VIS独立会话，避免与其他系统冲突）
// ============================================
define('VIS_SESSION_NAME', 'VIS_SESSID');
define('VIS_SESSION_TIMEOUT', 1800); // 30分钟
define('VIS_SESSION_SAMESITE', 'Strict');

// ============================================
// 上传限制
// ============================================
define('VIS_MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB（可根据需要调整）
define('VIS_ALLOWED_MIME_TYPES', ['video/mp4', 'video/quicktime']); // mp4, mov
define('VIS_ALLOWED_EXTENSIONS', ['mp4', 'mov']);

/**
 * 获取数据库连接
 * @return PDO
 * @throws PDOException
 */
function get_vis_db_connection() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            VIS_DB_HOST,
            VIS_DB_PORT,
            VIS_DB_NAME,
            VIS_DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO($dsn, VIS_DB_USER, VIS_DB_PASS, $options);

        return $pdo;
    } catch (PDOException $e) {
        error_log('VIS Database connection error: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * 启动安全会话（复用MRS的会话配置）
 */
function vis_start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', $is_https ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', VIS_SESSION_SAMESITE);

        $params = session_get_cookie_params();
        session_name(VIS_SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => $params['lifetime'],
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $is_https,
            'httponly' => true,
            'samesite' => VIS_SESSION_SAMESITE,
        ]);

        session_start();

        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
}

/**
 * 日志记录函数
 * @param string $message
 * @param string $level
 * @param array $context
 */
function vis_log($message, $level = 'INFO', $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? json_encode($context) : '';
    $log_message = sprintf("[%s] [VIS] [%s] %s %s\n", $timestamp, $level, $message, $context_str);
    error_log($log_message);
}

/**
 * JSON响应输出
 * @param bool $success
 * @param mixed $data
 * @param string $message
 */
function vis_json_response($success, $data = null, $message = '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 获取JSON输入
 * @return array|null
 */
function vis_get_json_input() {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return null;
    }
    return json_decode($input, true);
}

/**
 * 创建临时上传目录
 */
function vis_ensure_upload_dir() {
    if (!is_dir(VIS_UPLOAD_TEMP_DIR)) {
        mkdir(VIS_UPLOAD_TEMP_DIR, 0755, true);
    }
}
