<?php
/**
 * DMS Archive System - Document View
 */

defined('DMS_ENTRY') or exit;

$current_user = dms_get_current_user();
$can_edit = dms_can('edit');
$can_upload = dms_can('upload');
$is_admin = dms_has_role('admin');

// Get doc_id
$doc_id = $_GET['doc_id'] ?? '';
if (empty($doc_id) || !dms_validate_uuid($doc_id)) {
    die('<h1>无效的文档ID</h1>');
}

// Get document
$doc = dms_db_get_document($doc_id);
if (!$doc || $doc['status'] === 'deleted') {
    die('<h1>文档未找到</h1>');
}

// Get versions
$versions = dms_db_get_versions($doc_id, false);

// Parse attributes
$attributes = !empty($doc['attributes_json']) ? dms_json_decode($doc['attributes_json']) : [];
$category_schema = !empty($doc['category_schema']) ? dms_json_decode($doc['category_schema']) : null;

?>
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= dms_escape($doc['title']) ?> - DMS Archive System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include __DIR__ . '/_header.php'; ?>

        <main class="main-content">
            <div class="breadcrumb">
                <a href="index.php?action=doc_list">文档</a> / <?= dms_escape($doc['title']) ?>
            </div>

            <div class="page-header">
                <h1><?= dms_escape($doc['title']) ?></h1>
                <div class="page-actions">
                    <?php if ($can_upload): ?>
                        <a href="index.php?action=doc_upload&doc_id=<?= dms_escape($doc_id) ?>" class="btn">上传新版本</a>
                    <?php endif; ?>
                    <?php if ($is_admin): ?>
                        <button onclick="deleteDocument('<?= dms_escape($doc_id) ?>')" class="btn btn-danger">删除文档</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="doc-detail">
                <!-- Metadata -->
                <section class="detail-section">
                    <h2>信息</h2>
                    <dl class="detail-list">
                        <dt>分类</dt>
                        <dd><?= dms_escape($doc['category_name'] ?? '无') ?></dd>

                        <dt>项目</dt>
                        <dd><?= dms_escape($doc['project_name'] ?? '无') ?></dd>

                        <dt>描述</dt>
                        <dd><?= $doc['description'] ? dms_escape($doc['description']) : '<em>无描述</em>' ?></dd>

                        <dt>标签</dt>
                        <dd><?= $doc['tags'] ? dms_escape($doc['tags']) : '<em>无标签</em>' ?></dd>

                        <dt>创建者</dt>
                        <dd><?= dms_escape($doc['created_by_name'] ?? '未知') ?> 于 <?= dms_format_datetime($doc['created_at'], 'Y-m-d H:i') ?></dd>

                        <dt>最后更新</dt>
                        <dd><?= dms_format_datetime($doc['updated_at'], 'Y-m-d H:i') ?></dd>
                    </dl>

                    <?php if (!empty($attributes) && $category_schema): ?>
                        <h3>自定义属性</h3>
                        <dl class="detail-list">
                            <?php foreach ($category_schema['fields'] ?? [] as $field): ?>
                                <dt><?= dms_escape($field['name']) ?></dt>
                                <dd><?= isset($attributes[$field['name']]) ? dms_escape($attributes[$field['name']]) : '<em>未设置</em>' ?></dd>
                            <?php endforeach; ?>
                        </dl>
                    <?php endif; ?>
                </section>

                <!-- Versions -->
                <section class="detail-section">
                    <h2>版本 (<?= count($versions) ?>)</h2>

                    <?php if (empty($versions)): ?>
                        <p><em>暂无版本。</em></p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>版本</th>
                                    <th>文件名</th>
                                    <th>大小</th>
                                    <th>类型</th>
                                    <th>上传者</th>
                                    <th>上传时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($versions as $ver): ?>
                                    <tr class="<?= $ver['is_current'] ? 'version-current' : '' ?>">
                                        <td>
                                            v<?= $ver['version_no'] ?>
                                            <?php if ($ver['is_current']): ?>
                                                <span class="badge badge-primary">当前</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= dms_escape($ver['original_file_name']) ?></td>
                                        <td><?= dms_format_bytes($ver['file_size']) ?></td>
                                        <td><?= dms_escape($ver['file_ext']) ?></td>
                                        <td><?= dms_escape($ver['uploaded_by_name'] ?? 'Unknown') ?></td>
                                        <td><?= dms_format_datetime($ver['uploaded_at'], 'Y-m-d H:i') ?></td>
                                        <td class="actions">
                                            <?php
                                            $preview_type = dms_get_preview_type($ver['mime_type']);
                                            if ($preview_type): ?>
                                                <a href="index.php?action=file_preview&version_id=<?= $ver['version_id'] ?>" target="_blank" class="btn-sm btn-preview">预览</a>
                                            <?php endif; ?>
                                            <a href="index.php?action=file_download&version_id=<?= $ver['version_id'] ?>" class="btn-sm btn-download">下载</a>
                                            <?php if ($is_admin && !$ver['is_current']): ?>
                                                <button onclick="deleteVersion(<?= $ver['version_id'] ?>)" class="btn-sm btn-danger">删除</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </section>
            </div>
        </main>

        <?php include __DIR__ . '/_footer.php'; ?>
    </div>

    <script>
    function deleteDocument(docId) {
        if (!confirm('你确定要删除此文档吗？此操作无法撤销。')) {
            return;
        }

        const formData = new FormData();
        formData.append('doc_id', docId);

        fetch('index.php?action=doc_delete_submit', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('文档已成功删除');
                window.location.href = 'index.php?action=doc_list';
            } else {
                alert('错误: ' + data.message);
            }
        })
        .catch(err => {
            alert('请求失败: ' + err.message);
        });
    }

    function deleteVersion(versionId) {
        if (!confirm('你确定要删除此版本吗？')) {
            return;
        }

        const formData = new FormData();
        formData.append('version_id', versionId);

        fetch('index.php?action=version_delete_submit', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('版本已成功删除');
                location.reload();
            } else {
                alert('错误: ' + data.message);
            }
        })
        .catch(err => {
            alert('请求失败: ' + err.message);
        });
    }
    </script>
</body>
</html>
