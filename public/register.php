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

    if (empty($name)) {
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
<?php
$pageTitle = 'Registrierung für ' . htmlspecialchars($group['name']);
include __DIR__ . '/../includes/templates/header.php';
?>
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
        <form method="POST">
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

</body>
</html>
