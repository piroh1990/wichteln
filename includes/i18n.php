<?php

/**
 * Sprachschicht für de, fr und en.
 * Sprachwerte kommen nur aus einer festen Liste und werden nie in Pfade gesetzt.
 */

function i18n_languages() {
    return array('de', 'fr', 'en');
}

function i18n_is_allowed($lang) {
    if (!is_string($lang)) {
        return false;
    }
    return in_array(strtolower(trim($lang)), i18n_languages(), true);
}

function i18n_allow($lang) {
    if (!i18n_is_allowed($lang)) {
        return 'de';
    }
    return strtolower(trim($lang));
}

function i18n_current() {
    if (isset($GLOBALS['i18n_lang']) && i18n_is_allowed($GLOBALS['i18n_lang'])) {
        return i18n_allow($GLOBALS['i18n_lang']);
    }
    return 'de';
}

function i18n_html_lang() {
    return i18n_current();
}

function i18n_locale() {
    switch (i18n_current()) {
        case 'fr':
            return 'fr_CH';
        case 'en':
            return 'en_GB';
        default:
            return 'de_CH';
    }
}

function i18n_hreflang($lang) {
    switch (i18n_allow($lang)) {
        case 'fr':
            return 'fr-CH';
        case 'en':
            return 'en';
        default:
            return 'de-CH';
    }
}

function i18n_catalog($lang) {
    $lang = i18n_allow($lang);
    if (!isset($GLOBALS['i18n_catalogs']) || !is_array($GLOBALS['i18n_catalogs'])) {
        $GLOBALS['i18n_catalogs'] = array();
    }
    if (isset($GLOBALS['i18n_catalogs'][$lang]) && is_array($GLOBALS['i18n_catalogs'][$lang])) {
        return $GLOBALS['i18n_catalogs'][$lang];
    }

    $loaded = array();
    if ($lang === 'fr') {
        $loaded = require dirname(__DIR__) . '/lang/fr.php';
    } elseif ($lang === 'en') {
        $loaded = require dirname(__DIR__) . '/lang/en.php';
    } else {
        $loaded = require dirname(__DIR__) . '/lang/de.php';
    }
    if (!is_array($loaded)) {
        $loaded = array();
    }
    $GLOBALS['i18n_catalogs'][$lang] = $loaded;
    return $loaded;
}

function i18n_activate($lang) {
    $lang = i18n_allow($lang);
    i18n_catalog($lang);
    if ($lang !== 'de') {
        i18n_catalog('de');
    }
    $GLOBALS['i18n_lang'] = $lang;
    return $lang;
}

function i18n_with_lang($lang, $callback) {
    $previous = i18n_current();
    i18n_activate($lang);
    try {
        return call_user_func($callback);
    } finally {
        i18n_activate($previous);
    }
}

function i18n_lookup($key, $lang) {
    $catalog = i18n_catalog($lang);
    if (isset($catalog[$key]) && is_string($catalog[$key])) {
        return $catalog[$key];
    }
    return null;
}

function t($key, $params = array()) {
    $key = (string) $key;
    if (substr($key, -13) !== '.with_premium'
        && function_exists('premium_feature_enabled')
        && premium_feature_enabled()
    ) {
        $alt = $key . '.with_premium';
        if (i18n_lookup($alt, i18n_current()) !== null || i18n_lookup($alt, 'de') !== null) {
            $key = $alt;
        }
    }
    $text = i18n_lookup($key, i18n_current());
    if ($text === null && i18n_current() !== 'de') {
        $text = i18n_lookup($key, 'de');
    }
    if ($text === null) {
        $text = $key;
    }
    if (!is_array($params) || count($params) === 0) {
        return $text;
    }
    $map = array();
    foreach ($params as $name => $value) {
        $map[(string) $name] = (string) $value;
    }
    return strtr($text, $map);
}

function tn($key, $count, $params = array()) {
    $count = (int) $count;
    if (i18n_current() === 'fr') {
        $form = ($count === 0 || $count === 1) ? 'one' : 'other';
    } else {
        $form = ($count === 1) ? 'one' : 'other';
    }
    if (!is_array($params)) {
        $params = array();
    }
    $params['{count}'] = (string) $count;
    return t($key . '.' . $form, $params);
}

