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
mysql -u DEIN_USER -p DEINE_DATENBANK < database/migrations/20261007_add_reminder_sent_at.sql
```

`migration_add_created_at.sql` und die Statistik-Migrationen sind für ältere Stände. Sind die Spalten schon da, die jeweilige Datei überspringen oder den Fehler ignorieren, wenn sie nicht idempotent ist. Die Token-Migration und `20261006_add_reveal_sent_at.sql` sind idempotent.

`groups.reveal_sent_at` merkt sich, wann die Auflösung an alle Teilnehmer ging. In phpMyAdmin die Datenbank links auswählen, den Inhalt der Datei ins SQL-Fenster einfügen und ausführen. Ein zweites Mal ausführen ändert nichts. Danach kann der Admin nach der Auslosung «Auflösung an alle senden» nutzen.

`groups.reminder_sent_at` merkt sich, wann der Admin die Erinnerung an die Geschenkübergabe über den Knopf im Admin-Bereich gesendet hat. Dieselbe phpMyAdmin-Schritte gelten für `database/migrations/20261007_add_reminder_sent_at.sql`. Ohne die Spalte bleibt der Erinnerungs-Knopf gesperrt. Ein erneuter Versand braucht die Bestätigung im Formular.

Premium-Lizenzen: `database/migrations/20261019_premium_licenses.sql` legt `premium_licenses`, `premium_confirmations` und `groups.premium_license_id` an. Die Spalte ist NULL, bestehende Gruppen bleiben normale Gruppen. Die Migration ist idempotent. Ohne sie bleibt die Premium-Funktion nach dem Einschalten mit einem Hinweis stehen, der Rest der Seite läuft weiter.

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
- `MATOMO_URL` und `MATOMO_SITE_ID` für die Reichweitenmessung

### Einwilligung für Matomo

Der Hinweis auf der Seite fragt, ob Matomo ein Cookie setzen darf. «Akzeptieren» erlaubt das Cookie. «Ablehnen» lässt die Messung ohne Tracking-Cookies. Die Wahl liegt im Cookie `wichteln_consent` für 180 Tage. Auf der Datenschutzerklärung kann man sie widerrufen.

`CANONICAL_BASE_URL` (Default `https://xn--wichtl-gua.ch`) und optional `ICS_SIGNING_KEY` in eine bestehende `includes/config.php` übernehmen. Die früheren Konstanten `GOOGLE_ADS_*` und `GOOGLE_CMP_ENABLED` werden nicht mehr gelesen. Sie können aus der Konfiguration entfernt werden.

Freiwillige Unterstützung: `SUPPORT_URL` in `includes/config.php` auf den öffentlichen Stripe-Zahlungslink setzen (Payment Link, den Betrag wählt die zahlende Person, TWINT und Karte). `SUPPORT_PROVIDER_NAME` bleibt `Stripe`, ausser der angezeigte Name des Zahlungsdienstes soll anders lauten. Der Link ist kein Geheimnis. Ist `SUPPORT_URL` leer oder fehlt die Konstante, bleiben Footer-Link, Karte und Absatz unsichtbar, und `/unterstuetzen` antwortet mit 404.

Firmen-Premium ist aus, solange `PREMIUM_ENABLED` leer ist oder fehlt. Zum Einschalten in `includes/config.php` auf `'1'` setzen. Dann erscheinen die Option auf dem Formular «Gruppe erstellen», der Admin-Abschnitt, der Footer-Link und `/firmen-premium`. Ist der Schalter leer, antwortet `/firmen-premium` mit 404.

`PREMIUM_INVOICE_EMAIL` ist der Empfänger einer Rechnungsanfrage. Die Adresse wird nach der Punycode-Normalisierung genau so verwendet, zum Beispiel `kontakt@xn--wichtl-gua.ch`. Leer oder ungültig fällt auf `info@xn--wichtl-gua.ch` zurück; der Fallback steht im Log. Der Versand läuft über dieselbe Funktion `mail()` wie die übrigen Mails. Der Envelope-Absender ist `SMTP_FROM_EMAIL` (`-f`). Auf Plesk/Metanet muss diese Adresse zur Domain der Subscription gehören, sonst lehnt der Mailserver den ersten Versuch ab und es folgt ein zweiter ohne `-f`. Jeder Versuch landet in `logs/mail.log` und im PHP-Fehlerlog. Im Master-Admin zeigt die Premium-Diagnose den Empfänger ohne Geheimnisse und hat den Knopf «Test-Mail senden».

