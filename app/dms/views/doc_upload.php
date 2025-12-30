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
        die('<h1>Document Not Found</h1>');
    }
}

// Get categories
$categories = dms_db_get_categories();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_new_version ? 'Upload New Version' : 'Upload Document' ?> - DMS Archive System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include __DIR__ . '/_header.php'; ?>

        <main class="main-content">
            <div class="breadcrumb">
                <a href="index.php?action=doc_list">Documents</a> /
                <?php if ($is_new_version): ?>
                    <a href="index.php?action=doc_view&doc_id=<?= dms_escape($doc_id) ?>"><?= dms_escape($doc['title']) ?></a> /
                    Upload New Version
                <?php else: ?>
                    Upload New Document
                <?php endif; ?>
            </div>

            <div class="page-header">
                <h1><?= $is_new_version ? 'Upload New Version' : 'Upload New Document' ?></h1>
            </div>

            <form id="uploadForm" enctype="multipart/form-data" class="upload-form">
                <?php if ($is_new_version): ?>
                    <input type="hidden" name="doc_id" value="<?= dms_escape($doc_id) ?>">

                    <!-- Show existing document info -->
                    <div class="info-box">
                        <h3>Uploading new version for: <?= dms_escape($doc['title']) ?></h3>
                        <p><?= dms_escape($doc['description']) ?></p>
                    </div>
                <?php else: ?>
                    <!-- New document fields -->
                    <div class="form-group">
                        <label for="title">Document Title *</label>
                        <input type="text" id="title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id">
                            <option value="">None</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['category_id'] ?>"><?= dms_escape($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tags">Tags (comma-separated)</label>
                        <input type="text" id="tags" name="tags" placeholder="e.g., invoice, 2024, urgent">
                    </div>
                <?php endif; ?>

                <!-- File upload -->
                <div class="form-group">
                    <label for="file">File *</label>
                    <input type="file" id="file" name="file" required>
                    <small>Maximum size: <?= $DMS_CONFIG['upload_max_mb'] ?> MB</small>
                    <small>Allowed types: <?= implode(', ', array_slice($DMS_CONFIG['allowed_exts'], 0, 10)) ?>...</small>
                </div>

                <!-- Upload mode -->
                <div class="form-group">
                    <label for="upload_mode">Upload Mode</label>
                    <select id="upload_mode" name="upload_mode">
                        <option value="append">Append (keep all versions)</option>
                        <option value="overwrite">Overwrite (replace, but keep history)</option>
                    </select>
                    <small>Note: Both modes keep version history. "Overwrite" just marks the intent.</small>
                </div>

                <div class="form-group">
                    <label for="notes">Version Notes</label>
                    <input type="text" id="notes" name="notes" placeholder="Optional notes about this version">
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Upload</button>
                    <a href="<?= $is_new_version ? 'index.php?action=doc_view&doc_id=' . dms_escape($doc_id) : 'index.php?action=doc_list' ?>" class="btn">Cancel</a>
                </div>

                <div id="uploadProgress" class="upload-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    <p id="progressText">Uploading...</p>
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
                progressText.textContent = 'Upload completed!';

                setTimeout(() => {
                    window.location.href = 'index.php?action=doc_view&doc_id=' + data.data.doc_id;
                }, 1500);
            } else {
                result.innerHTML = '<div class="alert alert-error">Error: ' + data.message + '</div>';
                submitBtn.disabled = false;
                progress.style.display = 'none';
            }
        })
        .catch(err => {
            result.innerHTML = '<div class="alert alert-error">Request failed: ' + err.message + '</div>';
            submitBtn.disabled = false;
            progress.style.display = 'none';
        });
    });
    </script>
</body>
</html>
