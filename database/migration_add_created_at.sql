-- ====================================
-- Wichtlä.ch - Migration: Add created_at
-- ====================================
--
-- Adds the missing 'created_at' column to the 'groups' table.
-- This column is required for the cleanup script to identify
-- old, abandoned groups.
--
-- Usage: mysql -u root -p wichtel_db < database/migration_add_created_at.sql
-- ====================================

USE wichtel_db;

-- ====================================
-- GROUPS TABELLE AKTUALISIEREN
-- ====================================

-- 1. Füge Spalte created_at hinzu, falls sie nicht existiert
-- (MySQL hat kein direktes "IF NOT EXISTS" für ADD COLUMN in älteren Versionen,
-- aber wir können versuchen, sie hinzuzufügen. Wenn sie existiert, schlägt es fehl,
-- was in Ordnung ist, solange wir den Fehler ignorieren oder prozedural lösen.)

SET @dbname = DATABASE();
SET @tablename = "groups";
SET @columnname = "created_at";

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column created_at already exists' AS Status",
  "ALTER TABLE `groups` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Erstellungszeitpunkt'"
));

PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Index hinzufügen (für Performance bei Cleanup)
-- Auch hier prüfen wir zuerst, ob der Index existiert.

SET @indexname = "idx_created_at";

SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (index_name = @indexname)
  ) > 0,
  "SELECT 'Index idx_created_at already exists' AS Status",
  "CREATE INDEX idx_created_at ON `groups`(`created_at`)"
));

PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ====================================
-- STATUS ANZEIGEN
-- ====================================

DESCRIBE `groups`;
SELECT 'Migration erfolgreich: created_at Spalte geprüft/hinzugefügt.' AS Status;
