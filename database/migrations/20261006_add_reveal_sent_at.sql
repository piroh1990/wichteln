-- ====================================
-- Wichtlä.ch - Migration: groups.reveal_sent_at
-- ====================================
--
-- Speichert, wann der Admin die Auflösung (komplette Liste
-- Geber -> Beschenkter) an alle Teilnehmer gesendet hat.
-- NULL bedeutet: noch nie versendet.
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
--   mysql -u DEIN_USER -p DEINE_DATENBANK < database/migrations/20261006_add_reveal_sent_at.sql
--
-- Vorher ein Backup erstellen.
-- ====================================

SET @db := DATABASE();

SET @has_reveal_sent_at := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'groups'
      AND COLUMN_NAME = 'reveal_sent_at'
);

SET @add_reveal_sql := (
    SELECT CASE
        WHEN @db IS NULL THEN
            'SELECT ''Keine Datenbank ausgewählt. In phpMyAdmin links die Datenbank wählen, dann dieses Skript erneut ausführen.'' AS migration_note'
        WHEN @has_reveal_sent_at = 0 THEN
            'ALTER TABLE `groups` ADD COLUMN `reveal_sent_at` DATETIME NULL DEFAULT NULL COMMENT ''Zeitpunkt, zu dem die Auflösung an alle Teilnehmer gesendet wurde'' AFTER `is_drawn`'
        ELSE
            'SELECT ''groups.reveal_sent_at ist bereits vorhanden. Keine Änderung.'' AS migration_note'
    END
);

PREPARE add_reveal_stmt FROM @add_reveal_sql;
EXECUTE add_reveal_stmt;
DEALLOCATE PREPARE add_reveal_stmt;

SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'groups'
  AND COLUMN_NAME = 'reveal_sent_at';
