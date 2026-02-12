# 🎁 Wichtlä.ch - Online Wichteln leicht gemacht

Eine moderne, benutzerfreundliche Web-Anwendung für die Organisation von Wichtel-Gruppen (Secret Santa). Perfekt für Familien, Freunde und Arbeitskollegen!

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green.svg)

## ✨ Features

### Für Gruppenadministratoren
- 🎯 **Gruppe erstellen** - Einfache Gruppenerstellung mit Captcha-Schutz
- 📧 **Admin-E-Mail** - Automatischer Versand des Admin-Links per E-Mail
- 👥 **Teilnehmerverwaltung** - Teilnehmer hinzufügen, bearbeiten und löschen
- 🚫 **Ausschlüsse definieren** - Bestimme, wer wem nicht wichteln kann (z.B. Paare)
- 🎲 **Intelligente Auslosung** - Automatische Zuordnung unter Berücksichtigung aller Ausschlüsse
- 🔄 **Auslosung zurücksetzen** - Bei Bedarf Auslosung wiederholen
- 🗑️ **Gruppe löschen** - Sichere Löschung mit Warnhinweisen
- 📱 **WhatsApp-Teilen** - Einladungslink direkt per WhatsApp teilen

### Für Teilnehmer
- 📝 **Einfache Registrierung** - Schnelle Anmeldung mit Name und E-Mail
- 🎁 **Wunschliste** - Eigene Wunschliste erfassen und bearbeiten
- 👤 **Partner anzeigen** - Nach Auslosung Wichtelpartner und dessen Wunschliste sehen
- 📬 **E-Mail-Benachrichtigung** - Automatische Benachrichtigung bei Auslosung

### Design & UX
- 🎨 **Modernes Design** - Schöne Farbverläufe und Animationen
- 📱 **Mobile-First** - Vollständig responsive für alle Geräte
- 🌐 **Internationalisierte Domains** - Unterstützung für wichtlä.ch (IDN)
- 📧 **HTML-E-Mails** - Professionelle E-Mail-Templates im Website-Design
- ❄️ **Weihnachtliche Atmosphäre** - Schneefall-Animationen und festliches Design

### Sicherheit
- 🔐 **Token-basierte Authentifizierung** - Sichere Zugriffskontrolle
- 🤖 **Captcha-Schutz** - Bildbasierter Captcha gegen Spam
- 🛡️ **SQL-Injection-Schutz** - Prepared Statements für alle Datenbankzugriffe
- ✅ **Input-Validierung** - Umfassende Validierung aller Benutzereingaben

### API für Mobile Apps
- 🔌 **REST API** - Vollständige REST API für Android/iOS-Apps
- 📱 **JSON-Responses** - Strukturierte JSON-Antworten
- 🔒 **Token-Auth** - Sichere API-Authentifizierung
- 📊 **Rate Limiting** - Schutz vor Missbrauch
- 📝 **API-Dokumentation** - Umfassende Dokumentation unter `/api/README.md`

## 🚀 Installation

### Voraussetzungen

- PHP 7.4 oder höher
- MySQL 5.7 oder höher
- PHP GD Extension (für Captcha)
- Webserver (Apache, Nginx, etc.)
- E-Mail-Funktion aktiviert (PHP `mail()` oder SMTP)

### Schritt 1: Repository klonen

```bash
git clone https://github.com/yourusername/wichtel-app.git
cd wichtel-app
```

### Schritt 2: Datenbank einrichten

1. **Datenbank erstellen:**

```sql
CREATE DATABASE wichtel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Datenbankbenutzer erstellen:**

```sql
CREATE USER 'wichtel_db_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON wichtel_db.* TO 'wichtel_db_user'@'localhost';
FLUSH PRIVILEGES;
```

3. **Tabellen erstellen:**

```sql
USE wichtel_db;

