-- ====================================
-- Wichtlä.ch - Migration: Premium-Lizenzen
-- ====================================
--
-- Legt premium_licenses und premium_confirmations an und ergänzt
-- groups.premium_license_id. Bestehende Gruppen bleiben unverändert
-- (die Spalte ist NULL). Es gibt keinen Fremdschlüssel von groups
-- auf die Lizenz, damit eine Lizenz keine Gruppe mitlöscht.
--
-- Idempotent: mehrfach ausführen ändert nichts, was schon da ist.
--
-- phpMyAdmin:
--   1. Links die Live-Datenbank auswählen (dieses Skript enthält kein USE).
--   2. Register „SQL“ öffnen.
--   3. Den kompletten Inhalt einfügen und ausführen.
--
-- Kommandozeile:
--   mysql -u DEIN_USER -p DEINE_DATENBANK < database/migrations/20261019_premium_licenses.sql
--
-- Vorher ein Backup erstellen.
-- ====================================

SET @db := DATABASE();

CREATE TABLE IF NOT EXISTS `premium_licenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tier` VARCHAR(32) NOT NULL COMMENT 'verein, firma oder firma_plus',
  `status` VARCHAR(16) NOT NULL DEFAULT 'pending' COMMENT 'pending, active oder revoked',
  `buyer_email` VARCHAR(255) NOT NULL COMMENT 'E-Mail der kaufenden Person',
  `company_name` VARCHAR(255) NULL,
  `company_address` TEXT NULL,
  `contact_name` VARCHAR(255) NULL,
  `domain` VARCHAR(255) NULL COMMENT 'Optionale Domain-Lizenz, exakt, ohne Subdomains',
  `code_hash` CHAR(64) NULL COMMENT 'sha256 des Lizenzcodes, NULL bis zur Aktivierung',
  `access_token` VARCHAR(64) NULL COMMENT 'Klartext-Token der Übersicht, NULL bis zur Aktivierung',
  `greeting` TEXT NULL,
  `logo_file` VARCHAR(64) NULL,
  `color_primary` VARCHAR(7) NULL,
  `color_accent` VARCHAR(7) NULL,
  `stripe_session_id` VARCHAR(255) NULL,
  `stripe_event_id` VARCHAR(255) NULL,
  `season_year` SMALLINT NOT NULL,
  `language` VARCHAR(5) NOT NULL DEFAULT 'de',
  `created_at` DATETIME NOT NULL,
  `activated_at` DATETIME NULL,
  `notified_at` DATETIME NULL,
  UNIQUE KEY `uniq_premium_code_hash` (`code_hash`),
  UNIQUE KEY `uniq_premium_access_token` (`access_token`),
  UNIQUE KEY `uniq_premium_stripe_session` (`stripe_session_id`),
  INDEX `idx_premium_buyer_email` (`buyer_email`),
  INDEX `idx_premium_domain` (`domain`),
  INDEX `idx_premium_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Premium-Lizenzen pro Saison';

CREATE TABLE IF NOT EXISTS `premium_confirmations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `license_id` INT NOT NULL,
  `group_id` INT NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `purpose` VARCHAR(32) NOT NULL,
  `token_hash` CHAR(64) NOT NULL,
  `code_hash` CHAR(64) NOT NULL,
  `language` VARCHAR(5) NOT NULL DEFAULT 'de',
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL,
  `consumed_at` DATETIME NULL,
  INDEX `idx_premium_confirm_token` (`token_hash`),
  INDEX `idx_premium_confirm_group` (`group_id`),
  CONSTRAINT `fk_premium_confirm_license` FOREIGN KEY (`license_id`) REFERENCES `premium_licenses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='E-Mail-Bestätigung, bevor eine Gruppe eine Lizenz beansprucht';

SET @has_premium_license_id := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'groups'
      AND COLUMN_NAME = 'premium_license_id'
);

SET @add_premium_sql := (
    SELECT CASE
        WHEN @db IS NULL THEN
            'SELECT ''Keine Datenbank ausgewählt. In phpMyAdmin links die Datenbank wählen, dann dieses Skript erneut ausführen.'' AS migration_note'
        WHEN @has_premium_license_id = 0 THEN
            'ALTER TABLE `groups` ADD COLUMN `premium_license_id` INT NULL DEFAULT NULL COMMENT ''Premium-Lizenz. NULL = normale Gruppe.'''
        ELSE
            'SELECT ''groups.premium_license_id ist bereits vorhanden. Keine Änderung.'' AS migration_note'
    END
);

PREPARE add_premium_stmt FROM @add_premium_sql;
EXECUTE add_premium_stmt;
DEALLOCATE PREPARE add_premium_stmt;

SET @has_premium_index := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'groups'
      AND INDEX_NAME = 'idx_premium_license'
);

SET @add_premium_index_sql := (
    SELECT CASE
        WHEN @db IS NULL THEN
            'SELECT ''Keine Datenbank ausgewählt.'' AS migration_note'
        WHEN @has_premium_index = 0 THEN
            'ALTER TABLE `groups` ADD INDEX `idx_premium_license` (`premium_license_id`)'
        ELSE
            'SELECT ''idx_premium_license ist bereits vorhanden. Keine Änderung.'' AS migration_note'
    END
);

PREPARE add_premium_index_stmt FROM @add_premium_index_sql;
EXECUTE add_premium_index_stmt;
DEALLOCATE PREPARE add_premium_index_stmt;

SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'groups'
  AND COLUMN_NAME = 'premium_license_id';
