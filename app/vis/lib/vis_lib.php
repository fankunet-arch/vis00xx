<?php
/**
 * VIS Video Inspiration System - Core Library
 * 文件路径: app/vis/lib/vis_lib.php
 * 说明: 核心业务逻辑函数
 */

// 加载R2客户端
require_once __DIR__ . '/r2_client.php';

// ============================================
// 认证相关函数 (共享sys_users表)
// ============================================

/**
 * 验证用户登录（复用sys_users表）
 * @param PDO $pdo
 * @param string $username
 * @param string $password
 * @return array|false
 */
function vis_authenticate_user($pdo, $username, $password) {
    try {
        $stmt = $pdo->prepare("
            SELECT user_id, user_login, user_secret_hash, user_email,
                   user_display_name, user_status
            FROM sys_users
            WHERE user_login = :username
            LIMIT 1
        ");
        $stmt->bindValue(':username', $username);
        $stmt->execute();

        $user = $stmt->fetch();

        if (!$user) {
            vis_log("登录失败: 用户不存在 - {$username}", 'WARNING');
            return false;
        }

        if ($user['user_status'] !== 'active') {
            vis_log("登录失败: 账户未激活 - {$username}", 'WARNING');
            return false;
        }

        if (password_verify($password, $user['user_secret_hash'])) {
            $update = $pdo->prepare("UPDATE sys_users SET user_last_login_at = NOW(6) WHERE user_id = :user_id");
            $update->bindValue(':user_id', $user['user_id'], PDO::PARAM_INT);
            $update->execute();

            unset($user['user_secret_hash']);
            vis_log("登录成功: {$username}", 'INFO');
            return $user;
        }

        vis_log("登录失败: 密码错误 - {$username}", 'WARNING');
        return false;
    } catch (PDOException $e) {
        vis_log('用户认证失败: ' . $e->getMessage(), 'ERROR');
        return false;
    }
}

/**
 * 创建用户会话
 * @param array $user
 */
function vis_create_user_session($user) {
    vis_start_secure_session();

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_login'] = $user['user_login'];
    $_SESSION['user_display_name'] = $user['user_display_name'];
    $_SESSION['user_email'] = $user['user_email'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
}

/**
 * 检查用户是否登录
 * @return bool
 */
function vis_is_user_logged_in() {
    vis_start_secure_session();

    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }

    $timeout = VIS_SESSION_TIMEOUT;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        vis_destroy_user_session();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * 销毁会话
 */
function vis_destroy_user_session() {
    vis_start_secure_session();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

/**
 * 登录保护（跳转到VIS独立登录页面）
 */
function vis_require_login() {
    if (!vis_is_user_logged_in()) {
        header('Location: /vis/ap/index.php?action=login');
        exit;
    }
}

// ============================================
// R2存储辅助函数
// ============================================

/**
 * 获取R2客户端实例
 * @return R2Client
 */
function vis_get_r2_client() {
    static $client = null;

    if ($client === null) {
        $client = new R2Client(
            VIS_R2_ACCOUNT_ID,
            VIS_R2_ACCESS_KEY_ID,
            VIS_R2_SECRET_ACCESS_KEY,
            VIS_R2_BUCKET_NAME,
            VIS_R2_REGION
        );
    }

    return $client;
}

/**
 * 生成R2存储路径（格式: vis/YYYYMM/uuid.ext）
 * @param string $extension 文件扩展名
 * @return string
 */
function vis_generate_r2_key($extension) {
    $yearMonth = date('Ym');
    $uuid = bin2hex(random_bytes(16));
    return "vis/{$yearMonth}/{$uuid}.{$extension}";
}

/**
 * 上传文件到R2
 * @param string $localPath 本地文件路径
 * @param string $r2Key R2存储路径
 * @param string $mimeType MIME类型
 * @return array ['success' => bool, 'message' => string]
 */
function vis_upload_to_r2($localPath, $r2Key, $mimeType) {
    try {
        $client = vis_get_r2_client();
        $result = $client->putObject($r2Key, $localPath, $mimeType);

        if ($result['success']) {
            vis_log("文件上传成功: {$r2Key}", 'INFO');
        } else {
            vis_log("文件上传失败: {$r2Key} - " . $result['message'], 'ERROR');
        }

        return $result;
    } catch (Exception $e) {
        vis_log("R2上传异常: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * 从R2删除文件
 * @param string $r2Key R2存储路径
 * @return array ['success' => bool, 'message' => string]
 */
function vis_delete_from_r2($r2Key) {
    try {
        $client = vis_get_r2_client();
        $result = $client->deleteObject($r2Key);

        if ($result['success']) {
            vis_log("R2文件删除成功: {$r2Key}", 'INFO');
        } else {
            vis_log("R2文件删除失败: {$r2Key} - " . $result['message'], 'ERROR');
        }

        return $result;
    } catch (Exception $e) {
        vis_log("R2删除异常: " . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * 获取签名URL
 * @param string $r2Key R2存储路径
 * @param int $expiresIn 有效期（秒）
 * @return string|null
 */
function vis_get_signed_url($r2Key, $expiresIn = 300) {
    try {
        $client = vis_get_r2_client();
        return $client->getPresignedUrl($r2Key, $expiresIn);
    } catch (Exception $e) {
        vis_log("获取签名URL失败: " . $e->getMessage(), 'ERROR');
        return null;
    }
}

// ============================================
// 视频管理函数
// ============================================

/**
 * 获取视频列表
 * @param PDO $pdo
 * @param array $filters ['category' => '', 'platform' => '', 'status' => 'active']
 * @param int $limit
 * @param int $offset
 * @return array
 */
function vis_get_videos($pdo, $filters = [], $limit = 50, $offset = 0) {
    try {
        $sql = "SELECT * FROM vis_videos WHERE 1=1";
        $params = [];

        // 状态过滤（默认只显示active）
        $status = $filters['status'] ?? 'active';
        $sql .= " AND status = :status";
        $params['status'] = $status;

        // 分类过滤
        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params['category'] = $filters['category'];
        }

        // 平台过滤
        if (!empty($filters['platform'])) {
            $sql .= " AND platform = :platform";
            $params['platform'] = $filters['platform'];
        }

        // 搜索关键词
        if (!empty($filters['keyword'])) {
            $sql .= " AND title LIKE :keyword";
            $params['keyword'] = '%' . $filters['keyword'] . '%';
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        vis_log('获取视频列表失败: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 获取视频总数
 * @param PDO $pdo
 * @param array $filters
 * @return int
 */
function vis_get_videos_count($pdo, $filters = []) {
    try {
        $sql = "SELECT COUNT(*) as total FROM vis_videos WHERE 1=1";
        $params = [];

        $status = $filters['status'] ?? 'active';
        $sql .= " AND status = :status";
        $params['status'] = $status;

        if (!empty($filters['category'])) {
            $sql .= " AND category = :category";
            $params['category'] = $filters['category'];
        }

        if (!empty($filters['platform'])) {
            $sql .= " AND platform = :platform";
            $params['platform'] = $filters['platform'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND title LIKE :keyword";
            $params['keyword'] = '%' . $filters['keyword'] . '%';
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();

        return (int)$result['total'];
    } catch (PDOException $e) {
        vis_log('获取视频总数失败: ' . $e->getMessage(), 'ERROR');
        return 0;
    }
}

/**
 * 根据ID获取视频
 * @param PDO $pdo
 * @param int $id
 * @return array|null
 */
function vis_get_video_by_id($pdo, $id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM vis_videos WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    } catch (PDOException $e) {
        vis_log('获取视频详情失败: ' . $e->getMessage(), 'ERROR');
        return null;
    }
}

/**
 * 创建视频记录
 * @param PDO $pdo
 * @param array $data
 * @return array ['success' => bool, 'id' => int|null, 'message' => string]
 */
function vis_create_video($pdo, $data) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO vis_videos
            (title, platform, category, r2_key, cover_url, duration,
             file_size, mime_type, original_filename, created_by, status)
            VALUES
            (:title, :platform, :category, :r2_key, :cover_url, :duration,
             :file_size, :mime_type, :original_filename, :created_by, 'active')
        ");

        $stmt->execute([
            'title' => $data['title'],
            'platform' => $data['platform'] ?? 'other',
            'category' => $data['category'] ?? '其他',
            'r2_key' => $data['r2_key'],
            'cover_url' => $data['cover_url'] ?? null,
            'duration' => $data['duration'] ?? 0,
            'file_size' => $data['file_size'] ?? 0,
            'mime_type' => $data['mime_type'] ?? 'video/mp4',
            'original_filename' => $data['original_filename'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        $videoId = $pdo->lastInsertId();

        vis_log("视频创建成功: ID={$videoId}, title={$data['title']}", 'INFO');

        return [
            'success' => true,
            'id' => $videoId,
            'message' => '视频上传成功'
        ];
    } catch (PDOException $e) {
        vis_log('创建视频记录失败: ' . $e->getMessage(), 'ERROR');
        return [
            'success' => false,
            'id' => null,
            'message' => '创建失败: ' . $e->getMessage()
        ];
    }
}

/**
 * 更新视频信息
 * @param PDO $pdo
 * @param int $id
 * @param array $data
 * @return array ['success' => bool, 'message' => string]
 */
function vis_update_video($pdo, $id, $data) {
    try {
        $fields = [];
        $params = ['id' => $id];

        if (isset($data['title'])) {
            $fields[] = 'title = :title';
            $params['title'] = $data['title'];
        }

        if (isset($data['category'])) {
            $fields[] = 'category = :category';
            $params['category'] = $data['category'];
        }

        if (isset($data['platform'])) {
            $fields[] = 'platform = :platform';
            $params['platform'] = $data['platform'];
        }

        if (empty($fields)) {
            return ['success' => false, 'message' => '没有需要更新的字段'];
        }

        $sql = "UPDATE vis_videos SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        vis_log("视频更新成功: ID={$id}", 'INFO');

        return ['success' => true, 'message' => '更新成功'];
    } catch (PDOException $e) {
        vis_log('更新视频失败: ' . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => '更新失败: ' . $e->getMessage()];
    }
}

/**
 * 删除视频（软删除 + 物理删除R2文件）
 * @param PDO $pdo
 * @param int $id
 * @return array ['success' => bool, 'message' => string]
 */
function vis_delete_video($pdo, $id) {
    try {
        $pdo->beginTransaction();

        // 获取视频信息
        $video = vis_get_video_by_id($pdo, $id);
        if (!$video) {
            $pdo->rollBack();
            return ['success' => false, 'message' => '视频不存在'];
        }

        // 软删除数据库记录
        $stmt = $pdo->prepare("
            UPDATE vis_videos
            SET status = 'deleted', deleted_at = NOW(6)
            WHERE id = :id
        ");
        $stmt->execute(['id' => $id]);

        // 删除R2文件
        $r2Result = vis_delete_from_r2($video['r2_key']);

        if (!$r2Result['success']) {
            // 如果R2删除失败，记录日志但不回滚数据库操作
            vis_log("R2文件删除失败，但数据库已标记删除: ID={$id}, r2_key={$video['r2_key']}", 'WARNING');
        }

        $pdo->commit();

        vis_log("视频删除成功: ID={$id}", 'INFO');

        return ['success' => true, 'message' => '删除成功'];
    } catch (PDOException $e) {
        $pdo->rollBack();
        vis_log('删除视频失败: ' . $e->getMessage(), 'ERROR');
        return ['success' => false, 'message' => '删除失败: ' . $e->getMessage()];
    }
}

// ============================================
// 分类管理函数
// ============================================

/**
 * 获取所有分类
 * @param PDO $pdo
 * @return array
 */
function vis_get_categories($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT * FROM vis_categories
            WHERE is_enabled = 1
            ORDER BY sort_order ASC, id ASC
        ");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        vis_log('获取分类列表失败: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 验证文件类型
 * @param string $mimeType
 * @param string $extension
 * @return bool
 */
function vis_validate_file_type($mimeType, $extension) {
    $allowedMimes = VIS_ALLOWED_MIME_TYPES;
    $allowedExts = VIS_ALLOWED_EXTENSIONS;

    return in_array($mimeType, $allowedMimes) && in_array(strtolower($extension), $allowedExts);
}