-- Groups Tabelle
CREATE TABLE `groups` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `admin_token` VARCHAR(64) NOT NULL UNIQUE,
  `invite_token` VARCHAR(64) NOT NULL UNIQUE,
  `admin_email` VARCHAR(255) NULL,
  `budget` DECIMAL(10,2) NULL,
  `description` TEXT NULL,
  `gift_exchange_date` DATE NULL,
  `is_drawn` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Participants Tabelle
CREATE TABLE `participants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `assigned_to` INT NULL,
  `wishlist` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `participants`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exclusions Tabelle
CREATE TABLE `exclusions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL,
  `participant_id` INT NOT NULL,
  `excluded_participant_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`participant_id`) REFERENCES `participants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`excluded_participant_id`) REFERENCES `participants`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_exclusion` (`group_id`, `participant_id`, `excluded_participant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indices für bessere Performance
CREATE INDEX idx_admin_token ON `groups`(`admin_token`);
CREATE INDEX idx_invite_token ON `groups`(`invite_token`);
CREATE INDEX idx_participant_token ON `participants`(`token`);
CREATE INDEX idx_group_participants ON `participants`(`group_id`);
```

### Schritt 3: Konfiguration

1. **Kopiere die Beispiel-Konfiguration:**

```bash
cp includes/config.example.php includes/config.php
```

2. **Bearbeite `includes/config.php` mit deinen Daten:**

```php
<?php
// Datenbankeinstellungen
define('DB_HOST', 'localhost');
define('DB_NAME', 'wichtel_db');
define('DB_USER', 'wichtel_db_user');
define('DB_PASS', 'your_secure_password');

// E-Mail-Einstellungen
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'Wichtel Webseite');

