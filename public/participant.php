<?php
/**
 * participant.php
 * 
 * Teilnehmerbereich für Wichtel-Gruppen
 * 
 * Features:
 * - Cookie-basiertes Token-Management für automatisches Login
 * - Unterstützung für mehrere Gruppen pro Benutzer
 * - Gruppenauswahl-Interface wenn mehrere Gruppen vorhanden
 * - Token in URL hat Vorrang vor Cookie-Daten
 * - Sichere Cookie-Verwaltung (httpOnly, SameSite, begrenzte Speicherung)
 * 
 * Konfiguration:
 * - COOKIE_NAME: Name des Cookies (config.php)
 * - COOKIE_LIFETIME: Lebensdauer des Cookies in Sekunden (config.php)
 * - COOKIE_MAX_TOKENS: Max. Anzahl gespeicherter Tokens (config.php)
 * 
 * Sicherheit:
 * - Token-Validierung (nur alphanumerische Zeichen)
 * - Maximale Anzahl gespeicherter Tokens (konfigurierbar)
 * - Secure-Flag bei HTTPS
 * - SameSite=Lax zur CSRF-Prävention
 * - HttpOnly-Flag gegen XSS-Angriffe
 */

require_once __DIR__ . '/../includes/functions.php';

session_start();

$pdo = db_connect();

/**
 * Speichert einen Token im Cookie (fügt ihn zur Liste hinzu, falls noch nicht vorhanden)
 */
function save_token_to_cookie($token) {
    $tokens = get_tokens_from_cookie();
    
    // Token validieren (nur alphanumerische Zeichen)
    if (!preg_match('/^[a-zA-Z0-9]+$/', $token)) {
        return false;
    }
    
    // Token zur Liste hinzufügen, wenn noch nicht vorhanden
    if (!in_array($token, $tokens)) {
        $tokens[] = $token;
    }
    
    // Nur die letzten X Tokens behalten (konfigurierbar in config.php)
    $tokens = array_slice($tokens, -COOKIE_MAX_TOKENS);
    
    // Cookie mit Sicherheitseinstellungen setzen
    $cookie_options = [
        'expires' => time() + COOKIE_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Lax'
    ];
    
    return setcookie(COOKIE_NAME, json_encode($tokens), $cookie_options);
}

/**
 * Liest alle gespeicherten Tokens aus dem Cookie
 */
function get_tokens_from_cookie() {
    if (!isset($_COOKIE[COOKIE_NAME])) {
        return [];
    }
    
    $tokens = json_decode($_COOKIE[COOKIE_NAME], true);
    
    if (!is_array($tokens)) {
        return [];
    }
    
    // Nur gültige Tokens zurückgeben
    return array_filter($tokens, function($token) {
        return preg_match('/^[a-zA-Z0-9]+$/', $token);
    });
}

/**
 * Lädt alle Teilnehmerdaten für die gespeicherten Tokens
 */
