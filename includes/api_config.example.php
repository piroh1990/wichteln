<?php
/**
 * API Configuration Example
 * 
 * Kopiere diese Datei zu config.php und passe die Werte an
 */

// API Version
define('API_VERSION', 'v1');

// API Authentication Token
// WICHTIG: Generiere einen sicheren Token!
// Verwendung: php -r "echo bin2hex(random_bytes(32));"
define('API_TOKEN', 'your_secure_api_token_here_min_32_characters_long');

// Rate Limiting (Anfragen pro Minute)
define('API_RATE_LIMIT', 60);

// API aktiviert?
define('API_ENABLED', true);

// CORS Settings
// Allowlist konkreter Origins. Der Wildcard '*' wird nicht mehr unterstützt
// und von der API ignoriert. Browser senden den Origin-Header in Punycode,
// also https://xn--wichtl-gua.ch statt https://wichtlä.ch.
// Schema, Host und Port müssen exakt übereinstimmen, ohne abschliessenden Slash.
// Weitere Origins (z.B. eine App oder http://localhost:8080) hier ergänzen.
define('API_ALLOW_ORIGINS', [
    'https://wichtlä.ch',
    'https://www.wichtlä.ch',
    'https://xn--wichtl-gua.ch',
    'https://www.xn--wichtl-gua.ch',
]);
// Veraltet: eine einzelne Origin. '*' wird ignoriert. Nur setzen, wenn
// API_ALLOW_ORIGINS nicht verwendet wird.
// define('API_ALLOW_ORIGIN', 'https://app.example.com');
define('API_ALLOW_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('API_ALLOW_HEADERS', 'Content-Type, Authorization, X-API-Token');

// Response Format
define('API_DEFAULT_FORMAT', 'json'); // json, xml

// Debug Mode (NIEMALS in Produktion aktivieren!)
define('API_DEBUG', false);

// Logging
define('API_LOG_REQUESTS', true);
define('API_LOG_FILE', __DIR__ . '/../logs/api.log');
