-- ====================================
-- Wichtlä.ch - Migration: groups.reminder_sent_at
-- ====================================
--
-- Speichert, wann die Erinnerung an die Geschenkübergabe
-- an die Teilnehmer gesendet wurde.
-- NULL bedeutet: noch nie versendet.
-- Ein erneuter Versand ist nur mit ausdrücklicher Bestätigung möglich.
--
-- Idempotent: existiert die Spalte schon, passiert nichts.
-- Es werden keine Zeilen gelöscht oder verändert.
--
-- phpMyAdmin:
--   1. Links die Live-Datenbank auswählen (dieses Skript enthält kein USE).
--   2. Register „SQL“ öffnen.
--   3. Den kompletten Inhalt einfügen und ausführen.
--   Mehrfach ausführen ist unkritisch.
--
-- Kommandozeile:
--   mysql -u DEIN_USER -p DEINE_DATENBANK < database/migrations/20261007_add_reminder_sent_at.sql
--
-- Vorher ein Backup erstellen.
-- ====================================

SET @db := DATABASE();

SET @has_reminder_sent_at := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'groups'
      AND COLUMN_NAME = 'reminder_sent_at'
);

SET @add_reminder_sql := (
    SELECT CASE
        WHEN @db IS NULL THEN
            'SELECT ''Keine Datenbank ausgewählt. In phpMyAdmin links die Datenbank wählen, dann dieses Skript erneut ausführen.'' AS migration_note'
        WHEN @has_reminder_sent_at = 0 THEN
            'ALTER TABLE `groups` ADD COLUMN `reminder_sent_at` DATETIME NULL DEFAULT NULL COMMENT ''Zeitpunkt der Erinnerung an die Geschenkübergabe. NULL = noch nicht gesendet.'''
        ELSE
            'SELECT ''groups.reminder_sent_at ist bereits vorhanden. Keine Änderung.'' AS migration_note'
    END
);

PREPARE add_reminder_stmt FROM @add_reminder_sql;
EXECUTE add_reminder_stmt;
DEALLOCATE PREPARE add_reminder_stmt;

SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'groups'
  AND COLUMN_NAME = 'reminder_sent_at';
