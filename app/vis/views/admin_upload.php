<?php
/**
 * VIS View - Admin Upload
 * 文件路径: app/vis/views/admin_upload.php
 * 说明: 后台视频上传页面
 */

// 防止直接访问
if (!defined('VIS_ENTRY')) {
    die('Access denied');
}

// 获取内容类型列表
$categories = vis_get_categories($pdo);
// 获取季节列表
$seasons = vis_get_seasons($pdo);
// 初始化时不再加载所有产品和系列，改为异步搜索
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>上传视频 - VIS后台</title>
    <link rel="stylesheet" href="/vis/ap/css/common.css">
    <link rel="stylesheet" href="/vis/ap/css/admin.css">
    <link rel="stylesheet" href="/vis/ap/css/modal.css">
    <style>
        /* Series Tags Custom Styles */
        .tag-input-wrapper {
            min-height: 34px;
            height: auto;
            padding: 2px 4px;
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            align-items: center;
        }

        .tag-input-wrapper:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 1px rgba(255, 107, 74, 0.2);
        }

        #seriesTagInput {
            flex-grow: 1;
            min-width: 120px;
            border: none;
            outline: none;
            background: transparent;
            color: var(--text-main);
            font-size: 13px;
            padding: 2px 4px;
            height: 28px;
        }

        .tag-item {
            background-color: var(--bg-hover);
            color: var(--text-main);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 4px;
            border: 1px solid var(--border-color);
        }

        .tag-remove {
            cursor: pointer;
            color: var(--text-muted);
            font-weight: bold;
            font-size: 14px;
            line-height: 1;
            padding: 0 2px;
        }

        .tag-remove:hover {
            color: var(--text-main);
        }

        /* Dropdown Menu Styles */
        .series-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            display: none;
            margin-top: 4px;
        }

        .series-dropdown.active {
            display: block;
        }

        .series-dropdown-item {
            padding: 8px 12px;
            cursor: pointer;
            color: var(--text-main);
            font-size: 13px;
            transition: background-color 0.15s;
        }

        .series-dropdown-item:hover,
        .series-dropdown-item.active {
            background-color: var(--bg-hover);
        }

        .series-dropdown-item .highlight {
            color: var(--primary);
            font-weight: 600;
            background-color: rgba(255, 107, 74, 0.1);
            padding: 0 2px;
            border-radius: 2px;
        }

        .series-dropdown-empty {
            padding: 12px;
            text-align: center;
            color: var(--text-muted);
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- 侧边栏 -->
        <aside class="sidebar">
            <div class="logo-area">
                TOPTEA VIS<span class="logo-dot">.</span>
            </div>

            <div class="nav-scroll">
                <a href="/vis/ap/index.php?action=admin_list" class="nav-item">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    视频库
                </a>
                <a href="/vis/ap/index.php?action=admin_upload" class="nav-item active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                    上传视频
                </a>

                <div class="nav-group-label">分类筛选</div>
                <?php foreach ($categories as $cat): ?>
                <a href="/vis/ap/index.php?action=admin_list&category=<?php echo urlencode($cat['category_code']); ?>" class="nav-item">
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

        <!-- 移动端遮罩层 -->
        <div class="mobile-overlay" id="mobileOverlay"></div>

        <!-- 主区域 -->
        <main class="main-wrapper">
            <!-- 顶部栏 -->
            <header class="admin-header">
                <!-- 汉堡菜单按钮（仅移动端显示） -->
                <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="菜单">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                <div class="page-title">上传视频</div>

                <div class="admin-user">
                    <span><?php echo htmlspecialchars($_SESSION['user_display_name'] ?? $_SESSION['user_login'] ?? 'Admin'); ?></span>
                </div>
            </header>

            <!-- 内容区域 -->
            <div class="content-area">
                <div class="card upload-form">
                    <h2 class="card-header">上传新视频</h2>

                    <form id="uploadForm" enctype="multipart/form-data">
                        <!-- 文件上传区 -->
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-icon">📹</div>
                            <div class="upload-text">点击选择或拖拽视频文件</div>
                            <div class="upload-hint">支持 MP4、MOV 格式，最大 100MB</div>
                            <input type="file" id="fileInput" name="video" accept="video/mp4,video/quicktime" class="file-input">
                        </div>

                        <!-- 文件信息显示 -->
                        <div id="fileSelected" class="file-selected" style="display:none;">
                            <div class="file-info">
                                <span class="file-name" id="fileName"></span>
                                <span class="file-size" id="fileSize"></span>
                                <button type="button" class="file-remove" onclick="removeFile()">×</button>
                            </div>
                        </div>

                        <!-- 视频信息 -->
                        <div class="form-group">
                            <label class="form-label">视频标题 *</label>
                            <div style="position: relative;">
                                <input type="text" name="title" id="title" class="form-control" required
                                       placeholder="请输入视频标题" autocomplete="off" list="titleList">
                                <datalist id="titleList"></datalist>
                            </div>
                        </div>

                        <!-- 产品信息（核心） -->
                        <div class="form-group">
                            <label class="form-label">产品名称</label>
                            <div style="position: relative;">
                                <input type="text" name="product_name" id="productName" class="form-control"
                                       placeholder="输入产品名称（如：珍珠抹茶）或从下拉选择"
                                       list="productList" autocomplete="off">
                                <datalist id="productList"></datalist>
                                <input type="hidden" name="product_id" id="productId">
                                <input type="hidden" name="series_id" id="seriesIdHidden">
                            </div>
                            <small style="color: #666; font-size: 12px;">💡 输入新产品名称自动创建，或从列表选择（显示系列）</small>
                        </div>

                        <!-- 系列输入（仅在创建新产品时显示） -->
                        <div class="form-group" id="seriesInputGroup" style="display: none;">
                            <label class="form-label">所属系列 *</label>
                            <div style="position: relative;">
                                <input type="text" name="series_name" id="seriesName" class="form-control"
                                       placeholder="输入系列名称（如：抹茶系列）或从下拉选择"
                                       list="seriesList" autocomplete="off">
                                <datalist id="seriesList"></datalist>
                                <input type="hidden" name="series_id_for_new_product" id="seriesIdForNewProduct">
                            </div>
                            <small style="color: #666; font-size: 12px;">💡 输入新系列名称自动创建，或从列表选择已有系列</small>
                        </div>

                        <!-- 显示已选产品的系列信息 -->
                        <div class="form-group" id="seriesDisplayGroup" style="display: none;">
                            <label class="form-label">所属系列</label>
                            <div style="padding: 8px 12px; background: #f5f5f5; border-radius: 4px; color: #666;">
                                📦 <span id="seriesDisplayName">-</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">适用季节</label>
                            <select name="season_id" id="seasonId" class="form-select">
                                <option value="">不限季节</option>
                                <?php foreach ($seasons as $season): ?>
                                    <option value="<?php echo $season['id']; ?>"
                                            <?php echo ($season['season_code'] === 'all_seasons') ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($season['season_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="color: #666; font-size: 12px;">默认"四季"，可选择其他季节或留空表示不限季节</small>
                        </div>

                        <div class="form-group">
                            <label class="form-label">内容类型 *</label>
                            <select name="category" id="category" class="form-select" required>
                                <option value="">请选择类型</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category_code']); ?>"
                                            <?php echo ($cat['category_code'] === 'product') ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 系列标签输入 -->
                        <div class="form-group">
                            <label class="form-label">系列标签 <small style="font-weight: normal; color: #666;">(可多选，输入新系列回车创建)</small></label>
                            <div style="position: relative;">
                                <div class="form-control tag-input-wrapper" id="tagInputWrapper" onclick="document.getElementById('seriesTagInput').focus()">
                                    <!-- 动态生成的标签和输入框将直接在这里作为兄弟元素 -->
                                    <input type="text" id="seriesTagInput" placeholder="输入系列名称..." autocomplete="off">
                                </div>
                                <div id="seriesTagDropdown" class="series-dropdown"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">来源平台</label>
                            <select name="platform" id="platform" class="form-select">
                                <option value="other">其他</option>
                                <option value="wechat">微信</option>
                                <option value="xiaohongshu">小红书</option>
                                <option value="douyin">抖音</option>
                            </select>
                        </div>

                        <!-- 上传按钮 -->
                        <div style="display: flex; gap: 12px;">
                            <button type="submit" class="btn btn-primary" id="submitBtn">上传视频</button>
                            <a href="/vis/ap/index.php?action=admin_list" class="btn btn-outline">取消</a>
                        </div>
                    </form>

                    <!-- 上传进度 -->
                    <div id="uploadProgress" class="upload-progress" style="display:none;">
                        <div class="progress-bar">
                            <div class="progress-bar-fill" id="progressFill" style="width: 0%;"></div>
                        </div>
                        <div class="progress-text" id="progressText">上传中... 0%</div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="/vis/ap/js/modal.js"></script>
    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileSelected = document.getElementById('fileSelected');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const uploadForm = document.getElementById('uploadForm');
        const submitBtn = document.getElementById('submitBtn');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');

        let selectedFile = null;
        let videoDuration = 0;        // 视频时长（秒）
        let videoCoverBase64 = null;  // 视频封面（Base64）

        // 点击上传区选择文件
        uploadArea.addEventListener('click', () => fileInput.click());

        // 文件选择
        fileInput.addEventListener('change', handleFileSelect);

        // 拖拽上传
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragging');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragging');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragging');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFileSelect();
            }
        });

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (!file) return;

            // 验证文件类型
            if (!file.type.match('video/mp4') && !file.type.match('video/quicktime')) {
                showAlert('仅支持 MP4 和 MOV 格式的视频文件', '错误', 'error');
                fileInput.value = '';
                return;
            }

            // 验证文件大小（100MB）
            if (file.size > 100 * 1024 * 1024) {
                showAlert('文件大小超过限制（最大 100MB）', '错误', 'error');
                fileInput.value = '';
                return;
            }

            selectedFile = file;
            fileName.textContent = file.name;
            fileSize.textContent = (file.size / 1024 / 1024).toFixed(2) + ' MB';
            fileSelected.style.display = 'block';
            uploadArea.style.display = 'none';

            // [新增] 1. 立即禁用上传按钮，防止在截图生成前提交
            submitBtn.disabled = true;
            const originalBtnText = submitBtn.textContent;
            submitBtn.textContent = '正在生成封面...';
            submitBtn.dataset.originalText = originalBtnText; // 暂存原始文本

            // 提取视频元数据（时长和封面图）
            extractVideoMetadata(file);
        }

