<?php
// Feste Basis-URL für Links. Der Host-Header der Anfrage wird nicht verwendet.
define('CANONICAL_BASE_URL', 'https://xn--wichtl-gua.ch');

// Optionaler Schlüssel für Kalenderlinks. Wenn die Zeile auskommentiert bleibt,
// wird der Schlüssel aus MASTER_ADMIN_TOKEN abgeleitet. Ein späterer Wechsel
// macht bestehende Kalenderlinks ungültig.
// define('ICS_SIGNING_KEY', 'generate_a_separate_random_secret');

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

// Master Admin Token (Generiere ein sicheres, zufälliges Token)
define('MASTER_ADMIN_TOKEN', 'generate_a_secure_random_token_here');

?>
