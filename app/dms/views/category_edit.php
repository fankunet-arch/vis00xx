<?php
/**
 * DMS Archive System - Category Edit View
 */

defined('DMS_ENTRY') or exit;

dms_require_role('admin');

$current_user = dms_get_current_user();

// Get category_id if editing
$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$is_edit = $category_id > 0;

$category = null;
if ($is_edit) {
    $category = dms_db_get_category($category_id);
    if (!$category) {
        die('<h1>Category Not Found</h1>');
    }
}

$name = $category['name'] ?? '';
$description = $category['description'] ?? '';
$schema_json = $category['schema_json'] ?? '{"fields":[]}';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? '编辑' : '创建' ?> 分类 - DMS Archive System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include __DIR__ . '/_header.php'; ?>

        <main class="main-content">
            <div class="breadcrumb">
                <a href="index.php?action=category_list">分类列表</a> / <?= $is_edit ? '编辑' : '创建' ?>
            </div>

            <div class="page-header">
                <h1><?= $is_edit ? '编辑分类' : '创建新分类' ?></h1>
            </div>

            <form id="categoryForm" class="category-form">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="category_id" value="<?= $category_id ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">分类名称 *</label>
                    <input type="text" id="name" name="name" value="<?= dms_escape($name) ?>" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="description">描述</label>
                    <textarea id="description" name="description" rows="3"><?= dms_escape($description) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="schema_json">元数据架构 (JSON)</label>
                    <textarea id="schema_json" name="schema_json" rows="15" class="code-editor"><?= dms_escape($schema_json) ?></textarea>
                    <small>为此分类中的文档定义自定义字段。</small>
                    <details>
                        <summary>架构格式示例</summary>
                        <pre>{
  "fields": [
    {
      "name": "contract_date",
      "type": "date",
      "required": true
    },
    {
      "name": "counterparty",
      "type": "text",
      "required": true
    },
    {
      "name": "status",
      "type": "enum",
      "required": true,
      "options": ["draft", "active", "expired"]
    },
    {
      "name": "amount",
      "type": "number",
      "required": false
    },
    {
      "name": "reviewed",
      "type": "bool",
      "required": false
    }
  ]
}</pre>
                        <p>支持的类型：text, number, date, enum, bool</p>
                    </details>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">保存分类</button>
                    <a href="index.php?action=category_list" class="btn">取消</a>
                </div>

                <div id="result" class="result"></div>
            </form>
        </main>

        <?php include __DIR__ . '/_footer.php'; ?>
    </div>

    <script>
    document.getElementById('categoryForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitBtn');
        const result = document.getElementById('result');

        // Validate JSON
        try {
            const schemaJson = document.getElementById('schema_json').value;
            if (schemaJson.trim()) {
                JSON.parse(schemaJson);
            }
        } catch (err) {
            result.innerHTML = '<div class="alert alert-error">架构中的JSON无效：' + err.message + '</div>';
            return;
        }

        submitBtn.disabled = true;
        result.innerHTML = '';

        fetch('index.php?action=category_save', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                result.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
                setTimeout(() => {
                    window.location.href = 'index.php?action=category_list';
                }, 1000);
            } else {
                result.innerHTML = '<div class="alert alert-error">错误：' + data.message + '</div>';
                submitBtn.disabled = false;
            }
        })
        .catch(err => {
            result.innerHTML = '<div class="alert alert-error">请求失败：' + err.message + '</div>';
            submitBtn.disabled = false;
        });
    });
    </script>
</body>
</html>
