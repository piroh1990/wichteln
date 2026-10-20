-- ====================================
-- Wichtlä.ch - Migration: groups.affiliate_of
-- ====================================
--
-- Schalter für Werbelinks zu Orell Füssli.
-- 0 ist der Standard. Premium-Gruppen bleiben damit aus,
-- bis der Admin der Gruppe den Schalter setzt.
-- Bestehende Zeilen werden nicht verändert, ausser dass
-- die neue Spalte mit 0 dazukommt.
--
-- Idempotent: existiert die Spalte schon, passiert nichts.
--
-- phpMyAdmin:
--   1. Links die Live-Datenbank auswählen (dieses Skript enthält kein USE).
--   2. Register „SQL“ öffnen.
--   3. Den kompletten Inhalt einfügen und ausführen.
--   Mehrfach ausführen ist unkritisch.
--
-- Kommandozeile:
--   mysql -u DEIN_USER -p DEINE_DATENBANK < database/migrations/20261020_add_affiliate_of.sql
--
-- Vorher ein Backup erstellen.
-- ====================================

SET @db := DATABASE();

SET @has_affiliate_of := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'groups'
      AND COLUMN_NAME = 'affiliate_of'
);

SET @add_affiliate_sql := (
    SELECT CASE
        WHEN @db IS NULL THEN
            'SELECT ''Keine Datenbank ausgewählt. In phpMyAdmin links die Datenbank wählen, dann dieses Skript erneut ausführen.'' AS migration_note'
        WHEN @has_affiliate_of = 0 THEN
            'ALTER TABLE `groups` ADD COLUMN `affiliate_of` TINYINT(1) NOT NULL DEFAULT 0 COMMENT ''Werbelinks zu Orell Füssli. Bei Premium-Gruppen standardmässig aus.'''
        ELSE
            'SELECT ''groups.affiliate_of ist bereits vorhanden. Keine Änderung.'' AS migration_note'
    END
);

PREPARE add_affiliate_stmt FROM @add_affiliate_sql;
EXECUTE add_affiliate_stmt;
DEALLOCATE PREPARE add_affiliate_stmt;
