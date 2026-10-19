-- ====================================
-- Wichtlä.ch - Datenbank Setup
-- ====================================
-- 
-- Dieses Skript erstellt alle notwendigen Tabellen
-- für die Wichtlä.ch Applikation
--
-- Voraussetzung: Datenbank wurde bereits erstellt
-- CREATE DATABASE wichtel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- ====================================

USE wichtel_db;

-- ====================================
-- 1. GROUPS TABELLE
-- ====================================
-- Speichert alle Wichtel-Gruppen

CREATE TABLE IF NOT EXISTS `groups` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL COMMENT 'Name der Wichtelgruppe',
  `admin_token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Token für Admin-Zugriff',
  `invite_token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Token für Teilnehmer-Einladung',
  `admin_email` VARCHAR(255) NULL COMMENT 'E-Mail des Gruppenadministrators',
  `language` VARCHAR(5) NOT NULL DEFAULT 'de' COMMENT 'Sprache der Gruppe: de, fr oder en',
  `budget` DECIMAL(10,2) NULL COMMENT 'Budget für Geschenke (optional)',
  `description` TEXT NULL COMMENT 'Beschreibung der Gruppe (optional)',
  `gift_exchange_date` DATE NULL COMMENT 'Datum der Geschenkübergabe (optional)',
  `is_drawn` TINYINT(1) DEFAULT 0 COMMENT 'Wurde bereits ausgelost? (0=Nein, 1=Ja)',
  `reveal_sent_at` DATETIME NULL DEFAULT NULL COMMENT 'Zeitpunkt, zu dem die Auflösung an alle Teilnehmer gesendet wurde',
  `reminder_sent_at` DATETIME NULL DEFAULT NULL COMMENT 'Zeitpunkt der Erinnerung an die Geschenkübergabe. NULL = noch nicht gesendet.',
  `premium_license_id` INT NULL DEFAULT NULL COMMENT 'Premium-Lizenz. NULL = normale Gruppe. Kein Fremdschlüssel, bestehende Gruppen bleiben unverändert.',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Erstellungszeitpunkt',
  
  INDEX idx_admin_token (`admin_token`),
  INDEX idx_invite_token (`invite_token`),
  INDEX idx_created_at (`created_at`),
  INDEX idx_premium_license (`premium_license_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Wichtel-Gruppen';

-- ====================================
-- 2. PARTICIPANTS TABELLE
-- ====================================
-- Speichert alle Teilnehmer

CREATE TABLE IF NOT EXISTS `participants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL COMMENT 'Referenz zur Gruppe',
  `name` VARCHAR(255) NOT NULL COMMENT 'Name des Teilnehmers',
  `email` VARCHAR(255) NULL COMMENT 'E-Mail des Teilnehmers (optional)',
  `language` VARCHAR(5) NOT NULL DEFAULT 'de' COMMENT 'Sprache des Teilnehmers: de, fr oder en',
  `participant_token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Persönlicher Zugangs-Token',
  `assigned_to` INT NULL COMMENT 'ID des zugewiesenen Wichtelpartners',
  `wishlist` TEXT NULL COMMENT 'Wunschliste des Teilnehmers',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Registrierungszeitpunkt',
  
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `participants`(`id`) ON DELETE SET NULL,
  
  INDEX idx_participant_token (`participant_token`),
  INDEX idx_group_participants (`group_id`),
  INDEX idx_assigned_to (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Wichtel-Teilnehmer';

-- ====================================
-- 3. EXCLUSIONS TABELLE
-- ====================================
-- Speichert Ausschlüsse (wer darf wem nicht wichteln)

CREATE TABLE IF NOT EXISTS `exclusions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL COMMENT 'Referenz zur Gruppe',
  `participant_id` INT NOT NULL COMMENT 'Teilnehmer, der ausschließt',
  `excluded_participant_id` INT NOT NULL COMMENT 'Ausgeschlossener Teilnehmer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Erstellungszeitpunkt',
  
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`participant_id`) REFERENCES `participants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`excluded_participant_id`) REFERENCES `participants`(`id`) ON DELETE CASCADE,
  
  UNIQUE KEY `unique_exclusion` (`group_id`, `participant_id`, `excluded_participant_id`),
  INDEX idx_group_exclusions (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Wichtel-Ausschlüsse';

-- ====================================
-- 4. GROUP STATISTICS TABELLE
-- ====================================
-- Speichert anonymisierte Statistiken gelöschter Gruppen

CREATE TABLE IF NOT EXISTS `group_statistics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `original_group_id` INT NULL COMMENT 'ID der ursprünglichen Gruppe (optional)',
  `group_name` VARCHAR(255) NULL COMMENT 'Name der ursprünglichen Gruppe',
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

-- ====================================
-- 5. PREMIUM-LIZENZEN
-- ====================================
-- Eine Lizenz deckt mehrere Gruppen ab. Der Code liegt nur als Hash.

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

-- ====================================
-- TABELLEN ANZEIGEN
-- ====================================

SHOW TABLES;

-- ====================================
-- STRUKTUR PRÜFEN
-- ====================================

DESCRIBE `groups`;
DESCRIBE `participants`;
DESCRIBE `exclusions`;
DESCRIBE `group_statistics`;
DESCRIBE `premium_licenses`;
DESCRIBE `premium_confirmations`;

-- ====================================
-- TEST-DATEN (Optional - für Entwicklung)
-- ====================================
-- Auskommentieren für Produktions-Setup!

/*
-- Test-Gruppe erstellen
INSERT INTO `groups` (`name`, `admin_token`, `invite_token`, `admin_email`, `budget`, `description`, `gift_exchange_date`) 
VALUES (
    'Test Wichtelgruppe',
    'test_admin_token_12345',
    'test_invite_token_67890',
    'admin@test.ch',
    25.00,
    'Dies ist eine Test-Gruppe',
    '2025-12-24'
);

-- Test-Teilnehmer erstellen
INSERT INTO `participants` (`group_id`, `name`, `email`, `participant_token`, `wishlist`) VALUES
(1, 'Max Mustermann', 'max@test.ch', 'token_max_123', 'Bücher, Schokolade'),
(1, 'Anna Beispiel', 'anna@test.ch', 'token_anna_456', 'Tee, Kerzen'),
(1, 'Peter Test', 'peter@test.ch', 'token_peter_789', 'Socken, Kaffee');

-- Test-Ausschluss erstellen (Max kann nicht Anna wichteln)
INSERT INTO `exclusions` (`group_id`, `participant_id`, `excluded_participant_id`) 
VALUES (1, 1, 2);
*/

-- ====================================
-- ERFOLGSMELDUNG
-- ====================================

SELECT 'Datenbank-Setup erfolgreich abgeschlossen!' AS Status;
SELECT COUNT(*) AS Anzahl_Gruppen FROM `groups`;
SELECT COUNT(*) AS Anzahl_Teilnehmer FROM `participants`;
SELECT COUNT(*) AS Anzahl_Ausschluesse FROM `exclusions`;
