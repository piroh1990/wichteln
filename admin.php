<?php
// admin.php

require_once 'functions.php';

$admin_token = $_GET['token'] ?? '';
$pdo = db_connect();

// Gruppe abrufen
$stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `admin_token` = ?");
$stmt->execute([$admin_token]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    die('Ungültiger Token.');
}

// Teilnehmer abrufen
$stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `group_id` = ?");
$stmt->execute([$group['id']]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Auslosung zurücksetzen
if (isset($_POST['reset_draw'])) {
    $pdo->beginTransaction();
    try {
        // Setze is_drawn auf 0
        $stmt = $pdo->prepare("UPDATE `groups` SET `is_drawn` = 0 WHERE `id` = ?");
        $stmt->execute([$group['id']]);
        
        // Setze assigned_to auf NULL für alle Teilnehmer der Gruppe
        $stmt = $pdo->prepare("UPDATE `participants` SET `assigned_to` = NULL WHERE `group_id` = ?");
        $stmt->execute([$group['id']]);
        
        $pdo->commit();
        $reset_success = "Auslosung erfolgreich zurückgesetzt. Du kannst nun erneut auslosen.";
        
        // Gruppe neu abrufen
        $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `admin_token` = ?");
        $stmt->execute([$admin_token]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $reset_error = "Fehler beim Zurücksetzen der Auslosung: " . $e->getMessage();
    }
}

// Gruppe löschen
if (isset($_POST['delete_group'])) {
    $pdo->beginTransaction();
    try {
        $group_id = $group['id'];
        
        // Setze assigned_to auf NULL für alle Teilnehmer
        $stmt = $pdo->prepare("UPDATE `participants` SET `assigned_to` = NULL WHERE `group_id` = ?");
        $stmt->execute([$group_id]);
        
        // Lösche alle Ausschlüsse
        $stmt = $pdo->prepare("DELETE FROM `exclusions` WHERE `group_id` = ?");
        $stmt->execute([$group_id]);
        
        // Lösche alle Teilnehmer
        $stmt = $pdo->prepare("DELETE FROM `participants` WHERE `group_id` = ?");
        $stmt->execute([$group_id]);
        
        // Lösche die Gruppe
        $stmt = $pdo->prepare("DELETE FROM `groups` WHERE `id` = ?");
        $stmt->execute([$group_id]);
        
        $pdo->commit();
        
        // Weiterleitung zur Hauptseite mit Erfolgsmeldung
        header("Location: index.php?deleted=1");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $delete_error = "Fehler beim Löschen der Gruppe: " . $e->getMessage();
    }
}

// Teilnehmer E-Mail aktualisieren
if (isset($_POST['update_participant_email'])) {
    $participant_id = intval($_POST['participant_id']);
    $new_email = trim($_POST['participant_email']);
    
    // Validierung
    if (!empty($new_email) && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $participant_error = "Ungültige E-Mail-Adresse.";
    } else {
        // Prüfe ob Teilnehmer zur Gruppe gehört
        $stmt = $pdo->prepare("SELECT id FROM `participants` WHERE `id` = ? AND `group_id` = ?");
        $stmt->execute([$participant_id, $group['id']]);
        
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE `participants` SET `email` = ? WHERE `id` = ?");
            $stmt->execute([$new_email ?: null, $participant_id]);
            $participant_success = "E-Mail-Adresse erfolgreich aktualisiert.";
            
            // Teilnehmer neu laden
            $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `group_id` = ?");
            $stmt->execute([$group['id']]);
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $participant_error = "Teilnehmer nicht gefunden.";
        }
    }
}

// Teilnehmer löschen
if (isset($_GET['delete'])) {
    $participant_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM `participants` WHERE `id` = ?");
    $stmt->execute([$participant_id]);
    header("Location: admin.php?token=" . urlencode($admin_token));
    exit();
}

// Ausschluss hinzufügen
if (isset($_POST['add_exclusion'])) {
    $participant_id = intval($_POST['participant_id']);
    $excluded_id = intval($_POST['excluded_participant_id']);
    
    if ($participant_id && $excluded_id && $participant_id !== $excluded_id) {
        try {
            $stmt = $pdo->prepare("INSERT INTO `exclusions` (`group_id`, `participant_id`, `excluded_participant_id`) VALUES (?, ?, ?)");
            $stmt->execute([$group['id'], $participant_id, $excluded_id]);
            $exclusion_success = "Ausschluss erfolgreich hinzugefügt.";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                $exclusion_error = "Dieser Ausschluss existiert bereits.";
            } else {
                $exclusion_error = "Fehler beim Hinzufügen des Ausschlusses.";
            }
        }
    } else {
        $exclusion_error = "Ungültige Auswahl.";
    }
}

