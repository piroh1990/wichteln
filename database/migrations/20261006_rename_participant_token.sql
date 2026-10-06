-- ====================================
-- Wichtlä.ch - Migration: participant_token
-- ====================================
--
-- Die laufende Web-Oberfläche (register.php, participant.php, admin.php)
-- speichert den persönlichen Zugang in der Spalte `participants.participant_token`.
-- database/setup.sql und die API haben dieselbe Spalte zeitweise `token` genannt.
--
-- Diese Migration gleicht eine bestehende Datenbank an, ohne Daten zu löschen:
--   * Spalte heisst bereits participant_token  -> es passiert nichts
--   * Spalte heisst noch token                  -> sie wird umbenannt, Typ bleibt
--   * beide Spalten existieren                  -> es wird nichts verändert
--
-- Mehrfach ausführen ist unkritisch.
--
-- Aufruf gegen die LIVE-Datenbank (Name anpassen, kein festes USE):
--   mysql -u DEIN_USER -p DEINE_DATENBANK < database/migrations/20261006_rename_participant_token.sql
--
-- Vorher ein Backup erstellen.
-- ====================================

-- 1. Ausgangslage prüfen
SET @db := DATABASE();

SET @has_token := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'participants'
      AND COLUMN_NAME = 'token'
);

SET @has_participant_token := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'participants'
      AND COLUMN_NAME = 'participant_token'
);

-- Typ und NULL-Eigenschaft der alten Spalte übernehmen, damit nichts abgeschnitten wird.
SET @token_type := (
    SELECT COLUMN_TYPE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'participants'
      AND COLUMN_NAME = 'token'
);

SET @token_nullable := (
    SELECT IS_NULLABLE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'participants'
      AND COLUMN_NAME = 'token'
);

SET @rename_sql := (
    SELECT CASE
        WHEN @db IS NULL THEN
            'SELECT ''Keine Datenbank ausgewählt. Aufruf: mysql ... DEINE_DATENBANK < diese Datei'' AS migration_note'
        WHEN @has_token = 1 AND @has_participant_token = 0 THEN
            CONCAT(
                'ALTER TABLE `participants` CHANGE COLUMN `token` `participant_token` ',
                @token_type,
                IF(@token_nullable = 'YES', ' NULL', ' NOT NULL'),
                ' COMMENT ''Persönlicher Zugangs-Token'''
            )
        WHEN @has_token = 1 AND @has_participant_token = 1 THEN
            'SELECT ''Sowohl token als auch participant_token existieren. Es wurde nichts geändert, bitte manuell prüfen.'' AS migration_note'
        WHEN @has_participant_token = 1 THEN
            'SELECT ''participants.participant_token ist bereits vorhanden. Keine Umbenennung nötig.'' AS migration_note'
        ELSE
            'SELECT ''Weder token noch participant_token gefunden. Schema bitte prüfen.'' AS migration_note'
    END
);

PREPARE rename_stmt FROM @rename_sql;
EXECUTE rename_stmt;
DEALLOCATE PREPARE rename_stmt;

-- 2. Index sicherstellen, falls die Spalte participant_token existiert und der Index fehlt.
SET @has_participant_token := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'participants'
      AND COLUMN_NAME = 'participant_token'
);

SET @has_index := (
    SELECT COUNT(*)
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'participants'
      AND INDEX_NAME = 'idx_participant_token'
);

SET @index_sql := (
    SELECT IF(
        @has_participant_token = 1 AND @has_index = 0,
        'ALTER TABLE `participants` ADD INDEX `idx_participant_token` (`participant_token`)',
        'SELECT ''Index idx_participant_token vorhanden oder Spalte participant_token fehlt.'' AS migration_note'
    )
);

PREPARE index_stmt FROM @index_sql;
EXECUTE index_stmt;
DEALLOCATE PREPARE index_stmt;

SELECT
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'participants'
  AND COLUMN_NAME IN ('token', 'participant_token');
