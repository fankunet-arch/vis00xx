-- =====================================================
-- DMS Archive System - Database Migration v1.1
-- Add Project Management Feature
-- =====================================================
-- Description:
--   Adds project categorization to documents
--   Projects can be managed in settings (add/edit/delete)
--   Projects with active documents cannot be deleted
-- =====================================================

SET NAMES utf8mb4;
SET TIME_ZONE = '+00:00';
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- Table: dms_projects
-- Purpose: Project categories for document organization
-- =====================================================
CREATE TABLE IF NOT EXISTS `dms_projects` (
  `project_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `org_id` INT UNSIGNED NOT NULL DEFAULT 1,
  `name` VARCHAR(100) NOT NULL COMMENT 'Project name (e.g., "A项目", "B项目")',
  `code` VARCHAR(50) NULL COMMENT 'Optional project code (e.g., "PROJ-A", "PROJ-B")',
  `description` TEXT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active, archived, closed',
  `is_active` TINYINT NOT NULL DEFAULT 1,
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `updated_at` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
  PRIMARY KEY (`project_id`),
  UNIQUE KEY `idx_org_name` (`org_id`, `name`),
  KEY `idx_org_active` (`org_id`, `is_active`),
  KEY `idx_status` (`status`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Project categories for document organization';

-- =====================================================
-- Alter: dms_documents
-- Add project_id column to link documents to projects
-- =====================================================
ALTER TABLE `dms_documents`
  ADD COLUMN `project_id` INT UNSIGNED NULL COMMENT 'Related project ID' AFTER `category_id`,
  ADD KEY `idx_project` (`project_id`),
  ADD KEY `idx_org_project` (`org_id`, `project_id`);

-- =====================================================
-- Alter: dms_audit_log
-- Update entity_type comment to include 'project'
-- =====================================================
ALTER TABLE `dms_audit_log`
  MODIFY COLUMN `entity_type` VARCHAR(50) NULL COMMENT 'document, version, category, user, project';

-- =====================================================
-- Sample Projects (Optional)
-- =====================================================
INSERT INTO `dms_projects`
  (`org_id`, `name`, `code`, `description`, `status`)
VALUES
  (1, 'A项目', 'PROJ-A', '示例项目A', 'active'),
  (1, 'B项目', 'PROJ-B', '示例项目B', 'active')
ON DUPLICATE KEY UPDATE `project_id`=`project_id`;

-- =====================================================
-- Update Schema Version
-- =====================================================
INSERT INTO `dms_schema_version` (`version`) VALUES ('1.1')
ON DUPLICATE KEY UPDATE `version`='1.1';

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Migration Notes:
-- =====================================================
-- 1. Existing documents will have project_id=NULL (no project assigned)
-- 2. Projects can be assigned when uploading new documents or editing existing ones
-- 3. Project deletion is protected in application layer (not database constraint)
-- 4. This migration is safe to run multiple times (idempotent)
-- =====================================================
