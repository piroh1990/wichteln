<?php
// Datenbankeinstellungen
define('DB_HOST', 'localhost');
define('DB_NAME', 'wichtel_db');
define('DB_USER', 'wichtel_db_user');
define('DB_PASS', 'your_database_password_here');

// E-Mail-Einstellungen
define('SMTP_FROM_EMAIL', 'noreply@xn--wichtl-gua.ch'); // Ersetze mit deiner Absender-E-Mail
define('SMTP_FROM_NAME', 'Wichtel Webseite');

// Cookie-Einstellungen für automatisches Login
define('COOKIE_NAME', 'wichteln_tokens'); // Name des Cookies
define('COOKIE_LIFETIME', 60 * 60 * 24 * 90); // Cookie-Lebensdauer in Sekunden (90 Tage)
define('COOKIE_MAX_TOKENS', 10); // Maximale Anzahl gespeicherter Tokens pro Cookie

// Matomo Analytics Einstellungen
define('MATOMO_URL', '//analytics.site.ch/'); // URL deiner Matomo Installation
define('MATOMO_SITE_ID', '1'); // Deine Matomo Site ID

// Google AdSense
// Produktiv erst nach AdSense-Freigabe, echten ca-pub- und Slot-IDs und
// veröffentlichter Einwilligungsnachricht. Vorher false lassen.
// Layout-Prüfung: GOOGLE_ADS_ENABLED und GOOGLE_ADS_TESTING beide true.
// Der Testmodus zeigt die Markierung «Anzeige» und einen Platzhalter,
// lädt aber kein adsbygoogle.js.
define('GOOGLE_ADS_ENABLED', false);
define('GOOGLE_ADS_TESTING', false);
define('GOOGLE_ADS_CLIENT', 'ca-pub-XXXXXXXXXXXXXXXXX'); // echte Publisher-ID, z. B. ca-pub-1234567890123456
define('GOOGLE_ADS_SLOT_OPTION1', '1234567890'); // Slot 1: Startseite, unter dem Hero, vor «So funktioniert's»
define('GOOGLE_ADS_SLOT_OPTION2', '0987654321'); // Slot 2: was-ist-wichteln.php, nach dem Text, vor dem Footer
define('GOOGLE_ADS_SLOT_OPTION3', ''); // ungenutzt

// Genau zwei öffentliche Slots. Option 3 bleibt aus. Kein Slot im Teilnehmerbereich.
define('GOOGLE_ADS_SHOW_OPTION1', true);
define('GOOGLE_ADS_SHOW_OPTION2', true);
define('GOOGLE_ADS_SHOW_OPTION3', false);

// Google-CMP (Funding Choices) über AdSense → Privacy & messaging.
// true nur zusammen mit einer echten ca-pub-ID. Sonst lädt kein AdSense-Skript.
// Einrichtung in der AdSense-Konsole:
// 1. Privacy & messaging öffnen.
// 2. Europäische Vorschriften (TCF / Funding Choices) für Schweiz, EWR und UK anlegen.
// 3. Nachricht veröffentlichen und Consent Mode für Werbung aktivieren.
// 4. Diesen Schalter erst danach auf true setzen.
// Der eigene Cookie-Hinweis schaltet keine Anzeigen frei.
define('GOOGLE_CMP_ENABLED', false);

// Master Admin Token (Generiere ein sicheres, zufälliges Token)
define('MASTER_ADMIN_TOKEN', 'generate_a_secure_random_token_here');

?>
