<?php
/**
 * VIS View - Admin Video List
 * 文件路径: app/vis/views/admin_list.php
 * 说明: 后台视频列表管理页面
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

// 获取筛选参数
$category = $_GET['category'] ?? '';
$platform = $_GET['platform'] ?? '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 构建筛选条件
$filters = [];
if (!empty($category)) {
    $filters['category'] = $category;
}
if (!empty($platform)) {
    $filters['platform'] = $platform;
}

// 获取视频列表和总数
$videos = vis_get_videos($pdo, $filters, $limit, $offset);
$totalVideos = vis_get_videos_count($pdo, $filters);
$totalPages = ceil($totalVideos / $limit);

// 获取分类列表
$categories = vis_get_categories($pdo);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>视频管理 - VIS后台</title>
    <link rel="stylesheet" href="/vis/ap/css/common.css">
    <link rel="stylesheet" href="/vis/ap/css/admin.css">
    <link rel="stylesheet" href="/vis/ap/css/modal.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- 侧边栏 -->
        <aside class="sidebar">
            <div class="logo-area">
                TOPTEA VIS<span class="logo-dot">.</span>
            </div>

            <div class="nav-scroll">
                <a href="/vis/ap/index.php?action=admin_list" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    视频库
                </a>
                <a href="/vis/ap/index.php?action=admin_upload" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    上传视频
                </a>

                <div class="nav-group-label">分类筛选</div>
                <?php foreach ($categories as $cat): ?>
                <a href="?action=admin_list&category=<?php echo urlencode($cat['category_code']); ?>" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </a>
                <?php endforeach; ?>

                <div class="nav-group-label">系统</div>
                <a href="/vis/ap/index.php?action=logout" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    退出登录
                </a>
            </div>
        </aside>

        <!-- 主区域 -->
        <main class="main-wrapper">
            <!-- 顶部栏 -->
            <header class="admin-header">
                <div class="page-title">全部视频</div>

                <div class="search-container">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" class="search-input" placeholder="搜索视频标题...">
                </div>

                <a href="/vis/ap/index.php?action=admin_upload" class="btn btn-primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    上传视频
                </a>

                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['user_display_name'] ?? $_SESSION['user_login'] ?? 'Admin'); ?></span>
                </div>
            </header>

            <!-- 筛选栏 -->
            <div class="filter-bar">
                <a href="?action=admin_list" class="filter-pill <?php echo empty($category) && empty($platform) ? 'active' : ''; ?>">全部</a>
                <a href="?action=admin_list&platform=wechat" class="filter-pill <?php echo $platform === 'wechat' ? 'active' : ''; ?>">微信</a>
                <a href="?action=admin_list&platform=xiaohongshu" class="filter-pill <?php echo $platform === 'xiaohongshu' ? 'active' : ''; ?>">小红书</a>
                <a href="?action=admin_list&platform=douyin" class="filter-pill <?php echo $platform === 'douyin' ? 'active' : ''; ?>">抖音</a>
                <a href="?action=admin_list&platform=other" class="filter-pill <?php echo $platform === 'other' ? 'active' : ''; ?>">其他</a>
            </div>

            <!-- 内容区域 -->
            <div class="content-area">
                <!-- 视频网格 -->
                <?php if (empty($videos)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📹</div>
                        <div class="empty-state-text">暂无视频</div>
                    </div>
                <?php else: ?>
                    <div class="grid-layout">
                        <?php foreach ($videos as $video): ?>
                            <div class="card video-card">
                                <div class="card-cover">
                                    <?php if (!empty($video['cover_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($video['cover_url']); ?>"
                                             class="thumb"
                                             alt="<?php echo htmlspecialchars($video['title']); ?>">
                                    <?php else: ?>
                                        <div class="thumb-placeholder">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                            </svg>
                                        </div>
                                    <?php endif; ?>

                                    <!-- 分类标签 -->
                                    <div class="badge-season">
                                        <?php echo htmlspecialchars($video['category']); ?>
                                    </div>

                                    <!-- 平台标签 -->
                                    <div class="badge-platform platform-<?php echo $video['platform']; ?>">
                                        <?php
                                        $platformNames = [
                                            'wechat' => '微信',
                                            'xiaohongshu' => '小红书',
                                            'douyin' => '抖音',
                                            'other' => '其他'
                                        ];
                                        echo $platformNames[$video['platform']] ?? $video['platform'];
                                        ?>
                                    </div>

                                    <!-- 播放遮罩 -->
                                    <div class="play-layer" onclick="playVideo(<?php echo $video['id']; ?>)">
                                        <div class="play-btn">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                                <polygon points="5 3 19 12 5 21 5 3"></polygon>
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <div class="card-info">
                                    <div class="card-title"><?php echo htmlspecialchars($video['title']); ?></div>
                                    <div class="card-meta">
                                        <span class="meta-item">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12 6 12 12 16 14"></polyline>
                                            </svg>
                                            <?php echo date('Y-m-d', strtotime($video['created_at'])); ?>
                                        </span>
                                        <span class="meta-item">
                                            <?php echo round($video['file_size'] / 1024 / 1024, 1); ?> MB
                                        </span>
                                    </div>
                                    <div class="card-actions">
                                        <button class="action-btn action-btn-edit" onclick="editVideo(<?php echo $video['id']; ?>)" title="编辑">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                            </svg>
                                        </button>
                                        <button class="action-btn action-btn-delete" onclick="deleteVideo(<?php echo $video['id']; ?>)" title="删除">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                        <!-- 分页 -->
                        <?php if ($totalPages > 1): ?>
                            <div class="admin-pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?action=admin_list&category=<?php echo urlencode($category); ?>&platform=<?php echo urlencode($platform); ?>&page=<?php echo $page - 1; ?>" class="page-btn">上一页</a>
                                <?php endif; ?>

                                <span class="page-info">第 <?php echo $page; ?> / <?php echo $totalPages; ?> 页（共 <?php echo $totalVideos; ?> 个视频）</span>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?action=admin_list&category=<?php echo urlencode($category); ?>&platform=<?php echo urlencode($platform); ?>&page=<?php echo $page + 1; ?>" class="page-btn">下一页</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script src="/vis/ap/js/modal.js"></script>
    <script>
        // 播放视频
        async function playVideo(id) {
            try {
                const response = await fetch(`/vis/index.php?action=play_sign&id=${id}`);
                const result = await response.json();

                if (!result.success) {
                    showAlert(result.message, '错误', 'error');
                    return;
                }

                // 显示播放器模态框
                showModal({
                    title: result.data.title,
                    content: `
                        <video class="video-player" controls autoplay oncontextmenu="return false;">
                            <source src="${result.data.url}" type="video/mp4">
                            您的浏览器不支持视频播放。
                        </video>
                    `,
                    width: '800px',
                    footer: '<div class="modal-footer"><button class="modal-btn modal-btn-secondary" data-action="close">关闭</button></div>'
                });
            } catch (error) {
                showAlert('获取播放链接失败', '错误', 'error');
            }
        }

        // 编辑视频
        function editVideo(id) {
            // TODO: 实现编辑功能
            showAlert('编辑功能开发中', '提示', 'info');
        }

        // 删除视频
        async function deleteVideo(id) {
            const confirmed = await showConfirm(
                '确定要删除这个视频吗？删除后无法恢复。',
                '确认删除',
                { type: 'warning', confirmText: '删除', confirmClass: 'modal-btn-danger' }
            );

            if (!confirmed) return;

            try {
                const response = await fetch('/vis/ap/index.php?action=video_delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id })
                });

                const result = await response.json();

                if (result.success) {
                    showAlert(result.message, '成功', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(result.message, '错误', 'error');
                }
            } catch (error) {
                showAlert('删除失败', '错误', 'error');
            }
        }
    </script>
</body>
</html>
