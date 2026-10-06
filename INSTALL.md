# Installation von wichtlä.ch

Diese Anleitung beschreibt die Einrichtung auf klassischem PHP-Hosting. Die Anwendung braucht kein Composer und kein Framework. Der Document Root des Webservers muss das Verzeichnis `public/` sein.

Die Oberfläche ist Deutsch. Diese Anleitung ebenfalls.

## Voraussetzungen

- PHP 7.4 oder neuer (getestet bis 8.3)
- Erweiterungen: `pdo_mysql`, `gd` (Captcha), `mbstring`, `json`, `session`
- MySQL 5.7+ oder MariaDB 10.3+ mit Zeichensatz `utf8mb4`
- Funktion `mail()` bzw. ein funktionierender Sendmail-Pfad
- Schreibrecht für das Verzeichnis `logs/` (API-Log und Rate-Limit), nicht für `public/`

Lege keine Zugangsdaten ins Git. `includes/config.php` und `includes/api_config.php` sind in `.gitignore`.

## 1. Dateien auf den Server legen

```bash
git clone https://github.com/piroh1990/wichteln.git
cd wichteln
```

Der Webserver zeigt auf `public/`, nicht auf das Projektverzeichnis. So bleiben `includes/`, `database/`, `logs/` und `scripts/` ausserhalb des Document Root.

Beispiel (Apache):

```apache
DocumentRoot /pfad/zu/wichteln/public
<Directory /pfad/zu/wichteln/public>
    AllowOverride All
    Require all granted
</Directory>
```

Bei nginx analog `root /pfad/zu/wichteln/public;`.

## 2. Datenbank einrichten

Zuerst ein Backup, falls die Datenbank schon Daten enthält. Danach den Namen und das Passwort in `database/init.sql` anpassen (`CHANGE_THIS_PASSWORD`).

### Neue Installation

```bash
mysql -u root -p < database/init.sql
mysql -u wichtel_db_user -p wichtel_db < database/setup.sql
```

`setup.sql` legt die Tabellen an. Die persönliche Teilnehmer-Spalte heisst `participant_token`.

### Bestehende Live-Datenbank

Die Webseiten (`register.php`, `participant.php`, `admin.php`) verwenden seit jeher `participants.participant_token`. `setup.sql` und `public/api/` haben die Spalte zwischenzeitlich `token` genannt. Die Migration benennt nur um, wenn `token` existiert und `participant_token` noch nicht. Sie löscht keine Zeilen und kann mehrfach laufen.

```bash
mysqldump -u DEIN_USER -p DEINE_DATENBANK > backup_vor_migration.sql

mysql -u DEIN_USER -p DEINE_DATENBANK < database/migration_add_created_at.sql
mysql -u DEIN_USER -p DEINE_DATENBANK < database/migration_statistics.sql
mysql -u DEIN_USER -p DEINE_DATENBANK < database/migration_enhance_statistics.sql
mysql -u DEIN_USER -p DEINE_DATENBANK < database/migrations/20261006_rename_participant_token.sql
mysql -u DEIN_USER -p DEINE_DATENBANK < database/migrations/20261006_add_reveal_sent_at.sql
```

`migration_add_created_at.sql` und die Statistik-Migrationen sind für ältere Stände. Sind die Spalten schon da, die jeweilige Datei überspringen oder den Fehler ignorieren, wenn sie nicht idempotent ist. Die Token-Migration und `20261006_add_reveal_sent_at.sql` sind idempotent.

`groups.reveal_sent_at` merkt sich, wann die Auflösung an alle Teilnehmer ging. In phpMyAdmin die Datenbank links auswählen, den Inhalt der Datei ins SQL-Fenster einfügen und ausführen. Ein zweites Mal ausführen ändert nichts. Danach kann der Admin nach der Auslosung «Auflösung an alle senden» nutzen.

Prüfung:

```sql
SHOW COLUMNS FROM participants LIKE 'participant_token';
```

Es darf danach keine Spalte `token` mehr geben, ausser du hattest vorher absichtlich beide Spalten. In dem Fall ändert die Migration nichts und meldet das.

## 3. Konfiguration

```bash
cp includes/config.example.php includes/config.php
cp includes/api_config.example.php includes/api_config.php
mkdir -p logs
chmod 750 logs
```

In `includes/config.php`:

- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `SMTP_FROM_EMAIL` und `SMTP_FROM_NAME` (Absender von `mail()`)
- `MASTER_ADMIN_TOKEN`: lange Zufallszeichenkette, nicht leer

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Den Master-Admin erreichst du einmalig über:

```text
https://example.ch/admin/index.php?master_token=DEIN_TOKEN
```

