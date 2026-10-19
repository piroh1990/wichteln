<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/templates/public_shell.php';

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
}

if (!premium_feature_enabled()) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo premium_unavailable_body();
    exit;
}

$raw = isset($_GET['token']) ? (string) $_GET['token'] : '';
$pdo = db_connect();
$result = premium_confirm_token($pdo, $raw);
$ok = !empty($result['ok']);
i18n_boot_token('de');
token_page_open(t('premium.confirm.ok'));
?>
    <div class="container app-page">
        <header class="app-hero">
            <p class="app-kicker"><?php echo h(t('footer.premium')); ?></p>
            <h1><?php echo h($ok ? t('premium.confirm.ok') : t('premium.confirm.bad')); ?></h1>
        </header>
        <section class="section-card">
            <p><?php echo h($ok ? t('premium.confirm.ok') : t('premium.confirm.bad')); ?></p>
            <p><a class="button primary" href="<?php echo h(i18n_href('index')); ?>"><?php echo h(t('footer.home')); ?></a></p>
        </section>
    </div>
<?php
token_page_close();
