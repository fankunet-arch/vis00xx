<?php
/**
 * DMS Archive System - Database Functions
 * All functions prefixed with 'dms_db_'
 */

defined('DMS_ENTRY') or exit;

/**
 * Get user by username
 * @param string $username
 * @return array|false
 */
function dms_db_get_user_by_username(string $username) {
    global $DMS_DB;
    $stmt = $DMS_DB->prepare('
        SELECT * FROM dms_users
        WHERE username = :username AND is_active = 1
        LIMIT 1
    ');
    $stmt->execute(['username' => $username]);
    return $stmt->fetch();
}

/**
 * Get user by ID
 * @param int $user_id
 * @return array|false
 */
function dms_db_get_user_by_id(int $user_id) {
    global $DMS_DB;
    $stmt = $DMS_DB->prepare('SELECT * FROM dms_users WHERE user_id = :user_id LIMIT 1');
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetch();
}

/**
 * Create audit log entry
 * @param int|null $user_id
 * @param string $action
 * @param string|null $entity_type
 * @param string|null $entity_id
 * @param string|null $doc_id
 * @param int|null $version_id
 * @param array|null $details
 */
function dms_db_audit_log(
    ?int $user_id,
    string $action,
    ?string $entity_type = null,
    ?string $entity_id = null,
    ?string $doc_id = null,
    ?int $version_id = null,
    ?array $details = null
): void {
    global $DMS_DB, $DMS_CONFIG;

    $stmt = $DMS_DB->prepare('
        INSERT INTO dms_audit_log
        (user_id, org_id, action, entity_type, entity_id, doc_id, version_id, ip_address, user_agent, details_json)
        VALUES
        (:user_id, :org_id, :action, :entity_type, :entity_id, :doc_id, :version_id, :ip_address, :user_agent, :details_json)
    ');

    $stmt->execute([
        'user_id' => $user_id,
        'org_id' => 1, // Default org
        'action' => $action,
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'doc_id' => $doc_id,
        'version_id' => $version_id,
        'ip_address' => dms_get_client_ip(),
        'user_agent' => substr(dms_get_user_agent(), 0, 255),
        'details_json' => $details ? dms_json_encode($details) : null,
    ]);
}

/**
 * Get all categories
 * @param int $org_id
 * @return array
 */
function dms_db_get_categories(int $org_id = 1): array {
    global $DMS_DB;
    $stmt = $DMS_DB->prepare('
        SELECT * FROM dms_categories
        WHERE org_id = :org_id AND is_active = 1
        ORDER BY name ASC
    ');
    $stmt->execute(['org_id' => $org_id]);
    return $stmt->fetchAll();
}

/**
 * Get category by ID
 * @param int $category_id
 * @return array|false
 */
function dms_db_get_category(int $category_id) {
    global $DMS_DB;
    $stmt = $DMS_DB->prepare('SELECT * FROM dms_categories WHERE category_id = :id LIMIT 1');
    $stmt->execute(['id' => $category_id]);
    return $stmt->fetch();
}

/**
 * Create or update category
 * @param array $data
 * @return int Category ID
 */
function dms_db_save_category(array $data): int {
    global $DMS_DB;

    if (!empty($data['category_id'])) {
        // Update existing
        $stmt = $DMS_DB->prepare('
            UPDATE dms_categories
            SET name = :name, description = :description, schema_json = :schema_json
            WHERE category_id = :category_id
        ');
        $stmt->execute([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'schema_json' => isset($data['schema_json']) ? dms_json_encode($data['schema_json']) : null,
        ]);
        return (int)$data['category_id'];
    } else {
        // Insert new
        $stmt = $DMS_DB->prepare('
            INSERT INTO dms_categories (org_id, name, description, schema_json)
            VALUES (:org_id, :name, :description, :schema_json)
        ');
        $stmt->execute([
            'org_id' => $data['org_id'] ?? 1,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'schema_json' => isset($data['schema_json']) ? dms_json_encode($data['schema_json']) : null,
        ]);
        return (int)$DMS_DB->lastInsertId();
    }
}

/**
 * Get documents with optional filters
 * @param array $filters
 * @param int $limit
 * @param int $offset
 * @return array
 */
function dms_db_get_documents(array $filters = [], int $limit = 25, int $offset = 0): array {
    global $DMS_DB;

    $where = ['d.status = :status'];
    $params = ['status' => 'active'];

    if (!empty($filters['category_id'])) {
        $where[] = 'd.category_id = :category_id';
        $params['category_id'] = $filters['category_id'];
    }

    if (!empty($filters['search'])) {
        $where[] = '(d.title LIKE :search OR d.description LIKE :search OR d.tags LIKE :search)';
        $params['search'] = '%' . $filters['search'] . '%';
    }

    $where_sql = implode(' AND ', $where);

    $stmt = $DMS_DB->prepare("
        SELECT d.*, c.name AS category_name, u.full_name AS created_by_name
        FROM dms_documents d
        LEFT JOIN dms_categories c ON d.category_id = c.category_id
        LEFT JOIN dms_users u ON d.created_by = u.user_id
        WHERE {$where_sql}
        ORDER BY d.created_at DESC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $key => $val) {
        $stmt->bindValue(':' . $key, $val);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Count documents
 * @param array $filters
 * @return int
 */
function dms_db_count_documents(array $filters = []): int {
    global $DMS_DB;

    $where = ['status = :status'];
    $params = ['status' => 'active'];

    if (!empty($filters['category_id'])) {
        $where[] = 'category_id = :category_id';
        $params['category_id'] = $filters['category_id'];
    }

    if (!empty($filters['search'])) {
        $where[] = '(title LIKE :search OR description LIKE :search OR tags LIKE :search)';
        $params['search'] = '%' . $filters['search'] . '%';
    }

    $where_sql = implode(' AND ', $where);

    $stmt = $DMS_DB->prepare("SELECT COUNT(*) FROM dms_documents WHERE {$where_sql}");
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/**
 * Get document by ID
 * @param string $doc_id UUID
 * @return array|false
 */
function dms_db_get_document(string $doc_id) {
    global $DMS_DB;
    $stmt = $DMS_DB->prepare('
        SELECT d.*, c.name AS category_name, c.schema_json AS category_schema
        FROM dms_documents d
        LEFT JOIN dms_categories c ON d.category_id = c.category_id
        WHERE d.doc_id = :doc_id
        LIMIT 1
    ');
    $stmt->execute(['doc_id' => $doc_id]);
    return $stmt->fetch();
}

/**
 * Create new document
 * @param array $data
 * @return string Doc ID (UUID)
 */
function dms_db_create_document(array $data): string {
    global $DMS_DB;

    $doc_id = dms_generate_uuid();

    $stmt = $DMS_DB->prepare('
        INSERT INTO dms_documents
        (doc_id, org_id, category_id, title, description, tags, attributes_json, created_by)
        VALUES
        (:doc_id, :org_id, :category_id, :title, :description, :tags, :attributes_json, :created_by)
    ');

    $stmt->execute([
        'doc_id' => $doc_id,
        'org_id' => $data['org_id'] ?? 1,
        'category_id' => $data['category_id'] ?? null,
        'title' => $data['title'],
        'description' => $data['description'] ?? null,
        'tags' => $data['tags'] ?? null,
        'attributes_json' => isset($data['attributes']) ? dms_json_encode($data['attributes']) : null,
        'created_by' => $data['created_by'],
    ]);

    return $doc_id;
}

/**
 * Update document metadata
 * @param string $doc_id
 * @param array $data
 */
function dms_db_update_document(string $doc_id, array $data): void {
    global $DMS_DB;

    $fields = [];
    $params = ['doc_id' => $doc_id];

    if (array_key_exists('category_id', $data)) {
        $fields[] = 'category_id = :category_id';
        $params['category_id'] = $data['category_id'];
    }
    if (array_key_exists('title', $data)) {
        $fields[] = 'title = :title';
        $params['title'] = $data['title'];
    }
    if (array_key_exists('description', $data)) {
        $fields[] = 'description = :description';
        $params['description'] = $data['description'];
    }
    if (array_key_exists('tags', $data)) {
        $fields[] = 'tags = :tags';
        $params['tags'] = $data['tags'];
    }
    if (array_key_exists('attributes', $data)) {
        $fields[] = 'attributes_json = :attributes_json';
        $params['attributes_json'] = dms_json_encode($data['attributes']);
    }
    if (array_key_exists('status', $data)) {
        $fields[] = 'status = :status';
        $params['status'] = $data['status'];
    }

    if (empty($fields)) {
        return;
    }

    $sql = 'UPDATE dms_documents SET ' . implode(', ', $fields) . ' WHERE doc_id = :doc_id';
    $stmt = $DMS_DB->prepare($sql);
    $stmt->execute($params);
}

/**
 * Get document versions
 * @param string $doc_id
 * @param bool $include_deleted
 * @return array
 */
function dms_db_get_versions(string $doc_id, bool $include_deleted = false): array {
    global $DMS_DB;

    $where = 'doc_id = :doc_id';
    if (!$include_deleted) {
        $where .= ' AND is_deleted = 0';
    }

    $stmt = $DMS_DB->prepare("
        SELECT v.*, u.full_name AS uploaded_by_name
        FROM dms_document_versions v
        LEFT JOIN dms_users u ON v.uploaded_by = u.user_id
        WHERE {$where}
        ORDER BY version_no DESC
    ");
    $stmt->execute(['doc_id' => $doc_id]);
    return $stmt->fetchAll();
}

/**
 * Get version by ID
 * @param int $version_id
 * @return array|false
 */
function dms_db_get_version(int $version_id) {
    global $DMS_DB;
    $stmt = $DMS_DB->prepare('SELECT * FROM dms_document_versions WHERE version_id = :id LIMIT 1');
    $stmt->execute(['id' => $version_id]);
    return $stmt->fetch();
}

/**
 * Get next version number for document
 * @param string $doc_id
 * @return int
 */
function dms_db_get_next_version_no(string $doc_id): int {
    global $DMS_DB;
    $stmt = $DMS_DB->prepare('SELECT MAX(version_no) FROM dms_document_versions WHERE doc_id = :doc_id');
    $stmt->execute(['doc_id' => $doc_id]);
    $max = $stmt->fetchColumn();
    return $max ? (int)$max + 1 : 1;
}

/**
 * Create new document version
 * @param array $data
 * @return int Version ID
 */
function dms_db_create_version(array $data): int {
    global $DMS_DB;

    $stmt = $DMS_DB->prepare('
        INSERT INTO dms_document_versions
        (doc_id, version_no, upload_mode, storage_bucket, storage_key,
         original_file_name, stored_file_name, file_ext, mime_type, file_size,
         sha256, etag, is_current, uploaded_by, notes)
        VALUES
        (:doc_id, :version_no, :upload_mode, :storage_bucket, :storage_key,
         :original_file_name, :stored_file_name, :file_ext, :mime_type, :file_size,
         :sha256, :etag, :is_current, :uploaded_by, :notes)
    ');

    $stmt->execute([
        'doc_id' => $data['doc_id'],
        'version_no' => $data['version_no'],
        'upload_mode' => $data['upload_mode'],
        'storage_bucket' => $data['storage_bucket'],
        'storage_key' => $data['storage_key'],
        'original_file_name' => $data['original_file_name'],
        'stored_file_name' => $data['stored_file_name'],
        'file_ext' => $data['file_ext'],
        'mime_type' => $data['mime_type'],
        'file_size' => $data['file_size'],
        'sha256' => $data['sha256'],
        'etag' => $data['etag'] ?? null,
        'is_current' => $data['is_current'] ?? 0,
        'uploaded_by' => $data['uploaded_by'],
        'notes' => $data['notes'] ?? null,
    ]);

    return (int)$DMS_DB->lastInsertId();
}

/**
 * Set version as current (within transaction)
 * Ensures only one version is current per document
 *
 * @param string $doc_id
 * @param int $version_id
 */
function dms_db_set_current_version(string $doc_id, int $version_id): void {
    global $DMS_DB;

    // Clear all is_current flags for this doc
    $stmt = $DMS_DB->prepare('UPDATE dms_document_versions SET is_current = 0 WHERE doc_id = :doc_id');
    $stmt->execute(['doc_id' => $doc_id]);

    // Set new current
    $stmt = $DMS_DB->prepare('UPDATE dms_document_versions SET is_current = 1 WHERE version_id = :version_id');
    $stmt->execute(['version_id' => $version_id]);

    // Update document.current_version_id
    $stmt = $DMS_DB->prepare('UPDATE dms_documents SET current_version_id = :version_id WHERE doc_id = :doc_id');
    $stmt->execute(['version_id' => $version_id, 'doc_id' => $doc_id]);
}

/**
 * Mark version as deleted
 * @param int $version_id
 */
function dms_db_delete_version(int $version_id): void {
    global $DMS_DB;
    $stmt = $DMS_DB->prepare('UPDATE dms_document_versions SET is_deleted = 1 WHERE version_id = :id');
    $stmt->execute(['id' => $version_id]);
}

/**
 * Add object to deletion queue
 * @param string $bucket
 * @param string $key
 * @param string|null $doc_id
 * @param int|null $version_id
 */
function dms_db_queue_object_deletion(string $bucket, string $key, ?string $doc_id = null, ?int $version_id = null): void {
    global $DMS_DB;
    $stmt = $DMS_DB->prepare('
        INSERT INTO dms_deleted_object_queue (storage_bucket, storage_key, doc_id, version_id)
        VALUES (:bucket, :key, :doc_id, :version_id)
    ');
    $stmt->execute([
        'bucket' => $bucket,
        'key' => $key,
        'doc_id' => $doc_id,
        'version_id' => $version_id,
    ]);
}
