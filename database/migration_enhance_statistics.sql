-- ====================================
-- Wichtlä.ch - Migration: Enhance Statistics
-- ====================================
--
-- Adds group_name column to group_statistics table
-- so archived groups can be identified by name.
--
-- Usage: mysql -u root -p wichtel_db < database/migration_enhance_statistics.sql
-- ====================================

USE wichtel_db;

ALTER TABLE `group_statistics`
  ADD COLUMN `group_name` VARCHAR(255) NULL COMMENT 'Name der ursprünglichen Gruppe'
  AFTER `original_group_id`;

SELECT 'Migration erfolgreich: group_name Spalte zu group_statistics hinzugefügt.' AS Status;
