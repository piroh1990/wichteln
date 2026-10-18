<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/templates/public_shell.php';

start_secure_session();

$request_path = isset($_SERVER['REQUEST_URI']) ? (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
if (preg_match('#^/(fr|en)/admin-link/?$#', $request_path) === 1) {
    header('Location: /admin-link', true, 301);
    exit;
}

i18n_apply_explicit_choice();
i18n_boot_token(null);

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_email = trim(isset($_POST['admin_email']) ? $_POST['admin_email'] : '');
    $captcha_answer = trim(isset($_POST['captcha_answer']) ? $_POST['captcha_answer'] : '');
    $client = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';

    $captcha_ok = consume_captcha_answer($captcha_answer);

    if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $error = csrf_failure_message();
    } elseif (!admin_link_recovery_allowed($client)) {
        $error = t('error.rate_admin');
    } elseif ($admin_email === '' || ($length_error = limit_text_error($admin_email, 255, t('field.email'))) !== '') {
        $error = ($admin_email === '') ? t('error.email_invalid') : $length_error;
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = t('error.email_invalid');
    } elseif (!$captcha_ok) {
        $error = t('captcha.wrong');
    } else {
        $success = true;

        try {
            $pdo = db_connect();
            $columns = '`name`, `admin_token`';
            if (i18n_has_language_column($pdo, 'groups')) {
                $columns .= ', `language`';
            }
            $stmt = $pdo->prepare('SELECT ' . $columns . ' FROM `groups` WHERE `admin_email` = ? ORDER BY `created_at` DESC LIMIT 20');
            $stmt->execute(array($admin_email));
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $buckets = array();
            foreach ($groups as $group) {
                if (empty($group['admin_token'])) {
                    continue;
                }
                $lang = mail_recipient_lang($group);
                if (!isset($buckets[$lang])) {
                    $buckets[$lang] = array();
                }
                $buckets[$lang][] = array(
                    'name' => isset($group['name']) ? $group['name'] : '',
                    'admin_link' => get_display_url('/admin.php?token=' . rawurlencode($group['admin_token'])),
                );
            }
            foreach ($buckets as $lang => $payload) {
                if (!send_email(
                    $admin_email,
                    admin_recovery_email_subject($lang),
                    create_admin_recovery_email($payload, $lang),
                    true,
                    create_admin_recovery_plain_text($payload, $lang)
                )) {
                    error_log('Admin-Link konnte nicht erneut gesendet werden.');
                }
            }
        } catch (Exception $e) {
            error_log('Admin-Link-Wiederherstellung fehlgeschlagen: ' . $e->getMessage());
        }
    }
}

token_page_open(t('meta.adminlink.title'), t('meta.adminlink.description'), array(
    'robots' => '',
    'referrer' => false,
    'matomo' => true,
    'canonical_path' => '/admin-link',
));
?>
    <div class="container">
        <h1><?php echo h(t('recovery.heading')); ?></h1>
        <p><?php echo h(t('recovery.lead')); ?></p>

        <?php if (!empty($error)): ?>
            <div class="notification error" role="alert" aria-live="assertive">
                <?php echo h($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="notification success" role="status" aria-live="polite">
                <?php echo h(t('recovery.success')); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="admin-link-form">
            <?php echo csrf_input(); ?>
            <div class="form-group">
                <label for="admin_email"><?php echo h(t('recovery.email')); ?><span class="required-indicator" aria-hidden="true" title="<?php echo h(t('captcha.required')); ?>">*</span></label>
                <input type="email" id="admin_email" name="admin_email" required placeholder="<?php echo h(t('create.email_ph')); ?>" autocomplete="email" value="<?php echo isset($admin_email) ? h($admin_email) : ''; ?>">
            </div>
            <div class="form-group captcha-group">
                <label for="captcha_answer"><?php echo h(t('captcha.label')); ?><span class="required-indicator" aria-hidden="true" title="<?php echo h(t('captcha.required')); ?>">*</span></label>
                <div class="captcha-container">
                    <img src="/captcha.php" alt="<?php echo h(t('captcha.alt')); ?>" id="captcha-image" class="captcha-image">
                    <button type="button" onclick="refreshCaptcha()" class="button secondary small refresh-captcha" title="<?php echo h(t('captcha.reload')); ?>" aria-label="<?php echo h(t('captcha.reload')); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                        </svg>
                    </button>
                </div>
                <?php echo captcha_answer_field(); ?>
                <small id="captcha_hint" class="form-hint"><?php echo h(captcha_hint_text(t('captcha.same_image'))); ?></small>
            </div>
            <button type="submit" class="button primary"><?php echo h(t('recovery.submit')); ?></button>
        </form>
    </div>
    <script>
        function refreshCaptcha() {
            var img = document.getElementById('captcha-image');
            img.src = '/captcha.php?' + new Date().getTime();
        }
        document.addEventListener('DOMContentLoaded', function() {
            handleFormSubmit(document.getElementById('admin-link-form'), <?php echo json_encode(t('js.sending'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
        });
    </script>
<?php token_page_close(); ?>
