-- =====================================================
-- DMS Archive System - Schema Upgrade Template
-- Use this template for future schema upgrades
-- =====================================================

-- Example: Upgrade from v1.0 to v1.1
-- Uncomment and modify as needed

-- SET NAMES utf8mb4;
-- SET TIME_ZONE = '+00:00';

-- ALTER TABLE `dms_documents` ADD COLUMN `new_field` VARCHAR(100) NULL AFTER `tags`;

-- UPDATE `dms_schema_version` SET `version` = '1.1' WHERE `version` = '1.0';
-- INSERT INTO `dms_schema_version` (`version`) VALUES ('1.1') ON DUPLICATE KEY UPDATE `version`=`version`;