Der Token wird in der Session gespeichert und aus der Adresszeile entfernt. Alte Links mit `master_token` funktionieren weiter. Abmelden steht im Kopf des Master-Admins. Reset und Löschen laufen nur noch als Formular mit CSRF-Token, nicht mehr über GET.

In `includes/api_config.php`, falls die API genutzt wird:

- `API_TOKEN` setzen (ebenfalls `bin2hex(random_bytes(32))`)
- `API_ALLOW_ORIGINS` als Liste konkreter Origins. `*` wird ignoriert.
- Browser senden IDN-Hosts als Punycode, also `https://xn--wichtl-gua.ch`.
- Schema, Host und Port müssen exakt passen, ohne Slash am Ende.
- Eine alte Zeile `API_ALLOW_ORIGIN` mit einer konkreten Origin bleibt gültig. Der Wert `*` nicht.

Die API-Zähler liegen in `logs/rate-limit/` und werden mit Dateisperre geführt. Das Verzeichnis wird angelegt, sobald die API läuft. `logs/` muss für den PHP-Benutzer beschreibbar sein.

## 4. E-Mail

Standard ist PHP `mail()`. In `includes/functions.php` steht der Sendmail-Pfad:

```php
ini_set('sendmail_path', '/usr/sbin/sendmail -t -i');
```

Den Pfad an den Hoster anpassen, falls er abweicht. Absender ist `SMTP_FROM_EMAIL` aus der Konfiguration. Viele Hoster verlangen, dass diese Adresse zur Domain gehört, sonst wandert die Post in den Spam oder wird verworfen.

Es wird kein SMTP-Bibliothek mitgeliefert. Wer SMTP braucht, konfiguriert das auf dem Server (Sendmail, Postfix, msmtp), nicht in Composer.

## 5. Cron für das Aufräumen

`scripts/cleanup_groups.php` archiviert anonymisierte Statistik und löscht Gruppen, deren Ereignis (oder, ohne Datum, deren Erstellung) mehr als drei Monate zurückliegt.

Täglich, zum Beispiel um 03:15:

```cron
15 3 * * * /usr/bin/php /pfad/zu/wichteln/scripts/cleanup_groups.php >> /pfad/zu/wichteln/logs/cleanup.log 2>&1
```

Der PHP-CLI-Binary heisst auf manchen Hostern anders (`php8.2`, `php74`). `which php` zeigt den Pfad. Das Skript braucht `includes/config.php` und eine erreichbare Datenbank.

## 6. Prüfen

Im Browser:

1. Startseite öffnen.
2. Gruppe anlegen (Captcha). Die Admin-Mail sollte ankommen.
3. Über den Einladungslink eine Person registrieren.
4. Im Admin auslosen, zurücksetzen oder einen Teilnehmer nur über die Schaltfläche löschen.

Ein POST ohne CSRF-Token wird abgelehnt. Die Meldung lautet: «Ungültiger CSRF-Token. Bitte lade die Seite neu und versuche es erneut.»

Lokal, ohne Datenbank:

```bash
cp includes/config.example.php includes/config.php
cp includes/api_config.example.php includes/api_config.php
find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l
php tests/run_tests.php
```

Dieselben Prüfungen laufen in GitHub Actions (PHP 7.4 und 8.3) bei Push und Pull Request.

## 7. Aktualisieren

```bash
cd /pfad/zu/wichteln
git pull
```

`includes/config.php` und `includes/api_config.php` bleiben liegen, weil sie nicht im Repository sind. Nach dem Ziehen:

1. Diese Datei und `database/migrations/` auf neue SQL-Dateien prüfen.
2. Neue Migrationen wie oben gegen die Live-Datenbank ausführen, nach einem Backup.
3. Neue Konstanten aus den `*.example.php` in die echten Konfigurationsdateien übernehmen. Aktuell dazu: `API_ALLOW_ORIGINS` in `includes/api_config.php`, falls die API von einem Browser aus einer anderen Origin aufgerufen wird.
4. Den PHP-Benutzer auf Schreibrecht für `logs/` prüfen.

Ein Deploy der CSRF-Härtung ändert das Aussehen der Seiten nicht. Geändert haben sich dabei Schutzmechanismen (CSRF, Master-Admin-Session, CORS-Allowlist) und die einheitliche Spalte `participant_token`.

Die Auflösung ergänzt im Admin nach der Auslosung die Schaltfläche «Auflösung an alle senden». Dafür muss `groups.reveal_sent_at` existieren (`database/migrations/20261006_add_reveal_sent_at.sql`). Ohne diese Spalte bleibt der Versand gesperrt, die übrige Verwaltung funktioniert weiter.