function load_participants_from_tokens($pdo, $tokens) {
    if (empty($tokens)) {
        return [];
    }
    
    $placeholders = str_repeat('?,', count($tokens) - 1) . '?';
    $stmt = $pdo->prepare("SELECT p.*, g.name as group_name, g.is_drawn 
                           FROM `participants` p 
                           JOIN `groups` g ON p.group_id = g.id 
                           WHERE p.participant_token IN ($placeholders)
                           ORDER BY g.name, p.name");
    $stmt->execute($tokens);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Token aus URL hat Vorrang
$participant_token = $_GET['token'] ?? '';
$show_group_selector = false;
$participant = null;
$group = null;
$assigned = null;

// Wenn Token in URL vorhanden ist
if (!empty($participant_token)) {
    // Token validieren
    if (!preg_match('/^[a-zA-Z0-9]+$/', $participant_token)) {
        die('Ungültiger Token.');
    }
    
    // Teilnehmer abrufen
    $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `participant_token` = ?");
    $stmt->execute([$participant_token]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$participant) {
        die('Ungültiger Token.');
    }
    
    // Token im Cookie speichern
    save_token_to_cookie($participant_token);
    
} else {
    // Kein Token in URL - versuche aus Cookie zu laden
    $saved_tokens = get_tokens_from_cookie();
    
    $participants = [];

    if (!empty($saved_tokens)) {
        $participants = load_participants_from_tokens($pdo, $saved_tokens);

        // Wenn gespeicherte Tokens keine gültigen Teilnehmer liefern (z.B. gelöschte Gruppen)
        if (empty($participants)) {
            // Cookie löschen
            $cookie_options = [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
                'httponly' => true,
                'samesite' => 'Lax'
            ];
            setcookie(COOKIE_NAME, '', $cookie_options);
        }
    }

    if (empty($participants)) {
        // Keine gültigen Teilnahmen gefunden - zeige schöne Fehlerseite
        ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Keine Gruppe gefunden - Wichteln</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Roboto&display=swap" rel="stylesheet">
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
    <link rel="stylesheet" href="css/styles.css">
    <?php include __DIR__ . '/../includes/templates/matomo_tracking.php'; ?>
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/templates/navigation.php'; ?>

    <div class="container" style="margin-top: 2rem;">
        <div class="participant-info-card">
            <div class="participant-welcome">
                <h1 class="participant-greeting">Keine Gruppe gefunden 🎄</h1>
                <p class="participant-group-name">
                    Du hast noch keine Wichtel-Gruppe besucht oder dein Link ist nicht mehr gültig.
                </p>
            </div>
        </div>

        <div class="section-card waiting-card">
            <div class="waiting-icon">🏠</div>
            <h2>Was möchtest du tun?</h2>
            <div style="display: flex; gap: 1rem; justify-content: center; margin-top: 1.5rem; flex-wrap: wrap;">
                <a href="index.php" class="cta-button cta-button-secondary">
                    <span>Zur Startseite</span>
                </a>
                <a href="create_group.php" class="cta-button cta-button-primary">
                    <span>Neue Gruppe erstellen</span>
                </a>
            </div>
        </div>

        <div class="section-card">
            <div class="section-card-header">
                <span class="section-icon">💡</span>
                <h2>So kommst du zu deinem Teilnehmerbereich</h2>
            </div>
            <ul>
                <li><strong>Einladungslink erhalten?</strong> Benutze den Link, den dir der Gruppenadmin geschickt hat</li>
                <li><strong>Bereits angemeldet?</strong> Verwende deinen persönlichen Teilnehmer-Link aus der Bestätigungs-E-Mail</li>
                <li><strong>Link verloren?</strong> Kontaktiere den Gruppenadmin für einen neuen Link</li>
            </ul>
        </div>
    </div>

    <!-- Cookie Banner -->
    <?php include __DIR__ . '/cookie-banner.php'; ?>

    <!-- Footer -->
    <?php include __DIR__ . '/../includes/templates/footer.php'; ?>
</body>
</html>
        <?php
        exit;
    }
    
    // Wenn Gruppenauswahl per POST gesendet wurde
    if (isset($_POST['select_group']) && isset($_POST['selected_token'])) {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            die('CSRF-Token ungültig.');
        }

        $selected_token = $_POST['selected_token'];
        
        // Token validieren
        if (!preg_match('/^[a-zA-Z0-9]+$/', $selected_token) || !in_array($selected_token, $saved_tokens)) {
            die('Ungültiger Token.');
        }
        
        $participant_token = $selected_token;
        
        // Teilnehmer laden
        $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `participant_token` = ?");
        $stmt->execute([$participant_token]);
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$participant) {
            die('Ungültiger Token.');
        }
    } elseif (count($participants) > 1) {
        // Mehrere Gruppen - zeige Auswahlseite
        $show_group_selector = true;
    } else {
        // Nur eine Gruppe - direkt laden
        $participant = $participants[0];
        $participant_token = $participant['participant_token'];
    }
}

// Wenn Teilnehmer geladen wurde, lade Gruppendaten
if ($participant) {
    // Gruppe abrufen
    $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `id` = ?");
    $stmt->execute([$participant['group_id']]);
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Zugewiesenen Teilnehmer abrufen, falls ausgelost
    if ($group['is_drawn'] && !empty($participant['assigned_to'])) {
        $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `id` = ?");
        $stmt->execute([$participant['assigned_to']]);
        $assigned = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Wunschliste aktualisieren (nur wenn noch nicht ausgelost)
    if (isset($_POST['update_wishlist']) && !$group['is_drawn']) {
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            die('CSRF-Token ungültig.');
        }

        $wishlist = trim($_POST['wishlist']);
        
        $stmt = $pdo->prepare("UPDATE `participants` SET `wishlist` = ? WHERE `id` = ?");
        $stmt->execute([$wishlist, $participant['id']]);
        
        // Teilnehmer neu abrufen
        $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `participant_token` = ?");
        $stmt->execute([$participant_token]);
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $wishlist_success = "Deine Wunschliste wurde erfolgreich gespeichert.";
    }
}

