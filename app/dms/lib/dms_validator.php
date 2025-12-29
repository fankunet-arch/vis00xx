<?php
/**
 * DMS Archive System - Validation Functions
 * All functions prefixed with 'dms_validate_'
 */

defined('DMS_ENTRY') or exit;

/**
 * Validate uploaded file
 * @param array $file $_FILES array element
 * @return array ['valid' => bool, 'error' => string|null, 'info' => array]
 */
function dms_validate_uploaded_file(array $file): array {
    global $DMS_CONFIG;

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
        ];

        return [
            'valid' => false,
            'error' => $errors[$file['error']] ?? 'Unknown upload error',
            'info' => null
        ];
    }

    // Check file size
    $max_bytes = $DMS_CONFIG['upload_max_mb'] * 1024 * 1024;
    if ($file['size'] > $max_bytes) {
        return [
            'valid' => false,
            'error' => 'File size exceeds maximum allowed (' . $DMS_CONFIG['upload_max_mb'] . ' MB)',
            'info' => null
        ];
    }

    if ($file['size'] === 0) {
        return [
            'valid' => false,
            'error' => 'File is empty',
            'info' => null
        ];
    }

    // Check if file was actually uploaded
    if (!is_uploaded_file($file['tmp_name'])) {
        return [
            'valid' => false,
            'error' => 'Security: File was not uploaded via HTTP POST',
            'info' => null
        ];
    }

    // Get file extension
    $original_name = basename($file['name']);
    $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    if (!dms_is_allowed_ext($ext)) {
        return [
            'valid' => false,
            'error' => 'File type not allowed: .' . $ext,
            'info' => null
        ];
    }

    // Get MIME type (from finfo, not from $_FILES which can be spoofed)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!dms_is_allowed_mime($mime_type)) {
        return [
            'valid' => false,
            'error' => 'MIME type not allowed: ' . $mime_type,
            'info' => null
        ];
    }

    // Calculate hash
    $sha256 = dms_hash_file($file['tmp_name']);
    if ($sha256 === false) {
        return [
            'valid' => false,
            'error' => 'Failed to calculate file hash',
            'info' => null
        ];
    }

    return [
        'valid' => true,
        'error' => null,
        'info' => [
            'original_name' => $original_name,
            'ext' => $ext,
            'mime_type' => $mime_type,
            'size' => $file['size'],
            'sha256' => $sha256,
            'tmp_path' => $file['tmp_name']
        ]
    ];
}

/**
 * Validate category schema
 * @param array $schema
 * @return array ['valid' => bool, 'error' => string|null]
 */
function dms_validate_category_schema(array $schema): array {
    if (!isset($schema['fields']) || !is_array($schema['fields'])) {
        return [
            'valid' => false,
            'error' => 'Schema must have "fields" array'
        ];
    }

    $allowed_types = ['text', 'number', 'date', 'enum', 'bool'];

    foreach ($schema['fields'] as $idx => $field) {
        if (!isset($field['name']) || !is_string($field['name'])) {
            return [
                'valid' => false,
                'error' => "Field at index {$idx} missing 'name'"
            ];
        }

        if (!isset($field['type']) || !in_array($field['type'], $allowed_types, true)) {
            return [
                'valid' => false,
                'error' => "Field '{$field['name']}' has invalid type. Allowed: " . implode(', ', $allowed_types)
            ];
        }

        // If enum, must have options
        if ($field['type'] === 'enum') {
            if (!isset($field['options']) || !is_array($field['options']) || empty($field['options'])) {
                return [
                    'valid' => false,
                    'error' => "Field '{$field['name']}' is enum but missing options array"
                ];
            }
        }
    }

    return ['valid' => true, 'error' => null];
}

/**
 * Validate document attributes against category schema
 * @param array $attributes User-provided attributes
 * @param array $schema Category schema
 * @return array ['valid' => bool, 'error' => string|null]
 */
function dms_validate_attributes(array $attributes, array $schema): array {
    if (!isset($schema['fields']) || !is_array($schema['fields'])) {
        // No schema defined, allow anything
        return ['valid' => true, 'error' => null];
    }

    foreach ($schema['fields'] as $field) {
        $name = $field['name'];
        $type = $field['type'];
        $required = $field['required'] ?? false;

        // Check required
        if ($required && (!isset($attributes[$name]) || $attributes[$name] === '' || $attributes[$name] === null)) {
            return [
                'valid' => false,
                'error' => "Required field '{$name}' is missing"
            ];
        }

        // Skip validation if not required and not provided
        if (!isset($attributes[$name]) || $attributes[$name] === '' || $attributes[$name] === null) {
            continue;
        }

        $value = $attributes[$name];

        // Type validation
        switch ($type) {
            case 'text':
                if (!is_string($value)) {
                    return ['valid' => false, 'error' => "Field '{$name}' must be text"];
                }
                break;

            case 'number':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'error' => "Field '{$name}' must be a number"];
                }
                break;

            case 'date':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return ['valid' => false, 'error' => "Field '{$name}' must be a valid date (YYYY-MM-DD)"];
                }
                break;

            case 'enum':
                $options = $field['options'] ?? [];
                if (!in_array($value, $options, true)) {
                    return [
                        'valid' => false,
                        'error' => "Field '{$name}' must be one of: " . implode(', ', $options)
                    ];
                }
                break;

            case 'bool':
                if (!is_bool($value) && $value !== '0' && $value !== '1' && $value !== 0 && $value !== 1) {
                    return ['valid' => false, 'error' => "Field '{$name}' must be boolean"];
                }
                break;
        }
    }

    return ['valid' => true, 'error' => null];
}

/**
 * Validate UUID format
 * @param string $uuid
 * @return bool
 */
function dms_validate_uuid(string $uuid): bool {
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1;
}

/**
 * Sanitize and validate integer input
 * @param mixed $value
 * @param int|null $min
 * @param int|null $max
 * @return int|false
 */
function dms_validate_int($value, ?int $min = null, ?int $max = null) {
    if (!is_numeric($value)) {
        return false;
    }

    $int = (int)$value;

    if ($min !== null && $int < $min) {
        return false;
    }

    if ($max !== null && $int > $max) {
        return false;
    }

    return $int;
}

/**
 * Validate string length
 * @param string $value
 * @param int $min
 * @param int $max
 * @return bool
 */
function dms_validate_string_length(string $value, int $min = 0, int $max = PHP_INT_MAX): bool {
    $len = mb_strlen($value, 'UTF-8');
    return $len >= $min && $len <= $max;
}

/**
 * Validate email address
 * @param string $email
 * @return bool
 */
function dms_validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate upload mode
 * @param string $mode
 * @return bool
 */
function dms_validate_upload_mode(string $mode): bool {
    return in_array($mode, ['append', 'overwrite'], true);
}
