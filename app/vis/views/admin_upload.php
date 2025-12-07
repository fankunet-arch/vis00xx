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

// 获取分类列表
$categories = vis_get_categories($pdo);
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

        <!-- 主区域 -->
        <main class="main-wrapper">
            <!-- 顶部栏 -->
            <header class="admin-header">
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

                        <div class="form-group">
                            <label class="form-label">分类 *</label>
                            <select name="category" id="category" class="form-select" required>
                                <option value="">请选择分类</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category_code']); ?>">
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
            video.preload = 'metadata';

            // 创建临时 URL
            const videoURL = URL.createObjectURL(file);
            video.src = videoURL;

            video.onloadedmetadata = function() {
                // 获取视频时长（秒，四舍五入）
                videoDuration = Math.round(video.duration);

                console.log(`视频时长: ${videoDuration} 秒`);

                // 获取视频首帧作为封面
                video.currentTime = 0.1; // 定位到0.1秒（避免全黑帧）
            };

            video.onseeked = function() {
                try {
                    // 使用 Canvas 截取视频帧
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                    // 转换为 Base64（JPEG 格式，质量 0.8）
                    videoCoverBase64 = canvas.toDataURL('image/jpeg', 0.8);

                    console.log('封面图已生成');

                    // 释放临时 URL
                    URL.revokeObjectURL(videoURL);
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

            if (!title) {
                showAlert('请输入视频标题', '提示', 'warning');
                return;
            }

            if (!category) {
                showAlert('请选择视频分类', '提示', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('video', selectedFile);
            formData.append('title', title);
            formData.append('category', category);
            formData.append('platform', platform);

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
    </script>
</body>
</html>
