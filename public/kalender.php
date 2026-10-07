<?php
require_once __DIR__ . '/../includes/functions.php';

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: private, no-store');
    header('Referrer-Policy: no-referrer');
}

$rolle = isset($_GET['rolle']) ? (string) $_GET['rolle'] : '';
$token = isset($_GET['token']) ? (string) $_GET['token'] : '';

if (($rolle !== 'admin' && $rolle !== 'teilnehmer') || !preg_match('/^[a-f0-9]{16,128}$/i', $token)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Kalendereintrag nicht gefunden.';
    exit;
}

$pdo = db_connect();
if ($rolle === 'admin') {
    $stmt = $pdo->prepare('SELECT * FROM `groups` WHERE `admin_token` = ?');
    $stmt->execute(array($token));
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare('SELECT g.* FROM `groups` g INNER JOIN `participants` p ON p.group_id = g.id WHERE p.participant_token = ?');
    $stmt->execute(array($token));
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
}

$ics = $group ? build_gift_ics($group) : '';
if ($ics === '') {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Für diese Gruppe ist kein Geschenkdatum hinterlegt.';
    exit;
}

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="geschenkuebergabe.ics"');
echo $ics;
