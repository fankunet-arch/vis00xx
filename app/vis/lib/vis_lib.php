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
 * @param array $filters ['category' => '', 'platform' => '', 'product_id' => '', 'series_id' => '', 'season_id' => '', 'status' => 'active']
 * @param int $limit
 * @param int $offset
 * @return array
 */
function vis_get_videos($pdo, $filters = [], $limit = 50, $offset = 0) {
    try {
        // 使用 GROUP_CONCAT 聚合系列信息
        $sql = "
            SELECT v.*,
                   GROUP_CONCAT(vs.series_name) as series_tags
            FROM vis_videos v
            LEFT JOIN vis_video_series_rel vs ON v.id = vs.video_id
            WHERE 1=1
        ";
        $params = [];

        // 状态过滤（默认只显示active）
        $status = $filters['status'] ?? 'active';
        $sql .= " AND v.status = :status";
        $params['status'] = $status;

        // 分类过滤
        if (!empty($filters['category'])) {
            $sql .= " AND v.category = :category";
            $params['category'] = $filters['category'];
        }

        // 平台过滤
        if (!empty($filters['platform'])) {
            $sql .= " AND v.platform = :platform";
            $params['platform'] = $filters['platform'];
        }

        // 产品过滤
        if (!empty($filters['product_id'])) {
            $sql .= " AND v.product_id = :product_id";
            $params['product_id'] = $filters['product_id'];
        }

        // 系列过滤 (通过关联表)
        if (!empty($filters['series'])) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM vis_video_series_rel sub_vs
                WHERE sub_vs.video_id = v.id
                AND sub_vs.series_name = :series
            )";
            $params['series'] = $filters['series'];
        }
        // 兼容旧系列ID过滤
        if (!empty($filters['series_id'])) {
            $sql .= " AND v.series_id = :series_id";
            $params['series_id'] = $filters['series_id'];
        }

        // 季节过滤
        if (!empty($filters['season_id'])) {
            $sql .= " AND v.season_id = :season_id";
            $params['season_id'] = $filters['season_id'];
        }

        // 搜索关键词
        if (!empty($filters['keyword'])) {
            $sql .= " AND v.title LIKE :keyword";
            $params['keyword'] = '%' . $filters['keyword'] . '%';
        }

        $sql .= " GROUP BY v.id ORDER BY v.created_at DESC LIMIT :limit OFFSET :offset";

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
        $sql = "SELECT COUNT(DISTINCT v.id) as total FROM vis_videos v WHERE 1=1";
        $params = [];

        $status = $filters['status'] ?? 'active';
        $sql .= " AND v.status = :status";
        $params['status'] = $status;

        if (!empty($filters['category'])) {
            $sql .= " AND v.category = :category";
            $params['category'] = $filters['category'];
        }

        if (!empty($filters['series'])) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM vis_video_series_rel sub_vs
                WHERE sub_vs.video_id = v.id
                AND sub_vs.series_name = :series
            )";
            $params['series'] = $filters['series'];
        }

        if (!empty($filters['platform'])) {
            $sql .= " AND v.platform = :platform";
            $params['platform'] = $filters['platform'];
        }

        if (!empty($filters['product_id'])) {
            $sql .= " AND v.product_id = :product_id";
            $params['product_id'] = $filters['product_id'];
        }

        if (!empty($filters['series_id'])) {
            $sql .= " AND v.series_id = :series_id";
            $params['series_id'] = $filters['series_id'];
        }

        if (!empty($filters['season_id'])) {
            $sql .= " AND v.season_id = :season_id";
            $params['season_id'] = $filters['season_id'];
        }

        if (!empty($filters['keyword'])) {
            $sql .= " AND v.title LIKE :keyword";
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
        // 使用 GROUP_CONCAT 聚合系列信息
        $stmt = $pdo->prepare("
            SELECT v.*,
                   GROUP_CONCAT(vs.series_name) as series_tags
            FROM vis_videos v
            LEFT JOIN vis_video_series_rel vs ON v.id = vs.video_id
            WHERE v.id = :id
            GROUP BY v.id
            LIMIT 1
        ");
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
            (title, platform, category, product_id, series_id, season_id,
             r2_key, cover_url, duration, file_size, mime_type,
             original_filename, created_by, status)
            VALUES
            (:title, :platform, :category, :product_id, :series_id, :season_id,
             :r2_key, :cover_url, :duration, :file_size, :mime_type,
             :original_filename, :created_by, 'active')
        ");

        $stmt->execute([
            'title' => $data['title'],
            'platform' => $data['platform'] ?? 'other',
            'category' => $data['category'] ?? 'product',
            'product_id' => $data['product_id'] ?? null,
            'series_id' => $data['series_id'] ?? null,
            'season_id' => $data['season_id'] ?? null,
            'r2_key' => $data['r2_key'],
            'cover_url' => $data['cover_url'] ?? null,
            'duration' => $data['duration'] ?? 0,
            'file_size' => $data['file_size'] ?? 0,
            'mime_type' => $data['mime_type'] ?? 'video/mp4',
            'original_filename' => $data['original_filename'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);

        $videoId = $pdo->lastInsertId();

        // 2. 插入系列关联 (支持多系列标签)
        if (isset($data['series_names']) && is_array($data['series_names'])) {
            $dictStmt = $pdo->prepare("INSERT IGNORE INTO vis_series_dict (name) VALUES (?)");
            $relStmt = $pdo->prepare("INSERT INTO vis_video_series_rel (video_id, series_name) VALUES (?, ?)");

            foreach ($data['series_names'] as $seriesName) {
                $seriesName = trim($seriesName);
                if (empty($seriesName)) continue;

                // 确保系列在字典中存在
                $dictStmt->execute([$seriesName]);

                // 建立关联
                try {
                    $relStmt->execute([$videoId, $seriesName]);
                } catch (PDOException $e) {
                    // 忽略重复
                }
            }
        }

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

        if (isset($data['product_id'])) {
            $fields[] = 'product_id = :product_id';
            $params['product_id'] = $data['product_id'];
        }

        if (isset($data['series_id'])) {
            $fields[] = 'series_id = :series_id';
            $params['series_id'] = $data['series_id'];
        }

        if (isset($data['season_id'])) {
            $fields[] = 'season_id = :season_id';
            $params['season_id'] = $data['season_id'];
        }

        if (!empty($fields)) {
            $sql = "UPDATE vis_videos SET " . implode(', ', $fields) . " WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }

        // 2. 如果提供了 series_names 字段，更新关联表
        if (isset($data['series_names']) && is_array($data['series_names'])) {
            // A. 删除旧关联
            $pdo->prepare("DELETE FROM vis_video_series_rel WHERE video_id = ?")->execute([$id]);

            // B. 插入新关联
            $dictStmt = $pdo->prepare("INSERT IGNORE INTO vis_series_dict (name) VALUES (?)");
            $relStmt = $pdo->prepare("INSERT INTO vis_video_series_rel (video_id, series_name) VALUES (?, ?)");

            foreach ($data['series_names'] as $seriesName) {
                $seriesName = trim($seriesName);
                if (empty($seriesName)) continue;

                $dictStmt->execute([$seriesName]);
                try {
                    $relStmt->execute([$id, $seriesName]);
                } catch (PDOException $e) {
                    // 忽略重复
                }
            }
        }

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

        // 1. 删除R2视频主文件
        $r2Result = vis_delete_from_r2($video['r2_key']);

        // 2. [新增] 删除R2封面文件
        // 逻辑：将视频路径后缀（如 .mp4）替换为 .jpg
        // 例如：vis/202312/uuid.mp4 -> vis/202312/uuid.jpg
        $coverKey = preg_replace('/\.[^.]+$/', '.jpg', $video['r2_key']);

        // 确保生成的key有效且不等于原key（防止误删非扩展名文件）
        if ($coverKey && $coverKey !== $video['r2_key']) {
             // 尝试删除封面，即使文件不存在（S3协议返回204）也视为成功，不影响主流程
             vis_delete_from_r2($coverKey);
        }

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

// ============================================
// 系列管理函数
// ============================================

/**
 * 获取所有系列
 * @param PDO $pdo
 * @return array
 */
function vis_get_series($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT * FROM vis_series
            WHERE is_enabled = 1
            ORDER BY sort_order ASC, id ASC
        ");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        vis_log('获取系列列表失败: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 创建系列
 * @param PDO $pdo
 * @param array $data
 * @return array ['success' => bool, 'id' => int|null, 'message' => string]
 */
function vis_create_series($pdo, $data) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO vis_series (series_name, series_code, description, sort_order)
            VALUES (:series_name, :series_code, :description, :sort_order)
        ");

        $stmt->execute([
            'series_name' => $data['series_name'],
            'series_code' => $data['series_code'] ?? strtolower(preg_replace('/\s+/', '_', $data['series_name'])),
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0
        ]);

        $seriesId = $pdo->lastInsertId();
        vis_log("系列创建成功: ID={$seriesId}, name={$data['series_name']}", 'INFO');

        return ['success' => true, 'id' => $seriesId, 'message' => '系列创建成功'];
    } catch (PDOException $e) {
        vis_log('创建系列失败: ' . $e->getMessage(), 'ERROR');
        return ['success' => false, 'id' => null, 'message' => '创建失败: ' . $e->getMessage()];
    }
}

// ============================================
// 产品管理函数
// ============================================

/**
 * 获取所有产品
 * @param PDO $pdo
 * @param int|null $seriesId 可选，按系列筛选
 * @return array
 */
function vis_get_products($pdo, $seriesId = null) {
    try {
        $sql = "
            SELECT p.*, s.series_name
            FROM vis_products p
            LEFT JOIN vis_series s ON p.series_id = s.id
            WHERE p.is_enabled = 1
        ";

        if ($seriesId !== null) {
            $sql .= " AND p.series_id = :series_id";
        }

        $sql .= " ORDER BY p.sort_order ASC, p.id ASC";

        $stmt = $pdo->prepare($sql);
        if ($seriesId !== null) {
            $stmt->bindValue(':series_id', $seriesId, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        vis_log('获取产品列表失败: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 根据产品名称搜索（支持快速创建）
 * @param PDO $pdo
 * @param string $keyword
 * @return array
 */
function vis_search_products($pdo, $keyword) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, s.series_name
            FROM vis_products p
            LEFT JOIN vis_series s ON p.series_id = s.id
            WHERE p.is_enabled = 1
            AND p.product_name LIKE :keyword
            ORDER BY p.product_name ASC
            LIMIT 20
        ");

        $stmt->execute(['keyword' => '%' . $keyword . '%']);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        vis_log('搜索产品失败: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 创建产品
 * @param PDO $pdo
 * @param array $data
 * @return array ['success' => bool, 'id' => int|null, 'message' => string]
 */
function vis_create_product($pdo, $data) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO vis_products (product_name, product_code, series_id, description, sort_order)
            VALUES (:product_name, :product_code, :series_id, :description, :sort_order)
        ");

        $stmt->execute([
            'product_name' => $data['product_name'],
            'product_code' => $data['product_code'] ?? strtolower(preg_replace('/\s+/', '_', $data['product_name'])),
            'series_id' => $data['series_id'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0
        ]);

        $productId = $pdo->lastInsertId();
        vis_log("产品创建成功: ID={$productId}, name={$data['product_name']}", 'INFO');

        return ['success' => true, 'id' => $productId, 'message' => '产品创建成功'];
    } catch (PDOException $e) {
        vis_log('创建产品失败: ' . $e->getMessage(), 'ERROR');
        return ['success' => false, 'id' => null, 'message' => '创建失败: ' . $e->getMessage()];
    }
}

// ============================================
// 季节管理函数
// ============================================

/**
 * 获取所有季节
 * @param PDO $pdo
 * @return array
 */
function vis_get_seasons($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT * FROM vis_seasons
            WHERE is_enabled = 1
            ORDER BY sort_order ASC, id ASC
        ");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        vis_log('获取季节列表失败: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

// ============================================
// 辅助函数
// ============================================

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

/**
 * 搜索系列字典
 * @param PDO $pdo
 * @param string $keyword
 * @param int $limit
 * @return array
 */
function vis_search_series_dict($pdo, $keyword, $limit = 20) {
    try {
        $stmt = $pdo->prepare("
            SELECT name
            FROM vis_series_dict
            WHERE name LIKE :keyword
            ORDER BY name ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':keyword', '%' . $keyword . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        vis_log('搜索系列字典失败: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}

/**
 * 搜索不重复的视频标题
 * @param PDO $pdo
 * @param string $keyword
 * @param int $limit
 * @return array
 */
function vis_search_video_titles($pdo, $keyword, $limit = 20) {
    try {
        $sql = "
            SELECT DISTINCT title
            FROM vis_videos
            WHERE title LIKE :keyword
            ORDER BY title ASC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':keyword', '%' . $keyword . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        vis_log('搜索视频标题失败: ' . $e->getMessage(), 'ERROR');
        return [];
    }
}
