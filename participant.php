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

require_once 'functions.php';

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
    
    if (empty($saved_tokens)) {
        // Keine gespeicherten Tokens - zeige schöne Fehlerseite
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
        <div class="error-page">
            <div class="error-content">
                <div class="error-icon">🎄</div>
                <h1 class="error-title">Keine Gruppe gefunden</h1>
                <p class="error-message">
                    Du hast noch keine Wichtel-Gruppe besucht oder dein Link ist nicht mehr gültig.
                    Um auf deinen Teilnehmerbereich zuzugreifen, benötigst du einen persönlichen Teilnehmer-Link.
                </p>
                
                <div class="error-actions">
                    <a href="index.php" class="button primary">🏠 Zur Startseite</a>
                    <a href="create_group.php" class="button secondary">➕ Neue Gruppe erstellen</a>
                </div>
                
                <div class="error-help">
                    <h3>💡 So kommst du zu deinem Teilnehmerbereich:</h3>
                    <ul>
                        <li><strong>Einladungslink erhalten?</strong> Benutze den Link, den dir der Gruppenadmin geschickt hat</li>
                        <li><strong>Bereits angemeldet?</strong> Verwende deinen persönlichen Teilnehmer-Link aus der Bestätigungs-E-Mail</li>
                        <li><strong>Link verloren?</strong> Kontaktiere den Gruppenadmin für einen neuen Link</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
        <?php
        exit;
    }
    
    // Teilnehmer für alle gespeicherten Tokens laden
    $participants = load_participants_from_tokens($pdo, $saved_tokens);
    
    if (empty($participants)) {
        die('Keine gültigen Teilnahmen gefunden.');
    }
    
    // Wenn Gruppenauswahl per POST gesendet wurde
    if (isset($_POST['select_group']) && isset($_POST['selected_token'])) {
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
        <div class="group-selector">
            <h1>Willkommen zurück! 🎄</h1>
            <p>Du nimmst an mehreren Wichtel-Gruppen teil. Bitte wähle die Gruppe aus, die du ansehen möchtest:</p>
            
            <form method="POST" id="group-selector-form">
                <?php foreach ($participants as $p): ?>
                <label class="group-card">
                    <input type="radio" name="selected_token" value="<?php echo htmlspecialchars($p['participant_token']); ?>" required>
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Roboto&display=swap" rel="stylesheet">
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Zusätzliche Styles spezifisch für participant.php (falls nötig) */
    </style>
    <!-- JavaScript für Kopieren-Button -->
    <script>
        function copyToClipboard(elementId) {
            var copyText = document.getElementById(elementId).innerText;
            var tempInput = document.createElement("textarea");
            tempInput.value = copyText;
            document.body.appendChild(tempInput);
            tempInput.select();
            tempInput.setSelectionRange(0, 99999); // Für mobile Geräte
            document.execCommand("copy");
            document.body.removeChild(tempInput);
            alert("Link kopiert: " + copyText);
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
        <a href="index.php" title="Zur Startseite">
            <img src="images/logo.png" alt="Wichtel Logo">
        </a>
    </header>
    <div class="container">
        <h1>Willkommen, <?php echo htmlspecialchars($participant['name']); ?>!</h1>
        <p>Gruppe: <?php echo htmlspecialchars($group['name']); ?></p>
        
        <?php if (isset($wishlist_success)): ?>
            <div class="notification success">
                <?php echo htmlspecialchars($wishlist_success); ?>
            </div>
        <?php endif; ?>
        
		        <?php if ($group['is_drawn']): ?>
            <?php if ($assigned): ?>
                <h2>Dein Wichtelpartner:</h2>
                <p style="font-size: 1.3rem; font-weight: 600; color: var(--primary-color);"><?php echo htmlspecialchars($assigned['name']); ?></p>
                
                <?php if (!empty($assigned['wishlist'])): ?>
                    <h3>Wunschliste von <?php echo htmlspecialchars($assigned['name']); ?>:</h3>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; border-left: 4px solid var(--secondary-color); margin-bottom: 1.5rem;">
                        <p style="white-space: pre-wrap; margin: 0;"><?php echo htmlspecialchars($assigned['wishlist']); ?></p>
                    </div>
                <?php else: ?>
                    <p class="text-muted"><?php echo htmlspecialchars($assigned['name']); ?> hat noch keine Wunschliste hinterlegt.</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="notification error">
                    Dein Wichtelpartner konnte nicht gefunden werden.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <p>Die Auslosung wurde noch nicht durchgeführt. Bitte schaue später wieder vorbei.</p>
        <?php endif; ?>
        
        <hr>
        
        <!-- Wunschliste bearbeiten -->
        <h2>Deine Wunschliste</h2>
        <?php if (!$group['is_drawn']): ?>
            <p>Trage hier deine Wünsche ein. Dein Wichtelpartner wird diese nach der Auslosung sehen können.</p>
            <form method="POST">
                <div class="form-group">
                    <label for="wishlist">Wunschliste:</label>
                    <textarea id="wishlist" name="wishlist" rows="6" placeholder="z.B.&#10;- Bücher über...&#10;- Schokolade&#10;- Etwas Selbstgemachtes&#10;- Überraschung!"><?php echo htmlspecialchars($participant['wishlist'] ?? ''); ?></textarea>
                </div>
                <button type="submit" name="update_wishlist" class="button primary">Wunschliste speichern</button>
            </form>
        <?php else: ?>
            <?php if (!empty($participant['wishlist'])): ?>
                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; border-left: 4px solid var(--primary-color);">
                    <p style="white-space: pre-wrap; margin: 0;"><?php echo htmlspecialchars($participant['wishlist']); ?></p>
                </div>
            <?php else: ?>
                <p class="text-muted">Du hast keine Wunschliste hinterlegt.</p>
            <?php endif; ?>
            <p class="text-muted" style="margin-top: 1rem;">Die Wunschliste kann nach der Auslosung nicht mehr geändert werden.</p>
        <?php endif; ?>
		
        <hr>
        
        <h2>Gruppendetails</h2>
        <ul>
            <li><strong>Budget:</strong> <?php echo $group['budget'] !== null ? htmlspecialchars(number_format($group['budget'], 2)) . " CHF" : "Nicht festgelegt"; ?></li>
            <li><strong>Beschreibung:</strong> <?php echo htmlspecialchars($group['description'] ?: "Keine Beschreibung."); ?></li>
            <li><strong>Datum der Geschenkübergabe:</strong> <?php echo $group['gift_exchange_date'] ? htmlspecialchars(date('d.m.Y', strtotime($group['gift_exchange_date']))) : "Nicht festgelegt"; ?></li>
        </ul>

        <!-- Eigener Teilnehmer-Link anzeigen -->
        <h2>Dein Link</h2>
        <p>Du kannst diesen Link verwenden, um deine Teilnahme zu teilen oder für zukünftige Referenz zu speichern:</p>
        <pre id="participant-link"><?php echo htmlspecialchars(get_display_url('/participant.php?token=' . urlencode($participant_token))); ?></pre>
        <button class="button secondary small copy-button" onclick="copyToClipboard('participant-link')">Link kopieren</button>

        <?php
        // Prüfen ob mehrere Gruppen im Cookie gespeichert sind
        $saved_tokens = get_tokens_from_cookie();
        if (count($saved_tokens) > 1):
        ?>
        <hr>
        
        <!-- Navigation für mehrere Gruppen -->
        <div style="text-align: center; margin-top: 2rem;">
            <p class="text-muted">Du nimmst an mehreren Wichtel-Gruppen teil.</p>
            <a href="participant.php" class="button secondary small">🔄 Gruppe wechseln</a>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
