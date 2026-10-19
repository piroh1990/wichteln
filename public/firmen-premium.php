<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/templates/public_shell.php';

if (!premium_feature_enabled()) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo premium_unavailable_body();
    exit;
}

start_secure_session();
i18n_boot_public();

$notice = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $notice = array('type' => 'error', 'text' => csrf_failure_message());
    } else {
        $buy = isset($_POST['premium_buy']) ? (string) $_POST['premium_buy'] : '';
        if ($buy === 'stripe') {
            $result = premium_stripe_start_checkout(
                isset($_POST['tier']) ? $_POST['tier'] : '',
                isset($_POST['email']) ? $_POST['email'] : ''
            );
            if (!empty($result['ok']) && $result['url'] !== '') {
                header('Location: ' . $result['url']);
                exit;
            }
            $notice = array('type' => 'error', 'text' => t('premium.checkout.fail'));
        } elseif ($buy === 'invoice') {
            $pdo = db_connect();
            $result = premium_submit_invoice($pdo, $_POST, request_client_ip());
            if (!empty($result['ok'])) {
                $notice = array('type' => 'success', 'text' => t('premium.invoice.sent'));
            } elseif (isset($result['error']) && $result['error'] === 'rate') {
                $notice = array('type' => 'error', 'text' => t('premium.claim.rate'));
            } elseif (isset($result['error']) && $result['error'] === 'mail') {
                $notice = array('type' => 'error', 'text' => t('premium.claim.mail_fail'));
            } else {
                $notice = array('type' => 'error', 'text' => t('premium.invoice.invalid'));
            }
        }
    }
}

$checkout_note = '';
if (isset($_GET['checkout']) && (string) $_GET['checkout'] === 'ok') {
    $checkout_note = t('premium.success.paid');
} elseif (isset($_GET['checkout']) && (string) $_GET['checkout'] === 'cancel') {
    $checkout_note = t('premium.cancel.paid');
}

public_page_open('firmen-premium', 'meta.premium.title', 'meta.premium.description', 'website');
?>
<?php if ($checkout_note !== ''): ?>
    <div class="container">
        <div class="notification <?php echo (isset($_GET['checkout']) && $_GET['checkout'] === 'ok') ? 'success' : 'warning'; ?>" role="status">
            <?php echo h($checkout_note); ?>
        </div>
    </div>
<?php endif; ?>
<?php if (is_array($notice) && $notice['text'] !== ''): ?>
    <div class="container">
        <div class="notification <?php echo ($notice['type'] === 'success') ? 'success' : 'error'; ?>" role="status">
            <?php echo h($notice['text']); ?>
        </div>
    </div>
<?php endif; ?>
<?php
i18n_include_content('firmen-premium');
echo premium_purchase_section_html();
public_page_close();
