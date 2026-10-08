<?php
/**
 * API Helper Functions
 * 
 * Gemeinsame Funktionen für alle API-Endpoints
 */

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/api_config.php';

/**
 * Liefert die erlaubten CORS-Origins.
 * '*' wird nie übernommen. API_ALLOW_ORIGINS (Liste) hat Vorrang,
 * API_ALLOW_ORIGIN bleibt als einzelne konkrete Origin kompatibel.
 *
 * @return string[]
 */
function get_api_allowed_origins() {
    $origins = [];

    if (defined('API_ALLOW_ORIGINS')) {
        $configured = API_ALLOW_ORIGINS;
        if (is_string($configured)) {
            $configured = explode(',', $configured);
        }
        if (is_array($configured)) {
            foreach ($configured as $origin) {
                if (!is_string($origin)) {
                    continue;
                }
                $origin = trim($origin);
                if ($origin !== '' && $origin !== '*') {
                    $origins[] = $origin;
                }
            }
        }
    }

    if (defined('API_ALLOW_ORIGIN') && is_string(API_ALLOW_ORIGIN)) {
        $legacy = trim(API_ALLOW_ORIGIN);
        if ($legacy !== '' && $legacy !== '*' && !in_array($legacy, $origins, true)) {
            $origins[] = $legacy;
        }
    }

    return array_values(array_unique($origins));
}

/**
 * API-Token zeitkonstant vergleichen.
 */
function api_token_matches($provided) {
    if (!is_string($provided) || $provided === '') {
        return false;
    }
    if (!defined('API_TOKEN') || !is_string(API_TOKEN) || API_TOKEN === '') {
        return false;
    }
    return hash_equals(API_TOKEN, $provided);
}

/**
 * Setzt CORS-Header nur für explizit erlaubte Origins.
 */
function set_cors_headers() {
    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    if (is_string($origin) && $origin !== '' && in_array($origin, get_api_allowed_origins(), true)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }
    header('Access-Control-Allow-Methods: ' . API_ALLOW_METHODS);
    header('Access-Control-Allow-Headers: ' . API_ALLOW_HEADERS);
    header('Access-Control-Max-Age: 86400'); // 24 Stunden
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

/**
 * Authentifizierung prüfen
 */
function authenticate_api() {
    if (!API_ENABLED) {
        api_response(503, false, 'API ist derzeit deaktiviert', null);
        exit();
    }
    
    // Token aus verschiedenen Quellen holen
    $token = null;
    
    // 1. Authorization Header (Bearer Token)
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $token = $matches[1];
        }
    }
    
    // 2. X-API-Token Header
    if (!$token && isset($_SERVER['HTTP_X_API_TOKEN'])) {
        $token = $_SERVER['HTTP_X_API_TOKEN'];
    }
    
    // Token validieren. Query-Parameter werden nicht gelesen.
    if (!api_token_matches($token)) {
        $client = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
        $allowed = consume_rate_limit('api-auth-fail', $client, 10, 60, false);
        log_api_request('UNAUTHORIZED', isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
        if (!$allowed) {
            api_response(429, false, 'Zu viele Anfragen. Bitte später erneut versuchen.', null);
        }
        api_response(401, false, 'Ungültiges oder fehlendes API-Token', null);
        exit();
    }
    
    // Rate Limiting prüfen
    if (!check_rate_limit()) {
        api_response(429, false, 'Zu viele Anfragen. Bitte später erneut versuchen.', null);
        exit();
    }
}

/**
 * Verzeichnis für Rate-Limit-Zähler, ausserhalb von public/.
 */
function api_rate_limit_dir() {
    return dirname(__DIR__, 2) . '/logs/rate-limit';
}

/**
 * Rate Limiting Check.
 * Zähler liegen unter logs/rate-limit (nicht im weltbeschreibbaren Temp-Verzeichnis)
 * und werden mit einem exklusiven Lock aktualisiert.
 *
 * @param string|null $identity Standard: Client-IP. Nur für Tests überschreiben.
 */
function check_rate_limit($identity = null) {
    if ($identity === null) {
        $identity = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }
    if (!is_string($identity) || $identity === '') {
        $identity = 'unknown';
    }

    $dir = api_rate_limit_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        error_log('API rate limit directory is not writable: ' . $dir);
        return false;
    }

    $file = $dir . '/' . hash('sha256', $identity) . '.json';
    $handle = @fopen($file, 'c+');
    if ($handle === false) {
        error_log('API rate limit file could not be opened: ' . $file);
        return false;
    }

    $allowed = true;
    try {
        if (!flock($handle, LOCK_EX)) {
            error_log('API rate limit file could not be locked: ' . $file);
            return false;
        }

        $raw = stream_get_contents($handle);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        $current_time = time();
        $time_window = 60;

        if (!is_array($data) || !isset($data['start_time'], $data['count'])
            || ($current_time - (int) $data['start_time']) > $time_window) {
            $data = ['start_time' => $current_time, 'count' => 1];
        } else {
            $data['count'] = (int) $data['count'] + 1;
        }

        $allowed = $data['count'] <= (int) API_RATE_LIMIT;

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($data));
        fflush($handle);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    return $allowed;
}

