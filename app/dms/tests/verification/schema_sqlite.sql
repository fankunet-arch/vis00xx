-- DMS SQLite Schema

-- Users
CREATE TABLE IF NOT EXISTS `dms_users` (
  `user_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `username` TEXT NOT NULL UNIQUE,
  `email` TEXT NOT NULL UNIQUE,
  `password_hash` TEXT NOT NULL,
  `full_name` TEXT NULL,
  `role` TEXT NOT NULL DEFAULT 'viewer',
  `org_id` INTEGER NOT NULL DEFAULT 1,
  `is_active` INTEGER NOT NULL DEFAULT 1,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Sessions
CREATE TABLE IF NOT EXISTS `dms_user_sessions` (
  `session_id` TEXT PRIMARY KEY,
  `user_id` INTEGER NOT NULL,
  `ip_address` TEXT NULL,
  `user_agent` TEXT NULL,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TEXT NOT NULL
);

-- Categories
CREATE TABLE IF NOT EXISTS `dms_categories` (
  `category_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `org_id` INTEGER NOT NULL DEFAULT 1,
  `name` TEXT NOT NULL,
  `description` TEXT NULL,
  `schema_json` TEXT NULL,
  `is_active` INTEGER NOT NULL DEFAULT 1,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE(org_id, name)
);

-- Documents
CREATE TABLE IF NOT EXISTS `dms_documents` (
  `doc_id` TEXT PRIMARY KEY,
  `org_id` INTEGER NOT NULL DEFAULT 1,
  `category_id` INTEGER NULL,
  `title` TEXT NOT NULL,
  `description` TEXT NULL,
  `tags` TEXT NULL,
  `attributes_json` TEXT NULL,
  `current_version_id` INTEGER NULL,
  `status` TEXT NOT NULL DEFAULT 'active',
  `created_by` INTEGER NOT NULL,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Document Versions
CREATE TABLE IF NOT EXISTS `dms_document_versions` (
  `version_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `doc_id` TEXT NOT NULL,
  `version_no` INTEGER NOT NULL,
  `upload_mode` TEXT NOT NULL,
  `storage_bucket` TEXT NOT NULL,
  `storage_key` TEXT NOT NULL,
  `original_file_name` TEXT NOT NULL,
  `stored_file_name` TEXT NOT NULL,
  `file_ext` TEXT NOT NULL,
  `mime_type` TEXT NOT NULL,
  `file_size` INTEGER NOT NULL,
  `sha256` TEXT NOT NULL,
  `etag` TEXT NULL,
  `is_current` INTEGER NOT NULL DEFAULT 0,
  `is_deleted` INTEGER NOT NULL DEFAULT 0,
  `uploaded_by` INTEGER NOT NULL,
  `uploaded_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` TEXT NULL,
  UNIQUE(doc_id, version_no)
);

-- Audit Log
CREATE TABLE IF NOT EXISTS `dms_audit_log` (
  `log_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `user_id` INTEGER NULL,
  `org_id` INTEGER NOT NULL DEFAULT 1,
  `action` TEXT NOT NULL,
  `entity_type` TEXT NULL,
  `entity_id` TEXT NULL,
  `doc_id` TEXT NULL,
  `version_id` INTEGER NULL,
  `ip_address` TEXT NULL,
  `user_agent` TEXT NULL,
  `details_json` TEXT NULL,
  `created_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Deleted Object Queue
CREATE TABLE IF NOT EXISTS `dms_deleted_object_queue` (
  `queue_id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `storage_bucket` TEXT NOT NULL,
  `storage_key` TEXT NOT NULL,
  `doc_id` TEXT NULL,
  `version_id` INTEGER NULL,
  `deleted_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed` INTEGER NOT NULL DEFAULT 0,
  `processed_at` TEXT NULL,
  `error_message` TEXT NULL
);

-- Schema Version
CREATE TABLE IF NOT EXISTS `dms_schema_version` (
  `version` TEXT PRIMARY KEY,
  `applied_at` TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Seed Data
INSERT OR IGNORE INTO `dms_users`
  (`username`, `email`, `password_hash`, `full_name`, `role`, `org_id`, `is_active`)
VALUES
  ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 1, 1);

INSERT OR IGNORE INTO `dms_categories`
  (`org_id`, `name`, `description`, `schema_json`)
VALUES
  (1, 'General Documents', 'General purpose document storage', '{"fields":[{"name":"department","type":"text","required":false},{"name":"priority","type":"enum","required":false,"options":["low","medium","high"]}]}'),
  (1, 'Contracts', 'Legal contracts and agreements', '{"fields":[{"name":"contract_date","type":"date","required":true},{"name":"counterparty","type":"text","required":true},{"name":"amount","type":"number","required":false},{"name":"status","type":"enum","required":true,"options":["draft","active","expired"]}]}'),
  (1, 'Invoices', 'Financial invoices', '{"fields":[{"name":"invoice_number","type":"text","required":true},{"name":"invoice_date","type":"date","required":true},{"name":"amount","type":"number","required":true},{"name":"paid","type":"bool","required":false}]}')
;

INSERT OR IGNORE INTO `dms_schema_version` (`version`) VALUES ('1.0');