Stripe Checkout braucht `PREMIUM_STRIPE_SECRET_KEY`, `PREMIUM_STRIPE_WEBHOOK_SECRET` und die drei Price-IDs `PREMIUM_STRIPE_PRICE_1X`, `PREMIUM_STRIPE_PRICE_3X`, `PREMIUM_STRIPE_PRICE_5X`. Ist eine neue Konstante nicht definiert oder leer, fällt sie auf den alten Namen zurück: `PREMIUM_STRIPE_PRICE_VEREIN` für 1×, `PREMIUM_STRIPE_PRICE_FIRMA` für 3×, `PREMIUM_STRIPE_PRICE_FIRMA_PLUS` für 5×. Diese alten Price-IDs berechnen die früheren Beträge und nicht CHF 9/19/29. Dafür in Stripe drei neue Preise in CHF anlegen und die neuen Konstanten setzen. Der Secret Key muss mit `sk_test_`, `sk_live_`, `rk_test_` oder `rk_live_` beginnen. Ein Publishable Key `pk_` wird abgelehnt. Ein eingeschränkter Key `rk_` braucht die Berechtigung, Checkout Sessions anzulegen; sonst antwortet Stripe mit einem Fehler und die Seite zeigt `premium.checkout.fail`. Das Webhook-Geheimnis muss mit `whsec_` beginnen. Die Price-Konstanten sind Stripe-IDs (`price_…`), nicht der Frankenbetrag. Die angezeigten Preise stehen in `PREMIUM_PRICE_1X`, `PREMIUM_PRICE_3X` und `PREMIUM_PRICE_5X`. Fehlen sie, gelten CHF 9, 19 und 29. `PREMIUM_PRICE_VEREIN`, `PREMIUM_PRICE_FIRMA` und `PREMIUM_PRICE_FIRMA_PLUS` ändern diese Anzeige nicht. Anführungszeichen um den Wert und ein UTF-8-BOM am Anfang werden entfernt. Fehlt eine gültige Angabe, bleibt die Kartenzahlung verborgen, die Rechnung geht trotzdem. Die Diagnose im Master-Admin nennt jeden Punkt als fehlt, ungültig oder vorhanden, ohne den Schlüssel zu zeigen.

Webhook-URL: `https://xn--wichtl-gua.ch/premium-webhook.php`. Ereignis: `checkout.session.completed`. Ein Paket ist ein Guthaben und läuft nicht ab. 1× Premium-Wichteln ist 1 Gruppe, 3× sind 3, 5× sind 5. Jede neue Premium-Gruppe und jede Kopie fürs nächste Jahr verbraucht eines. Ein weiterer Kauf mit derselben E-Mail legt eine weitere Lizenz an; freie Plätze werden zusammengezählt. Optionale Limits: `PREMIUM_LIMIT_1X`, `PREMIUM_LIMIT_3X`, `PREMIUM_LIMIT_5X`. Bereits gespeicherte Zeilen mit `tier` `verein`, `firma` oder `firma_plus` bleiben bei 3, 10 und ohne Limit (`PREMIUM_LIMIT_VEREIN`, `PREMIUM_LIMIT_FIRMA`, `PREMIUM_LIMIT_FIRMA_PLUS`, `0` oder `unlimited` heisst kein Limit). Dafür braucht es kein SQL. Alle Pakete enthalten dieselben Funktionen (CSV, Logo und Farben, Kopie, HR-Übersicht mit Live-Aufdeckung, Begrüssungstext, ohne «powered by», Domain-Lizenz, Lizenzübersicht). Ausschlussregeln gehören zum normalen Wichteln und sind keine Premium-Funktion. Widerrufen geht im Master-Admin. Das Verzeichnis `storage/premium-logos/` muss für den PHP-Benutzer beschreibbar sein. Logos liegen dort, nicht unter `public/`.