// Ausschluss löschen
if (isset($_GET['delete_exclusion'])) {
    $exclusion_id = intval($_GET['delete_exclusion']);
    $stmt = $pdo->prepare("DELETE FROM `exclusions` WHERE `id` = ? AND `group_id` = ?");
    $stmt->execute([$exclusion_id, $group['id']]);
    header("Location: admin.php?token=" . urlencode($admin_token));
    exit();
}

// Alle Ausschlüsse für diese Gruppe abrufen
$stmt = $pdo->prepare("
    SELECT e.*, 
           p1.name as participant_name, 
           p2.name as excluded_name 
    FROM `exclusions` e
    JOIN `participants` p1 ON e.participant_id = p1.id
    JOIN `participants` p2 ON e.excluded_participant_id = p2.id
    WHERE e.group_id = ?
    ORDER BY p1.name, p2.name
");
$stmt->execute([$group['id']]);
$exclusions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gruppe bearbeiten
if (isset($_POST['update_group'])) {
    $new_budget = trim($_POST['budget']) ?: null;
    $new_description = trim($_POST['description']) ?: null;
    $new_gift_exchange_date = trim($_POST['gift_exchange_date']) ?: null;
    
    // Validierung (optional)
    if ($new_budget !== null && !is_numeric($new_budget)) {
        $update_error = "Budget muss eine Zahl sein.";
    } elseif ($new_gift_exchange_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_gift_exchange_date)) {
        $update_error = "Datum der Geschenkübergabe muss im Format YYYY-MM-DD sein.";
    } else {
        // Aktualisiere die Gruppe
        $stmt = $pdo->prepare("UPDATE `groups` SET `budget` = ?, `description` = ?, `gift_exchange_date` = ? WHERE `id` = ?");
        $stmt->execute([$new_budget, $new_description, $new_gift_exchange_date, $group['id']]);
        
        // Aktualisiere das $group-Array
        $group['budget'] = $new_budget;
        $group['description'] = $new_description;
        $group['gift_exchange_date'] = $new_gift_exchange_date;
        
        $update_success = "Gruppeninformationen erfolgreich aktualisiert.";
    }
}

