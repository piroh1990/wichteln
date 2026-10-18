-- ====================================
-- Wichtlä.ch - Datenbank & Benutzer Setup
-- ====================================
--
-- WICHTIG: Dieses Skript muss als MySQL root-Benutzer
-- oder mit entsprechenden Rechten ausgeführt werden!
--
-- Verwendung:
-- mysql -u root -p < database/init.sql
-- ====================================

-- ====================================
-- 1. DATENBANK ERSTELLEN
-- ====================================

-- Alte Datenbank löschen (VORSICHT in Produktion!)
-- DROP DATABASE IF EXISTS wichtel_db;

-- Neue Datenbank erstellen
CREATE DATABASE IF NOT EXISTS wichtel_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- ====================================
-- 2. BENUTZER ERSTELLEN
-- ====================================

-- Benutzer erstellen (falls noch nicht vorhanden)
CREATE USER IF NOT EXISTS 'wichtel_db_user'@'localhost' 
IDENTIFIED BY 'CHANGE_THIS_PASSWORD';

-- ====================================
-- 3. RECHTE VERGEBEN
-- ====================================

-- Alle Rechte auf die Datenbank vergeben
GRANT ALL PRIVILEGES ON wichtel_db.* 
TO 'wichtel_db_user'@'localhost';

-- Rechte aktualisieren
FLUSH PRIVILEGES;

-- ====================================
-- 4. PRÜFUNG
-- ====================================

-- Datenbank anzeigen
SHOW DATABASES LIKE 'wichtel_db';

-- Benutzer anzeigen
SELECT User, Host FROM mysql.user WHERE User = 'wichtel_db_user';

-- Rechte anzeigen
SHOW GRANTS FOR 'wichtel_db_user'@'localhost';

-- ====================================
-- 5. ERFOLGSMELDUNG
-- ====================================

SELECT 'Datenbank und Benutzer erfolgreich erstellt!' AS Status;
SELECT 'Nächster Schritt: mysql -u wichtel_db_user -p wichtel_db < database/setup.sql' AS Hinweis;

-- ====================================
-- HINWEISE
-- ====================================
-- 
-- 1. Ändere 'CHANGE_THIS_PASSWORD' zu einem sicheren Passwort
-- 2. Verwende das gleiche Passwort in config.php
-- 3. Führe danach setup.sql aus um die Tabellen zu erstellen
-- 
-- Sicheres Passwort generieren (Linux/macOS):
-- openssl rand -base64 32
-- 
-- Oder in PHP:
-- php -r "echo bin2hex(random_bytes(16));"
-- ====================================

-- Sprachspalten groups.language und participants.language legt database/setup.sql an.
-- Bestehende Datenbanken: database/migrations/20261018_add_language.sql einmal ausführen.
