<?php
// register.php

require_once __DIR__ . '/../includes/functions.php';

$invite_token = $_GET['token'] ?? '';
$pdo = db_connect();

// Gruppe abrufen
$stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `invite_token` = ?");
$stmt->execute([$invite_token]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    die('Ungültiger Einladungstoken.');
}

if ($group['is_drawn']) {
    die('Die Registrierung ist nicht mehr möglich. Die Auslosung wurde bereits durchgeführt.');
}

// Teilnehmer registrieren
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']) ?: null;

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Ungültiger CSRF-Token. Bitte lade die Seite neu und versuche es erneut.';
    } elseif (empty($name)) {
        $error = "Name darf nicht leer sein.";
    } elseif ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Ungültige E-Mail-Adresse.";
    } else {
        $participant_token = generate_token();

        $stmt = $pdo->prepare("INSERT INTO `participants` (`group_id`, `name`, `email`, `participant_token`) VALUES (?, ?, ?, ?)");
        $stmt->execute([$group['id'], $name, $email, $participant_token]);

        // Link an Teilnehmer senden, falls E-Mail angegeben
        if ($email) {
            $participant_link = get_base_url() . '/participant.php?token=' . urlencode($participant_token);
            $budget_display = $group['budget'] !== null ? number_format($group['budget'], 2) . " CHF" : "Nicht festgelegt";
            $description_display = $group['description'] ?: "Keine Beschreibung.";
            $gift_exchange_date_display = $group['gift_exchange_date'] ? date('d.m.Y', strtotime($group['gift_exchange_date'])) : "Nicht festgelegt";
            
            $subject = 'Willkommen beim Wichteln! 🎁';
            $html_message = create_registration_email(
                $name,
                $group['name'],
                $participant_link,
                $budget_display,
                $description_display,
                $gift_exchange_date_display
            );

            if (!send_email($email, $subject, $html_message, true)) {
                // Fehlerbehandlung, falls E-Mail nicht gesendet werden konnte
                error_log("E-Mail konnte nicht an $email gesendet werden.");
                $email_error = "E-Mail konnte nicht gesendet werden. Bitte überprüfe deine E-Mail-Adresse.";
            }
        }

        // Weiterleitung zur Teilnehmerseite
        header("Location: participant.php?token=" . urlencode($participant_token));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Registrierung für <?php echo htmlspecialchars($group['name']); ?></title>
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
    <?php include __DIR__ . '/../includes/templates/matomo_tracking.php'; ?>
</head>
<body>
    <header>
        <a href="index.php" title="Zur Startseite">
            <img src="images/logo.png" alt="Wichtel Logo">
        </a>
    </header>
    <div class="container">
        <h1>Registrierung für <?php echo htmlspecialchars($group['name']); ?></h1>
        <?php if (isset($error)): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($email_error)): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($email_error); ?>
            </div>
        <?php endif; ?>
        <form method="POST" id="register-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(get_csrf_token()); ?>">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">E-Mail (optional):</label>
                <input type="email" id="email" name="email">
            </div>
            <button type="submit" class="button primary">Registrieren</button>
        </form>
    </div>
    <!-- Cookie Banner -->
    <?php include __DIR__ . '/cookie-banner.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var registerForm = document.getElementById('register-form');
            if (registerForm) {
                registerForm.addEventListener('submit', function(e) {
                    var submitBtn = registerForm.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.style.minWidth = submitBtn.offsetWidth + 'px';
                        submitBtn.style.whiteSpace = 'nowrap';
                        submitBtn.innerHTML = '<span class="loading" aria-hidden="true"></span> Wird registriert...';
                        submitBtn.disabled = true;
                        submitBtn.style.cursor = 'wait';
                    }
                });
            }
        });
    </script>
</body>
</html>
