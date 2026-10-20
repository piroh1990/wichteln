<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/templates/public_shell.php';

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store');
}

if (!premium_feature_enabled()) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo premium_unavailable_body();
    exit;
}

start_secure_session();
$raw = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw = isset($_POST['token']) ? (string) $_POST['token'] : '';
} elseif (isset($_GET['token'])) {
    $raw = (string) $_GET['token'];
}
$pdo = db_connect();
$preview = premium_confirmation_preview($pdo, $raw);
$ok = false;
$bad = ($preview === null);
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $notice = csrf_failure_message();
        $bad = ($preview === null);
    } elseif ($preview === null) {
        $bad = true;
    } else {
        $result = premium_confirm_token($pdo, $raw);
        $ok = !empty($result['ok']);
        $bad = !$ok;
        $preview = null;
    }
}
i18n_boot_token('de');
token_page_open($ok ? t('premium.confirm.ok') : ($bad ? t('premium.confirm.bad') : t('premium.confirm.preview_title')));
?>
    <div class="container app-page">
        <header class="app-hero">
            <p class="app-kicker"><?php echo h(t('footer.premium')); ?></p>
            <h1><?php echo h($ok ? t('premium.confirm.ok') : ($preview ? t('premium.confirm.preview_title') : t('premium.confirm.bad'))); ?></h1>
        </header>
        <section class="section-card">
            <?php if ($notice !== ''): ?>
                <p class="notification error" role="alert"><?php echo h($notice); ?></p>
            <?php endif; ?>
            <?php if ($ok): ?>
                <p><?php echo h(t('premium.confirm.ok')); ?></p>
            <?php elseif (is_array($preview)): ?>
                <p><?php echo h(t('premium.confirm.lead', array(
                    '{group}' => $preview['group_name'],
                    '{email}' => $preview['email'],
                ))); ?></p>
                <p><?php echo h(t('premium.confirm.preview_help')); ?></p>
                <form method="post" action="/premium-bestaetigen.php">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="token" value="<?php echo h(premium_token_format($raw)); ?>">
                    <button type="submit" class="button primary"><?php echo h(t('premium.confirm.button')); ?></button>
                </form>
            <?php else: ?>
                <p><?php echo h(t('premium.confirm.bad')); ?></p>
            <?php endif; ?>
            <p><a class="button secondary" href="<?php echo h(i18n_href('index')); ?>"><?php echo h(t('footer.home')); ?></a></p>
        </section>
    </div>
<?php
token_page_close();
