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

// Freiwillige Unterstützung über einen öffentlichen Stripe-Zahlungslink.
// Payment Link, Betrag wählt die zahlende Person, TWINT und Karte. Kein Geheimnis.
// In der Server-Datei includes/config.php setzen, diese Beispieldatei bleibt leer:
// define('SUPPORT_URL', 'https://buy.stripe.com/...');
// Ist der Wert leer oder fehlt die Konstante, gibt es keinen Footer-Link,
// keine Karte und keinen Absatz. /unterstuetzen antwortet dann mit 404.
define('SUPPORT_URL', '');

// Angezeigter Name des Zahlungsdienstes.
define('SUPPORT_PROVIDER_NAME', 'Stripe');

// Premium Firmen-Wichteln. Die Funktion ist aus, solange dieser Wert leer ist
// oder fehlt. In includes/config.php auf '1' setzen, wenn sie sichtbar sein soll.
// '1', 'true', 'yes' und 'on' schalten sie ein. Alles andere lässt sie aus.
define('PREMIUM_ENABLED', '');

// Empfänger der Rechnungsanfrage. Punycode in der Mail-Adresse.
// Leer oder ungültig fällt auf info@xn--wichtl-gua.ch zurück.
define('PREMIUM_INVOICE_EMAIL', 'info@xn--wichtl-gua.ch');

// Stripe Checkout (Karte und TWINT). Alle drei Price-IDs, der Secret Key
// und das Webhook-Geheimnis müssen gesetzt sein, sonst bleibt die Kartenzahlung
// verborgen. Die Rechnung funktioniert auch ohne Stripe.
// Webhook: https://xn--wichtl-gua.ch/premium-webhook.php
// Ereignis: checkout.session.completed
define('PREMIUM_STRIPE_SECRET_KEY', '');
define('PREMIUM_STRIPE_WEBHOOK_SECRET', '');
define('PREMIUM_STRIPE_PRICE_VEREIN', '');
define('PREMIUM_STRIPE_PRICE_FIRMA', '');
define('PREMIUM_STRIPE_PRICE_FIRMA_PLUS', '');

// Optionale Preise (CHF, ganze Franken) und Gruppen-Limits.
// Auskommentiert heisst: Verein 19 / 3, Firma 49 / 10, Firma Plus 99 / unbegrenzt.
// Alle Stufen enthalten dieselben Funktionen. Der Unterschied ist die Anzahl Gruppen.
// Limit 0 oder 'unlimited' heisst kein Limit.
// define('PREMIUM_PRICE_VEREIN', 19);
// define('PREMIUM_PRICE_FIRMA', 49);
// define('PREMIUM_PRICE_FIRMA_PLUS', 99);
// define('PREMIUM_LIMIT_VEREIN', 3);
// define('PREMIUM_LIMIT_FIRMA', 10);
// define('PREMIUM_LIMIT_FIRMA_PLUS', 'unlimited');

?>