// Wenn Gruppenauswahl angezeigt werden soll
if ($show_group_selector) {
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Gruppe auswählen - Wichteln</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Roboto&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
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
    <?php include __DIR__ . '/../includes/templates/matomo_tracking.php'; ?>
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/templates/navigation.php'; ?>
    <div class="container">
        <div class="group-selector">
            <h1>Willkommen zurück! 🎄</h1>
            <p>Du nimmst an mehreren Wichtel-Gruppen teil. Bitte wähle die Gruppe aus, die du ansehen möchtest:</p>
            
            <form method="POST" id="group-selector-form">
                <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                <?php foreach ($participants as $p): ?>
                <label class="group-card">
                    <input type="radio" name="selected_token" value="<?php echo htmlspecialchars($p['participant_token']); ?>" required aria-label="Gruppe: <?php echo htmlspecialchars($p['group_name']); ?> (als <?php echo htmlspecialchars($p['name']); ?>)">
                    <div class="group-card-content">
                        <div class="group-card-header">
                            <div>
                                <h2 class="group-name"><?php echo htmlspecialchars($p['group_name']); ?></h2>
                                <p class="group-participant">Als: <strong><?php echo htmlspecialchars($p['name']); ?></strong></p>
                            </div>
                            <span class="group-status <?php echo $p['is_drawn'] ? 'status-drawn' : 'status-pending'; ?>">
                                <?php echo $p['is_drawn'] ? '✓ Ausgelost' : 'Ausstehend'; ?>
                            </span>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
                
                <div class="submit-container">
                    <button type="submit" name="select_group" class="group-submit-button" id="submit-button" disabled>Gruppe öffnen</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Bei Klick auf die Card auch das Radio-Button aktivieren
        document.querySelectorAll('.group-card').forEach(card => {
            card.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                radio.checked = true;
                
                // Button aktivieren
                const submitButton = document.getElementById('submit-button');
                submitButton.disabled = false;
                submitButton.classList.add('enabled');
            });
        });
        
        // Button aktivieren wenn Radio-Button geändert wird
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const submitButton = document.getElementById('submit-button');
                submitButton.disabled = false;
                submitButton.classList.add('enabled');
            });
        });
    </script>
</body>
</html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Teilnehmerbereich - <?php echo htmlspecialchars($participant['name']); ?></title>
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="css/styles.css">
    <!-- JavaScript für Kopieren-Button -->
    <script>
        function copyToClipboard(elementId) {
            var element = document.getElementById(elementId);
            var copyText = element.getAttribute('data-url') || element.innerText || element.textContent;
            
            // Moderne Clipboard API (bevorzugt)
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(copyText).then(function() {
                    // Zeige Erfolgs-Feedback
                    var btn = event.target.closest('button');
                    var originalText = btn.innerHTML;
                    btn.innerHTML = '✓ Kopiert!';
                    btn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
                    setTimeout(function() {
                        btn.innerHTML = originalText;
                        btn.style.background = '';
                    }, 2000);
                }).catch(function(err) {
                    fallbackCopy(copyText);
                });
            } else {
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
            tempInput.setSelectionRange(0, 99999);
            try {
                document.execCommand("copy");
                alert("Link kopiert!");
            } catch (err) {
                alert("Fehler beim Kopieren. Bitte manuell kopieren.");
            }
            document.body.removeChild(tempInput);
        }
    </script>
    <?php include __DIR__ . '/../includes/templates/matomo_tracking.php'; ?>