/**
         * 提取视频元数据（时长和首帧封面）
         * 增加超时保险，防止手机端卡死
         */
        function extractVideoMetadata(file) {
            const video = document.createElement('video');
            video.preload = 'auto';
            video.muted = true;
            video.playsInline = true; // 关键：iOS 必须属性

            // 创建临时 URL
            const videoURL = URL.createObjectURL(file);
            video.src = videoURL;

            // [新增] 1. 定义清理和恢复函数
            // 无论成功、失败还是超时，最后都要执行这个，确保按钮恢复
            const finishProcess = (isSuccess) => {
                // 清除超时计时器
                if (video.dataset.timeoutId) {
                    clearTimeout(parseInt(video.dataset.timeoutId));
                }

                // 释放资源
                if (video.src) {
                    URL.revokeObjectURL(video.src);
                    video.removeAttribute('src');
                }

                // 恢复按钮状态
                if (submitBtn.disabled) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = submitBtn.dataset.originalText || '上传视频';
                }
            };

            // [新增] 2. 设置 3 秒超时保险
            // 如果手机浏览器 3 秒内没搞定封面，就放弃生成，让用户能继续上传
            video.dataset.timeoutId = setTimeout(() => {
                console.warn('生成封面超时，跳过封面生成步骤');
                finishProcess(false);
            }, 3000);

            video.onloadedmetadata = function() {
                videoDuration = Math.round(video.duration);
                console.log(`视频时长: ${videoDuration} 秒`);
            };

            video.onloadeddata = function() {
                // 尝试跳转到 10% 或 1秒处
                const seekTime = Math.min(1, video.duration * 0.1);
                video.currentTime = seekTime;
            };

            video.onseeked = function() {
                try {
                    // 等待渲染，手机上可能需要更宽松的等待
                    requestAnimationFrame(() => {
                        setTimeout(() => { // 加一个小延时兼容低端机
                            try {
                                const canvas = document.createElement('canvas');
                                canvas.width = video.videoWidth;
                                canvas.height = video.videoHeight;
                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                                videoCoverBase64 = canvas.toDataURL('image/jpeg', 0.8);
                                console.log('封面图生成成功');
                            } catch (err) {
                                console.error('绘图失败:', err);
                            }
                            // 成功完成，触发清理
                            finishProcess(true);
                        }, 50);
                    });
                } catch (error) {
                    console.error('截图过程异常:', error);
                    finishProcess(false);
                }
            };

            video.onerror = function() {
                console.error('视频加载出错');
                finishProcess(false);
            };

            // [新增] 3. 显式调用 load()，刺激部分手机浏览器开始加载
            video.load();
        }

        function removeFile() {
            selectedFile = null;
            videoDuration = 0;
            videoCoverBase64 = null;
            fileInput.value = '';
            fileSelected.style.display = 'none';
            uploadArea.style.display = 'block';

            // [新增] 重置按钮状态
            submitBtn.disabled = false;
            submitBtn.textContent = '上传视频';
        }

        // 产品名称输入框处理
        const productName = document.getElementById('productName');
        const productId = document.getElementById('productId');
        const seriesIdHidden = document.getElementById('seriesIdHidden');
        const seriesInputGroup = document.getElementById('seriesInputGroup');
        const seriesDisplayGroup = document.getElementById('seriesDisplayGroup');
        const seriesDisplayName = document.getElementById('seriesDisplayName');
        const seriesName = document.getElementById('seriesName');
        const seriesIdForNewProduct = document.getElementById('seriesIdForNewProduct');
        const titleInput = document.getElementById('title');

        // 数据列表引用
        const productDataList = document.getElementById('productList');
        const seriesDataList = document.getElementById('seriesList');
        const titleDataList = document.getElementById('titleList');

        // 缓存搜索结果，用于ID匹配
        let productSearchResults = [];
        let seriesSearchResults = [];

        // ---------------------------------------------------------
        // Tagging Logic (Series)
        // ---------------------------------------------------------

        const seriesTagInput = document.getElementById('seriesTagInput');
        const seriesTagDropdown = document.getElementById('seriesTagDropdown');
        const tagInputWrapper = document.getElementById('tagInputWrapper');
        let collectedTags = []; // Store current tags
        let currentDropdownIndex = -1; // For keyboard navigation

        // Render tag list (inline with input)
        function renderTags() {
            // Remove existing tags
            const existingTags = tagInputWrapper.querySelectorAll('.tag-item');
            existingTags.forEach(tag => tag.remove());

            // Create and insert tags before input
            collectedTags.forEach((tag, index) => {
                const tagEl = document.createElement('div');
                tagEl.className = 'tag-item';
                tagEl.innerHTML = `${tag} <span class="tag-remove" onclick="removeTag(${index})">&times;</span>`;
                tagInputWrapper.insertBefore(tagEl, seriesTagInput);
            });
        }

        // Add tag
        function addTag(tagName) {
            tagName = tagName.trim();
            if (tagName && !collectedTags.includes(tagName)) {
                collectedTags.push(tagName);
                renderTags();
                seriesTagInput.value = '';
                seriesTagDropdown.classList.remove('active');
                seriesTagInput.focus();
            }
        }

        // Remove tag global function
        window.removeTag = function(index) {
            collectedTags.splice(index, 1);
            renderTags();
        }

        // Highlight matching text
        function highlightMatch(text, keyword) {
            if (!keyword) return text;
            const regex = new RegExp(`(${keyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return text.replace(regex, '<span class="highlight">$1</span>');
        }

        // Update dropdown active item
        function updateDropdownActiveItem() {
            const items = seriesTagDropdown.querySelectorAll('.series-dropdown-item');
            items.forEach((item, index) => {
                if (index === currentDropdownIndex) {
                    item.classList.add('active');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('active');
                }
            });
        }

        // Input event for autocomplete
        seriesTagInput.addEventListener('input', debounce(async function() {
            const keyword = this.value.trim();
            currentDropdownIndex = -1; // Reset selection

            if (keyword.length < 1) {
                seriesTagDropdown.innerHTML = '';
                seriesTagDropdown.classList.remove('active');
                return;
            }

            try {
                const response = await fetch(`/vis/ap/index.php?action=search_series&keyword=${encodeURIComponent(keyword)}`);
                const result = await response.json();
                if (result.success && result.data && result.data.series && result.data.series.length > 0) {
                    seriesTagDropdown.innerHTML = result.data.series.map(s => {
                        const highlightedText = highlightMatch(s, keyword);
                        return `<div class="series-dropdown-item" onclick="addTag('${s.replace(/'/g, "\\'")}')">${highlightedText}</div>`;
                    }).join('');
                    seriesTagDropdown.classList.add('active');
                } else {
                    seriesTagDropdown.innerHTML = '<div class="series-dropdown-empty">未找到匹配的系列</div>';
                    seriesTagDropdown.classList.add('active');
                }
            } catch (e) {
                console.error('Series search error:', e);
                seriesTagDropdown.classList.remove('active');
            }
        }, 300));

        // Keydown event for Enter/Comma and Arrow Keys
        seriesTagInput.addEventListener('keydown', function(e) {
            const items = seriesTagDropdown.querySelectorAll('.series-dropdown-item:not(.series-dropdown-empty)');
            const isDropdownOpen = seriesTagDropdown.classList.contains('active') && items.length > 0;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (isDropdownOpen) {
                    currentDropdownIndex = Math.min(currentDropdownIndex + 1, items.length - 1);
                    updateDropdownActiveItem();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (isDropdownOpen) {
                    currentDropdownIndex = Math.max(currentDropdownIndex - 1, -1);
                    updateDropdownActiveItem();
                }
            } else if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                if (isDropdownOpen && currentDropdownIndex >= 0 && currentDropdownIndex < items.length) {
                    // Select the highlighted item
                    const selectedText = items[currentDropdownIndex].textContent.trim();
                    addTag(selectedText);
                } else {
                    // Add what the user typed
                    addTag(this.value);
                }
            } else if (e.key === 'Escape') {
                seriesTagDropdown.classList.remove('active');
                currentDropdownIndex = -1;
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!seriesTagInput.contains(e.target) && !seriesTagDropdown.contains(e.target)) {
                seriesTagDropdown.classList.remove('active');
            }
        });


        // ---------------------------------------------------------
        // 1. 模糊搜索逻辑 (Debounce + Ajax)
        // ---------------------------------------------------------

        function debounce(func, wait) {
            let timeout;
            return function(...args) {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        }

        // 视频标题搜索
        titleInput.addEventListener('input', debounce(async function() {
            const keyword = this.value.trim();

            // Sync Logic Trigger: Also check sync whenever input changes
            handleTitleSync(keyword);

            if (keyword.length < 1) {
                titleDataList.innerHTML = '';
                return;
            }

            try {
                const response = await fetch(`/vis/ap/index.php?action=search_titles&keyword=${encodeURIComponent(keyword)}`);
                const result = await response.json();

                if (result.success && result.data && result.data.titles) {
                    // Distinct checked by backend
                    titleDataList.innerHTML = result.data.titles.map(t => `<option value="${t}">`).join('');
                }
            } catch (e) {
                console.error('Search error:', e);
            }
        }, 300));

        // 产品搜索
        productName.addEventListener('input', debounce(async function() {
            const keyword = this.value.trim();

            // 重置ID，直到匹配
            productId.value = '';
            seriesIdHidden.value = '';

            // 如果为空，重置UI
            if (keyword.length < 1) {
                productDataList.innerHTML = '';
                seriesDisplayGroup.style.display = 'none';
                seriesInputGroup.style.display = 'none';
                return;
            }

            // UI逻辑：假设它是新产品，直到被证明是已存在的
            seriesInputGroup.style.display = 'block';
            seriesDisplayGroup.style.display = 'none';

            try {
                const response = await fetch(`/vis/ap/index.php?action=product_quick_create&action=search&keyword=${encodeURIComponent(keyword)}`);
                const result = await response.json();

                if (result.success && result.data && result.data.products) {
                    productSearchResults = result.data.products;
                    productDataList.innerHTML = productSearchResults.map(p => {
                        const label = p.series_name ? `${p.product_name} (${p.series_name})` : p.product_name;
                        return `<option value="${p.product_name}">${label}</option>`; // option value 只显示名称
                    }).join('');

                    // 检查是否完全匹配当前输入
                    const matched = productSearchResults.find(p => p.product_name === keyword);
                    if (matched) {
                         productId.value = matched.id;
                         seriesIdHidden.value = matched.series_id || '';

                         // 显示已关联系列
                         if (matched.series_name) {
                             seriesDisplayGroup.style.display = 'block';
                             seriesDisplayName.textContent = matched.series_name;
                             seriesInputGroup.style.display = 'none';
                         }
                    }
                }
            } catch (e) {
                console.error('Product Search error:', e);
            }
        }, 300));

        // 系列搜索
        seriesName.addEventListener('input', debounce(async function() {
            const keyword = this.value.trim();

            seriesIdForNewProduct.value = '';
            // update shared hidden too if needed, but logic uses `seriesIdForNewProduct` or `seriesIdHidden` depending on context.
            // Actually, `seriesIdHidden` is for product's series. If product is new, we look at `seriesName`.

            if (keyword.length < 1) {
                seriesDataList.innerHTML = '';
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'search');
                formData.append('keyword', keyword);

                const response = await fetch('/vis/ap/index.php?action=series_quick_create', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success && result.data && result.data.series) {
                    seriesSearchResults = result.data.series;
                    seriesDataList.innerHTML = seriesSearchResults.map(s =>
                        `<option value="${s.series_name}">`
                    ).join('');

                    const matched = seriesSearchResults.find(s => s.series_name === keyword);
                    if (matched) {
                         seriesIdForNewProduct.value = matched.id;
                         seriesIdHidden.value = matched.id; // Also update this just in case
                    }
                }
            } catch (e) {
                console.error('Series Search error:', e);
            }
        }, 300));

        // ---------------------------------------------------------
        // 2. 智能字段同步逻辑 (Sync Logic)
        // ---------------------------------------------------------

        let lastSyncedTitle = '';

        // 初始化：如果页面加载时已有值（例如编辑模式，虽此处是上传页），可以设初始值
        // 这里默认是空

        function handleTitleSync(newTitle) {
            const currentProduct = productName.value;

            // 核心逻辑:
            // 如果 Product Name 为空，或者 Product Name 等于我们上次同步进去的值（说明尚未手动脱钩）
            // 则进行同步。

            // 初始状态：A="", B="" -> last="" -> match.
            // 输入A="T" -> B="T", last="T".
            // 修改A="Te" -> match (B is "T" == last "T") -> B="Te", last="Te".
            // 修改B="Test" -> A="Te", B="Test", last="Te".
            // 修改A="Tes" -> match? (B is "Test" != last "Tes"?) -> No sync.

            // 注意：input事件触发时，productName.value 是当前值。
            // 我们需要比较的是 productName.value 是否等于 lastSyncedTitle。

            if (currentProduct === lastSyncedTitle) {
                productName.value = newTitle;
                lastSyncedTitle = newTitle;

                // 触发产品名的 input 事件以激活搜索和ID清除逻辑
                productName.dispatchEvent(new Event('input'));
            } else {
                // 如果不匹配，说明用户手动改过B，或者B本来就有值。
                // 此时只更新 lastSyncedTitle 为当前A，不再去碰B。
                // 等等，如果现在A变化了，lastSyncedTitle 应该更新为A的新值，以便下次比较？
                // 不，Sync Logic要求: "修改 A 为 XYZ, B 保持 GGG 不变 (因修改前两者不一致)"

                // 但如果我把A改回去了呢？
                // 场景：A="ABC", B="GGG". last="ABC".
                // 改A -> "ABCD". B="GGG". last="ABCD".

                lastSyncedTitle = newTitle;
            }
        }


        // 表单提交
        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!selectedFile) {
                showAlert('请选择要上传的视频文件', '提示', 'warning');
                return;
            }

            const title = document.getElementById('title').value.trim();

            const category = document.getElementById('category').value;
            const platform = document.getElementById('platform').value;
            const productNameValue = productName.value.trim();
            const productIdValue = productId.value;
            // seriesIdHidden gets populated if we select existing product
            // or if we select existing series for new product (via seriesName input logic above)
            let seriesIdValue = seriesIdHidden.value;
            const seasonId = document.getElementById('seasonId').value;

            if (!title) {
                showAlert('请输入视频标题', '提示', 'warning');
                return;
            }

            if (!category) {
                showAlert('请选择内容类型', '提示', 'warning');
                return;
            }

            // 验证新产品的系列
            const seriesNameValue = seriesName ? seriesName.value.trim() : '';

            // 如果是新产品 (有名字无ID)，必须有系列 (有名字或有ID)
            if (productNameValue && !productIdValue) {
                // 检查系列: seriesIdValue (hidden) 或 seriesNameValue (text)
                // 注意: seriesName input listener 会更新 seriesIdHidden 如果匹配已有系列
                if (!seriesIdValue && !seriesNameValue) {
                     showAlert('创建新产品时必须指定所属系列', '提示', 'warning');
                     return;
                }
            }

            const formData = new FormData();
            formData.append('video', selectedFile);
            formData.append('title', title);
            formData.append('category', category);

            // Append Series Tags
            collectedTags.forEach(tag => {
                formData.append('series_names[]', tag);
            });

            formData.append('platform', platform);

            if (seasonId) {
                formData.append('season_id', seasonId);
            }

            // 发送产品信息
            if (productIdValue) {
                formData.append('product_id', productIdValue);
            } else if (productNameValue) {
                formData.append('product_name', productNameValue);
            }

            // 发送系列信息
            // 逻辑：
            // 1. 如果选了现有产品，series_id 可能已经有了 (from product)。
            // 2. 如果是新产品，可能选了现有系列 (seriesIdValue)，也可能输入新系列 (seriesNameValue)。
            if (seriesIdValue) {
                formData.append('series_id', seriesIdValue);
            }
            // 即使有ID，如果用户意图是新建/指定名称，也可以传名称，后端会校验
            // 但如果已经匹配了ID，传ID更稳。
            // 如果没有ID，传名称。
            if (!seriesIdValue && seriesNameValue) {
                formData.append('series_name', seriesNameValue);
            }

            // 添加视频元数据
            if (videoDuration > 0) {
                formData.append('duration', videoDuration);
            }

            if (videoCoverBase64) {
                formData.append('cover_base64', videoCoverBase64);
            }

            // 显示进度条
            uploadProgress.style.display = 'block';
            submitBtn.disabled = true;
            submitBtn.textContent = '上传中...';

            try {
                const xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        progressFill.style.width = percent + '%';
                        progressText.textContent = `上传中... ${percent}%`;
                    }
                });

                xhr.addEventListener('load', async () => {
                    if (xhr.status === 200) {
                        try {
                            const result = JSON.parse(xhr.responseText);
                            if (result.success) {
                                await showAlert('视频上传成功！', '成功', 'success');
                                window.location.href = '/vis/ap/index.php?action=admin_list';
                            } else {
                                showAlert(result.message || '上传失败', '错误', 'error');
                                resetUploadForm();
                            }
                        } catch (e) {
                             showAlert('服务器返回格式错误', '错误', 'error');
                             resetUploadForm();
                        }
                    } else {
                        showAlert('上传失败，服务器错误', '错误', 'error');
                        resetUploadForm();
                    }
                });

                xhr.addEventListener('error', () => {
                    showAlert('上传失败，网络错误', '错误', 'error');
                    resetUploadForm();
                });

                xhr.open('POST', '/vis/ap/index.php?action=video_upload');
                xhr.send(formData);

            } catch (error) {
                showAlert('上传失败：' + error.message, '错误', 'error');
                resetUploadForm();
            }
        });

        function resetUploadForm() {
            uploadProgress.style.display = 'none';
            progressFill.style.width = '0%';
            progressText.textContent = '上传中... 0%';
            submitBtn.disabled = false;
            submitBtn.textContent = '上传视频';
        }

        // Removed quick_create functions as they are now handled by video_upload transaction
    </script>
    <script src="/vis/ap/js/mobile-menu.js"></script>
</body>
</html>
