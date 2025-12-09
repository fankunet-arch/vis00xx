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
$productId = $_GET['product_id'] ?? '';
$seriesId = $_GET['series_id'] ?? '';
$seasonId = $_GET['season_id'] ?? '';
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
if (!empty($productId)) {
    $filters['product_id'] = $productId;
}
if (!empty($seriesId)) {
    $filters['series_id'] = $seriesId;
}
if (!empty($seasonId)) {
    $filters['season_id'] = $seasonId;
}

// 获取视频列表
$videos = vis_get_videos($pdo, $filters, $limit, $offset);
$totalVideos = vis_get_videos_count($pdo, $filters);
$totalPages = ceil($totalVideos / $limit);

// 获取内容类型列表
$categories = vis_get_categories($pdo);
// 获取产品、系列、季节列表
$products = vis_get_products($pdo);
$series = vis_get_series($pdo);
$seasons = vis_get_seasons($pdo);

// 创建映射表（便于显示）
$productMap = [];
foreach ($products as $prod) {
    $productMap[$prod['id']] = $prod['product_name'];
}

$seasonMap = [];
foreach ($seasons as $season) {
    $seasonMap[$season['id']] = $season['season_name'];
}

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
            <div class="gallery-title">视频灵感库</div>
            <p class="gallery-subtitle">探索精选视频内容，激发创意灵感</p>
            </header>

        <!-- 主内容 -->
        <main>
            <div class="container">
                <!-- 筛选栏 -->
                <div class="gallery-filters">
                    <?php
                        // 检查是否有活跃的筛选条件
                        $hasActiveFilters = !empty($category) || !empty($platform) || !empty($productId) || !empty($seriesId) || !empty($seasonId);
                        $filterBtnText = $hasActiveFilters ? '🔵 已启用筛选 (点击修改)' : '🔍 筛选视频 / 查找';
                    ?>

                    <button type="button" class="filter-toggle-btn <?php echo $hasActiveFilters ? 'has-filters' : ''; ?>" onclick="toggleFilters()">
                        <?php echo $filterBtnText; ?>
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:auto">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </button>

                    <form id="galleryFilterForm" method="GET" action="/vis/index.php">
                        <input type="hidden" name="action" value="gallery">

                        <div class="filter-row" style="margin-bottom: 12px;">
                            <div class="filter-group">
                                <label class="filter-label">📦 系列</label>
                                <select name="series_id" id="seriesFilter" class="filter-select">
                                    <option value="">全部系列</option>
                                    <?php foreach ($series as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"
                                            <?php echo $seriesId == $s['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s['series_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">🔍 产品</label>
                                <select name="product_id" id="productFilter" class="filter-select">
                                    <option value="">全部产品</option>
                                    <?php foreach ($products as $prod): ?>
                                        <option value="<?php echo $prod['id']; ?>"
                                            data-series-id="<?php echo $prod['series_id'] ?? ''; ?>"
                                            <?php echo $productId == $prod['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($prod['product_name']); ?>
                                            <?php if (!empty($prod['series_name'])): ?>
                                                (<?php echo htmlspecialchars($prod['series_name']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="filter-group">
                                <label class="filter-label">🌸 季节</label>
                                <select name="season_id" class="filter-select">
                                    <option value="">全部季节</option>
                                    <?php foreach ($seasons as $season): ?>
                                        <option value="<?php echo $season['id']; ?>"
                                            <?php echo $seasonId == $season['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($season['season_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <button type="submit" class="filter-btn">筛选</button>
                        </div>

                        <div class="filter-row filter-row-secondary">
                            <div class="filter-group">
                                <label class="filter-label">类型</label>
                                <select name="category" class="filter-select">
                                    <option value="">全部类型</option>
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

                            <button type="button" class="filter-btn filter-btn-reset" onclick="location.href='/vis/index.php?action=gallery'">
                                重置
                            </button>
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
                                        <!-- 系列 -->
                                        <?php
                                        if (!empty($video['series_tags'])) {
                                            $tags = explode(',', $video['series_tags']);
                                            foreach (array_slice($tags, 0, 2) as $tag) {
                                                echo '<span class="video-badge" style="background: #e0f7fa; color: #006064;">' . htmlspecialchars($tag) . '</span> ';
                                            }
                                        }
                                        ?>
                                        <?php if (!empty($video['product_id']) && isset($productMap[$video['product_id']])): ?>
                                            <span class="video-badge" style="background: #e8f5e9; color: #2e7d32;">
                                                🍵 <?php echo htmlspecialchars($productMap[$video['product_id']]); ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if (!empty($video['season_id']) && isset($seasonMap[$video['season_id']])): ?>
                                            <span class="video-badge" style="background: #fce4ec; color: #c2185b;">
                                                🌸 <?php echo htmlspecialchars($seasonMap[$video['season_id']]); ?>
                                            </span>
                                        <?php endif; ?>
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
                        <?php
                        // 构建分页URL参数
                        $paginationParams = [
                            'action' => 'gallery',
                            'category' => $category,
                            'platform' => $platform,
                            'product_id' => $productId,
                            'series_id' => $seriesId,
                            'season_id' => $seasonId,
                        ];
                        // 移除空参数
                        $paginationParams = array_filter($paginationParams, function($v) { return $v !== ''; });

                        function buildPaginationUrl($params, $page) {
                            $params['page'] = $page;
                            return '?' . http_build_query($params);
                        }
                        ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <button class="pagination-btn" onclick="location.href='<?php echo buildPaginationUrl($paginationParams, $page - 1); ?>'">
                                    上一页
                                </button>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <button class="pagination-btn <?php echo $i === $page ? 'active' : ''; ?>"
                                    onclick="location.href='<?php echo buildPaginationUrl($paginationParams, $i); ?>'">
                                    <?php echo $i; ?>
                                </button>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <button class="pagination-btn" onclick="location.href='<?php echo buildPaginationUrl($paginationParams, $page + 1); ?>'">
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
                // modal.close(false);  <--- 注释掉或删除这一行

                if (!result.success) {
                    // 如果出错，这里showAlert会覆盖加载框，也是安全的
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

        // 级联筛选逻辑（系列 → 产品）
        const seriesFilter = document.getElementById('seriesFilter');
        const productFilter = document.getElementById('productFilter');

        // 保存所有产品选项
        const allProductOptions = Array.from(productFilter.options).slice(1); // 排除"全部产品"选项
        const currentProductId = '<?php echo $productId; ?>';
        const currentSeriesId = '<?php echo $seriesId; ?>';

        // 系列选择变化时，过滤产品列表
        seriesFilter.addEventListener('change', function() {
            const selectedSeriesId = this.value;

            // 移除除"全部产品"外的所有选项
            while (productFilter.options.length > 1) {
                productFilter.remove(1);
            }

            // 重新添加符合条件的产品
            if (selectedSeriesId === '') {
                // 未选择系列，显示所有产品
                allProductOptions.forEach(option => {
                    productFilter.add(option.cloneNode(true));
                });
            } else {
                // 选择了系列，只显示该系列的产品
                allProductOptions.forEach(option => {
                    if (option.dataset.seriesId === selectedSeriesId) {
                        productFilter.add(option.cloneNode(true));
                    }
                });
            }

            // 重置产品选择
            productFilter.value = '';
        });

        // 产品选择变化时，自动选择对应系列（可选功能）
        productFilter.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.dataset.seriesId) {
                // 可选：自动同步系列选择
                // seriesFilter.value = selectedOption.dataset.seriesId;
            }
        });

        // 页面加载时，如果已选择系列，过滤产品列表
        if (currentSeriesId) {
            // 触发过滤
            const event = new Event('change');
            seriesFilter.dispatchEvent(event);

            // 恢复当前选中的产品
            if (currentProductId) {
                productFilter.value = currentProductId;
            }
        }

        // 切换筛选栏显示/隐藏
        function toggleFilters() {
            const form = document.getElementById('galleryFilterForm');
            const btn = document.querySelector('.filter-toggle-btn svg');

            form.classList.toggle('expanded');

            // 旋转箭头图标
            if (form.classList.contains('expanded')) {
                btn.style.transform = 'rotate(180deg)';
            } else {
                btn.style.transform = 'rotate(0deg)';
            }
        }
    </script>
</body>
</html>
