<?php
/**
 * VIS View - Gallery
 * 文件路径: app/vis/views/gallery.php
 * 说明: 前台视频展示页面（响应式设计）
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

// 获取筛选参数
$category = $_GET['category'] ?? '';
$platform = $_GET['platform'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// 构建筛选条件
$filters = [];
if (!empty($category)) {
    $filters['category'] = $category;
}
if (!empty($platform)) {
    $filters['platform'] = $platform;
}

// 获取视频列表
$videos = vis_get_videos($pdo, $filters, $limit, $offset);
$totalVideos = vis_get_videos_count($pdo, $filters);
$totalPages = ceil($totalVideos / $limit);

// 获取分类列表
$categories = vis_get_categories($pdo);

// 平台名称映射
$platformNames = [
    'wechat' => '微信',
    'xiaohongshu' => '小红书',
    'douyin' => '抖音',
    'other' => '其他'
];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>视频灵感库 - VIS</title>
    <link rel="stylesheet" href="/vis/ap/css/common.css">
    <link rel="stylesheet" href="/vis/ap/css/gallery.css">
    <link rel="stylesheet" href="/vis/ap/css/modal.css">
</head>
<body>
    <div class="gallery-wrapper">
        <!-- 头部 -->
        <header class="gallery-header">
            <div class="container">
                <h1 class="gallery-title">视频灵感库</h1>
                <p class="gallery-subtitle">探索精选视频内容，激发创意灵感</p>
            </div>
        </header>

        <!-- 主内容 -->
        <main>
            <div class="container">
                <!-- 筛选栏 -->
                <div class="gallery-filters">
                    <form method="GET" action="/vis/index.php">
                        <input type="hidden" name="action" value="gallery">
                        <div class="filter-row">
                            <div class="filter-group">
                                <label class="filter-label">分类</label>
                                <select name="category" class="filter-select">
                                    <option value="">全部分类</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat['category_code']); ?>"
                                            <?php echo $category === $cat['category_code'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">平台</label>
                                <select name="platform" class="filter-select">
                                    <option value="">全部平台</option>
                                    <option value="wechat" <?php echo $platform === 'wechat' ? 'selected' : ''; ?>>微信</option>
                                    <option value="xiaohongshu" <?php echo $platform === 'xiaohongshu' ? 'selected' : ''; ?>>小红书</option>
                                    <option value="douyin" <?php echo $platform === 'douyin' ? 'selected' : ''; ?>>抖音</option>
                                    <option value="other" <?php echo $platform === 'other' ? 'selected' : ''; ?>>其他</option>
                                </select>
                            </div>

                            <button type="submit" class="filter-btn">筛选</button>
                        </div>
                    </form>
                </div>

                <!-- 视频网格 -->
                <?php if (empty($videos)): ?>
                    <div class="empty-gallery">
                        <div class="empty-icon">📹</div>
                        <div class="empty-text">暂无视频内容</div>
                        <div class="empty-subtext">请调整筛选条件或稍后再来</div>
                    </div>
                <?php else: ?>
                    <div class="video-grid">
                        <?php foreach ($videos as $video): ?>
                            <div class="video-card" onclick="playVideo(<?php echo $video['id']; ?>, '<?php echo htmlspecialchars(addslashes($video['title'])); ?>')">
                                <div class="video-thumbnail">
                                    <?php if (!empty($video['cover_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($video['cover_url']); ?>"
                                             alt="<?php echo htmlspecialchars($video['title']); ?>"
                                             class="video-thumbnail-img">
                                    <?php endif; ?>
                                    <div class="video-play-icon"></div>
                                    <?php if ($video['duration'] > 0): ?>
                                        <div class="video-duration">
                                            <?php
                                            $minutes = floor($video['duration'] / 60);
                                            $seconds = $video['duration'] % 60;
                                            echo sprintf('%02d:%02d', $minutes, $seconds);
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="video-info">
                                    <h3 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h3>
                                    <div class="video-meta">
                                        <span class="video-badge category"><?php echo htmlspecialchars($video['category']); ?></span>
                                        <span class="video-badge platform-<?php echo $video['platform']; ?>">
                                            <?php echo $platformNames[$video['platform']] ?? $video['platform']; ?>
                                        </span>
                                        <span class="video-date"><?php echo date('m-d', strtotime($video['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- 分页 -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <button class="pagination-btn" onclick="location.href='?action=gallery&category=<?php echo urlencode($category); ?>&platform=<?php echo urlencode($platform); ?>&page=<?php echo $page - 1; ?>'">
                                    上一页
                                </button>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <button class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"
                                    onclick="location.href='?action=gallery&category=<?php echo urlencode($category); ?>&platform=<?php echo urlencode($platform); ?>&page=<?php echo $i; ?>'">
                                    <?php echo $i; ?>
                                </button>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <button class="pagination-btn" onclick="location.href='?action=gallery&category=<?php echo urlencode($category); ?>&platform=<?php echo urlencode($platform); ?>&page=<?php echo $page + 1; ?>'">
                                    下一页
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>

        <!-- 后台入口（仅登录用户可见） -->
        <?php if (vis_is_user_logged_in()): ?>
            <div style="position: fixed; bottom: 20px; right: 20px;">
                <a href="/vis/ap/index.php?action=admin_list" class="btn btn-primary" style="box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    管理后台
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="/vis/ap/js/modal.js"></script>
    <script>
        // 播放视频
        async function playVideo(id, title) {
            try {
                // 显示加载提示
                const loadingModal = showModal({
                    title: '加载中',
                    content: '<div style="text-align:center;padding:40px;"><div class="spinner"></div></div>',
                    footer: '',
                    showClose: false
                });

                const response = await fetch(`/vis/index.php?action=play_sign&id=${id}`);
                const result = await response.json();

                // 关闭加载提示
                modal.close(false);

                if (!result.success) {
                    showAlert(result.message, '错误', 'error');
                    return;
                }

                // 显示播放器模态框
                showModal({
                    title: title,
                    content: `
                        <video class="video-player" controls autoplay oncontextmenu="return false;">
                            <source src="${result.data.url}" type="video/mp4">
                            您的浏览器不支持视频播放。
                        </video>
                    `,
                    width: '90%',
                    footer: `
                        <div class="modal-footer">
                            <button class="modal-btn modal-btn-secondary" data-action="close">关闭</button>
                        </div>
                    `
                });
            } catch (error) {
                modal.close(false);
                showAlert('获取播放链接失败，请稍后重试', '错误', 'error');
            }
        }

        // 禁用视频右键菜单
        document.addEventListener('contextmenu', function(e) {
            if (e.target.tagName === 'VIDEO') {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>
