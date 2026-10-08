<?php

/**
 * Session mit Secure, HttpOnly und SameSite=Lax.
 * Eine bereits laufende Session wird nicht neu gestartet.
 */
function start_secure_session() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_start();
}
