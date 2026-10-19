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
$token = isset($_GET['token']) ? (string) $_GET['token'] : '';
$pdo = db_connect();
$license = premium_tables_ready($pdo) ? premium_fetch_license_by_access($pdo, $token) : null;
if (!$license || !isset($license['status']) || $license['status'] !== 'active') {
    i18n_boot_token('de');
    token_page_open(t('premium.overview.invalid'));
    echo '<div class="container app-page"><p>' . h(t('premium.overview.invalid')) . '</p></div>';
    token_page_close();
    exit;
}

i18n_boot_token(isset($license['language']) ? $license['language'] : 'de');
$notice = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $notice = array('type' => 'error', 'text' => csrf_failure_message());
    } else {
        $binary = null;
        $logo_name = '';
        if (isset($_FILES['logo']) && is_array($_FILES['logo']) && (int) $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $tmp = isset($_FILES['logo']['tmp_name']) ? (string) $_FILES['logo']['tmp_name'] : '';
            $size = isset($_FILES['logo']['size']) ? (int) $_FILES['logo']['size'] : 0;
            if ($tmp !== '' && is_uploaded_file($tmp) && $size > 0 && $size <= 2097152) {
                $binary = file_get_contents($tmp);
                $logo_name = isset($_FILES['logo']['name']) ? (string) $_FILES['logo']['name'] : '';
            } else {
                $binary = '';
            }
        }
        $saved = premium_save_branding($pdo, $license, $_POST, $binary, $logo_name);
        if (!empty($saved['ok'])) {
            $notice = array('type' => 'success', 'text' => t('premium.brand.saved'));
            $license = premium_fetch_license($pdo, $license['id']);
        } elseif (isset($saved['error']) && $saved['error'] === 'logo') {
            $notice = array('type' => 'error', 'text' => t('premium.brand.bad_logo'));
        } elseif (isset($saved['error']) && $saved['error'] === 'color') {
            $notice = array('type' => 'error', 'text' => t('premium.brand.bad_color'));
        } elseif (isset($saved['error']) && $saved['error'] === 'greeting') {
            $notice = array('type' => 'error', 'text' => t('premium.brand.greeting_locked'));
        } else {
            $notice = array('type' => 'error', 'text' => t('premium.brand.bad_logo'));
        }
    }
}

$groups = premium_groups_for_license($pdo, $license['id']);
$used = count($groups);
$def = premium_tier($license['tier']);
$allow_greeting = ($def === null) || !empty($def['greeting']);
token_page_open(t('premium.overview.title'));
?>
    <div class="container app-page<?php echo premium_page_class($license); ?>"<?php echo premium_page_style_attr($license); ?>>
        <header class="app-hero">
            <p class="app-kicker"><?php echo h(premium_tier_label($license['tier'])); ?></p>
            <h1><?php echo h(t('premium.overview.title')); ?></h1>
            <p class="app-hero-lead"><?php echo h(t('premium.overview.lead')); ?></p>
            <?php echo premium_hero_extras_html($license); ?>
            <p><?php echo h(premium_slots_text($license, $used)); ?></p>
        </header>
        <?php if (is_array($notice) && $notice['text'] !== ''): ?>
            <div class="notification <?php echo ($notice['type'] === 'success') ? 'success' : 'error'; ?>" role="status"><?php echo h($notice['text']); ?></div>
        <?php endif; ?>
        <section class="section-card" id="erscheinung">
            <h2><?php echo h(t('premium.brand.title')); ?></h2>
            <form method="post" enctype="multipart/form-data">
                <?php echo csrf_input(); ?>
                <label for="premium-logo"><?php echo h(t('premium.brand.logo')); ?></label>
                <input type="file" id="premium-logo" name="logo" accept="image/png,image/jpeg,image/gif,image/webp">
                <label for="color_primary"><?php echo h(t('premium.brand.primary')); ?></label>
                <input type="text" id="color_primary" name="color_primary" value="<?php echo h(isset($license['color_primary']) ? $license['color_primary'] : ''); ?>" placeholder="#e63946" maxlength="7">
                <label for="color_accent"><?php echo h(t('premium.brand.accent')); ?></label>
                <input type="text" id="color_accent" name="color_accent" value="<?php echo h(isset($license['color_accent']) ? $license['color_accent'] : ''); ?>" placeholder="#2a9d8f" maxlength="7">
                <?php if ($allow_greeting): ?>
                    <label for="greeting"><?php echo h(t('premium.brand.greeting')); ?></label>
                    <textarea id="greeting" name="greeting" rows="3" maxlength="2000"><?php echo h(isset($license['greeting']) ? $license['greeting'] : ''); ?></textarea>
                <?php endif; ?>
                <button type="submit" class="button primary"><?php echo h(t('premium.brand.save')); ?></button>
            </form>
        </section>
        <section class="section-card" id="gruppen">
            <h2><?php echo h(t('premium.overview.groups')); ?></h2>
            <?php if (count($groups) === 0): ?>
                <p><?php echo h(t('premium.overview.empty')); ?></p>
            <?php else: ?>
                <ul class="premium-group-list">
                    <?php foreach ($groups as $group): ?>
                        <li>
                            <?php echo h($group['name']); ?>
                            · <?php echo (int) $group['participant_count']; ?>
                            · <a href="/admin.php?token=<?php echo h(urlencode($group['admin_token'])); ?>"><?php echo h(t('premium.overview.open_admin')); ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
<?php
token_page_close();