// Master Admin Token (Generiere ein sicheres Token)
define('MASTER_ADMIN_TOKEN', bin2hex(random_bytes(32)));
?>
```

3. **Generiere ein sicheres Master-Admin-Token:**

```bash
php -r "echo bin2hex(random_bytes(32));"
```

### Schritt 4: Berechtigungen setzen

```bash
# Stelle sicher, dass der Webserver Schreibrechte hat
chmod 755 .
chmod 644 *.php
chmod 644 css/*.css
chmod 644 images/*
```

### Schritt 5: E-Mail-Konfiguration

Die App verwendet standardmäßig die PHP `mail()` Funktion. Für bessere Zustellbarkeit:

1. **Sendmail-Pfad in `functions.php` anpassen** (falls nötig):

```php
ini_set('sendmail_path', '/usr/sbin/sendmail -t -i');
```

2. **Oder SMTP konfigurieren** (optional, erfordert zusätzliche Bibliothek wie PHPMailer)

### Schritt 6: Testen

1. Öffne die Website in deinem Browser
2. Erstelle eine Test-Gruppe
3. Registriere Teilnehmer
4. Teste die Auslosung

### Schritt 7: Automatische Bereinigung einrichten (Cronjob)

Um alte Gruppen (älter als 3 Monate) automatisch zu löschen und Statistiken zu archivieren, richte einen täglichen Cronjob ein:

1. Cron-Tabelle bearbeiten:
```bash
crontab -e
```

2. Zeile hinzufügen (läuft täglich um 03:00 Uhr):
```
0 3 * * * /usr/bin/php /path/to/wichtel-app/scripts/cleanup_groups.php >> /path/to/wichtel-app/logs/cleanup.log 2>&1
```

*Hinweis: Passe die Pfade entsprechend deiner Installation an.*

## 📁 Projektstruktur

```
wichtel-app/
├── public/                 # Öffentliches Verzeichnis (Document Root)
│   ├── admin/              # Master-Admin Bereich
│   ├── api/                # REST API
│   ├── css/                # Stylesheets
│   ├── images/             # Bilder und Icons
│   ├── index.php           # Landing Page
│   ├── admin.php           # Admin-Bereich für Gruppen
│   ├── create_group.php    # Gruppenerstellung
│   ├── participant.php     # Teilnehmer-Bereich
│   ├── register.php        # Registrierung
│   └── ...                 # Weitere Seiten
├── includes/               # Interne Logik und Konfiguration
│   ├── config.php          # Konfiguration (nicht im Repo)
│   ├── api_config.php      # API Konfiguration (nicht im Repo)
│   ├── functions.php       # Kern-Funktionen
│   └── templates/          # Partials (Footer, Nav, etc.)
├── database/               # SQL Skripte
├── logs/                   # Logdateien
├── tests/                  # Tests
└── README.md               # Diese Datei
```

## 🎨 Anpassungen

### Farben ändern

Bearbeite die CSS-Variablen in `css/styles.css`:

```css
:root {
    --primary: #e63946;     /* Hauptfarbe (Rot) */
    --secondary: #2a9d8f;   /* Sekundärfarbe (Türkis) */
    --accent: #f4a261;      /* Akzentfarbe (Orange) */
    --dark: #264653;        /* Dunkelblau */
    --success: #2a9d8f;     /* Erfolgsfarbe */
    --error: #e63946;       /* Fehlerfarbe */
}
```

### Logo ersetzen

Ersetze `images/logo.png` mit deinem eigenen Logo (empfohlene Größe: 250x60px).

### E-Mail-Templates anpassen

E-Mail-Templates befinden sich in `functions.php`:
- `create_html_email()` - Partner-Benachrichtigung
- `create_registration_email()` - Registrierungsbestätigung
- `create_admin_email()` - Admin-Willkommens-E-Mail

## 🔒 Sicherheitshinweise

1. **includes/config.php niemals committen** - Bereits in `.gitignore` enthalten
2. **Starke Passwörter verwenden** - Für Datenbank und Admin-Token
3. **HTTPS verwenden** - In Produktion immer SSL/TLS aktivieren
4. **Regelmäßige Updates** - PHP und MySQL aktuell halten
5. **Error-Reporting deaktivieren** - In Produktion in `includes/functions.php`:

```php
// In Produktion auskommentieren:
// ini_set('display_errors', 0);
// error_reporting(0);
```

## 🐛 Fehlersuche

### E-Mails werden nicht versendet

1. Prüfe PHP Mail-Konfiguration:
```bash
php -r "mail('test@example.com', 'Test', 'Test');"
```

2. Prüfe Sendmail-Pfad in `includes/functions.php`
3. Prüfe Server-Logs für Fehler

### Captcha funktioniert nicht

1. Stelle sicher, dass PHP GD Extension installiert ist:
```bash
php -m | grep -i gd
```

2. Falls nicht installiert:
```bash
# Ubuntu/Debian
sudo apt-get install php-gd

# CentOS/RHEL
sudo yum install php-gd
```

### Datenbankfehler

1. Prüfe Verbindungsdaten in `config.php`
2. Prüfe Datenbankberechtigungen
3. Prüfe Zeichensatz (UTF8MB4)

## 📝 Lizenz

MIT License - siehe LICENSE-Datei für Details

## 🤝 Beitragen

Beiträge sind willkommen! Bitte:

1. Forke das Repository
2. Erstelle einen Feature-Branch (`git checkout -b feature/AmazingFeature`)
3. Committe deine Änderungen (`git commit -m 'Add some AmazingFeature'`)
4. Pushe zum Branch (`git push origin feature/AmazingFeature`)
5. Öffne einen Pull Request

## 📧 Support

Bei Fragen oder Problemen:
- Öffne ein [Issue](https://github.com/yourusername/wichtel-app/issues)
- E-Mail: support@yourdomain.com

## 🎄 Credits

Entwickelt mit ❤️ für die Weihnachtszeit

- Icons: Custom SVG Icons
- Fonts: Google Fonts (Playfair Display, Roboto)
- Design: Custom Design inspiriert von modernen Web-Standards

---

**Frohe Weihnachten und viel Spaß beim Wichteln! 🎁**
