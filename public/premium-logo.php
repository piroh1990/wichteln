<?php
require_once __DIR__ . '/../includes/functions.php';

$name = isset($_GET['name']) ? (string) $_GET['name'] : '';
if (!premium_feature_enabled() || !premium_logo_filename_ok($name)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '404';
    exit;
}

$pdo = db_connect();
if (!premium_tables_ready($pdo)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '404';
    exit;
}

$stmt = $pdo->prepare('SELECT `id` FROM `premium_licenses` WHERE `logo_file` = ? AND `status` = ? LIMIT 1');
$stmt->execute(array($name, 'active'));
if (!$stmt->fetchColumn()) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '404';
    exit;
}

$path = premium_logo_resolve_path($name);
if ($path === '' || !is_file($path)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '404';
    exit;
}

header('Content-Type: image/png');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=86400');
header('X-Robots-Tag: noindex, nofollow');
readfile($path);