function h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function fmt_date($value, $with_time = false) {
    if ($value === null || $value === '') {
        return '';
    }
    if (is_numeric($value) && !is_string($value)) {
        $timestamp = (int) $value;
    } else {
        $timestamp = strtotime((string) $value);
    }
    if ($timestamp === false) {
        return '';
    }
    if (i18n_current() === 'en') {
        $date = date('j M Y', $timestamp);
    } else {
        $date = date('d.m.Y', $timestamp);
    }
    if (!$with_time) {
        return $date;
    }
    return t('date.datetime', array(
        '{date}' => $date,
        '{time}' => date('H:i', $timestamp),
    ));
}

function fmt_money($amount) {
    if ($amount === null || $amount === '' || !is_numeric($amount)) {
        return t('value.unset');
    }
    $number = number_format((float) $amount, 2, '.', '');
    if (i18n_current() === 'en') {
        return 'CHF ' . $number;
    }
    return $number . ' CHF';
}

function i18n_prefix($lang) {
    switch (i18n_allow($lang)) {
        case 'fr':
            return '/fr';
        case 'en':
            return '/en';
        default:
            return '';
    }
}

function i18n_public_slugs() {
    return array(
        'index',
        'faq',
        'was-ist-wichteln',
        'impressum',
        'datenschutz',
        'wichtel-ideen',
        'firmenwichteln-tipps',
        'ueber-uns',
        'unterstuetzen',
        'firmen-premium',
        'create_group',
    );
}

function i18n_public_url($slug, $lang = null) {
    if (!in_array($slug, i18n_public_slugs(), true)) {
        $slug = 'index';
    }
    if ($lang === null) {
        $lang = i18n_current();
    }
    $prefix = i18n_prefix($lang);
    if ($slug === 'index') {
        return ($prefix === '') ? '/' : $prefix . '/';
    }
    return $prefix . '/' . $slug;
}

function i18n_href($slug) {
    if ($slug === 'participant') {
        return '/participant.php';
    }
    if ($slug === 'admin-link') {
        return '/admin-link';
    }
    return i18n_public_url($slug, i18n_current());
}

function i18n_set_public_page($slug) {
    if (!in_array($slug, i18n_public_slugs(), true)) {
        $slug = 'index';
    }
    $GLOBALS['i18n_public_slug'] = $slug;
    $GLOBALS['i18n_is_token_page'] = false;
}

function i18n_mark_token_page() {
    $GLOBALS['i18n_is_token_page'] = true;
}

function i18n_token_base_path() {
    $script = isset($_SERVER['SCRIPT_NAME']) ? basename((string) $_SERVER['SCRIPT_NAME']) : '';
    if ($script === 'admin-link.php') {
        return '/admin-link';
    }
    $path = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '/';
    if ($path === '' || $path[0] !== '/') {
        $path = '/' . ltrim($path, '/');
    }
    return $path;
}

function i18n_token_page_url() {
    $query = $_GET;
    unset($query['setlang'], $query['lang']);
    $qs = http_build_query($query);
    return i18n_token_base_path() . ($qs !== '' ? '?' . $qs : '');
}

function i18n_page_url($lang) {
    if (!empty($GLOBALS['i18n_is_token_page'])) {
        return i18n_token_page_url();
    }
    $slug = isset($GLOBALS['i18n_public_slug']) ? $GLOBALS['i18n_public_slug'] : 'index';
    return i18n_public_url($slug, $lang);
}

function i18n_switch_href($lang) {
    $lang = i18n_allow($lang);
    $url = i18n_page_url($lang);
    $separator = (strpos($url, '?') === false) ? '?' : '&';
    return $url . $separator . 'setlang=' . $lang;
}

function i18n_absolute_public_url($slug, $lang = null) {
    if (!function_exists('get_display_url')) {
        return i18n_public_url($slug, $lang);
    }
    return get_display_url(i18n_public_url($slug, $lang));
}