Werbelinks zu Orell Füssli laufen über Awin und sind aus, solange `AFFILIATE_OF_ENABLED` fehlt oder nicht `'1'` ist. In `includes/config.php`: `AFFILIATE_OF_ENABLED` (`'0'` oder `'1'`), `AFFILIATE_OF_AWINMID` (`13971`), `AFFILIATE_OF_AWINAFFID` (`3128240`), `AFFILIATE_OF_SEARCH_URL` (Default `https://www.orellfuessli.ch/suche?sq=`, der Suchbegriff wird urlencodiert angehängt) und `AFFILIATE_OF_GIFT_URL` (Default `https://www.orellfuessli.ch/kategorie/geschenke-trends-17794/`). Auf der Seite liegt kein Skript und kein Cookie, nur Links. Normale Gruppen zeigen die Links, sobald der Schalter an ist. Premium-Gruppen bleiben aus, bis die Gruppe den Schalter im Premium-Bereich setzt. Dafür `groups.affiliate_of` anlegen: `database/migrations/20261020_add_affiliate_of.sql`. Ohne diese Spalte bleiben Premium-Gruppen ohne Werbelinks, der Rest der Seite läuft weiter.

`logs/rate-limit/` muss für den PHP-Benutzer beschreibbar sein. Ohne dieses Verzeichnis lehnen Captcha, Anmeldung und API weitere Versuche ab.

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Den Master-Admin erreichst du über das Formular unter `/admin/`. Das Master-Token wird per POST gesendet. Ein Token in der Adresszeile wird nicht mehr angenommen. Abmelden steht im Kopf des Master-Admins. Reset und Löschen laufen nur noch als Formular mit CSRF-Token, nicht mehr über GET.

In `includes/api_config.php`, falls die API genutzt wird:

- `API_TOKEN` setzen (ebenfalls `bin2hex(random_bytes(32))`). Der Token gilt nur im Header `Authorization: Bearer` oder `X-API-Token`.
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
3. Neue Konstanten aus den `*.example.php` in die echten Konfigurationsdateien übernehmen. Aktuell dazu: `API_ALLOW_ORIGINS` in `includes/api_config.php`, falls die API von einem Browser aus einer anderen Origin aufgerufen wird. Alte Konstanten `GOOGLE_ADS_*` und `GOOGLE_CMP_ENABLED` werden nicht mehr gelesen und können entfallen. Für die freiwillige Unterstützung: `SUPPORT_URL` (Stripe-Zahlungslink) und `SUPPORT_PROVIDER_NAME` (`Stripe`). Ohne `SUPPORT_URL` bleibt die Unterstützung unsichtbar. Für Firmen-Premium: `PREMIUM_ENABLED` bleibt leer, bis die Funktion live gehen soll, dann `'1'`. Dazu `PREMIUM_INVOICE_EMAIL` und, für Karte/TWINT, die Stripe-Konstanten. Die Migration `database/migrations/20261019_premium_licenses.sql` vorher ausführen. Für Werbelinks zu Orell Füssli: `AFFILIATE_OF_ENABLED` bleibt `'0'`, bis die Links sichtbar sein sollen. Dazu die Awin- und URL-Konstanten aus `includes/config.example.php` und die Migration `database/migrations/20261020_add_affiliate_of.sql`.
4. Den PHP-Benutzer auf Schreibrecht für `logs/` und, sobald Premium an ist, für `storage/premium-logos/` prüfen.

Ein Deploy der CSRF-Härtung ändert das Aussehen der Seiten nicht. Geändert haben sich dabei Schutzmechanismen (CSRF, Master-Admin-Session, CORS-Allowlist) und die einheitliche Spalte `participant_token`.

Die Auflösung ergänzt im Admin nach der Auslosung die Schaltfläche «Auflösung an alle senden». Dafür muss `groups.reveal_sent_at` existieren (`database/migrations/20261006_add_reveal_sent_at.sql`). Ohne diese Spalte bleibt der Versand gesperrt, die übrige Verwaltung funktioniert weiter.