</head>
<body>
    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/templates/navigation.php'; ?>
    
    <div class="container" style="margin-top: 2rem;">
        <!-- Welcome Card -->
        <div class="participant-info-card">
            <div class="participant-welcome">
                <h1 class="participant-greeting">Willkommen, <?php echo htmlspecialchars($participant['name']); ?>! 🎄</h1>
                <p class="participant-group-name">📦 Gruppe: <?php echo htmlspecialchars($group['name']); ?></p>
            </div>
        </div>
        
        <?php if (isset($wishlist_success)): ?>
            <div class="notification success">
                ✓ <?php echo htmlspecialchars($wishlist_success); ?>
            </div>
        <?php endif; ?>
        
        <!-- Partner Reveal Card -->
        <?php if ($group['is_drawn']): ?>
            <div class="partner-reveal-card">
                <div class="partner-reveal-header">
                    <span class="partner-reveal-icon">🎁</span>
                    <h2 class="partner-reveal-title">Dein Wichtelpartner</h2>
                </div>
                
                <?php if ($assigned): ?>
                    <div class="partner-name-container">
                        <p class="partner-name"><?php echo htmlspecialchars($assigned['name']); ?></p>
                    </div>
                    
                    <?php if (!empty($assigned['wishlist'])): ?>
                        <div class="partner-wishlist-section">
                            <h3 class="wishlist-heading">
                                <span class="wishlist-icon">📝</span>
                                Wunschliste von <?php echo htmlspecialchars($assigned['name']); ?>
                            </h3>
                            <div class="wishlist-display">
                                <p><?php echo nl2br(htmlspecialchars($assigned['wishlist'])); ?></p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-wishlist">
                            <span class="empty-wishlist-icon" aria-hidden="true">📋</span>
                            <p><?php echo htmlspecialchars($assigned['name']); ?> hat noch keine Wunschliste hinterlegt.</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="notification error">
                        ⚠️ Dein Wichtelpartner konnte nicht gefunden werden.
                    </div>
                <?php endif; ?>
            </div>
            
            <?php 
            // Google Ad Position 1: Nach Wichtelpartner-Bereich
            if (defined('GOOGLE_ADS_ENABLED') && GOOGLE_ADS_ENABLED && 
                defined('GOOGLE_ADS_SHOW_OPTION1') && GOOGLE_ADS_SHOW_OPTION1): 
                $is_testing = defined('GOOGLE_ADS_TESTING') && GOOGLE_ADS_TESTING;
            ?>
            <div class="ad-container">
                <div class="ad-label"><?php echo $is_testing ? 'Test-Anzeige (Position 1)' : 'Anzeige'; ?></div>
                <?php if ($is_testing): ?>
                    <div class="ad-test-placeholder">
                        📊 Google Ad Placeholder<br>
                        Position 1: Nach Wichtelpartner<br>
                        Responsive Display Ad
                    </div>
                <?php else: ?>
                    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo htmlspecialchars(GOOGLE_ADS_CLIENT); ?>"
                         crossorigin="anonymous"></script>
                    <ins class="adsbygoogle"
                         style="display:block"
                         data-ad-client="<?php echo htmlspecialchars(GOOGLE_ADS_CLIENT); ?>"
                         data-ad-slot="<?php echo htmlspecialchars(GOOGLE_ADS_SLOT_OPTION1); ?>"
                         data-ad-format="auto"
                         data-full-width-responsive="true"></ins>
                    <script>
                         (adsbygoogle = window.adsbygoogle || []).push({});
                    </script>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="section-card waiting-card">
                <div class="waiting-icon">⏳</div>
                <h2>Auslosung ausstehend</h2>
                <p>Die Auslosung wurde noch nicht durchgeführt. Bitte schaue später wieder vorbei!</p>
            </div>
        <?php endif; ?>
        
        <!-- Wunschliste Section -->
        <!-- Wunschliste Section -->
        <div class="section-card">
            <div class="section-card-header">
                <span class="section-icon">📝</span>
                <h2>Deine Wunschliste</h2>
            </div>
            
            <?php if (!$group['is_drawn']): ?>
                <p class="section-description">Trage hier deine Wünsche ein. Dein Wichtelpartner wird diese nach der Auslosung sehen können.</p>
                <form method="POST" class="wishlist-form">
                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                    <div class="form-group">
                        <label for="wishlist" class="form-label">
                            <span>Deine Wünsche</span>
                            <span class="form-hint">z.B. Bücher, Schokolade, Selbstgemachtes...</span>
                        </label>
                        <textarea id="wishlist" 
                                  name="wishlist" 
                                  rows="6" 
                                  class="form-textarea"
                                  placeholder="- Bücher über...&#10;- Schokolade&#10;- Etwas Selbstgemachtes&#10;- Überraschung!"><?php echo htmlspecialchars($participant['wishlist'] ?? ''); ?></textarea>
                    </div>
                    <input type="hidden" name="update_wishlist" value="1">
                    <button type="submit" class="button primary">
                        <span aria-hidden="true">💾</span>
                        Wunschliste speichern
                    </button>
                </form>
            <?php else: ?>
                <?php if (!empty($participant['wishlist'])): ?>
                    <div class="wishlist-display locked">
                        <div class="wishlist-locked-header">
                            <span class="lock-icon">🔒</span>
                            <span>Deine gespeicherte Wunschliste</span>
                        </div>
                        <p><?php echo nl2br(htmlspecialchars($participant['wishlist'])); ?></p>
                    </div>
                <?php else: ?>
                    <div class="empty-wishlist">
                        <span class="empty-wishlist-icon" aria-hidden="true">📋</span>
                        <p>Du hast keine Wunschliste hinterlegt.</p>
                    </div>
                <?php endif; ?>
                <p class="text-muted">
                    <span class="info-icon">ℹ️</span>
                    Die Wunschliste kann nach der Auslosung nicht mehr geändert werden.
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Gruppendetails Section -->
        <div class="section-card">
            <div class="section-card-header">
                <span class="section-icon">ℹ️</span>
                <h2>Gruppendetails</h2>
            </div>
            
            <div class="group-info-grid">
                <div class="group-info-item">
                    <span class="info-label">💰 Budget</span>
                    <span class="info-value">
                        <?php echo $group['budget'] !== null ? number_format($group['budget'], 2) . " CHF" : "Nicht festgelegt"; ?>
                    </span>
                </div>
                <div class="group-info-item">
                    <span class="info-label">📅 Geschenkübergabe</span>
                    <span class="info-value">
                        <?php echo $group['gift_exchange_date'] ? date('d.m.Y', strtotime($group['gift_exchange_date'])) : "Nicht festgelegt"; ?>
                    </span>
                </div>
                <?php if (!empty($group['description'])): ?>
                <div class="group-info-item full-width">
                    <span class="info-label">📄 Beschreibung</span>
                    <span class="info-value"><?php echo htmlspecialchars($group['description']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Teilnehmer-Link Section -->
        <div class="section-card">
            <div class="section-card-header">
                <span class="section-icon">🔗</span>
                <h2>Dein persönlicher Link</h2>
            </div>
            
            <p class="section-description">Du kannst diesen Link verwenden, um später wieder auf deine Teilnahme zuzugreifen.</p>
            
            <div class="link-display-container">
                <pre id="participant-link" class="link-display" data-url="<?php echo htmlspecialchars(get_display_url('/participant.php?token=' . urlencode($participant_token))); ?>"><?php echo htmlspecialchars(get_display_url('/participant.php?token=' . urlencode($participant_token))); ?></pre>
                <button class="button secondary copy-btn" onclick="copyToClipboard('participant-link')">
                    <span aria-hidden="true">📋</span>
                    Link kopieren
                </button>
            </div>
        </div>

        <?php
        // Prüfen ob mehrere Gruppen im Cookie gespeichert sind
        $saved_tokens = get_tokens_from_cookie();
        if (count($saved_tokens) > 1):
        ?>
        <!-- Multi-Group Navigation -->
        <div class="multi-group-nav">
            <p class="multi-group-text">
                <span class="multi-group-icon" aria-hidden="true">🎁</span>
                Du nimmst an mehreren Wichtel-Gruppen teil
            </p>
            <a href="participant.php" class="button secondary">
                <span aria-hidden="true">🔄</span> Gruppe wechseln
            </a>
        </div>
        <?php endif; ?>

        <?php 
        // Google Ad Position 2: Am Ende der Seite
        if (defined('GOOGLE_ADS_ENABLED') && GOOGLE_ADS_ENABLED && 
            defined('GOOGLE_ADS_SHOW_OPTION2') && GOOGLE_ADS_SHOW_OPTION2): 
            $is_testing = defined('GOOGLE_ADS_TESTING') && GOOGLE_ADS_TESTING;
        ?>
        <div class="ad-container">
            <div class="ad-label"><?php echo $is_testing ? 'Test-Anzeige (Position 2)' : 'Anzeige'; ?></div>
            <?php if ($is_testing): ?>
                <div class="ad-test-placeholder">
                    📊 Google Ad Placeholder<br>
                    Position 2: Am Ende der Seite<br>
                    Responsive Display Ad
                </div>
            <?php else: ?>
                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?php echo htmlspecialchars(GOOGLE_ADS_CLIENT); ?>"
                     crossorigin="anonymous"></script>
                <ins class="adsbygoogle"
                     style="display:block"
                     data-ad-client="<?php echo htmlspecialchars(GOOGLE_ADS_CLIENT); ?>"
                     data-ad-slot="<?php echo htmlspecialchars(GOOGLE_ADS_SLOT_OPTION2); ?>"
                     data-ad-format="auto"
                     data-full-width-responsive="true"></ins>
                <script>
                     (adsbygoogle = window.adsbygoogle || []).push({});
                </script>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Cookie Banner -->
    <?php include __DIR__ . '/cookie-banner.php'; ?>
    
    <!-- Footer -->
    <?php include __DIR__ . '/../includes/templates/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var wishlistForm = document.querySelector('.wishlist-form');
            if (wishlistForm) {
                wishlistForm.addEventListener('submit', function(e) {
                    var submitBtn = wishlistForm.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.style.minWidth = submitBtn.offsetWidth + 'px';
                        submitBtn.style.whiteSpace = 'nowrap';
                        submitBtn.innerHTML = '<span class="loading" aria-hidden="true"></span> Wird gespeichert...';
                        submitBtn.disabled = true;
                        submitBtn.style.cursor = 'wait';
                    }
                });
            }
        });
    </script>
</body>
</html>