function i18n_requested_choice() {
    if (!isset($_GET['setlang'])) {
        return null;
    }
    $value = strtolower(trim((string) $_GET['setlang']));
    if (!i18n_is_allowed($value)) {
        return null;
    }
    return i18n_allow($value);
}

function i18n_set_choice_cookie($lang) {
    $lang = i18n_allow($lang);
    $secure = function_exists('request_is_https') ? request_is_https() : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('lang', $lang, array(
        'expires' => time() + 365 * 24 * 60 * 60,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    $_COOKIE['lang'] = $lang;
}

function i18n_cookie_lang() {
    if (!isset($_COOKIE['lang'])) {
        return null;
    }
    $value = strtolower(trim((string) $_COOKIE['lang']));
    if (!i18n_is_allowed($value)) {
        return null;
    }
    return i18n_allow($value);
}

function i18n_from_accept() {
    $header = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
    if ($header === '') {
        return null;
    }
    $ranked = array();
    foreach (explode(',', $header) as $part) {
        $part = trim($part);
        if ($part === '') {
            continue;
        }
        $bits = explode(';', $part);
        $tag = strtolower(trim($bits[0]));
        $quality = 1.0;
        if (isset($bits[1]) && preg_match('/q=([0-9.]+)/', $bits[1], $matches)) {
            $quality = (float) $matches[1];
        }
        $primary = substr($tag, 0, 2);
        if (!i18n_is_allowed($primary)) {
            continue;
        }
        $ranked[] = array('lang' => i18n_allow($primary), 'q' => $quality);
    }
    if (count($ranked) === 0) {
        return null;
    }
    usort($ranked, function ($left, $right) {
        if ($left['q'] === $right['q']) {
            return 0;
        }
        return ($left['q'] > $right['q']) ? -1 : 1;
    });
    return $ranked[0]['lang'];
}

function i18n_safe_local_path($path) {
    $path = (string) $path;
    if ($path === '' || $path[0] !== '/') {
        return '/';
    }
    $decoded = rawurldecode($path);
    if ($decoded !== $path) {
        $path = $decoded;
    }
    if (preg_match('/[\x00-\x20\x7F\\\\]/', $path) === 1) {
        return '/';
    }
    if (strpos($path, '//') !== false || strpos($path, ':') !== false || strpos($path, '..') !== false) {
        return '/';
    }
    if (preg_match('#\A/(?:[A-Za-z0-9._~-]+/)*[A-Za-z0-9._~-]*\z#', $path) !== 1) {
        return '/';
    }
    return $path;
}

function i18n_redirect_location_from_request($uri, array $query) {
    $path = parse_url((string) $uri, PHP_URL_PATH);
    if (!is_string($path)) {
        $path = '/';
    }
    $path = i18n_safe_local_path($path);
    unset($query['setlang'], $query['lang']);
    $clean = array();
    foreach ($query as $key => $value) {
        if (!is_string($key) || preg_match('/\A[A-Za-z0-9_-]{1,40}\z/', $key) !== 1) {
            continue;
        }
        if (is_array($value)) {
            continue;
        }
        $clean[$key] = (string) $value;
    }
    $qs = http_build_query($clean);
    return $path . ($qs !== '' ? '?' . $qs : '');
}

function i18n_redirect_drop_setlang() {
    if (headers_sent()) {
        return;
    }
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $query = isset($_GET) && is_array($_GET) ? $_GET : array();
    header('Location: ' . i18n_redirect_location_from_request($uri, $query));
    exit;
}

function i18n_boot_public() {
    $choice = i18n_requested_choice();
    if ($choice !== null) {
        i18n_set_choice_cookie($choice);
        i18n_redirect_drop_setlang();
    }
    $lang = 'de';
    if (isset($_GET['lang']) && i18n_is_allowed($_GET['lang'])) {
        $lang = i18n_allow($_GET['lang']);
    }
    i18n_activate($lang);
}

function i18n_row_language($row) {
    if (!is_array($row) || !array_key_exists('language', $row)) {
        return null;
    }
    if (!i18n_is_allowed($row['language'])) {
        return null;
    }
    return i18n_allow($row['language']);
}

function i18n_resolve_token($db_lang, $explicit = null) {
    if (is_string($explicit) && i18n_is_allowed($explicit)) {
        return i18n_allow($explicit);
    }
    if (is_string($db_lang) && i18n_is_allowed($db_lang)) {
        return i18n_allow($db_lang);
    }
    $cookie = i18n_cookie_lang();
    if ($cookie !== null) {
        return $cookie;
    }
    $accept = i18n_from_accept();
    if ($accept !== null) {
        return $accept;
    }
    return 'de';
}

function i18n_boot_token($db_lang, $explicit = null) {
    i18n_mark_token_page();
    i18n_activate(i18n_resolve_token($db_lang, $explicit));
}

function i18n_register_session_key($invite_token) {
    $safe = preg_replace('/[^a-zA-Z0-9]/', '', (string) $invite_token);
    return 'register_lang_' . $safe;
}

function i18n_boot_register($group, $invite_token) {
    i18n_mark_token_page();
    $key = i18n_register_session_key($invite_token);
    $choice = i18n_requested_choice();
    if ($choice !== null) {
        i18n_set_choice_cookie($choice);
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[$key] = $choice;
        }
        i18n_redirect_drop_setlang();
    }
    $explicit = null;
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION[$key]) && i18n_is_allowed($_SESSION[$key])) {
        $explicit = i18n_allow($_SESSION[$key]);
    }
    $db_lang = is_array($group) ? i18n_row_language($group) : null;
    i18n_activate(i18n_resolve_token($db_lang, $explicit));
}

