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
  `budget` DECIMAL(10,2) NULL COMMENT 'Budget für Geschenke (optional)',
  `description` TEXT NULL COMMENT 'Beschreibung der Gruppe (optional)',
  `gift_exchange_date` DATE NULL COMMENT 'Datum der Geschenkübergabe (optional)',
  `is_drawn` TINYINT(1) DEFAULT 0 COMMENT 'Wurde bereits ausgelost? (0=Nein, 1=Ja)',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Erstellungszeitpunkt',
  
  INDEX idx_admin_token (`admin_token`),
  INDEX idx_invite_token (`invite_token`),
  INDEX idx_created_at (`created_at`)
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
  `token` VARCHAR(64) NOT NULL UNIQUE COMMENT 'Persönlicher Zugangs-Token',
  `assigned_to` INT NULL COMMENT 'ID des zugewiesenen Wichtelpartners',
  `wishlist` TEXT NULL COMMENT 'Wunschliste des Teilnehmers',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Registrierungszeitpunkt',
  
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `participants`(`id`) ON DELETE SET NULL,
  
  INDEX idx_participant_token (`token`),
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
INSERT INTO `participants` (`group_id`, `name`, `email`, `token`, `wishlist`) VALUES
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
