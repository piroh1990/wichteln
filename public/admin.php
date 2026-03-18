<?php
// admin.php

require_once __DIR__ . '/../includes/functions.php';

$admin_token = $_GET['token'] ?? '';
$pdo = db_connect();

// Gruppe abrufen
$stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `admin_token` = ?");
$stmt->execute([$admin_token]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    die('Ungültiger Token.');
}

// Check for email error from creation
if (isset($_GET['email_error'])) {
    $email_error = "Die E-Mail mit dem Admin-Link konnte nicht gesendet werden. Bitte speichere diese Seite als Lesezeichen, da du den Link sonst verlierst!";
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

// E-Mail erneut senden
if (isset($_POST['resend_email'])) {
    $participant_id = intval($_POST['participant_id']);
    
    // Prüfe ob Gruppe ausgelost wurde und Teilnehmer zur Gruppe gehört
    if (!$group['is_drawn']) {
        $email_error = "Die Auslosung wurde noch nicht durchgeführt.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `id` = ? AND `group_id` = ?");
        $stmt->execute([$participant_id, $group['id']]);
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$participant) {
            $email_error = "Teilnehmer nicht gefunden.";
        } elseif (empty($participant['email'])) {
            $email_error = "Teilnehmer hat keine E-Mail-Adresse hinterlegt.";
        } elseif (empty($participant['assigned_to'])) {
            $email_error = "Diesem Teilnehmer wurde noch keine Person zugewiesen.";
        } else {
            // Zugewiesenen Teilnehmer abrufen
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

                if (send_email($participant['email'], $subject, $html_message, true)) {
                    $email_success = "E-Mail erfolgreich an " . htmlspecialchars($participant['name']) . " gesendet.";
                } else {
                    $email_error = "Fehler beim Versenden der E-Mail an " . htmlspecialchars($participant['name']) . ".";
                }
            } else {
                $email_error = "Zugewiesener Teilnehmer nicht gefunden.";
            }
        }
    }
}

