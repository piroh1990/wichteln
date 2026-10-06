# Datenbank-Setup

Dieses Verzeichnis enthält alle SQL-Skripte für die Datenbank-Einrichtung.

## 📁 Dateien

- **`init.sql`** - Erstellt Datenbank und Benutzer (als root ausführen)
- **`setup.sql`** - Erstellt alle Tabellen und Indizes (Neuanlage)
- **`migration_*.sql`** - Ältere, ergänzende Migrationen
- **`migrations/`** - Idempotente Migrationen für bestehende Datenbanken (`participant_token`, `groups.reveal_sent_at`)
- **`backups/`** - Verzeichnis für Datenbank-Backups (nicht im Repository)

Die Teilnehmerspalte heisst `participant_token`. Bestehende Datenbanken, die noch `participants.token` haben, mit `migrations/20261006_rename_participant_token.sql` umbenennen. Die Migration löscht keine Daten und ist bei wiederholtem Ausführen wirkungslos. Siehe `INSTALL.md`.

## 🚀 Schnellstart

### 1. Datenbank und Benutzer erstellen

```bash
# Als MySQL root-Benutzer ausführen
mysql -u root -p < database/init.sql
```

**Wichtig:** Ändere zuerst `CHANGE_THIS_PASSWORD` in `init.sql` zu einem sicheren Passwort!

### 2. Tabellen erstellen

```bash
# Als wichtel_db_user ausführen
mysql -u wichtel_db_user -p wichtel_db < database/setup.sql
```

### 3. Passwort generieren

```bash
# Sicheres Passwort generieren (Linux/macOS)
openssl rand -base64 32

# Oder mit PHP
php -r "echo bin2hex(random_bytes(16));"
```

## 📊 Datenbank-Struktur

### Groups
- Speichert alle Wichtel-Gruppen
- Enthält Admin- und Invite-Tokens
- Optional: Budget, Beschreibung, Datum
- `reveal_sent_at`: Zeitpunkt der Auflösungs-Mail (NULL = noch nicht versendet). Bestehende Datenbanken: `migrations/20261006_add_reveal_sent_at.sql`

### Participants
- Speichert alle Teilnehmer
- Verknüpft mit Gruppen
- Optional: E-Mail, Wunschliste
- Enthält Zuordnung (assigned_to)

### Exclusions
- Speichert Ausschlüsse
- Verhindert bestimmte Paarungen
- Z.B. Paare können sich nicht gegenseitig wichteln

## 🔄 Backup & Restore

### Backup erstellen

```bash
# Komplettes Backup
mysqldump -u wichtel_db_user -p wichtel_db > database/backups/backup_$(date +%Y%m%d_%H%M%S).sql

# Nur Struktur
mysqldump -u wichtel_db_user -p --no-data wichtel_db > database/backups/structure_only.sql

# Nur Daten
mysqldump -u wichtel_db_user -p --no-create-info wichtel_db > database/backups/data_only.sql

# Mit Kompression
mysqldump -u wichtel_db_user -p wichtel_db | gzip > database/backups/backup_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Backup wiederherstellen

```bash
# Aus SQL-Datei
mysql -u wichtel_db_user -p wichtel_db < database/backups/backup_20251010_123456.sql

# Aus komprimierter Datei
gunzip < database/backups/backup_20251010_123456.sql.gz | mysql -u wichtel_db_user -p wichtel_db
```

### Automatisches Backup (Cron)

```bash
# Crontab bearbeiten
crontab -e

# Tägliches Backup um 3 Uhr nachts
0 3 * * * /usr/bin/mysqldump -u wichtel_db_user -pPASSWORT wichtel_db | gzip > /pfad/zu/wichtel/database/backups/backup_$(date +\%Y\%m\%d).sql.gz

# Alte Backups löschen (älter als 30 Tage)
0 4 * * * find /pfad/zu/wichtel/database/backups/ -name "*.sql.gz" -mtime +30 -delete
```

## 🛠 Nützliche SQL-Befehle

### Datenbank-Informationen

```sql
-- Alle Gruppen anzeigen
SELECT * FROM groups;

