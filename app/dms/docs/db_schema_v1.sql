-- =====================================================
-- DMS Archive System - Database Schema v1.0
-- All tables prefixed with 'dms_'
-- PHP 8.4 + MariaDB 10
-- =====================================================

SET NAMES utf8mb4;
SET TIME_ZONE = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- Table: dms_users
-- Purpose: User accounts with role-based access
-- =====================================================
CREATE TABLE IF NOT EXISTS `dms_users` (
  `user_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255) NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'viewer' COMMENT 'admin, editor, viewer',
  `org_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `is_active` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `idx_username` (`username`),
  UNIQUE KEY `idx_email` (`email`),
  KEY `idx_org_active` (`org_id`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: dms_user_sessions
-- Purpose: Track user sessions for security
-- =====================================================
CREATE TABLE IF NOT EXISTS `dms_user_sessions` (
  `session_id` VARCHAR(128) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `last_activity` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  `expires_at` DATETIME(6) NOT NULL,
  PRIMARY KEY (`session_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: dms_categories
-- Purpose: Document categories with schema_json for attributes
-- =====================================================
CREATE TABLE IF NOT EXISTS `dms_categories` (
  `category_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `org_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `schema_json` JSON NULL COMMENT 'Field definitions: text/number/date/enum/bool',
  `is_active` TINYINT NOT NULL DEFAULT 1,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `idx_org_name` (`org_id`, `name`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: dms_documents
-- Purpose: Main document registry (UUID based)
-- =====================================================
CREATE TABLE IF NOT EXISTS `dms_documents` (
  `doc_id` CHAR(36) NOT NULL COMMENT 'UUID v4',
  `org_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `category_id` INT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `tags` VARCHAR(500) NULL COMMENT 'Comma-separated tags',
  `attributes_json` JSON NULL COMMENT 'Category-specific attributes',
  `current_version_id` BIGINT UNSIGNED NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active, archived, deleted',
  `created_by` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`doc_id`),
  KEY `idx_org_category` (`org_id`, `category_id`),
  KEY `idx_current_version` (`current_version_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: dms_document_versions
-- Purpose: Version history for each document
-- Critical: UNIQUE (doc_id, version_no)
-- =====================================================
CREATE TABLE IF NOT EXISTS `dms_document_versions` (
  `version_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doc_id` CHAR(36) NOT NULL,
  `version_no` INT UNSIGNED NOT NULL,
  `upload_mode` VARCHAR(20) NOT NULL COMMENT 'append, overwrite',
  `storage_bucket` VARCHAR(80) NOT NULL,
  `storage_key` VARCHAR(512) NOT NULL COMMENT 'org/{org_id}/doc/{doc_id}/v/{version_no}/{stored_file_name}',
  `original_file_name` VARCHAR(255) NOT NULL,
  `stored_file_name` VARCHAR(255) NOT NULL,
  `file_ext` VARCHAR(20) NOT NULL,
  `mime_type` VARCHAR(120) NOT NULL,
  `file_size` BIGINT UNSIGNED NOT NULL,
  `sha256` CHAR(64) NOT NULL,
  `etag` VARCHAR(120) NULL COMMENT 'S3 ETag for verification',
  `is_current` TINYINT NOT NULL DEFAULT 0,
  `is_deleted` TINYINT NOT NULL DEFAULT 0,
  `uploaded_by` BIGINT UNSIGNED NOT NULL,
  `uploaded_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `notes` VARCHAR(255) NULL,
  PRIMARY KEY (`version_id`),
  UNIQUE KEY `idx_doc_version` (`doc_id`, `version_no`),
  KEY `idx_doc_current` (`doc_id`, `is_current`),
  KEY `idx_sha256` (`sha256`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_uploaded_at` (`uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: dms_audit_log
-- Purpose: Complete audit trail of all actions
-- =====================================================
CREATE TABLE IF NOT EXISTS `dms_audit_log` (
  `log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `org_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `action` VARCHAR(50) NOT NULL COMMENT 'login, logout, upload, download, preview, update_meta, delete, etc.',
  `entity_type` VARCHAR(50) NULL COMMENT 'document, version, category, user',
  `entity_id` VARCHAR(100) NULL COMMENT 'ID of affected entity',
  `doc_id` CHAR(36) NULL,
  `version_id` BIGINT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(255) NULL,
  `details_json` JSON NULL COMMENT 'Additional context',
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`log_id`),
  KEY `idx_user_action` (`user_id`, `action`),
  KEY `idx_org_action` (`org_id`, `action`),
  KEY `idx_doc` (`doc_id`),
  KEY `idx_version` (`version_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Table: dms_deleted_object_queue
-- Purpose: Track S3 objects pending deletion (optional)
-- Only used if purge_old enabled
-- =====================================================
CREATE TABLE IF NOT EXISTS `dms_deleted_object_queue` (
  `queue_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `storage_bucket` VARCHAR(80) NOT NULL,
  `storage_key` VARCHAR(512) NOT NULL,
  `doc_id` CHAR(36) NULL,
  `version_id` BIGINT UNSIGNED NULL,
  `deleted_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `processed` TINYINT NOT NULL DEFAULT 0,
  `processed_at` DATETIME(6) NULL,
  `error_message` TEXT NULL,
  PRIMARY KEY (`queue_id`),
  KEY `idx_processed` (`processed`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Default Admin User
-- Password: admin123 (MUST be changed after first login)
-- =====================================================
INSERT INTO `dms_users`
  (`username`, `email`, `password_hash`, `full_name`, `role`, `org_id`, `is_active`)
VALUES
  ('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 1, 1)
ON DUPLICATE KEY UPDATE `user_id`=`user_id`;

-- =====================================================
-- Sample Categories
-- =====================================================
INSERT INTO `dms_categories`
  (`org_id`, `name`, `description`, `schema_json`)
VALUES
  (1, 'General Documents', 'General purpose document storage', '{"fields":[{"name":"department","type":"text","required":false},{"name":"priority","type":"enum","required":false,"options":["low","medium","high"]}]}'),
  (1, 'Contracts', 'Legal contracts and agreements', '{"fields":[{"name":"contract_date","type":"date","required":true},{"name":"counterparty","type":"text","required":true},{"name":"amount","type":"number","required":false},{"name":"status","type":"enum","required":true,"options":["draft","active","expired"]}]}'),
  (1, 'Invoices', 'Financial invoices', '{"fields":[{"name":"invoice_number","type":"text","required":true},{"name":"invoice_date","type":"date","required":true},{"name":"amount","type":"number","required":true},{"name":"paid","type":"bool","required":false}]}')
ON DUPLICATE KEY UPDATE `category_id`=`category_id`;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Schema Version Tracking
-- =====================================================
CREATE TABLE IF NOT EXISTS `dms_schema_version` (
  `version` VARCHAR(20) NOT NULL,
  `applied_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `dms_schema_version` (`version`) VALUES ('1.0') ON DUPLICATE KEY UPDATE `version`=`version`;