// Teilnehmer löschen
if (isset($_GET['delete'])) {
    $participant_id = intval($_GET['delete']);
    // Sicherstellen, dass die Gruppe noch nicht ausgelost wurde und der Teilnehmer zur Gruppe gehört
    if (!$group['is_drawn']) {
        $stmt = $pdo->prepare("DELETE FROM `participants` WHERE `id` = ? AND `group_id` = ?");
        $stmt->execute([$participant_id, $group['id']]);
    }
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
    // Sicherstellen, dass die Gruppe noch nicht ausgelost wurde
    if (!$group['is_drawn']) {
        $stmt = $pdo->prepare("DELETE FROM `exclusions` WHERE `id` = ? AND `group_id` = ?");
        $stmt->execute([$exclusion_id, $group['id']]);
    }
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

// Löschdatum berechnen (3 Monate nach Event oder Erstellung)
$base_date = $group['gift_exchange_date'] ? $group['gift_exchange_date'] : date('Y-m-d', strtotime($group['created_at']));
$deletion_date = date('d.m.Y', strtotime($base_date . ' + 3 months'));

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

            // Pre-load participants for O(1) lookup to avoid N+1 query problem
            $participants_by_id = array_column($participants, null, 'id');

            // E-Mails versenden
            foreach ($participants as $participant) {
                if (!empty($participant['email'])) {
                    // Zugewiesenen Teilnehmer abrufen
                    if (!empty($participant['assigned_to'])) {
                        // Use pre-loaded array instead of database query
                        $assigned = $participants_by_id[$participant['assigned_to']] ?? null;

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
    
    <!-- Shared JavaScript -->
    <script src="js/main.js"></script>
    <?php include __DIR__ . '/../includes/templates/matomo_tracking.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/templates/navigation.php'; ?>
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
        
        <?php if (isset($email_success)): ?>
            <div class="notification success">
                <?php echo $email_success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($email_error)): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($email_error); ?>
            </div>
        <?php endif; ?>

        <!-- Auto-Lösch Hinweis -->
        <div class="admin-info-box warning">
            <div class="admin-info-icon" aria-hidden="true">ℹ️</div>
            <div class="admin-info-content">
                <h3 class="admin-info-title">Wichtiger Hinweis zum Datenschutz</h3>
                <p class="admin-info-text">
                    Diese Gruppe und alle personenbezogenen Daten werden automatisch am <strong><?php echo htmlspecialchars($deletion_date); ?></strong> gelöscht (3 Monate nach dem Event).
                    Anonymisierte Statistiken bleiben erhalten.
                </p>
            </div>
        </div>

        <!-- Gruppendetails bearbeiten -->
        <h2>Gruppendetails</h2>
        <form method="POST" id="update-group-form">
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
                <input type="date" id="gift_exchange_date" name="gift_exchange_date" value="<?php echo htmlspecialchars($group['gift_exchange_date'] ?? ''); ?>" min="<?php echo date('Y-m-d'); ?>">
            </div>
            <input type="hidden" name="update_group" value="1">
            <button type="submit" class="button secondary">
                <span aria-hidden="true">💾</span> Gruppendetails aktualisieren
            </button>
        </form>
        
        <hr>
        
        <!-- Einladungslink für Teilnehmer -->
        <h2>Einladungslink für Teilnehmer</h2>
        <pre id="participant-link"><?php echo htmlspecialchars(get_display_url('/register.php?token=' . urlencode($group['invite_token']))); ?></pre>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 1rem;">
            <button class="button secondary small copy-button" onclick="copyToClipboard('participant-link')" aria-label="Einladungslink kopieren">Link kopieren</button>
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
            <div class="admin-info-icon" aria-hidden="true">💡</div>
            <div class="admin-info-content">
                <h3 class="admin-info-title">Hinweis für dich als Administrator</h3>
                <p class="admin-info-text">Wenn du selbst beim Wichteln mitmachen möchtest, musst du dich ebenfalls über den obigen Einladungslink als Teilnehmer registrieren. Der Admin-Link dient nur zur Verwaltung der Gruppe.</p>
            </div>
        </div>
        
        <!-- Admin-Link anzeigen -->
        <h2>Admin-Link</h2>
        <p>Dieser Link ermöglicht den direkten Zugriff auf den Admin-Bereich:</p>
        <pre id="admin-link"><?php echo htmlspecialchars(get_display_url('/admin.php?token=' . urlencode($admin_token))); ?></pre>
        <button class="button secondary small copy-button" onclick="copyToClipboard('admin-link')" aria-label="Admin-Link kopieren">Link kopieren</button>
        
        <hr>
        
        <!-- Teilnehmerliste anzeigen -->
        <h2>Teilnehmer (<?php echo count($participants); ?>)</h2>
        <?php if ($participants): ?>
            <div class="participants-grid">
                <?php foreach ($participants as $p): ?>
                    <div class="participant-card">
                        <div class="participant-header">
                            <div class="participant-info">
                                <h3 class="participant-name"><?php echo htmlspecialchars($p['name'] ?? ''); ?></h3>
                                <div class="participant-email">
                                    <?php if (!empty($p['email'])): ?>
                                        <span class="email-display">✉️ <?php echo htmlspecialchars($p['email']); ?></span>
                                    <?php else: ?>
                                        <span class="email-missing">⚠️ Keine E-Mail</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="participant-actions">
                            <?php if (!empty($p['participant_token'])): ?>
                                <button type="button"
                                        class="action-btn copy-btn" 
                                        onclick="copyToClipboard('participant-link-<?php echo $p['id']; ?>')"
                                        title="Teilnehmer-Link kopieren"
                                        aria-label="Teilnehmer-Link kopieren für <?php echo htmlspecialchars($p['name']); ?>">
                                    <span class="btn-icon" aria-hidden="true">📋</span>
                                    <span class="btn-text">Link kopieren</span>
                                </button>
                                <span id="participant-link-<?php echo $p['id']; ?>" 
                                      data-url="<?php echo htmlspecialchars(get_display_url('/participant.php?token=' . urlencode($p['participant_token']))); ?>"
                                      style="display: none;"></span>
                            <?php endif; ?>
                            
                            <?php 
                            $can_send_email = $group['is_drawn'] && !empty($p['email']) && !empty($p['assigned_to']);
                            ?>
                            <form method="POST" class="action-form resend-email-form">
                                <input type="hidden" name="participant_id" value="<?php echo $p['id']; ?>">
                                <input type="hidden" name="resend_email" value="1">
                                <button type="submit" 
                                        class="action-btn email-btn <?php echo !$can_send_email ? 'disabled' : ''; ?>"
                                        title="<?php echo $can_send_email ? 'E-Mail erneut senden' : 'E-Mail kann nicht gesendet werden (keine E-Mail-Adresse oder Auslosung nicht durchgeführt)'; ?>"
                                        aria-label="E-Mail senden an <?php echo htmlspecialchars($p['name']); ?>"
                                        <?php echo !$can_send_email ? 'disabled' : ''; ?>
                                        <?php echo $can_send_email ? 'onclick="return confirm(\'E-Mail mit Wichtelpartner-Info an ' . htmlspecialchars($p['name']) . ' senden?\');"' : ''; ?>>
                                    <span class="btn-icon" aria-hidden="true">📧</span>
                                    <span class="btn-text">E-Mail senden</span>
                                </button>
                            </form>
                            
                            <?php if (!$group['is_drawn']): ?>
                                <a href="admin.php?token=<?php echo urlencode($admin_token); ?>&delete=<?php echo urlencode($p['id']); ?>" 
                                   class="action-btn delete-btn"
                                   aria-label="<?php echo htmlspecialchars($p['name']); ?> löschen"
                                   onclick="return confirm('Möchtest du <?php echo htmlspecialchars($p['name']); ?> wirklich löschen?');">
                                    <span class="btn-icon" aria-hidden="true">🗑️</span>
                                    <span class="btn-text">Löschen</span>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="participant-email-edit">
                            <form method="POST" class="email-edit-form">
                                <input type="hidden" name="participant_id" value="<?php echo $p['id']; ?>">
                                <input type="hidden" name="update_participant_email" value="1">
                                <div class="email-edit-group">
                                    <input type="email" 
                                           name="participant_email" 
                                           value="<?php echo htmlspecialchars($p['email'] ?? ''); ?>" 
                                           placeholder="E-Mail hinzufügen..."
                                           class="email-edit-input"
                                           aria-label="E-Mail-Adresse für <?php echo htmlspecialchars($p['name']); ?>">
                                    <button type="submit" 
                                            class="email-edit-btn"
                                            title="E-Mail speichern"
                                            aria-label="E-Mail für <?php echo htmlspecialchars($p['name']); ?> speichern">
                                        <span aria-hidden="true">💾</span> Speichern
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon" aria-hidden="true">👥</div>
                <p class="empty-text">Noch keine Teilnehmer registriert.</p>
                <p class="empty-hint" style="margin-bottom: 1rem;">Teile den Einladungslink, damit sich Teilnehmer anmelden können.</p>
                <button class="button primary copy-button" onclick="copyToClipboard('participant-link')" aria-label="Einladungslink für Teilnehmer kopieren">
                    <span class="btn-icon" aria-hidden="true">📋</span> Einladungslink kopieren
                </button>
            </div>
        <?php endif; ?>

        <!-- Ausschlüsse verwalten -->
        <?php if (!$group['is_drawn'] && count($participants) >= 2): ?>
            <hr>
            
            <h2>Ausschlüsse verwalten</h2>
            <p>Lege fest, wer wem nicht wichteln kann. Dies ist nützlich, wenn z.B. Paare sich gegenseitig nicht beschenken sollen.</p>
            
            <form method="POST" class="exclusion-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="participant_id">Person:<span class="required-indicator" aria-hidden="true" title="Erforderlich">*</span></label>
                        <select id="participant_id" name="participant_id" required>
                            <option value="">-- Person auswählen --</option>
                            <?php foreach ($participants as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="excluded_participant_id">kann nicht wichteln:<span class="required-indicator" aria-hidden="true" title="Erforderlich">*</span></label>
                        <select id="excluded_participant_id" name="excluded_participant_id" required>
                            <option value="">-- Person auswählen --</option>
                            <?php foreach ($participants as $p): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label aria-hidden="true" style="opacity: 0;">Hinzufügen</label>
                        <input type="hidden" name="add_exclusion" value="1">
                        <button type="submit" class="button secondary">Ausschluss hinzufügen</button>
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
                                       aria-label="Ausschluss löschen: <?php echo htmlspecialchars($ex['participant_name']); ?> darf nicht <?php echo htmlspecialchars($ex['excluded_name']); ?> wichteln"
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
            <form method="POST" style="margin-top: 1rem;" id="draw-form">
                <input type="hidden" name="draw" value="1">
                <button type="submit" class="button primary">Jetzt auslosen</button>
            </form>
        <?php else: ?>
            <hr>
            <h2>Auslosung</h2>
            <div class="notification success">
                ✓ Die Auslosung wurde bereits durchgeführt. Alle Teilnehmer mit E-Mail-Adresse wurden benachrichtigt.
            </div>
            
            <h3>Auslosung zurücksetzen</h3>
            <p class="text-muted">Du kannst die Auslosung zurücksetzen, um sie erneut durchzuführen. Dies löscht alle aktuellen Zuordnungen, und du kannst danach neue Teilnehmer hinzufügen oder Ausschlüsse ändern.</p>
            <form method="POST" style="margin-top: 1rem;" id="reset-draw-form" onsubmit="return confirm('Möchtest du die Auslosung wirklich zurücksetzen? Alle aktuellen Zuordnungen werden gelöscht.');">
                <input type="hidden" name="reset_draw" value="1">
                <button type="submit" class="button error">Auslosung zurücksetzen</button>
            </form>
        <?php endif; ?>
        
        <!-- Gruppe löschen -->
        <hr>
        <h2 style="color: var(--error);">⚠️ Gefahrenzone</h2>
        <p class="text-muted">Das Löschen der Gruppe kann nicht rückgängig gemacht werden. Alle Teilnehmer, Ausschlüsse und die Auslosung werden permanent gelöscht.</p>
        <form method="POST" style="margin-top: 1rem;" id="delete-group-form" onsubmit="return confirm('⚠️ ACHTUNG: Möchtest du die Gruppe \"<?php echo htmlspecialchars($group['name']); ?>\" wirklich PERMANENT löschen?\n\nAlle Teilnehmer, Ausschlüsse und die Auslosung werden unwiderruflich gelöscht!\n\nDiese Aktion kann NICHT rückgängig gemacht werden.');">
            <input type="hidden" name="delete_group" value="1">
            <button type="submit" class="button error" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                <span aria-hidden="true">🗑️</span> Gruppe permanent löschen
            </button>
        </form>
    </div>
    
    <!-- Footer -->
    <?php include __DIR__ . '/../includes/templates/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            handleFormSubmit(document.getElementById('draw-form'), 'Wird ausgelost...');
            handleFormSubmit(document.getElementById('update-group-form'), 'Wird aktualisiert...');
            handleFormSubmit(document.getElementById('reset-draw-form'), 'Wird zurückgesetzt...');
            handleFormSubmit(document.getElementById('delete-group-form'), 'Wird gelöscht...');

            const exclusionForm = document.querySelector('.exclusion-form');
            if (exclusionForm) handleFormSubmit(exclusionForm, 'Wird hinzugefügt...');

            document.querySelectorAll('.resend-email-form').forEach(form => {
                handleFormSubmit(form, 'Wird gesendet...');
            });

            document.querySelectorAll('.email-edit-form').forEach(form => {
                handleFormSubmit(form, 'Wird gespeichert...');
            });
        });
    </script>
</body>
</html>