-- Teilnehmer einer Gruppe
SELECT p.* FROM participants p 
JOIN groups g ON p.group_id = g.id 
WHERE g.name = 'Gruppenname';

-- Ausschlüsse anzeigen
SELECT 
    g.name as gruppe,
    p1.name as person,
    p2.name as ausgeschlossen
FROM exclusions e
JOIN groups g ON e.group_id = g.id
JOIN participants p1 ON e.participant_id = p1.id
JOIN participants p2 ON e.excluded_participant_id = p2.id;

-- Statistiken
SELECT 
    COUNT(DISTINCT g.id) as anzahl_gruppen,
    COUNT(DISTINCT p.id) as anzahl_teilnehmer,
    COUNT(DISTINCT e.id) as anzahl_ausschluesse
FROM groups g
LEFT JOIN participants p ON g.id = p.group_id
LEFT JOIN exclusions e ON g.id = e.group_id;
```

### Datenbank-Wartung

```sql
-- Tabellen optimieren
OPTIMIZE TABLE groups, participants, exclusions;

-- Tabellen-Größe anzeigen
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.TABLES
WHERE table_schema = 'wichtel_db';

-- Index-Nutzung prüfen
SHOW INDEX FROM participants;
```

### Daten bereinigen

```sql
-- Alte Gruppen löschen (älter als 1 Jahr)
DELETE FROM groups WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Gruppen ohne Teilnehmer
SELECT g.* FROM groups g
LEFT JOIN participants p ON g.id = p.group_id
WHERE p.id IS NULL;

-- Verwaiste Ausschlüsse (sollte durch CASCADE nicht vorkommen)
SELECT e.* FROM exclusions e
LEFT JOIN participants p ON e.participant_id = p.id
WHERE p.id IS NULL;
```

## 🔒 Sicherheit

### Berechtigungen einschränken

Für Produktion nur minimale Rechte vergeben:

```sql
-- Alte Rechte entfernen
REVOKE ALL PRIVILEGES ON wichtel_db.* FROM 'wichtel_db_user'@'localhost';

-- Nur notwendige Rechte vergeben
GRANT SELECT, INSERT, UPDATE, DELETE 
ON wichtel_db.* 
TO 'wichtel_db_user'@'localhost';

FLUSH PRIVILEGES;
```

### Passwort ändern

```sql
ALTER USER 'wichtel_db_user'@'localhost' 
IDENTIFIED BY 'NEUES_SICHERES_PASSWORT';

FLUSH PRIVILEGES;
```

## 📊 Performance-Optimierung

### Indizes prüfen

```sql
-- Fehlende Indizes finden
SELECT * FROM sys.schema_unused_indexes;

-- Langsame Queries loggen
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

### Query-Performance

```sql
-- Explain verwenden
EXPLAIN SELECT * FROM participants WHERE group_id = 1;

-- Profiling aktivieren
SET profiling = 1;
SELECT * FROM participants WHERE group_id = 1;
SHOW PROFILES;
```

## 🚨 Fehlerbehebung

### Verbindungsfehler

```bash
# MySQL Status prüfen
sudo systemctl status mysql

# MySQL starten
sudo systemctl start mysql
```

### Berechtigungsprobleme

```sql
-- Rechte prüfen
SHOW GRANTS FOR 'wichtel_db_user'@'localhost';

-- Rechte neu vergeben
GRANT ALL PRIVILEGES ON wichtel_db.* TO 'wichtel_db_user'@'localhost';
FLUSH PRIVILEGES;
```

### Zeichensatz-Probleme

```sql
-- Zeichensatz prüfen
SHOW VARIABLES LIKE 'character_set%';

-- Auf UTF8MB4 umstellen
ALTER DATABASE wichtel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE groups CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 📚 Weitere Ressourcen

- [MySQL Dokumentation](https://dev.mysql.com/doc/)
- [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [mysqldump Dokumentation](https://dev.mysql.com/doc/refman/8.0/en/mysqldump.html)

---

**Immer Backups erstellen vor größeren Änderungen! 🔒**
