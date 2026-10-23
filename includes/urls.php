<?php

/**
 * Feste Basis-URL. Der Host-Header wird absichtlich nicht verwendet.
 */
function canonical_base_url() {
    $fallback = 'https://xn--wichtl-gua.ch';
    if (!defined('CANONICAL_BASE_URL') || !is_string(CANONICAL_BASE_URL)) {
        return $fallback;
    }
    $url = rtrim(CANONICAL_BASE_URL, '/');
    if (preg_match('#^https://[A-Za-z0-9.\-:\[\]]+$#', $url) !== 1) {
        return $fallback;
    }
    return $url;
}

/**
 * Öffentliche Seiten, deren .php-Adresse auf die kanonische URL zeigt.
 * index wird zu /. Token-Seiten und /api/ stehen hier bewusst nicht.
 */
function seo_legacy_php_slugs() {
    return array(
        'index',
        'faq',
        'was-ist-wichteln',
        'impressum',
        'datenschutz',
        'wichtel-ideen',
        'wichtel-generator',
        'firmenwichteln-tipps',
        'ueber-uns',
        'unterstuetzen',
        'firmen-premium',
        'create_group',
        'admin-link',
    );
}

function seo_request_host($host) {
    $host = strtolower(trim((string) $host));
    $host = preg_replace('/:\d+\z/', '', $host);
    return rtrim($host, '.');
}

function seo_site_host_match($host, &$is_www) {
    $is_www = false;
    $host = seo_request_host($host);
    if ($host === '') {
        return false;
    }
    $canonical_host = strtolower((string) parse_url(canonical_base_url(), PHP_URL_HOST));
    $aliases = array($canonical_host);
    if ($canonical_host === 'xn--wichtl-gua.ch') {
        $aliases[] = 'wichtlä.ch';
    }
    foreach ($aliases as $alias) {
        if ($alias === '') {
            continue;
        }
        if ($host === $alias) {
            return true;
        }
        if ($host === 'www.' . $alias) {
            $is_www = true;
            return true;
        }
    }
    return false;
}

function seo_safe_local_path($path) {
    if (!is_string($path) || $path === '' || $path[0] !== '/') {
        return null;
    }
    if (preg_match('/[\x00-\x20\x7F\\\\]/', $path) === 1) {
        return null;
    }
    if (strpos($path, '//') !== false || strpos($path, '..') !== false || strpos($path, ':') !== false) {
        return null;
    }
    if (preg_match('#\A/(?:[A-Za-z0-9._~-]+/)*[A-Za-z0-9._~-]*\z#', $path) !== 1) {
        return null;
    }
    return $path;
}

/**
 * Kanonischer Pfad für eine alte öffentliche .php-URL, sonst null.
 * /index.php wird /, /fr/index.php wird /fr/.
 */
function seo_clean_legacy_php_path($path) {
    $path = (string) $path;
    if (preg_match('#\A/(?:(?P<lang>en|fr)/)?(?P<slug>[A-Za-z0-9_-]+)\.php/?\z#', $path, $matches) !== 1) {
        return null;
    }
    $slug = $matches['slug'];
    if (!in_array($slug, seo_legacy_php_slugs(), true)) {
        return null;
    }
    $lang = (isset($matches['lang']) && ($matches['lang'] === 'en' || $matches['lang'] === 'fr'))
        ? $matches['lang']
        : '';
    $prefix = ($lang === '') ? '' : '/' . $lang;
    if ($slug === 'index') {
        return ($prefix === '') ? '/' : $prefix . '/';
    }
    return $prefix . '/' . $slug;
}

/**
 * Ziel einer 301-Umleitung, oder null.
 * Nur GET und HEAD. POST bleibt auf der aufgerufenen Adresse, damit Formulare
 * nicht die Nutzdaten verlieren. /api/ und Token-Seiten behalten .php.
 * www wird auf den kanonischen Host gezogen, die Query bleibt roh erhalten.
 *
 * $request_uri ist der Originalpfad (REQUEST_URI). SCRIPT_NAME und PHP_SELF
 * zeigen nach einem internen Rewrite oder unter PHP-FPM auf faq.php, auch
 * wenn der Browser /faq aufgerufen hat. Die hier nicht verwenden.
 */
function seo_legacy_redirect_target($method, $host, $request_uri) {
    $method = strtoupper(trim((string) $method));
    if ($method !== 'GET' && $method !== 'HEAD') {
        return null;
    }
    $request_uri = (string) $request_uri;
    if ($request_uri === '' || preg_match('/[\r\n\x00]/', $request_uri) === 1 || preg_match('/[\r\n\x00]/', (string) $host) === 1) {
        return null;
    }
    $parts = parse_url($request_uri);
    if (!is_array($parts) || !isset($parts['path']) || !is_string($parts['path'])) {
        return null;
    }
    $path = seo_safe_local_path($parts['path']);
    if ($path === null) {
        return null;
    }
    $query = (isset($parts['query']) && is_string($parts['query'])) ? $parts['query'] : '';
    if ($query !== '' && preg_match('/[\x00-\x1F\x7F]/', $query) === 1) {
        return null;
    }

    $clean = seo_clean_legacy_php_path($path);
    $is_www = false;
    $on_site = seo_site_host_match($host, $is_www);
    if ($clean === null && !$is_www) {
        return null;
    }
    $target_path = ($clean === null) ? $path : $clean;
    if (!$is_www && $target_path === $path) {
        return null;
    }
    if (!$on_site && $clean === null) {
        return null;
    }

    $location = $target_path . ($query !== '' ? '?' . $query : '');
    if ($on_site) {
        return canonical_base_url() . $location;
    }
    return $location;
}

/**
 * Öffentliche Datei mit Versionsquery, damit ein neues CSS oder JS
 * den langen Cache-Control-Header aus public/.htaccess bricht.
 */
function public_asset_href($web_path) {
    $web_path = '/' . ltrim((string) $web_path, '/');
    if (strpos($web_path, '..') !== false || strpos($web_path, '?') !== false) {
        return $web_path;
    }
    $full = dirname(__DIR__) . '/public' . $web_path;
    if (!is_file($full)) {
        return $web_path;
    }
    $mtime = filemtime($full);
    if ($mtime === false) {
        return $web_path;
    }
    return $web_path . '?v=' . $mtime;
}

/**
 * Liest nur Methode, Host und REQUEST_URI. SCRIPT_NAME und PHP_SELF bleiben
 * aussen vor, sonst leitet /faq nach dem internen Rewrite auf faq.php wieder
 * auf /faq um.
 */
function seo_legacy_redirect_from_server(array $server) {
    $method = isset($server['REQUEST_METHOD']) ? (string) $server['REQUEST_METHOD'] : '';
    $host = isset($server['HTTP_HOST']) ? (string) $server['HTTP_HOST'] : '';
    $uri = isset($server['REQUEST_URI']) ? (string) $server['REQUEST_URI'] : '';
    return seo_legacy_redirect_target($method, $host, $uri);
}

function seo_maybe_redirect_legacy() {
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }
    $target = seo_legacy_redirect_from_server($_SERVER);
    if ($target === null || $target === '') {
        return;
    }
    header('Location: ' . $target, true, 301);
    header('Cache-Control: public, max-age=86400');
    exit;
}
