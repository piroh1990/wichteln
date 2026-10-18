-- Sprache der Gruppe und der Teilnehmer.
-- In phpMyAdmin links die Datenbank wählen, Register SQL, dieses Skript ausführen.
-- Einmal ausführen. Danach gibt es die Spalten.

ALTER TABLE `groups` ADD COLUMN `language` VARCHAR(5) NOT NULL DEFAULT 'de' AFTER `admin_email`;
ALTER TABLE `participants` ADD COLUMN `language` VARCHAR(5) NOT NULL DEFAULT 'de' AFTER `email`;