function i18n_language_select($name, $selected, $id) {
    $selected = is_string($selected) && i18n_is_allowed($selected) ? i18n_allow($selected) : i18n_current();
    $html = '<select id="' . h($id) . '" name="' . h($name) . '">';
    foreach (i18n_languages() as $code) {
        $html .= '<option value="' . h($code) . '"' . ($code === $selected ? ' selected' : '') . '>' . h(t('lang.name.' . $code)) . '</option>';
    }
    $html .= '</select>';
    return $html;
}

function i18n_apply_explicit_choice($pdo = null, $table = null, $id = null) {
    $choice = i18n_requested_choice();
    if ($choice === null) {
        return null;
    }
    i18n_set_choice_cookie($choice);
    if ($pdo instanceof PDO && ($table === 'groups' || $table === 'participants') && (int) $id > 0) {
        i18n_save_language($pdo, $table, (int) $id, $choice);
    }
    i18n_redirect_drop_setlang();
    return $choice;
}

function i18n_has_language_column($pdo, $table) {
    static $cache = array();
    if (!$pdo instanceof PDO) {
        return false;
    }
    if ($table !== 'groups' && $table !== 'participants') {
        return false;
    }
    $key = spl_object_hash($pdo) . ':' . $table;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    if (!function_exists('mysql_column_exists')) {
        $cache[$key] = false;
        return false;
    }
    try {
        $cache[$key] = mysql_column_exists($pdo, $table, 'language');
    } catch (Exception $e) {
        $cache[$key] = false;
    }
    return $cache[$key];
}

function i18n_save_language($pdo, $table, $id, $lang) {
    if (!$pdo instanceof PDO) {
        return false;
    }
    if ($table !== 'groups' && $table !== 'participants') {
        return false;
    }
    $id = (int) $id;
    if ($id < 1 || !i18n_has_language_column($pdo, $table)) {
        return false;
    }
    $lang = i18n_allow($lang);
    try {
        $stmt = $pdo->prepare('UPDATE `' . $table . '` SET `language` = ? WHERE `id` = ?');
        $stmt->execute(array($lang, $id));
        return true;
    } catch (Exception $e) {
        error_log('Sprache konnte nicht gespeichert werden: ' . $e->getMessage());
        return false;
    }
}

