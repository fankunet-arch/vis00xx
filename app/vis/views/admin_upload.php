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
// 获取系列列表
$series = vis_get_series($pdo);
// 获取季节列表
$seasons = vis_get_seasons($pdo);
// 获取产品列表
$products = vis_get_products($pdo);
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
                            <input type="text" name="title" id="title" class="form-control" required placeholder="请输入视频标题">
                        </div>

                        <!-- 产品信息（核心） -->
                        <div class="form-group">
                            <label class="form-label">产品名称</label>
                            <div style="position: relative;">
                                <input type="text" name="product_name" id="productName" class="form-control"
                                       placeholder="输入产品名称（如：珍珠抹茶）或从下拉选择"
                                       list="productList" autocomplete="off">
                                <datalist id="productList">
                                    <?php foreach ($products as $prod): ?>
                                        <option value="<?php echo htmlspecialchars($prod['product_name']); ?>"
                                                data-id="<?php echo $prod['id']; ?>"
                                                data-series-id="<?php echo $prod['series_id'] ?? ''; ?>"
                                                data-series-name="<?php echo htmlspecialchars($prod['series_name'] ?? ''); ?>">
                                            <?php if (!empty($prod['series_name'])): ?>
                                                <?php echo htmlspecialchars($prod['product_name'] . ' (' . $prod['series_name'] . ')'); ?>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($prod['product_name']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </datalist>
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
                                <datalist id="seriesList">
                                    <?php foreach ($series as $s): ?>
                                        <option value="<?php echo htmlspecialchars($s['series_name']); ?>"
                                                data-id="<?php echo $s['id']; ?>">
                                        </option>
                                    <?php endforeach; ?>
                                </datalist>
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

            // 提取视频元数据（时长和封面图）
            extractVideoMetadata(file);
        }

        /**
         * 提取视频元数据（时长和首帧封面）
         * @param {File} file - 视频文件
         */
        function extractVideoMetadata(file) {
            const video = document.createElement('video');
            video.preload = 'auto'; // 改为 auto，加载更多数据
            video.muted = true; // 静音，避免播放声音
            video.playsInline = true; // 内联播放

            // 创建临时 URL
            const videoURL = URL.createObjectURL(file);
            video.src = videoURL;

            video.onloadedmetadata = function() {
                // 获取视频时长（秒，四舍五入）
                videoDuration = Math.round(video.duration);
                console.log(`视频时长: ${videoDuration} 秒`);
            };

            // 等待视频可以播放后再截图
            video.onloadeddata = function() {
                // 先播放一小段，确保视频帧加载
                video.currentTime = Math.min(1, video.duration * 0.1); // 10%位置或1秒
            };

            video.onseeked = function() {
                try {
                    // 等待下一帧渲染
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            // 使用 Canvas 截取视频帧
                            const canvas = document.createElement('canvas');
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;

                            const ctx = canvas.getContext('2d');
                            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                            // 转换为 Base64（JPEG 格式，质量 0.8）
                            videoCoverBase64 = canvas.toDataURL('image/jpeg', 0.8);

                            console.log('封面图已生成，尺寸:', canvas.width, 'x', canvas.height);

                            // 释放临时 URL
                            URL.revokeObjectURL(videoURL);
                        });
                    });
                } catch (error) {
                    console.error('封面图生成失败:', error);
                    // 不中断上传流程，继续不带封面上传
                    URL.revokeObjectURL(videoURL);
                }
            };

            video.onerror = function() {
                console.error('视频元数据加载失败');
                URL.revokeObjectURL(videoURL);
            };
        }

        function removeFile() {
            selectedFile = null;
            videoDuration = 0;
            videoCoverBase64 = null;
            fileInput.value = '';
            fileSelected.style.display = 'none';
            uploadArea.style.display = 'block';
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

        const productList = <?php echo json_encode(array_map(function($p) {
            return [
                'id' => $p['id'],
                'name' => $p['product_name'],
                'series_id' => $p['series_id'] ?? null,
                'series_name' => $p['series_name'] ?? ''
            ];
        }, $products)); ?>;

        const seriesList = <?php echo json_encode(array_map(function($s) {
            return [
                'id' => $s['id'],
                'name' => $s['series_name']
            ];
        }, $series)); ?>;

        productName.addEventListener('input', function() {
            const inputValue = this.value.trim();

            // 检查是否匹配已有产品
            const matchedProduct = productList.find(p => p.name === inputValue);

            if (matchedProduct) {
                // 选择了已有产品
                productId.value = matchedProduct.id;
                seriesIdHidden.value = matchedProduct.series_id || '';

                // 显示系列信息
                seriesInputGroup.style.display = 'none';
                if (matchedProduct.series_name) {
                    seriesDisplayGroup.style.display = 'block';
                    seriesDisplayName.textContent = matchedProduct.series_name;
                } else {
                    seriesDisplayGroup.style.display = 'none';
                }
            } else if (inputValue) {
                // 输入了新产品名称
                productId.value = '';
                seriesIdHidden.value = '';
                seriesDisplayGroup.style.display = 'none';
                seriesInputGroup.style.display = 'block';
            } else {
                // 清空
                productId.value = '';
                seriesIdHidden.value = '';
                seriesInputGroup.style.display = 'none';
                seriesDisplayGroup.style.display = 'none';
            }
        });

        // 系列名称输入框处理
        seriesName.addEventListener('input', function() {
            const inputValue = this.value.trim();

            // 检查是否匹配已有系列
            const matchedSeries = seriesList.find(s => s.name === inputValue);

            if (matchedSeries) {
                // 选择了已有系列
                seriesIdForNewProduct.value = matchedSeries.id;
                seriesIdHidden.value = matchedSeries.id;
            } else {
                // 输入了新系列名称，清空ID（上传时会自动创建）
                seriesIdForNewProduct.value = '';
                seriesIdHidden.value = '';
            }
        });

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
            const seriesIdValue = seriesIdHidden.value;
            const seasonId = document.getElementById('seasonId').value;

            if (!title) {
                showAlert('请输入视频标题', '提示', 'warning');
                return;
            }

            if (!category) {
                showAlert('请选择内容类型', '提示', 'warning');
                return;
            }

            // 如果输入了产品名称但没有匹配到已有产品，则需要先创建产品
            let finalProductId = productIdValue;
            let finalSeriesId = seriesIdValue;
            const seriesNameValue = seriesName ? seriesName.value.trim() : '';

            if (productNameValue && !productIdValue) {
                // 新产品：必须输入系列
                if (!seriesNameValue) {
                    showAlert('创建新产品时必须指定所属系列', '提示', 'warning');
                    return;
                }

                // 如果输入了新系列名称（没有匹配到已有系列），先创建系列
                if (seriesNameValue && !seriesIdValue) {
                    try {
                        const createSeriesResult = await createSeries(seriesNameValue);
                        if (createSeriesResult.success) {
                            finalSeriesId = createSeriesResult.id;
                            console.log('新系列已创建:', seriesNameValue, 'ID:', finalSeriesId);
                        } else {
                            showAlert('创建系列失败: ' + createSeriesResult.message, '错误', 'error');
                            return;
                        }
                    } catch (error) {
                        showAlert('创建系列时出错: ' + error.message, '错误', 'error');
                        return;
                    }
                }

                // 快速创建新产品
                try {
                    const createResult = await createProduct(productNameValue, finalSeriesId);
                    if (createResult.success) {
                        finalProductId = createResult.id;
                        console.log('新产品已创建:', productNameValue, 'ID:', finalProductId, 'Series ID:', finalSeriesId);
                    } else {
                        showAlert('创建产品失败: ' + createResult.message, '错误', 'error');
                        return;
                    }
                } catch (error) {
                    showAlert('创建产品时出错: ' + error.message, '错误', 'error');
                    return;
                }
            }

            const formData = new FormData();
            formData.append('video', selectedFile);
            formData.append('title', title);
            formData.append('category', category);
            formData.append('platform', platform);

            // 季节是可选的（允许空值）
            if (seasonId) {
                formData.append('season_id', seasonId);
            }

            // 产品ID（可选）
            if (finalProductId) {
                formData.append('product_id', finalProductId);
            }

            // 系列ID（从产品自动获取或新建时指定，可选）
            if (finalSeriesId) {
                formData.append('series_id', finalSeriesId);
            }

            // 添加视频元数据（时长和封面图）
            if (videoDuration > 0) {
                formData.append('duration', videoDuration);
                console.log('上传视频时长:', videoDuration, '秒');
            }

            if (videoCoverBase64) {
                formData.append('cover_base64', videoCoverBase64);
                console.log('上传封面图: Base64 (长度:', videoCoverBase64.length, ')');
            }

            // 显示进度条
            uploadProgress.style.display = 'block';
            submitBtn.disabled = true;
            submitBtn.textContent = '上传中...';

            try {
                const xhr = new XMLHttpRequest();

                // 进度监听
                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        progressFill.style.width = percent + '%';
                        progressText.textContent = `上传中... ${percent}%`;
                    }
                });

                xhr.addEventListener('load', async () => {
                    if (xhr.status === 200) {
                        const result = JSON.parse(xhr.responseText);
                        if (result.success) {
                            await showAlert('视频上传成功！', '成功', 'success');
                            window.location.href = '/vis/ap/index.php?action=admin_list';
                        } else {
                            showAlert(result.message || '上传失败', '错误', 'error');
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

        /**
         * 快速创建系列
         * @param {string} seriesName - 系列名称
         * @returns {Promise<{success: boolean, id: number|null, message: string}>}
         */
        async function createSeries(seriesName) {
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('series_name', seriesName);

            const response = await fetch('/vis/ap/index.php?action=series_quick_create', {
                method: 'POST',
                body: formData
            });

            return await response.json();
        }

        /**
         * 快速创建产品
         * @param {string} productName - 产品名称
         * @param {string} seriesId - 系列ID（可选）
         * @returns {Promise<{success: boolean, id: number|null, message: string}>}
         */
        async function createProduct(productName, seriesId) {
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('product_name', productName);
            if (seriesId) {
                formData.append('series_id', seriesId);
            }

            const response = await fetch('/vis/ap/index.php?action=product_quick_create', {
                method: 'POST',
                body: formData
            });

            return await response.json();
        }
    </script>
    <script src="/vis/ap/js/mobile-menu.js"></script>
</body>
</html>
