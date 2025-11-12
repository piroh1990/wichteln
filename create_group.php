<?php
// index.php

require_once 'functions.php';

// Session starten für Captcha
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_name = trim($_POST['group_name']);
    $admin_email = trim($_POST['admin_email']);
    $budget = trim($_POST['budget']) ?: null;
    $description = trim($_POST['description']) ?: null;
    $gift_exchange_date = trim($_POST['gift_exchange_date']) ?: null;
    $captcha_answer = trim($_POST['captcha_answer']);
    
    // Validierung (optional)
    if (empty($group_name)) {
        $error = "Gruppenname darf nicht leer sein.";
    } elseif (empty($admin_email)) {
        $error = "Admin-E-Mail darf nicht leer sein.";
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Ungültige E-Mail-Adresse.";
    } elseif ($budget !== null && !is_numeric($budget)) {
        $error = "Budget muss eine Zahl sein.";
    } elseif ($gift_exchange_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $gift_exchange_date)) {
        $error = "Datum der Geschenkübergabe muss im Format YYYY-MM-DD sein.";
    } elseif (empty($captcha_answer) || $captcha_answer !== $_SESSION['captcha_code']) {
        $error = "Der Sicherheitscode ist falsch. Bitte versuche es erneut.";
    } else {
        $pdo = db_connect();

        // Generiere Tokens
        $admin_token = generate_token();
        $invite_token = generate_token();

        // Gruppe einfügen
        $stmt = $pdo->prepare("INSERT INTO `groups` (`name`, `admin_token`, `invite_token`, `admin_email`, `budget`, `description`, `gift_exchange_date`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$group_name, $admin_token, $invite_token, $admin_email, $budget, $description, $gift_exchange_date]);

        // Erstelle die Links
        $admin_link = get_display_url('/admin.php?token=' . urlencode($admin_token));
        $invite_link = get_display_url('/register.php?token=' . urlencode($invite_token));
        
        // Formatiere Gruppendetails für E-Mail
        $group_budget = $budget !== null ? number_format($budget, 2) . " CHF" : "Nicht festgelegt";
        $group_description = $description ?: "Keine Beschreibung";
        $gift_exchange_date_formatted = $gift_exchange_date ? date('d.m.Y', strtotime($gift_exchange_date)) : "Nicht festgelegt";
        
        // Sende Admin-E-Mail
        $subject = 'Deine Wichtelgruppe "' . $group_name . '" wurde erstellt! 🎁';
        $html_message = create_admin_email(
            $group_name,
            $admin_link,
            $invite_link,
            $group_budget,
            $group_description,
            $gift_exchange_date_formatted
        );
        
        send_email($admin_email, $subject, $html_message, true);

        // Captcha zurücksetzen
        unset($_SESSION['captcha_code']);

        // Weiterleitung zum Adminbereich
        header("Location: admin.php?token=" . urlencode($admin_token));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Wichtel Gruppe erstellen</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="57x57" href="/images/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/images/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/images/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/images/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/images/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/images/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/images/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="/images/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/images/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="/images/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/images/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Roboto&display=swap" rel="stylesheet">
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="css/styles.css">
<!-- Matomo -->
    <?php if (file_exists('config.php')) { require_once 'config.php'; } ?>
    <?php if (defined('MATOMO_URL') && defined('MATOMO_SITE_ID')): ?>
    <script>
      var _paq = window._paq = window._paq || [];
      _paq.push(['trackPageView']);
      _paq.push(['enableLinkTracking']);
      (function() {
        var u="<?php echo MATOMO_URL; ?>";
        _paq.push(['setTrackerUrl', u+'matomo.php']);
        _paq.push(['setSiteId', '<?php echo MATOMO_SITE_ID; ?>']);
        var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
        g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
      })();
    </script>
    <?php endif; ?>
    <!-- End Matomo Code -->
</head>
<body>
    <header>
        <a href="index.php" title="Zur Startseite">
            <img src="images/logo.png" alt="Wichtel Logo">
        </a>
    </header>
    <div class="container">
        <h1>Wichtel Gruppe erstellen</h1>
        <?php if (isset($error)): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="group_name">Gruppenname:</label>
                <input type="text" id="group_name" name="group_name" required>
            </div>
            <div class="form-group">
                <label for="admin_email">Deine E-Mail-Adresse (Admin):</label>
                <input type="email" id="admin_email" name="admin_email" required placeholder="admin@beispiel.ch">
                <small style="color: #5f6368; font-size: 13px; display: block; margin-top: 5px;">Du erhältst den Admin-Link per E-Mail</small>
            </div>
            <div class="form-group">
                <label for="budget">Budget (optional):</label>
                <input type="number" step="0.01" id="budget" name="budget" placeholder="z.B. 20.00">
            </div>
            <div class="form-group">
                <label for="description">Beschreibung (optional):</label>
                <textarea id="description" name="description" rows="4" placeholder="Optionaler Text"></textarea>
            </div>
            <div class="form-group">
                <label for="gift_exchange_date">Datum der Geschenkübergabe (optional):</label>
                <input type="date" id="gift_exchange_date" name="gift_exchange_date">
            </div>
            <div class="form-group captcha-group">
                <label for="captcha_answer">Sicherheitscode:</label>
                <div class="captcha-container">
                    <img src="captcha.php" alt="Captcha" id="captcha-image" class="captcha-image">
                    <button type="button" onclick="refreshCaptcha()" class="button secondary small refresh-captcha" title="Neues Bild laden">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21.5 2v6h-6M2.5 22v-6h6M2 11.5a10 10 0 0 1 18.8-4.3M22 12.5a10 10 0 0 1-18.8 4.2"/>
                        </svg>
                    </button>
                </div>
                <input type="text" id="captcha_answer" name="captcha_answer" required placeholder="Gib die Zahlen aus dem Bild ein" maxlength="5" autocomplete="off">
                <small class="captcha-hint">Bitte gib die 5 Zahlen aus dem Bild ein.</small>
            </div>
            <button type="submit" class="button primary">Gruppe erstellen</button>
        </form>
    </div>
    
    <script>
        function refreshCaptcha() {
            var img = document.getElementById('captcha-image');
            img.src = 'captcha.php?' + new Date().getTime();
        }
    </script>
        <!-- Cookie Banner -->
    <?php include 'cookie-banner.php'; ?>

</body>
</html>
