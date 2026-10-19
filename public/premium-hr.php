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

start_secure_session();
$admin_token = isset($_GET['token']) ? (string) $_GET['token'] : '';
$pdo = db_connect();
$stmt = $pdo->prepare('SELECT * FROM `groups` WHERE `admin_token` = ?');
$stmt->execute(array($admin_token));
$group = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$group) {
    i18n_boot_token(null);
    http_response_code(404);
    die(h(t('error.token')));
}

i18n_boot_token(i18n_row_language($group));
$license = premium_license_for_group($pdo, $group);
if (!$license) {
    http_response_code(404);
    token_page_open(t('premium.hr.title'));
    echo '<div class="container app-page"><p>' . h(t('premium.copy.need')) . '</p></div>';
    token_page_close();
    exit;
}

$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_ok = verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '');
    $unlocked = premium_reveal_unlock($_POST, $_SESSION, $group['id'], $csrf_ok);
    $_SESSION = $unlocked['session'];
    if ($unlocked['error'] === '') {
        header('Location: /premium-aufdeckung.php?token=' . urlencode($admin_token));
        exit;
    }
    if ($unlocked['error'] === 'csrf') {
        $notice = csrf_failure_message();
    } else {
        $notice = t('premium.hr.live_need');
    }
}

$people = $pdo->prepare('SELECT `id`, `name`, `email`, `wishlist` FROM `participants` WHERE `group_id` = ?');
$people->execute(array((int) $group['id']));
$rows = premium_hr_rows($people->fetchAll(PDO::FETCH_ASSOC), $group);
token_page_open(t('premium.hr.title'));
?>
    <div class="container app-page<?php echo premium_page_class($license); ?>"<?php echo premium_page_style_attr($license); ?>>
        <header class="app-hero">
            <p class="app-kicker"><?php echo h($group['name']); ?></p>
            <h1><?php echo h(t('premium.hr.title')); ?></h1>
            <p class="app-hero-lead"><?php echo h(t('premium.hr.lead')); ?></p>
            <?php echo premium_hero_extras_html($license); ?>
        </header>
        <?php if ($notice !== ''): ?>
            <div class="notification error" role="alert"><?php echo h($notice); ?></div>
        <?php endif; ?>
        <section class="section-card">
            <?php echo premium_hr_table_html($rows); ?>
        </section>
        <section class="section-card" id="aufdeckung">
            <h2><?php echo h(t('premium.hr.live')); ?></h2>
            <p><?php echo h(t('premium.hr.live_help')); ?></p>
            <?php if (empty($group['is_drawn'])): ?>
                <p><?php echo h(t('premium.hr.not_drawn')); ?></p>
            <?php else: ?>
                <form method="post">
                    <?php echo csrf_input(); ?>
                    <label class="reveal-confirm-label" for="confirm-reveal">
                        <input type="checkbox" id="confirm-reveal" name="confirm_reveal" value="1">
                        <?php echo h(t('premium.hr.live_confirm')); ?>
                    </label>
                    <button type="submit" class="button primary"><?php echo h(t('premium.hr.live_start')); ?></button>
                </form>
            <?php endif; ?>
        </section>
    </div>
<?php
token_page_close();