/**
 * API Response senden
 * 
 * @param int $status_code HTTP Status Code
 * @param bool $success Erfolg?
 * @param string $message Nachricht
 * @param mixed $data Daten
 * @param array $meta Zusätzliche Metadaten
 */
function api_response($status_code, $success, $message, $data = null, $meta = []) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'meta' => array_merge([
            'timestamp' => time(),
            'version' => API_VERSION,
        ], $meta)
    ];
    
    if (API_DEBUG && isset($_GET['debug'])) {
        $response['debug'] = [
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'request_uri' => $_SERVER['REQUEST_URI'],
            'php_version' => PHP_VERSION,
            'execution_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) . 's'
        ];
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Input validieren
 */
function validate_input($field, $value, $rules) {
    foreach ($rules as $rule) {
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    return "$field ist erforderlich";
                }
                break;
                
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "$field muss eine gültige E-Mail sein";
                }
                break;
                
            case 'numeric':
                if (!is_numeric($value)) {
                    return "$field muss eine Zahl sein";
                }
                break;
                
            case 'date':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    return "$field muss ein gültiges Datum sein (YYYY-MM-DD)";
                }
                break;
        }
    }
    return null;
}

/**
 * Request-Body parsen (JSON oder Form-Data)
 */
function get_request_body() {
    $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    if (strpos($content_type, 'application/json') !== false) {
        $json = file_get_contents('php://input');
        return json_decode($json, true);
    }
    
    return $_POST;
}

/**
 * API-Request loggen
 */
function api_log_endpoint($endpoint) {
    $endpoint = (string) $endpoint;
    $parts = explode('?', $endpoint, 2);
    if (count($parts) < 2) {
        return $endpoint;
    }
    parse_str($parts[1], $query);
    unset($query['api_token'], $query['token'], $query['master_token'], $query['admin_token'], $query['invite_token']);
    $qs = http_build_query($query);
    return $qs === '' ? $parts[0] : ($parts[0] . '?' . $qs);
}

function log_api_request($status, $endpoint, $message = '') {
    if (!API_LOG_REQUESTS) {
        return;
    }
    
    $log_dir = dirname(API_LOG_FILE);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_entry = sprintf(
        "[%s] %s %s %s - %s %s\n",
        date('Y-m-d H:i:s'),
        isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
        isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
        api_log_endpoint($endpoint),
        $status,
        $message
    );
    
    file_put_contents(API_LOG_FILE, $log_entry, FILE_APPEND);
}

/**
 * Sanitize Output für API
 */
function sanitize_for_api($data) {
    if (is_array($data)) {
        return array_map('sanitize_for_api', $data);
    }
    
    if (is_string($data)) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

/**
 * Pagination Helper
 */
function paginate($query, $pdo, $page = 1, $per_page = 20) {
    $page = max(1, intval($page));
    $per_page = min(100, max(1, intval($per_page))); // Max 100 items
    $offset = ($page - 1) * $per_page;
    
    // Count total
    $count_query = preg_replace('/SELECT .+ FROM/i', 'SELECT COUNT(*) as total FROM', $query, 1);
    $stmt = $pdo->query($count_query);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get paginated results
    $query .= " LIMIT $per_page OFFSET $offset";
    $stmt = $pdo->query($query);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'items' => $items,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => intval($total),
            'total_pages' => ceil($total / $per_page),
            'has_more' => $page * $per_page < $total
        ]
    ];
}

/**
 * Error Handler für API
 */
function api_error_handler($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error_msg = "Error [$errno]: $errstr in $errfile on line $errline";
    log_api_request('ERROR', $_SERVER['REQUEST_URI'], $error_msg);
    
    if (API_DEBUG) {
        api_response(500, false, 'Internal Server Error', null, ['error' => $error_msg]);
    } else {
        api_response(500, false, 'Internal Server Error', null);
    }
}

/**
 * Exception Handler für API
 */
function api_exception_handler($exception) {
    $error_msg = $exception->getMessage();
    log_api_request('EXCEPTION', $_SERVER['REQUEST_URI'], $error_msg);
    
    if (API_DEBUG) {
        api_response(500, false, 'Internal Server Error', null, [
            'exception' => $error_msg,
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    } else {
        api_response(500, false, 'Internal Server Error', null);
    }
}

// Error Handler registrieren
set_error_handler('api_error_handler');
set_exception_handler('api_exception_handler');
