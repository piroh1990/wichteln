<?php

/**
 * Session mit Secure, HttpOnly und SameSite=Lax.
 * Eine bereits laufende Session wird nicht neu gestartet.
 */
function request_is_https() {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
        return true;
    }
    $forwarded = isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? strtolower(trim((string) $_SERVER['HTTP_X_FORWARDED_PROTO'])) : '';
    $comma = strpos($forwarded, ',');
    if ($comma !== false) {
        $forwarded = trim(substr($forwarded, 0, $comma));
    }
    return $forwarded === 'https';
}

function start_secure_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = request_is_https();
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_start();
}

function send_baseline_security_headers() {
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('X-Permitted-Cross-Domain-Policies: none');
    header("Content-Security-Policy: " . baseline_content_security_policy());
    if (request_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function baseline_content_security_policy() {
    $matomo = '';
    if (defined('MATOMO_URL')) {
        $url = trim((string) MATOMO_URL);
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }
        $parts = parse_url($url);
        if (is_array($parts) && !empty($parts['host']) && preg_match('/\A[A-Za-z0-9.-]+\z/', $parts['host']) === 1) {
            $scheme = (isset($parts['scheme']) && $parts['scheme'] === 'http') ? 'http' : 'https';
            $matomo = ' ' . $scheme . '://' . $parts['host'];
        }
    }
    return "default-src 'self'; "
        . "base-uri 'self'; "
        . "object-src 'none'; "
        . "frame-ancestors 'self'; "
        . "form-action 'self'; "
        . "script-src 'self' 'unsafe-inline'" . $matomo . "; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com data:; "
        . "img-src 'self' data: https:; "
        . "connect-src 'self'" . $matomo;
}
