<?php
/**
 * DMS Archive System - Document Upload View
 */

defined('DMS_ENTRY') or exit;

dms_require_permission('upload');

$current_user = dms_get_current_user();

// Check if uploading new version for existing doc
$doc_id = $_GET['doc_id'] ?? '';
$is_new_version = !empty($doc_id) && dms_validate_uuid($doc_id);

$doc = null;
if ($is_new_version) {
    $doc = dms_db_get_document($doc_id);
    if (!$doc || $doc['status'] === 'deleted') {
        die('<h1>文档未找到</h1>');
    }
}

// Get categories and projects
$categories = dms_db_get_categories();
$projects = dms_db_get_projects();

?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_new_version ? '上传新版本' : '上传文档' ?> - DMS Archive System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include __DIR__ . '/_header.php'; ?>

        <main class="main-content">
            <div class="breadcrumb">
                <a href="index.php?action=doc_list">文档</a> /
                <?php if ($is_new_version): ?>
                    <a href="index.php?action=doc_view&doc_id=<?= dms_escape($doc_id) ?>"><?= dms_escape($doc['title']) ?></a> /
                    上传新版本
                <?php else: ?>
                    上传新文档
                <?php endif; ?>
            </div>

            <div class="page-header">
                <h1><?= $is_new_version ? '上传新版本' : '上传新文档' ?></h1>
            </div>

            <form id="uploadForm" enctype="multipart/form-data" class="upload-form">
                <?php if ($is_new_version): ?>
                    <input type="hidden" name="doc_id" value="<?= dms_escape($doc_id) ?>">

                    <!-- Show existing document info -->
                    <div class="info-box">
                        <h3>正在上传新版本：<?= dms_escape($doc['title']) ?></h3>
                        <p><?= dms_escape($doc['description']) ?></p>
                    </div>
                <?php else: ?>
                    <!-- New document fields - 两列布局 -->
                    <div class="form-row">
                        <div class="form-group form-group-full">
                            <label for="title">文档标题 *</label>
                            <input type="text" id="title" name="title" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">描述</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="category_id">分类</label>
                            <select id="category_id" name="category_id">
                                <option value="">无</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['category_id'] ?>"><?= dms_escape($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="project_id">项目</label>
                            <select id="project_id" name="project_id">
                                <option value="">无</option>
                                <?php foreach ($projects as $proj): ?>
                                    <?php if ($proj['status'] === 'active'): ?>
                                        <option value="<?= $proj['project_id'] ?>"><?= dms_escape($proj['name']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="tags">标签（用逗号分隔）</label>
                        <input type="text" id="tags" name="tags" placeholder="例如：发票、2024、紧急">
                    </div>
                <?php endif; ?>

                <!-- File upload and settings - 两列布局 -->
                <div class="form-group">
                    <label for="file">文件 *</label>
                    <input type="file" id="file" name="file" required>
                    <small>最大大小：<?= $DMS_CONFIG['upload_max_mb'] ?> MB | 允许类型：<?= implode(', ', array_slice($DMS_CONFIG['allowed_exts'], 0, 8)) ?>...</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="upload_mode">上传模式</label>
                        <select id="upload_mode" name="upload_mode">
                            <option value="append">追加（保留所有版本）</option>
                            <option value="overwrite">覆盖（替换，但保留历史）</option>
                        </select>
                        <small>两种模式都保留版本历史</small>
                    </div>

                    <div class="form-group">
                        <label for="notes">版本说明</label>
                        <input type="text" id="notes" name="notes" placeholder="关于此版本的可选说明">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">上传</button>
                    <a href="<?= $is_new_version ? 'index.php?action=doc_view&doc_id=' . dms_escape($doc_id) : 'index.php?action=doc_list' ?>" class="btn">取消</a>
                </div>

                <div id="uploadProgress" class="upload-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <p id="progressText">正在上传...</p>
                </div>

                <div id="uploadResult" class="upload-result"></div>
            </form>
        </main>

        <?php include __DIR__ . '/_footer.php'; ?>
    </div>

    <script>
    document.getElementById('uploadForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitBtn');
        const progress = document.getElementById('uploadProgress');
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        const result = document.getElementById('uploadResult');

        submitBtn.disabled = true;
        progress.style.display = 'block';
        result.innerHTML = '';

        const actionUrl = <?= $is_new_version ? "'index.php?action=version_upload_submit'" : "'index.php?action=doc_upload_submit'" ?>;

        fetch(actionUrl, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                result.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                progressText.textContent = '上传完成！';

                setTimeout(() => {
                    window.location.href = 'index.php?action=doc_view&doc_id=' + data.data.doc_id;
                }, 1500);
            } else {
                result.innerHTML = '<div class="alert alert-error">错误：' + data.message + '</div>';
                submitBtn.disabled = false;
                progress.style.display = 'none';
            }
        })
        .catch(err => {
            result.innerHTML = '<div class="alert alert-error">请求失败：' + err.message + '</div>';
            submitBtn.disabled = false;
            progress.style.display = 'none';
        });
    });
    </script>
</body>
</html>