// Auslosung durchführen
if (isset($_POST['draw'])) {
    if (count($participants) < 2) {
        $draw_error = 'Es müssen mindestens 2 Teilnehmer vorhanden sein.';
    } else {
        // Ausschlüsse laden
        $stmt = $pdo->prepare("SELECT participant_id, excluded_participant_id FROM `exclusions` WHERE `group_id` = ?");
        $stmt->execute([$group['id']]);
        $exclusion_rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Exclusions-Map erstellen
        $exclusions_map = [];
        foreach ($exclusion_rules as $rule) {
            if (!isset($exclusions_map[$rule['participant_id']])) {
                $exclusions_map[$rule['participant_id']] = [];
            }
            $exclusions_map[$rule['participant_id']][] = $rule['excluded_participant_id'];
        }
        
        $participant_ids = array_column($participants, 'id');
        $assigned_ids = $participant_ids;
        
        // Versuche eine gültige Zuteilung zu finden (max 1000 Versuche)
        $max_attempts = 1000;
        $attempt = 0;
        $valid_assignment = false;
        
        while (!$valid_assignment && $attempt < $max_attempts) {
            shuffle($assigned_ids);
            $valid_assignment = true;
            
            for ($i = 0; $i < count($participant_ids); $i++) {
                $giver = $participant_ids[$i];
                $receiver = $assigned_ids[$i];
                
                // Prüfe ob Person sich selbst zieht
                if ($giver == $receiver) {
                    $valid_assignment = false;
                    break;
                }
                
                // Prüfe ob diese Zuteilung ausgeschlossen ist
                if (isset($exclusions_map[$giver]) && in_array($receiver, $exclusions_map[$giver])) {
                    $valid_assignment = false;
                    break;
                }
            }
            
            $attempt++;
        }
        
        if (!$valid_assignment) {
            $draw_error = 'Es konnte keine gültige Auslosung gefunden werden. Bitte überprüfe die Ausschlüsse - möglicherweise sind zu viele Ausschlüsse definiert.';
        } else {
            // Zuordnungen speichern
            for ($i = 0; $i < count($participant_ids); $i++) {
                $stmt = $pdo->prepare("UPDATE `participants` SET `assigned_to` = ? WHERE `id` = ?");
                $stmt->execute([$assigned_ids[$i], $participant_ids[$i]]);
            }

            // Gruppe als ausgelost markieren
            $stmt = $pdo->prepare("UPDATE `groups` SET `is_drawn` = 1 WHERE `id` = ?");
            $stmt->execute([$group['id']]);

            // Teilnehmer erneut abrufen, um die aktualisierten `assigned_to`-Werte zu erhalten
            $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `group_id` = ?");
            $stmt->execute([$group['id']]);
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // E-Mails versenden
            foreach ($participants as $participant) {
                if (!empty($participant['email'])) {
                    // Zugewiesenen Teilnehmer abrufen
                    if (!empty($participant['assigned_to'])) {
                        $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `id` = ?");
                        $stmt->execute([$participant['assigned_to']]);
                        $assigned = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($assigned) {
                            // Gruppendetails abrufen
                            $group_budget = $group['budget'] !== null ? number_format($group['budget'], 2) . " CHF" : "Nicht festgelegt";
                            $group_description = $group['description'] ?: "Keine Beschreibung.";
                            $gift_exchange_date = $group['gift_exchange_date'] ? date('d.m.Y', strtotime($group['gift_exchange_date'])) : "Nicht festgelegt";

                            // Erstelle HTML-E-Mail
                            $subject = 'Dein Wichtelpartner 🎁';
                            $html_message = create_html_email(
                                $participant['name'],
                                $assigned['name'],
                                $assigned['wishlist'] ?? '',
                                $group_budget,
                                $group_description,
                                $gift_exchange_date
                            );

                            if (!send_email($participant['email'], $subject, $html_message, true)) {
                                // Fehlerbehandlung, falls E-Mail nicht gesendet werden konnte
                                error_log("E-Mail konnte nicht an {$participant['email']} gesendet werden.");
                            }
                        } else {
                            // Fehlerprotokollierung, wenn der zugewiesene Teilnehmer nicht gefunden wird
                            error_log("Zugewiesener Teilnehmer mit ID {$participant['assigned_to']} nicht gefunden.");
                        }
                    } else {
                        // Fehlerprotokollierung, wenn `assigned_to` leer ist
                        error_log("Teilnehmer mit ID {$participant['id']} hat keinen zugewiesenen Teilnehmer.");
                    }
                }
            }

            // Weiterleitung zum Adminbereich ohne vorherige Ausgabe
            header("Location: admin.php?token=" . urlencode($admin_token));
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin Bereich - <?php echo htmlspecialchars($group['name']); ?></title>
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
    <style>
        /* Zusätzliche Styles spezifisch für admin.php */
        
        /* Teilnehmer Tabelle - Verbesserte Spaltenbreiten */
        table.participants-table th:nth-child(1) { width: 20%; } /* Name */
        table.participants-table th:nth-child(2) { width: 35%; } /* E-Mail */
        table.participants-table th:nth-child(3) { width: 30%; } /* Link */
        table.participants-table th:nth-child(4) { width: 15%; text-align: center; } /* Aktionen */
        
        /* Verhindere dass die Tabelle zu breit wird */
        table.participants-table {
            table-layout: fixed;
            width: 100%;
        }
        
        /* Überschreibe das globale td:last-child flexbox */
        table.participants-table td {
            display: table-cell;
            vertical-align: middle;
        }
        
        table.participants-table td:nth-child(4) {
            text-align: center;
        }
        
        /* E-Mail Input Styling */
        table.participants-table td form input[type="email"] {
            font-size: 0.9rem;
        }
        
        /* Button Styling in Tabelle */
        table.participants-table .button.small {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            min-width: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Link Container */
        table.participants-table .link-container {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            justify-content: space-between;
        }
        
        /* Responsive Anpassungen */
        @media (max-width: 768px) {
            table.participants-table th:nth-child(3) { 
                width: 25%; 
            }
            table.participants-table td form {
                flex-direction: column;
                gap: 0.25rem;
            }
            table.participants-table td form input[type="email"] {
                min-width: 100%;
            }
        }
    </style>
    <!-- JavaScript für Kopieren-Button -->
    <script>
        function copyToClipboard(elementId) {
            var element = document.getElementById(elementId);
            var copyText = element.getAttribute('data-url') || element.innerText || element.textContent;
            
            // Moderne Clipboard API (bevorzugt)
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(copyText).then(function() {
                    alert("Link kopiert: " + copyText);
                }).catch(function(err) {
                    // Fallback bei Fehler
                    fallbackCopy(copyText);
                });
            } else {
                // Fallback für ältere Browser
                fallbackCopy(copyText);
            }
        }
        
        function fallbackCopy(text) {
            var tempInput = document.createElement("textarea");
            tempInput.value = text;
            tempInput.style.position = "fixed";
            tempInput.style.opacity = "0";
            document.body.appendChild(tempInput);
            tempInput.select();
            tempInput.setSelectionRange(0, 99999); // Für mobile Geräte
            try {
                document.execCommand("copy");
                alert("Link kopiert: " + text);
            } catch (err) {
                alert("Fehler beim Kopieren. Bitte manuell kopieren.");
            }
            document.body.removeChild(tempInput);
        }
    </script>
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
        <img src="images/logo.png" alt="Wichtel Logo">
    </header>
    <div class="container">
        <h1>Admin Bereich - <?php echo htmlspecialchars($group['name']); ?></h1>
        
        <?php if (isset($update_error)): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($update_error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($update_success)): ?>
            <div class="notification success">
                <?php echo htmlspecialchars($update_success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($draw_error)): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($draw_error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($exclusion_success)): ?>
            <div class="notification success">
                <?php echo htmlspecialchars($exclusion_success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($exclusion_error)): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($exclusion_error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($reset_success)): ?>
            <div class="notification success">
                <?php echo htmlspecialchars($reset_success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($reset_error)): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($reset_error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($delete_error)): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($delete_error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($participant_success)): ?>
            <div class="notification success">
                <?php echo htmlspecialchars($participant_success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($participant_error)): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($participant_error); ?>
            </div>
        <?php endif; ?>

        <!-- Gruppendetails bearbeiten -->
        <h2>Gruppendetails</h2>
        <form method="POST">
            <div class="form-group">
                <label for="budget">Budget (optional):</label>
                <input type="number" step="0.01" id="budget" name="budget" value="<?php echo htmlspecialchars($group['budget'] ?? ''); ?>" placeholder="z.B. 20.00">
            </div>
            <div class="form-group">
                <label for="description">Beschreibung (optional):</label>
                <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($group['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label for="gift_exchange_date">Datum der Geschenkübergabe (optional):</label>
                <input type="date" id="gift_exchange_date" name="gift_exchange_date" value="<?php echo htmlspecialchars($group['gift_exchange_date'] ?? ''); ?>">
            </div>
            <button type="submit" name="update_group" class="button secondary">Gruppendetails aktualisieren</button>
        </form>
        
        <hr>
        
        <!-- Einladungslink für Teilnehmer -->
        <h2>Einladungslink für Teilnehmer</h2>
        <pre id="participant-link"><?php echo htmlspecialchars(get_display_url('/register.php?token=' . urlencode($group['invite_token']))); ?></pre>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 1rem;">
            <button class="button secondary small copy-button" onclick="copyToClipboard('participant-link')">Link kopieren</button>
            <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('Hallo! Du bist eingeladen, beim Wichteln mitzumachen. 🎁') . '%0A%0A' . urlencode('Gruppe: ' . $group['name']) . '%0A%0A' . urlencode('Melde dich hier an: ' . get_display_url('/register.php?token=' . urlencode($group['invite_token']))); ?>" 
               target="_blank" 
               class="button secondary small">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="margin-right: 0.25rem;">
                    <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z" fill="#25D366"/>
                </svg>
                Via WhatsApp teilen
            </a>
        </div>
        
        <div class="admin-info-box">
            <div class="admin-info-icon">💡</div>
            <div class="admin-info-content">
                <h3 class="admin-info-title">Hinweis für dich als Administrator</h3>
                <p class="admin-info-text">Wenn du selbst beim Wichteln mitmachen möchtest, musst du dich ebenfalls über den obigen Einladungslink als Teilnehmer registrieren. Der Admin-Link dient nur zur Verwaltung der Gruppe.</p>
            </div>
        </div>
        
        <!-- Admin-Link anzeigen -->
        <h2>Admin-Link</h2>
        <p>Dieser Link ermöglicht den direkten Zugriff auf den Admin-Bereich:</p>
        <pre id="admin-link"><?php echo htmlspecialchars(get_display_url('/admin.php?token=' . urlencode($admin_token))); ?></pre>
        <button class="button secondary small copy-button" onclick="copyToClipboard('admin-link')">Link kopieren</button>
        
        <hr>
        
        <!-- Teilnehmerliste anzeigen -->
        <h2>Teilnehmer</h2>
        <?php if ($participants): ?>
            <table class="participants-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Teilnehmer-Link</th>
                        <?php if (!$group['is_drawn']): ?>
                            <th>Aktionen</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($participants as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['name'] ?? ''); ?></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 0.5rem; align-items: center; margin: 0;">
                                    <input type="hidden" name="participant_id" value="<?php echo $p['id']; ?>">
                                    <input type="email" 
                                           name="participant_email" 
                                           value="<?php echo htmlspecialchars($p['email'] ?? ''); ?>" 
                                           placeholder="email@example.com"
                                           style="flex: 1; min-width: 180px; padding: 0.4rem; border: 1px solid #ddd; border-radius: 4px;">
                                    <button type="submit" 
                                            name="update_participant_email" 
                                            class="button secondary small"
                                            style="white-space: nowrap;">
                                        💾
                                    </button>
                                </form>
                            </td>
                            <td>
                                <?php if (!empty($p['participant_token'])): ?>
                                <div class="link-container">
                                    <span id="participant-link-<?php echo $p['id']; ?>" 
                                          data-url="<?php echo htmlspecialchars(get_display_url('/participant.php?token=' . urlencode($p['participant_token']))); ?>"
                                          style="font-size: 0.8rem; color: #666; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 200px;"
                                          title="Klicke auf 📋 um den vollständigen Link zu kopieren">
                                    </span>
                                    <button type="button"
                                            class="button secondary small" 
                                            onclick="copyToClipboard('participant-link-<?php echo $p['id']; ?>')"
                                            title="Link kopieren"
                                            style="white-space: nowrap; padding: 0.5rem 0.75rem;">
                                        📋
                                    </button>
                                </div>
                                <?php else: ?>
                                <span style="color: #999; font-style: italic; font-size: 0.85rem;">Kein Token</span>
                                <?php endif; ?>
                            </td>
                            <?php if (!$group['is_drawn']): ?>
                                <td style="text-align: center;">
                                    <a href="admin.php?token=<?php echo urlencode($admin_token); ?>&delete=<?php echo urlencode($p['id']); ?>" 
                                       class="button error small"
                                       onclick="return confirm('Möchtest du <?php echo htmlspecialchars($p['name']); ?> wirklich löschen?');">
                                        🗑️
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Noch keine Teilnehmer.</p>
        <?php endif; ?>

        <!-- Ausschlüsse verwalten -->
        <?php if (!$group['is_drawn'] && count($participants) >= 2): ?>
            <hr>
            
            <h2>Ausschlüsse verwalten</h2>
            <p>Lege fest, wer wem nicht wichteln kann. Dies ist nützlich, wenn z.B. Paare sich gegenseitig nicht beschenken sollen.</p>
            
            <form method="POST" class="exclusion-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="participant_id">Person:</label>
                        <select id="participant_id" name="participant_id" required>
                            <option value="">-- Person auswählen --</option>
                            <?php foreach ($participants as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="excluded_participant_id">kann nicht wichteln:</label>
                        <select id="excluded_participant_id" name="excluded_participant_id" required>
                            <option value="">-- Person auswählen --</option>
                            <?php foreach ($participants as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label style="opacity: 0;">Hinzufügen</label>
                        <button type="submit" name="add_exclusion" class="button secondary">Ausschluss hinzufügen</button>
                    </div>
                </div>
            </form>
            
            <?php if ($exclusions): ?>
                <h3>Aktive Ausschlüsse</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Person</th>
                            <th>kann nicht wichteln</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exclusions as $ex): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($ex['participant_name']); ?></td>
                                <td><?php echo htmlspecialchars($ex['excluded_name']); ?></td>
                                <td>
                                    <a href="admin.php?token=<?php echo urlencode($admin_token); ?>&delete_exclusion=<?php echo urlencode($ex['id']); ?>" 
                                       class="button error small"
                                       onclick="return confirm('Möchtest du diesen Ausschluss wirklich löschen?');">
                                        Löschen
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="text-muted">Keine Ausschlüsse definiert.</p>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Auslosung durchführen -->
        <?php if (!$group['is_drawn']): ?>
            <hr>
            <h2>Auslosung durchführen</h2>
            <p>Wenn alle Teilnehmer registriert sind und alle Ausschlüsse definiert wurden, kannst du die Auslosung durchführen.</p>
            <form method="POST" style="margin-top: 1rem;">
                <button type="submit" name="draw" class="button primary">Jetzt auslosen</button>
            </form>
        <?php else: ?>
            <hr>
            <h2>Auslosung</h2>
            <div class="notification success">
                ✓ Die Auslosung wurde bereits durchgeführt. Alle Teilnehmer mit E-Mail-Adresse wurden benachrichtigt.
            </div>
            
            <h3>Auslosung zurücksetzen</h3>
            <p class="text-muted">Du kannst die Auslosung zurücksetzen, um sie erneut durchzuführen. Dies löscht alle aktuellen Zuordnungen, und du kannst danach neue Teilnehmer hinzufügen oder Ausschlüsse ändern.</p>
            <form method="POST" style="margin-top: 1rem;" onsubmit="return confirm('Möchtest du die Auslosung wirklich zurücksetzen? Alle aktuellen Zuordnungen werden gelöscht.');">
                <button type="submit" name="reset_draw" class="button error">Auslosung zurücksetzen</button>
            </form>
        <?php endif; ?>
        
        <!-- Gruppe löschen -->
        <hr>
        <h2 style="color: var(--error);">⚠️ Gefahrenzone</h2>
        <p class="text-muted">Das Löschen der Gruppe kann nicht rückgängig gemacht werden. Alle Teilnehmer, Ausschlüsse und die Auslosung werden permanent gelöscht.</p>
        <form method="POST" style="margin-top: 1rem;" onsubmit="return confirm('⚠️ ACHTUNG: Möchtest du die Gruppe \"<?php echo htmlspecialchars($group['name']); ?>\" wirklich PERMANENT löschen?\n\nAlle Teilnehmer, Ausschlüsse und die Auslosung werden unwiderruflich gelöscht!\n\nDiese Aktion kann NICHT rückgängig gemacht werden.');">
            <button type="submit" name="delete_group" class="button error" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                🗑️ Gruppe permanent löschen
            </button>
        </form>
    </div>
</body>
</html>