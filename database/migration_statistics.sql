-- ====================================
-- Wichtlä.ch - Migration Statistics
-- ====================================
--
-- Adds the group_statistics table to store anonymized data
-- from deleted groups.
--
-- Usage: mysql -u root -p wichtel_db < database/migration_statistics.sql
-- ====================================

USE wichtel_db;

-- ====================================
-- GROUP STATISTICS TABELLE
-- ====================================
-- Speichert anonymisierte Statistiken gelöschter Gruppen

CREATE TABLE IF NOT EXISTS `group_statistics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `original_group_id` INT NULL COMMENT 'ID der ursprünglichen Gruppe (optional)',
  `participant_count` INT DEFAULT 0 COMMENT 'Anzahl der Teilnehmer',
  `participant_with_email_count` INT DEFAULT 0 COMMENT 'Anzahl der Teilnehmer mit E-Mail',
  `exclusion_count` INT DEFAULT 0 COMMENT 'Anzahl der Ausschlüsse',
  `budget` DECIMAL(10,2) NULL COMMENT 'Budget der Gruppe',
  `gift_exchange_date` DATE NULL COMMENT 'Datum der Geschenkübergabe',
  `is_drawn` TINYINT(1) DEFAULT 0 COMMENT 'War die Gruppe ausgelost?',
  `created_at` TIMESTAMP NULL COMMENT 'Wann wurde die Gruppe erstellt',
  `archived_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Wann wurde die Gruppe archiviert'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Anonymisierte Statistiken gelöschter Gruppen';

SELECT 'Migration erfolgreich: group_statistics Tabelle erstellt.' AS Status;
