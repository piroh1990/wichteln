<?php
require_once __DIR__ . '/../includes/functions.php';

session_start();

$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_email = trim(isset($_POST['admin_email']) ? $_POST['admin_email'] : '');
    $captcha_answer = trim(isset($_POST['captcha_answer']) ? $_POST['captcha_answer'] : '');
    $client = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';

    if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $error = csrf_failure_message();
    } elseif (!admin_link_recovery_allowed($client)) {
        $error = 'Zu viele Anfragen. Bitte später erneut versuchen.';
    } elseif ($admin_email === '' || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ungültige E-Mail-Adresse.';
    } elseif ($captcha_answer === '' || !isset($_SESSION['captcha_code']) || $captcha_answer !== $_SESSION['captcha_code']) {
        $error = 'Der Sicherheitscode ist falsch. Bitte versuche es erneut.';
    } else {
        unset($_SESSION['captcha_code']);
        $success = true;

        try {
            $pdo = db_connect();
            $stmt = $pdo->prepare('SELECT `name`, `admin_token` FROM `groups` WHERE `admin_email` = ? ORDER BY `created_at` DESC LIMIT 20');
            $stmt->execute(array($admin_email));
            $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $payload = array();
            foreach ($groups as $group) {
                if (empty($group['admin_token'])) {
                    continue;
                }
                $payload[] = array(
                    'name' => isset($group['name']) ? $group['name'] : '',
                    'admin_link' => get_display_url('/admin.php?token=' . rawurlencode($group['admin_token'])),
                );
            }
            if (count($payload) > 0) {
                $html = create_admin_recovery_email($payload);
                $plain = create_admin_recovery_plain_text($payload);
                if (!send_email($admin_email, 'Dein Admin-Link', $html, true, $plain)) {
                    error_log('Admin-Link konnte nicht erneut gesendet werden.');
                }
            }
        } catch (Exception $e) {
            error_log('Admin-Link-Wiederherstellung fehlgeschlagen: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin-Link erneut senden - Wichtlä.ch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin-Link für eine Wichtelgruppe erneut an die hinterlegte E-Mail-Adresse senden.">
    <link rel="canonical" href="https://wichtlä.ch/admin-link">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon/favicon-16x16.png">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <script src="js/main.js"></script>
    <?php include __DIR__ . '/../includes/templates/matomo_tracking.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/templates/navigation.php'; ?>
    <div class="container">
        <h1>Admin-Link erneut senden</h1>
        <p>Trag die E-Mail-Adresse ein, die du beim Erstellen der Gruppe angegeben hast. Wenn dazu eine Gruppe existiert, schicken wir den Admin-Link noch einmal. Die Antwort ist immer gleich, damit niemand prüfen kann, ob eine Adresse gespeichert ist.</p>

        <?php if (!empty($error)): ?>
            <div class="notification error" role="alert" aria-live="assertive">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="notification success" role="status" aria-live="polite">
                Wenn zu dieser Adresse eine Gruppe gehört, haben wir den Admin-Link erneut gesendet. Schau auch im Spam-Ordner nach.
            </div>
        <?php endif; ?>

        <form method="POST" id="admin-link-form">
            <?php echo csrf_input(); ?>
            <div class="form-group">
                <label for="admin_email">E-Mail-Adresse des Admins:<span class="required-indicator" aria-hidden="true" title="Erforderlich">*</span></label>
                <input type="email" id="admin_email" name="admin_email" required placeholder="admin@beispiel.ch" autocomplete="email" value="<?php echo isset($admin_email) ? htmlspecialchars($admin_email) : ''; ?>">
            </div>
            <div class="form-group captcha-group">
                <label for="captcha_answer">Sicherheitscode:<span class="required-indicator" aria-hidden="true" title="Erforderlich">*</span></label>
                <div class="captcha-container">
                    <img src="captcha.php" alt="Captcha" id="captcha-image" class="captcha-image">
                    <button type="button" onclick="refreshCaptcha()" class="button secondary small refresh-captcha" title="Neues Bild laden" aria-label="Neues Captcha laden">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                        </svg>
                    </button>
                </div>
                <input type="text" id="captcha_answer" name="captcha_answer" required placeholder="Gib die Zahlen aus dem Bild ein" maxlength="5" autocomplete="off" aria-describedby="captcha_hint">
                <small id="captcha_hint" class="form-hint">Bitte gib die 5 Zahlen aus dem Bild ein. Dasselbe Bild wie beim Erstellen einer Gruppe.</small>
            </div>
            <button type="submit" class="button primary">Admin-Link senden</button>
        </form>
    </div>
    <script>
        function refreshCaptcha() {
            var img = document.getElementById('captcha-image');
            img.src = 'captcha.php?' + new Date().getTime();
        }
        document.addEventListener('DOMContentLoaded', function() {
            handleFormSubmit(document.getElementById('admin-link-form'), 'Wird gesendet...');
        });
    </script>
    <?php include __DIR__ . '/cookie-banner.php'; ?>
</body>
</html>
