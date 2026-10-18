<?php
require_once __DIR__ . '/../includes/functions.php';

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: private, no-store');
    header('Referrer-Policy: no-referrer');
}

$group_id = isset($_GET['g']) ? (int) $_GET['g'] : 0;
$date = isset($_GET['d']) ? (string) $_GET['d'] : '';
$signature = isset($_GET['s']) ? (string) $_GET['s'] : '';
$requested = isset($_GET['lang']) ? strtolower(trim((string) $_GET['lang'])) : '';
$lang = i18n_is_allowed($requested) ? i18n_allow($requested) : 'de';
i18n_activate($lang);

if (!gift_ics_signature_valid($group_id, $date, $signature)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo t('calendar.not_found');
    exit;
}

$pdo = db_connect();
$stmt = $pdo->prepare('SELECT * FROM `groups` WHERE `id` = ?');
$stmt->execute(array($group_id));
$group = $stmt->fetch(PDO::FETCH_ASSOC);

$stored_date = (is_array($group) && isset($group['gift_exchange_date'])) ? (string) $group['gift_exchange_date'] : '';
if (!is_array($group) || $stored_date !== $date) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo t('calendar.not_found');
    exit;
}

if (!i18n_is_allowed($requested)) {
    $from_row = i18n_row_language($group);
    $lang = ($from_row === null) ? 'de' : $from_row;
    i18n_activate($lang);
}

$ics = build_gift_ics($group, null, $lang);
if ($ics === '') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo t('calendar.no_date');
    exit;
}

$filename = t('calendar.filename');
$filename = str_replace(array("\r", "\n", '"'), '', $filename);
if ($filename === '') {
    $filename = 'geschenkuebergabe.ics';
}

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $ics;
