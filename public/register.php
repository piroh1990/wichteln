<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/templates/public_shell.php';

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
}

start_secure_session();

$invite_token = isset($_GET['token']) ? (string) $_GET['token'] : '';
$pdo = db_connect();

$stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `invite_token` = ?");
$stmt->execute(array($invite_token));
$group = $stmt->fetch(PDO::FETCH_ASSOC);

i18n_boot_register(is_array($group) ? $group : null, $invite_token);

if (!$group) {
    die(h(t('error.invite')));
}

if ($group['is_drawn']) {
    die(h(t('error.registration_closed')));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '') ?: null;

    if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $error = csrf_failure_message();
    } elseif (!registration_attempt_allowed($invite_token, request_client_ip())) {
        $error = t('error.rate_register');
    } elseif (empty($name)) {
        $error = t('error.name_empty');
    } elseif (($length_error = limit_text_error($name, 255, t('field.name'))) !== '') {
        $error = $length_error;
    } elseif ($email !== null && ($length_error = limit_text_error($email, 255, t('field.email'))) !== '') {
        $error = $length_error;
    } elseif ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = t('error.email_invalid');
    } else {
        $participant_token = generate_token();
        $lang = i18n_current();
        i18n_insert_participant($pdo, $group['id'], $name, $email, $participant_token, $lang);

        if ($email) {
            $participant_link = get_display_url('/participant.php?token=' . urlencode($participant_token));
            if (!send_registration_mail($email, $name, $group, $participant_link, $lang)) {
                error_log('E-Mail konnte nicht an ' . mask_email($email) . ' gesendet werden.');
            }
        }

        header('Location: /participant.php?token=' . urlencode($participant_token));
        exit();
    }
}

$presented = mail_present_group($group, i18n_current());
token_page_open(t('meta.register.title', array('{group}' => $group['name'])));
?>
    <div class="container app-page">
        <header class="app-hero">
            <p class="app-kicker"><?php echo h(t('register.kicker')); ?></p>
            <h1><?php echo h(t('register.heading', array('{group}' => $group['name']))); ?></h1>
            <p class="app-hero-lead"><?php echo h(t('register.lead')); ?></p>
        </header>
        <?php if (isset($error)): ?>
            <div class="notification error" role="alert" aria-live="assertive">
                <?php echo h($error); ?>
            </div>
        <?php endif; ?>

        <section class="section-card" aria-labelledby="register-group-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">ℹ️</span>
                <h2 id="register-group-heading"><?php echo h(t('register.about')); ?></h2>
            </div>
            <div class="group-info-grid">
                <div class="group-info-item">
                    <span class="info-label"><span aria-hidden="true">💰</span> <?php echo h(t('register.budget')); ?></span>
                    <span class="info-value"><?php echo h($presented['budget']); ?></span>
                </div>
                <div class="group-info-item">
                    <span class="info-label"><span aria-hidden="true">📅</span> <?php echo h(t('register.date')); ?></span>
                    <span class="info-value"><?php echo h($presented['gift_date']); ?></span>
                </div>
                <?php if (!empty($group['description'])): ?>
                <div class="group-info-item full-width">
                    <span class="info-label"><span aria-hidden="true">📄</span> <?php echo h(t('register.description')); ?></span>
                    <span class="info-value"><?php echo h($group['description']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-card" aria-labelledby="register-form-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">✏️</span>
                <h2 id="register-form-heading"><?php echo h(t('register.form')); ?></h2>
            </div>
            <form method="POST" id="register-form">
                <?php echo csrf_input(); ?>
                <div class="form-group">
                    <label for="name"><?php echo h(t('register.name')); ?><span class="required-indicator" aria-hidden="true" title="<?php echo h(t('captcha.required')); ?>">*</span></label>
                    <input type="text" id="name" name="name" required placeholder="<?php echo h(t('register.name_ph')); ?>" autocomplete="name" value="<?php echo isset($name) ? h($name) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="email"><?php echo h(t('register.email')); ?></label>
                    <input type="email" id="email" name="email" placeholder="<?php echo h(t('register.email_ph')); ?>" aria-describedby="email_hint" autocomplete="email" value="<?php echo isset($email) && $email !== null ? h($email) : ''; ?>">
                    <small id="email_hint" class="form-hint"><?php echo h(t('register.email_hint')); ?></small>
                </div>
                <button type="submit" class="button primary"><?php echo h(t('register.submit')); ?></button>
            </form>
        </section>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            handleFormSubmit(document.getElementById('register-form'), <?php echo json_encode(t('js.registering'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
        });
    </script>
<?php token_page_close(); ?>