function i18n_insert_group($pdo, $name, $admin_token, $invite_token, $admin_email, $budget, $description, $gift_date, $lang) {
    $lang = i18n_allow($lang);
    if (i18n_has_language_column($pdo, 'groups')) {
        try {
            $stmt = $pdo->prepare('INSERT INTO `groups` (`name`, `admin_token`, `invite_token`, `admin_email`, `language`, `budget`, `description`, `gift_exchange_date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute(array($name, $admin_token, $invite_token, $admin_email, $lang, $budget, $description, $gift_date));
            return (int) $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log('Gruppe mit Sprache anlegen fehlgeschlagen, Versuch ohne Spalte: ' . $e->getMessage());
        }
    }
    $stmt = $pdo->prepare('INSERT INTO `groups` (`name`, `admin_token`, `invite_token`, `admin_email`, `budget`, `description`, `gift_exchange_date`) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute(array($name, $admin_token, $invite_token, $admin_email, $budget, $description, $gift_date));
    return (int) $pdo->lastInsertId();
}

function i18n_insert_participant($pdo, $group_id, $name, $email, $token, $lang) {
    $lang = i18n_allow($lang);
    if (i18n_has_language_column($pdo, 'participants')) {
        try {
            $stmt = $pdo->prepare('INSERT INTO `participants` (`group_id`, `name`, `email`, `language`, `participant_token`) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute(array($group_id, $name, $email, $lang, $token));
            return (int) $pdo->lastInsertId();
        } catch (Exception $e) {
            error_log('Teilnehmer mit Sprache anlegen fehlgeschlagen, Versuch ohne Spalte: ' . $e->getMessage());
        }
    }
    $stmt = $pdo->prepare('INSERT INTO `participants` (`group_id`, `name`, `email`, `participant_token`) VALUES (?, ?, ?, ?)');
    $stmt->execute(array($group_id, $name, $email, $token));
    return (int) $pdo->lastInsertId();
}

function i18n_content_path($page, $lang) {
    if (!in_array($page, i18n_public_slugs(), true)) {
        return '';
    }
    switch (i18n_allow($lang)) {
        case 'fr':
            $dir = dirname(__DIR__) . '/content/fr';
            break;
        case 'en':
            $dir = dirname(__DIR__) . '/content/en';
            break;
        default:
            $dir = dirname(__DIR__) . '/content/de';
            break;
    }
    return $dir . '/' . $page . '.php';
}

function i18n_include_content($page) {
    $path = i18n_content_path($page, i18n_current());
    if ($path === '' || !is_file($path)) {
        $path = i18n_content_path($page, 'de');
    }
    if ($path !== '' && is_file($path)) {
        include $path;
    }
}

function i18n_js_keys() {
    return array(
        'nav.menu_open',
        'nav.menu_close',
        'js.copied',
        'js.copy_fail',
        'js.loading',
        'js.creating',
        'js.registering',
        'js.sending',
        'js.saving',
        'js.wishlist_copied',
        'admin.confirm.delete_participant',
        'admin.confirm.delete_exclusion',
        'admin.confirm.draw',
        'admin.confirm.reset_draw',
        'admin.confirm.delete_group',
        'admin.confirm.resend_partner',
        'admin.confirm.reveal',
        'admin.confirm.reveal_again',
        'admin.confirm.reminder',
        'admin.confirm.reminder_again',
    );
}

function i18n_script_tag() {
    $data = array();
    foreach (i18n_js_keys() as $key) {
        $data[$key] = t($key);
    }
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    if (!is_string($json)) {
        $json = '{}';
    }
    return '<script>window.I18N=' . $json . ';</script>';
}

function i18n_confirm_attrs($key, $vars = array()) {
    $html = ' data-i18n-confirm="' . h($key) . '"';
    if (is_array($vars) && count($vars) > 0) {
        $json = json_encode($vars, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        if (is_string($json)) {
            $html .= ' data-i18n-confirm-vars="' . h($json) . '"';
        }
    }
    return $html;
}

function i18n_show_switcher() {
    $script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
    return strpos($script, '/admin/') === false;
}

if (!isset($GLOBALS['i18n_lang'])) {
    i18n_activate('de');
}
