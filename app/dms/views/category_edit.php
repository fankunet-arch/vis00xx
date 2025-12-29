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
    <title><?= $is_edit ? 'Edit' : 'Create' ?> Category - DMS Archive System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="layout">
        <?php include __DIR__ . '/_header.php'; ?>

        <main class="main-content">
            <div class="breadcrumb">
                <a href="index.php?action=category_list">Categories</a> / <?= $is_edit ? 'Edit' : 'Create' ?>
            </div>

            <div class="page-header">
                <h1><?= $is_edit ? 'Edit Category' : 'Create New Category' ?></h1>
            </div>

            <form id="categoryForm" class="category-form">
                <?php if ($is_edit): ?>
                    <input type="hidden" name="category_id" value="<?= $category_id ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="name">Category Name *</label>
                    <input type="text" id="name" name="name" value="<?= dms_escape($name) ?>" required maxlength="100">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"><?= dms_escape($description) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="schema_json">Schema (JSON)</label>
                    <textarea id="schema_json" name="schema_json" rows="15" class="code-editor"><?= dms_escape($schema_json) ?></textarea>
                    <small>Define custom fields for documents in this category.</small>
                    <details>
                        <summary>Schema Format Example</summary>
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
                        <p>Supported types: text, number, date, enum, bool</p>
                    </details>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Save Category</button>
                    <a href="index.php?action=category_list" class="btn">Cancel</a>
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
            result.innerHTML = '<div class="alert alert-error">Invalid JSON in schema: ' + err.message + '</div>';
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
                result.innerHTML = '<div class="alert alert-error">Error: ' + data.message + '</div>';
                submitBtn.disabled = false;
            }
        })
        .catch(err => {
            result.innerHTML = '<div class="alert alert-error">Request failed: ' + err.message + '</div>';
            submitBtn.disabled = false;
        });
    });
    </script>
</body>
</html>
