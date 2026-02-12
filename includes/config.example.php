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

// Google Ads Einstellungen
define('GOOGLE_ADS_ENABLED', true); // Auf true setzen um Google Ads zu aktivieren (auch für Test-Modus!)
define('GOOGLE_ADS_TESTING', true); // Auf true für Test-Modus (zeigt BLAUE Platzhalter statt echte Ads)
define('GOOGLE_ADS_CLIENT', 'ca-pub-XXXXXXXXXXXXXXXXX'); // Deine Google AdSense Publisher ID
define('GOOGLE_ADS_SLOT_OPTION1', '1234567890'); // Ad Slot ID für Position 1 (nach Wichtelpartner)
define('GOOGLE_ADS_SLOT_OPTION2', '0987654321'); // Ad Slot ID für Position 2 (am Ende der Seite)
define('GOOGLE_ADS_SLOT_OPTION3', '1122334455'); // Ad Slot ID für Position 3 (Sidebar Desktop)

// Google Ads Positionssteuerung (einzeln ein-/ausschaltbar)
define('GOOGLE_ADS_SHOW_OPTION1', true); // Position 1: Nach Wichtelpartner-Bereich
define('GOOGLE_ADS_SHOW_OPTION2', true); // Position 2: Am Ende der Seite (vor Footer)
define('GOOGLE_ADS_SHOW_OPTION3', true); // Position 3: Sidebar (nur Desktop, experimentell)

// Master Admin Token (Generiere ein sicheres, zufälliges Token)
define('MASTER_ADMIN_TOKEN', 'generate_a_secure_random_token_here');

?>
