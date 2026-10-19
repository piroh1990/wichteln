<?php
require_once __DIR__ . '/../includes/functions.php';

if (!premium_feature_enabled()) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '404';
    exit;
}

$config = premium_stripe_config();
if (!premium_stripe_is_configured($config)) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo '404';
    exit;
}

$payload = file_get_contents('php://input');
if (!is_string($payload)) {
    $payload = '';
}
$header = isset($_SERVER['HTTP_STRIPE_SIGNATURE']) ? (string) $_SERVER['HTTP_STRIPE_SIGNATURE'] : '';
$pdo = db_connect();
$result = premium_handle_webhook($pdo, $payload, $header, $config['webhook'], null, null, $config);
$response = premium_webhook_response($result);
http_response_code($response['http']);
header('Content-Type: application/json; charset=UTF-8');
echo $response['body'];
