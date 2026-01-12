<?php
/**
 * VIS System Configuration
 * 文件路径: app/vis/config_vis/env_vis.php
 * 说明: VIS 系统配置文件 (QNAP QuObjects 适配版)
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

// ============================================
// 数据库配置
// ============================================
define('VIS_DB_HOST', '127.0.0.1');
define('VIS_DB_PORT', '3306');
define('VIS_DB_NAME', 'mhdlmskp2kpxg');
define('VIS_DB_USER', 'mhdlmskp2kpxg');
define('VIS_DB_PASS', 'BWNrmksqMEqgbX37r3QNDJLGRrUka');
define('VIS_DB_CHARSET', 'utf8mb4');

// ============================================
// QNAP QuObjects S3 配置
// ============================================
// 1. QNAP Access Key
define('VIS_R2_ACCESS_KEY_ID', 'QuObjects:7qzs7Cmr2R4Ch863DwAW'); 

// 2. QNAP Secret Key
define('VIS_R2_SECRET_ACCESS_KEY', 'jijPB10hGPGUigccXyPoKyorELIl11DS'); 

// 3. 存储桶名称
define('VIS_R2_BUCKET_NAME', 'vis-videos'); 

// 4. QNAP S3 服务地址 (后台上传用)
define('VIS_R2_ENDPOINT', 'http://192.168.1.129:8010'); 

// 5. 区域 (默认)
define('VIS_R2_REGION', 'us-east-1');

// 6. 公共访问域名 (前台显示用)
define('VIS_R2_PUBLIC_URL', 'http://192.168.1.129:8010/v1/AUTH_QuObjects/dms');

// 签名URL有效期
define('VIS_SIGNED_URL_EXPIRES', 300);

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
// 会话配置
// ============================================
define('VIS_SESSION_NAME', 'VIS_SESSID');
define('VIS_SESSION_TIMEOUT', 1800);
define('VIS_SESSION_SAMESITE', 'Strict');

// ============================================
// 上传限制
// ============================================
define('VIS_MAX_FILE_SIZE', 100 * 1024 * 1024);
define('VIS_ALLOWED_MIME_TYPES', ['video/mp4', 'video/quicktime']);
define('VIS_ALLOWED_EXTENSIONS', ['mp4', 'mov']);

// ============================================
// 核心系统函数 (之前丢失的部分)
// ============================================

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
 * 启动安全会话
 */
function vis_start_secure_session() {
    if (session_status() === PHP_SESSION_NONE) {
        $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        // 尝试设置 session cookie 参数，但在某些环境可能需要调整
        ini_set('session.cookie_httponly', 1);
        // 如果不是HTTPS环境，关闭 secure 标志以防无法登录
        if ($is_https) {
            ini_set('session.cookie_secure', 1);
        }
        ini_set('session.use_strict_mode', 1);
        
        // 兼容性设置：如果设置 SameSite 导致问题，可注释掉
        @ini_set('session.cookie_samesite', VIS_SESSION_SAMESITE);

        $params = session_get_cookie_params();
        session_name(VIS_SESSION_NAME);
        
        // 再次确保 cookie 参数正确
        session_set_cookie_params([
            'lifetime' => $params['lifetime'],
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $is_https, // 自动检测
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
 */
function vis_log($message, $level = 'INFO', $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? json_encode($context) : '';
    $log_message = sprintf("[%s] [VIS] [%s] %s %s\n", $timestamp, $level, $message, $context_str);
    error_log($log_message);
}

/**
 * JSON响应输出
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