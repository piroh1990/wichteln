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

if (!gift_ics_signature_valid($group_id, $date, $signature)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Kalendereintrag nicht gefunden.';
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
    echo 'Kalendereintrag nicht gefunden.';
    exit;
}

$ics = build_gift_ics($group);
if ($ics === '') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Für diese Gruppe ist kein Geschenkdatum hinterlegt.';
    exit;
}

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="geschenkuebergabe.ics"');
echo $ics;
