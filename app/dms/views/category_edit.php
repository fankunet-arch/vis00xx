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
                    <label>元数据字段</label>
                    <p class="form-help">为此分类中的文档定义自定义字段。添加您需要的字段，系统会自动处理。</p>

                    <div id="fieldsContainer" class="fields-container"></div>

                    <button type="button" id="addFieldBtn" class="btn btn-secondary">+ 添加字段</button>

                    <!-- Hidden field to store JSON schema -->
                    <input type="hidden" id="schema_json" name="schema_json" value="<?= dms_escape($schema_json) ?>">
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
    // Field management state
    let fields = [];
    let fieldIdCounter = 0;

    // Field type labels
    const typeLabels = {
        'text': '文本',
        'number': '数字',
        'date': '日期',
        'enum': '下拉选择',
        'bool': '是/否'
    };

    // Initialize: Load existing schema
    function initFields() {
        try {
            const schemaJson = document.getElementById('schema_json').value;
            if (schemaJson && schemaJson.trim()) {
                const schema = JSON.parse(schemaJson);
                if (schema.fields && Array.isArray(schema.fields)) {
                    fields = schema.fields.map(f => ({
                        id: fieldIdCounter++,
                        name: f.name || '',
                        label: f.label || '',
                        type: f.type || 'text',
                        required: f.required || false,
                        options: f.options || []
                    }));
                }
            }
        } catch (err) {
            console.error('Failed to parse existing schema:', err);
        }
        renderFields();
    }

    // Render all fields
    function renderFields() {
        const container = document.getElementById('fieldsContainer');
        container.innerHTML = '';

        if (fields.length === 0) {
            container.innerHTML = '<p class="empty-state">还没有字段。点击下方按钮添加第一个字段。</p>';
            return;
        }

        fields.forEach((field, index) => {
            const fieldCard = createFieldCard(field, index);
            container.appendChild(fieldCard);
        });
    }

    // Create a field card element
    function createFieldCard(field, index) {
        const card = document.createElement('div');
        card.className = 'field-card';
        card.innerHTML = `
            <div class="field-card-header">
                <span class="field-number">#${index + 1}</span>
                <button type="button" class="btn-icon btn-danger" onclick="deleteField(${index})" title="删除字段">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                    </svg>
                </button>
            </div>
            <div class="field-card-body">
                <div class="field-row">
                    <div class="field-col">
                        <label>字段名称（英文）*</label>
                        <input type="text" class="form-control" value="${escapeHtml(field.name)}"
                               onchange="updateFieldName(${index}, this.value)"
                               placeholder="例如: contract_date" required>
                        <small>用于系统内部标识，只能使用英文字母、数字和下划线</small>
                    </div>
                    <div class="field-col">
                        <label>显示标签（可选）</label>
                        <input type="text" class="form-control" value="${escapeHtml(field.label)}"
                               onchange="updateFieldLabel(${index}, this.value)"
                               placeholder="例如: 合同日期">
                        <small>友好的中文名称，如不填写则显示字段名称</small>
                    </div>
                </div>
                <div class="field-row">
                    <div class="field-col">
                        <label>字段类型 *</label>
                        <select class="form-control" onchange="updateFieldType(${index}, this.value)">
                            ${Object.entries(typeLabels).map(([value, label]) =>
                                `<option value="${value}" ${field.type === value ? 'selected' : ''}>${label}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="field-col">
                        <label>
                            <input type="checkbox" ${field.required ? 'checked' : ''}
                                   onchange="updateFieldRequired(${index}, this.checked)">
                            必填字段
                        </label>
                    </div>
                </div>
                ${field.type === 'enum' ? renderEnumOptions(field, index) : ''}
            </div>
        `;
        return card;
    }

    // Render enum options editor
    function renderEnumOptions(field, index) {
        const optionsHtml = (field.options || []).map((opt, optIndex) => `
            <div class="enum-option">
                <input type="text" class="form-control" value="${escapeHtml(opt)}"
                       onchange="updateEnumOption(${index}, ${optIndex}, this.value)"
                       placeholder="选项值">
                <button type="button" class="btn-icon btn-danger" onclick="deleteEnumOption(${index}, ${optIndex})" title="删除">×</button>
            </div>
        `).join('');

        return `
            <div class="enum-options-container">
                <label>选项列表 *</label>
                <div class="enum-options">
                    ${optionsHtml}
                </div>
                <button type="button" class="btn btn-small" onclick="addEnumOption(${index})">+ 添加选项</button>
            </div>
        `;
    }

    // Field operations
    function addField() {
        fields.push({
            id: fieldIdCounter++,
            name: '',
            label: '',
            type: 'text',
            required: false,
            options: []
        });
        renderFields();
    }

    function deleteField(index) {
        if (confirm('确定要删除这个字段吗？')) {
            fields.splice(index, 1);
            renderFields();
        }
    }

    function updateFieldName(index, value) {
        // Sanitize field name: only allow letters, numbers, underscore
        const sanitized = value.toLowerCase().replace(/[^a-z0-9_]/g, '_');
        fields[index].name = sanitized;
        renderFields();
    }

    function updateFieldLabel(index, value) {
        fields[index].label = value;
    }

    function updateFieldType(index, value) {
        fields[index].type = value;
        if (value === 'enum' && (!fields[index].options || fields[index].options.length === 0)) {
            fields[index].options = [''];
        }
        renderFields();
    }

    function updateFieldRequired(index, value) {
        fields[index].required = value;
    }

    function addEnumOption(fieldIndex) {
        if (!fields[fieldIndex].options) {
            fields[fieldIndex].options = [];
        }
        fields[fieldIndex].options.push('');
        renderFields();
    }

    function deleteEnumOption(fieldIndex, optionIndex) {
        fields[fieldIndex].options.splice(optionIndex, 1);
        renderFields();
    }

    function updateEnumOption(fieldIndex, optionIndex, value) {
        fields[fieldIndex].options[optionIndex] = value;
    }

    // Generate JSON schema from fields
    function generateSchema() {
        const schema = {
            fields: fields.map(f => {
                const field = {
                    name: f.name,
                    type: f.type,
                    required: f.required
                };
                if (f.label) {
                    field.label = f.label;
                }
                if (f.type === 'enum') {
                    field.options = f.options.filter(opt => opt.trim() !== '');
                }
                return field;
            })
        };
        return JSON.stringify(schema);
    }

    // Validate fields before submit
    function validateFields() {
        for (let i = 0; i < fields.length; i++) {
            const field = fields[i];
            if (!field.name || field.name.trim() === '') {
                return { valid: false, error: `字段 #${i + 1} 缺少字段名称` };
            }
            if (field.type === 'enum') {
                const validOptions = (field.options || []).filter(opt => opt.trim() !== '');
                if (validOptions.length === 0) {
                    return { valid: false, error: `字段 "${field.name}" (下拉选择) 至少需要一个选项` };
                }
            }
        }
        // Check for duplicate field names
        const names = fields.map(f => f.name);
        const duplicates = names.filter((name, index) => names.indexOf(name) !== index);
        if (duplicates.length > 0) {
            return { valid: false, error: `字段名称重复: ${duplicates.join(', ')}` };
        }
        return { valid: true };
    }

    // Utility function
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Event listeners
    document.getElementById('addFieldBtn').addEventListener('click', addField);

    document.getElementById('categoryForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const submitBtn = document.getElementById('submitBtn');
        const result = document.getElementById('result');

        // Validate fields
        const validation = validateFields();
        if (!validation.valid) {
            result.innerHTML = '<div class="alert alert-error">验证失败：' + validation.error + '</div>';
            return;
        }

        // Generate and set schema JSON
        const schemaJson = generateSchema();
        document.getElementById('schema_json').value = schemaJson;

        const formData = new FormData(this);
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

    // Initialize on page load
    initFields();
    </script>
</body>
</html>
