<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/templates/public_shell.php';

start_secure_session();
i18n_boot_public();
i18n_set_public_page('create_group');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_name = trim(isset($_POST['group_name']) ? $_POST['group_name'] : '');
    $admin_email = trim(isset($_POST['admin_email']) ? $_POST['admin_email'] : '');
    $budget = trim(isset($_POST['budget']) ? $_POST['budget'] : '') ?: null;
    $description = trim(isset($_POST['description']) ? $_POST['description'] : '') ?: null;
    $gift_exchange_date = trim(isset($_POST['gift_exchange_date']) ? $_POST['gift_exchange_date'] : '') ?: null;
    $captcha_answer = trim(isset($_POST['captcha_answer']) ? $_POST['captcha_answer'] : '');
    $captcha_ok = consume_captcha_answer($captcha_answer);

    if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $error = csrf_failure_message();
    } elseif (!create_group_attempt_allowed(request_client_ip())) {
        $error = t('error.rate');
    } elseif (empty($group_name)) {
        $error = t('error.group_empty');
    } elseif (($length_error = limit_text_error($group_name, 255, t('field.group'))) !== '') {
        $error = $length_error;
    } elseif (empty($admin_email)) {
        $error = t('error.admin_email_empty');
    } elseif (($length_error = limit_text_error($admin_email, 255, t('field.email'))) !== '') {
        $error = $length_error;
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = t('error.email_invalid');
    } elseif ($description !== null && ($length_error = limit_text_error($description, 2000, t('field.description'))) !== '') {
        $error = $length_error;
    } elseif ($budget !== null && !is_numeric($budget)) {
        $error = t('error.budget_number');
    } elseif ($gift_exchange_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $gift_exchange_date)) {
        $error = t('error.date_format');
    } elseif (!$captcha_ok) {
        $error = t('captcha.wrong');
    } else {
        $pdo = db_connect();
        $admin_token = generate_token();
        $invite_token = generate_token();
        $lang = i18n_current();

        $created_group_id = i18n_insert_group(
            $pdo,
            $group_name,
            $admin_token,
            $invite_token,
            $admin_email,
            $budget,
            $description,
            $gift_exchange_date,
            $lang
        );

        $group_row = array(
            'id' => $created_group_id,
            'name' => $group_name,
            'admin_email' => $admin_email,
            'admin_token' => $admin_token,
            'invite_token' => $invite_token,
            'budget' => $budget,
            'description' => $description,
            'gift_exchange_date' => $gift_exchange_date,
            'language' => $lang,
        );
        $email_sent = send_created_group_mail($group_row);

        $premium_notice = premium_after_group_created($pdo, $created_group_id, $_POST, null, request_client_ip());
        $redirect_url = '/admin.php?token=' . urlencode($admin_token);
        if (!$email_sent) {
            $redirect_url .= '&email_error=1';
        }
        if (is_array($premium_notice) && $premium_notice['text'] !== '') {
            $_SESSION['admin_flash'] = $premium_notice;
            $redirect_url .= '&premium=1';
        }
        header('Location: ' . $redirect_url);
        exit();
    }
}

public_page_open('create_group', 'meta.create.title', 'meta.create.description');
?>
    <header>
        <a href="<?php echo h(i18n_href('index')); ?>" title="<?php echo h(t('nav.home')); ?>">
            <img src="/images/logo.png" alt="<?php echo h(t('create.logo_alt')); ?>">
        </a>
    </header>
    <div class="container">
        <h1><?php echo h(t('create.heading')); ?></h1>
        <p><?php echo h(t('create.lead')); ?></p>
        <?php if (isset($error)): ?>
            <div class="notification error" role="alert" aria-live="assertive">
                <?php echo h($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" id="create-group-form">
            <?php echo csrf_input(); ?>
            <div class="form-group">
                <label for="group_name"><?php echo h(t('create.name')); ?><span class="required-indicator" aria-hidden="true" title="<?php echo h(t('captcha.required')); ?>">*</span></label>
                <input type="text" id="group_name" name="group_name" required placeholder="<?php echo h(t('create.name_ph')); ?>" value="<?php echo isset($group_name) ? h($group_name) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="admin_email"><?php echo h(t('create.email')); ?><span class="required-indicator" aria-hidden="true" title="<?php echo h(t('captcha.required')); ?>">*</span></label>
                <input type="email" id="admin_email" name="admin_email" required placeholder="<?php echo h(t('create.email_ph')); ?>" aria-describedby="admin_email_hint" value="<?php echo isset($admin_email) ? h($admin_email) : ''; ?>">
                <small id="admin_email_hint" class="form-hint"><?php echo h(t('create.email_hint')); ?> <a href="<?php echo h(i18n_href('admin-link')); ?>"><?php echo h(t('create.email_link')); ?></a>.</small>
            </div>
            <div class="form-group">
                <label for="budget"><?php echo h(t('create.budget')); ?></label>
                <input type="number" step="0.01" id="budget" name="budget" placeholder="<?php echo h(t('create.budget_ph')); ?>" aria-describedby="budget_hint" value="<?php echo isset($budget) && $budget !== null ? h($budget) : ''; ?>">
                <small id="budget_hint" class="form-hint"><?php echo h(t('create.budget_hint')); ?></small>
            </div>
            <div class="form-group">
                <label for="description"><?php echo h(t('create.description')); ?></label>
                <textarea id="description" name="description" rows="4" placeholder="<?php echo h(t('create.description_ph')); ?>" aria-describedby="description_hint"><?php echo isset($description) && $description !== null ? h($description) : ''; ?></textarea>
                <small id="description_hint" class="form-hint"><?php echo h(t('create.description_hint')); ?></small>
            </div>
            <div class="form-group">
                <label for="gift_exchange_date"><?php echo h(t('create.date')); ?></label>
                <input type="date" id="gift_exchange_date" name="gift_exchange_date" min="<?php echo h(date('Y-m-d')); ?>" aria-describedby="gift_exchange_date_hint" value="<?php echo isset($gift_exchange_date) && $gift_exchange_date !== null ? h($gift_exchange_date) : ''; ?>">
                <small id="gift_exchange_date_hint" class="form-hint"><?php echo h(t('create.date_hint')); ?></small>
            </div>
            <?php
            $premium_checked = isset($_POST['firmen_premium']) && (string) $_POST['firmen_premium'] === '1';
            $premium_values = array(
                'email' => isset($_POST['premium_license_email']) ? $_POST['premium_license_email'] : '',
                'code' => isset($_POST['premium_license_code']) ? $_POST['premium_license_code'] : '',
            );
            echo premium_create_form_html(null, null, $premium_checked, $premium_values);
            ?>
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
                <small id="captcha_hint" class="form-hint"><?php echo h(captcha_hint_text()); ?></small>
            </div>
            <button type="submit" class="button primary"><?php echo h(t('create.submit')); ?></button>
        </form>
    </div>

    <script>
        function refreshCaptcha() {
            var img = document.getElementById('captcha-image');
            img.src = '/captcha.php?' + new Date().getTime();
        }

        document.addEventListener('DOMContentLoaded', function() {
            handleFormSubmit(document.getElementById('create-group-form'), <?php echo json_encode(t('js.creating'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
        });
    </script>
<?php public_page_close(); ?>
