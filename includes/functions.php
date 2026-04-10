<?php
require_once __DIR__ . '/config.php';

// Setze den korrekten Sendmail-Pfad
ini_set('sendmail_path', '/usr/sbin/sendmail -t -i'); // Passe den Pfad an

// Fehleranzeige (in Produktion deaktiviert)
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Datenbankverbindung herstellen
function db_connect() {
    $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        return $pdo;
    } catch (PDOException $e) {
        die('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
    }
}

// Generiere einen zufälligen Token
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * CSRF-Token generieren und in Session speichern
 */
function get_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF-Token validieren
 */
function verify_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// E-Mail senden mit PHP's mail() Funktion
function send_email($to, $subject, $message, $is_html = false) {
    // Kopfzeilen für die E-Mail
    $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    
    if ($is_html) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    }

    // Verwende die mail() Funktion
    return mail($to, $subject, $message, $headers);
}

/**
 * Gemeinsames Template für alle E-Mails
 */
function render_email_template($title, $preview_text, $body_content) {
    $current_year = date('Y');
    return '
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); min-height: 100vh;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 40px 20px;">
        <tr>
            <td align="center">
                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 16px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12); overflow: hidden; max-width: 100%;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #264653 0%, #2a9d8f 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-family: \'Playfair Display\', Georgia, serif; font-size: 32px; font-weight: 700; text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);">🎁 Wichteln</h1>
                            <p style="margin: 10px 0 0 0; color: rgba(255, 255, 255, 0.9); font-size: 16px;">' . htmlspecialchars($preview_text) . '</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            ' . $body_content . '
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: #264653; padding: 25px 30px; text-align: center;">
                            <p style="margin: 0; color: rgba(255, 255, 255, 0.8); font-size: 13px;">
                                Diese E-Mail wurde automatisch von <strong style="color: #ffffff;">wichtlä.ch</strong> versendet
                            </p>
                            <p style="margin: 8px 0 0 0; color: rgba(255, 255, 255, 0.6); font-size: 12px;">
                                © ' . $current_year . ' wichtlä.ch - Online Wichteln leicht gemacht
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

// Funktion zum Erstellen einer schönen HTML-E-Mail im Wichtel-Design
function create_html_email($data) {
    $name = $data['name'] ?? '';
    $assigned_name = $data['assigned_name'] ?? '';
    $wishlist = $data['wishlist'] ?? '';
    $budget = $data['budget'] ?? '';
    $description = $data['description'] ?? '';
    $gift_date = $data['gift_date'] ?? '';

    $body = '
                            <!-- Greeting -->
                            <p style="margin: 0 0 20px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Hallo <strong style="color: #e63946;">' . htmlspecialchars($name) . '</strong>,
                            </p>
                            
                            <!-- Partner Info Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, rgba(230, 57, 70, 0.05), rgba(231, 111, 81, 0.05)); border-left: 4px solid #e63946; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 8px 0; color: #5f6368; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Dein Wichtelpartner</p>
                                        <h2 style="margin: 0; color: #e63946; font-family: \'Playfair Display\', Georgia, serif; font-size: 28px; font-weight: 700;">' . htmlspecialchars($assigned_name) . '</h2>
                                    </td>
                                </tr>
                            </table>';
    
    // Wunschliste wenn vorhanden
    if (!empty($wishlist)) {
        $body .= '
                            <!-- Wishlist Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f8f9fa; border-left: 4px solid #2a9d8f; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px 0; color: #2a9d8f; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">✨ Wunschliste von ' . htmlspecialchars($assigned_name) . '</p>
                                        <p style="margin: 0; color: #2b2d42; font-size: 15px; line-height: 1.7; white-space: pre-wrap;">' . htmlspecialchars($wishlist) . '</p>
                                    </td>
                                </tr>
                            </table>';
    }
    
    $body .= '
                            <!-- Group Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0 20px 0; border-top: 2px solid #e1e4e8; padding-top: 20px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 15px 0; color: #2a9d8f; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">📋 Gruppendetails</p>
                                        
                                        <table width="100%" cellpadding="8" cellspacing="0">
                                            <tr>
                                                <td style="color: #5f6368; font-size: 14px; padding: 8px 0;">💰 Budget:</td>
                                                <td style="color: #2b2d42; font-size: 14px; font-weight: 600; padding: 8px 0; text-align: right;">' . htmlspecialchars($budget) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #5f6368; font-size: 14px; padding: 8px 0;">📝 Beschreibung:</td>
                                                <td style="color: #2b2d42; font-size: 14px; font-weight: 600; padding: 8px 0; text-align: right;">' . htmlspecialchars($description) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #5f6368; font-size: 14px; padding: 8px 0;">🎁 Geschenkübergabe:</td>
                                                <td style="color: #2b2d42; font-size: 14px; font-weight: 600; padding: 8px 0; text-align: right;">' . htmlspecialchars($gift_date) . '</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Closing -->
                            <p style="margin: 30px 0 0 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Viel Spaß beim Wichteln! 🎄
                            </p>';
    
    return render_email_template('Dein Wichtelpartner', 'Dein Wichtelpartner wurde ausgelost!', $body);
}

// Funktion zum Erstellen einer Registrierungs-Bestätigungs-E-Mail
function create_registration_email($data) {
    $name = $data['name'] ?? '';
    $group_name = $data['group_name'] ?? '';
    $participant_link = $data['participant_link'] ?? '';
    $budget = $data['budget'] ?? '';
    $description = $data['description'] ?? '';
    $gift_date = $data['gift_date'] ?? '';

    $body = '
                            <p style="margin: 0 0 20px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Hallo <strong style="color: #e63946;">' . htmlspecialchars($name) . '</strong>,
                            </p>
                            
                            <p style="margin: 0 0 25px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Du hast dich erfolgreich für die Wichtelgruppe <strong>"' . htmlspecialchars($group_name) . '"</strong> registriert! 🎉
                            </p>
                            
                            <!-- Personal Link Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, rgba(42, 157, 143, 0.08), rgba(38, 70, 83, 0.08)); border-left: 4px solid #2a9d8f; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px 0; color: #2a9d8f; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">🔗 Dein persönlicher Link</p>
                                        <p style="margin: 0 0 8px 0; color: #5f6368; font-size: 13px; line-height: 1.5;">
                                            Speichere diesen Link, um später deine Wunschliste zu bearbeiten und deinen Wichtelpartner zu sehen:
                                        </p>
                                        <a href="' . htmlspecialchars($participant_link) . '" style="display: inline-block; margin-top: 10px; padding: 12px 24px; background: linear-gradient(135deg, #2a9d8f, #264653); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px;">Zum Teilnehmerbereich →</a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Group Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0 20px 0; border-top: 2px solid #e1e4e8; padding-top: 20px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 15px 0; color: #2a9d8f; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">📋 Gruppendetails</p>
                                        
                                        <table width="100%" cellpadding="8" cellspacing="0">
                                            <tr>
                                                <td style="color: #5f6368; font-size: 14px; padding: 8px 0;">💰 Budget:</td>
                                                <td style="color: #2b2d42; font-size: 14px; font-weight: 600; padding: 8px 0; text-align: right;">' . htmlspecialchars($budget) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #5f6368; font-size: 14px; padding: 8px 0;">📝 Beschreibung:</td>
                                                <td style="color: #2b2d42; font-size: 14px; font-weight: 600; padding: 8px 0; text-align: right;">' . htmlspecialchars($description) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #5f6368; font-size: 14px; padding: 8px 0;">🎁 Geschenkübergabe:</td>
                                                <td style="color: #2b2d42; font-size: 14px; font-weight: 600; padding: 8px 0; text-align: right;">' . htmlspecialchars($gift_date) . '</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Tip Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #fff8e1; border-left: 4px solid #f4a261; border-radius: 8px; margin: 25px 0;">
                                <tr>
                                    <td style="padding: 15px 20px;">
                                        <p style="margin: 0; color: #2b2d42; font-size: 14px; line-height: 1.6;">
                                            <strong style="color: #f4a261;">💡 Tipp:</strong> Hinterlege schon jetzt deine Wunschliste über deinen persönlichen Link. Nach der Auslosung sieht dein Wichtelpartner deine Wünsche!
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 25px 0 0 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Viel Spaß beim Wichteln! 🎄
                            </p>';
    
    return render_email_template('Willkommen beim Wichteln', 'Willkommen beim Wichteln!', $body);
}

// Funktion zum Erstellen einer Admin-Willkommens-E-Mail
function create_admin_email($data) {
    $group_name = $data['group_name'] ?? '';
    $admin_link = $data['admin_link'] ?? '';
    $invite_link = $data['invite_link'] ?? '';
    $budget = $data['budget'] ?? '';
    $description = $data['description'] ?? '';
    $gift_date = $data['gift_date'] ?? '';

    $body = '
                            <p style="margin: 0 0 20px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Hallo <strong style="color: #e63946;">Admin</strong>,
                            </p>
                            
                            <p style="margin: 0 0 25px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Deine Wichtelgruppe <strong>"' . htmlspecialchars($group_name) . '"</strong> wurde erfolgreich erstellt! 🎉
                            </p>
                            
                            <!-- Admin Link Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, rgba(230, 57, 70, 0.08), rgba(231, 111, 81, 0.08)); border-left: 4px solid #e63946; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px 0; color: #e63946; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">🔐 Admin-Bereich</p>
                                        <p style="margin: 0 0 8px 0; color: #5f6368; font-size: 13px; line-height: 1.5;">
                                            Über diesen Link verwaltest du deine Gruppe, fügst Teilnehmer hinzu und führst die Auslosung durch:
                                        </p>
                                        <a href="' . htmlspecialchars($admin_link) . '" style="display: inline-block; margin-top: 10px; padding: 12px 24px; background: linear-gradient(135deg, #e63946, #d62828); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px;">Zum Admin-Bereich →</a>
                                        <p style="margin: 12px 0 0 0; color: #5f6368; font-size: 12px; line-height: 1.5;">
                                            ⚠️ <strong>Wichtig:</strong> Speichere diesen Link sicher! Er ist dein Zugang zur Verwaltung der Gruppe.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Invite Link Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, rgba(42, 157, 143, 0.08), rgba(38, 70, 83, 0.08)); border-left: 4px solid #2a9d8f; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px 0; color: #2a9d8f; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">👥 Einladungslink für Teilnehmer</p>
                                        <p style="margin: 0 0 8px 0; color: #5f6368; font-size: 13px; line-height: 1.5;">
                                            Teile diesen Link mit allen, die beim Wichteln mitmachen sollen:
                                        </p>
                                        <p style="margin: 10px 0 0 0; padding: 12px; background: #ffffff; border: 1px solid #e1e4e8; border-radius: 6px; color: #2a9d8f; font-size: 13px; font-family: monospace; word-break: break-all;">
                                            ' . htmlspecialchars($invite_link) . '
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Group Details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0 20px 0; border-top: 2px solid #e1e4e8; padding-top: 20px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 15px 0; color: #2a9d8f; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">📋 Gruppendetails</p>
                                        
                                        <table width="100%" cellpadding="8" cellspacing="0">
                                            <tr>
                                                <td style="color: #5f6368; font-size: 14px; padding: 8px 0;">💰 Budget:</td>
                                                <td style="color: #2b2d42; font-size: 14px; font-weight: 600; padding: 8px 0; text-align: right;">' . htmlspecialchars($budget) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #5f6368; font-size: 14px; padding: 8px 0;">📝 Beschreibung:</td>
                                                <td style="color: #2b2d42; font-size: 14px; font-weight: 600; padding: 8px 0; text-align: right;">' . htmlspecialchars($description) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="color: #5f6368; font-size: 14px; padding: 8px 0;">🎁 Geschenkübergabe:</td>
                                                <td style="color: #2b2d42; font-size: 14px; font-weight: 600; padding: 8px 0; text-align: right;">' . htmlspecialchars($gift_date) . '</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Next Steps -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f8f9fa; border-left: 4px solid #f4a261; border-radius: 8px; margin: 25px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px 0; color: #f4a261; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">📝 Nächste Schritte</p>
                                        <ol style="margin: 0; padding-left: 20px; color: #2b2d42; font-size: 14px; line-height: 1.8;">
                                            <li>Teile den Einladungslink mit allen Teilnehmern</li>
                                            <li>Warte, bis sich alle registriert haben</li>
                                            <li>Lege optional Ausschlüsse fest (z.B. Paare)</li>
                                            <li>Führe die Auslosung im Admin-Bereich durch</li>
                                        </ol>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="margin: 25px 0 0 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Viel Spaß beim Wichteln! 🎄
                            </p>';
    
    return render_email_template('Deine Wichtelgruppe wurde erstellt', 'Deine Gruppe wurde erfolgreich erstellt!', $body);
}

// Funktion zum Erstellen einer E-Mail bei Wunschlisten-Aktualisierung
function create_wishlist_update_email($giver_name, $updater_name, $wishlist) {
    $body = '
                            <!-- Greeting -->
                            <p style="margin: 0 0 20px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Hallo <strong style="color: #e63946;">' . htmlspecialchars($giver_name) . '</strong>,
                            </p>
                            
                            <p style="margin: 0 0 25px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Dein Wichtelpartner <strong>' . htmlspecialchars($updater_name) . '</strong> hat seine Wunschliste aktualisiert.
                            </p>';

    if (!empty($wishlist)) {
        $body .= '
                            <!-- Wishlist Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f8f9fa; border-left: 4px solid #2a9d8f; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0 0 12px 0; color: #2a9d8f; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">✨ Aktuelle Wunschliste von ' . htmlspecialchars($updater_name) . '</p>
                                        <p style="margin: 0; color: #2b2d42; font-size: 15px; line-height: 1.7; white-space: pre-wrap;">' . htmlspecialchars($wishlist) . '</p>
                                    </td>
                                </tr>
                            </table>';
    } else {
        $body .= '
                            <!-- Empty Wishlist Notice -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background: #f8f9fa; border-left: 4px solid #f4a261; border-radius: 8px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <p style="margin: 0; color: #2b2d42; font-size: 15px; line-height: 1.7;">
                                            <strong style="color: #f4a261;">📋</strong> Die Wunschliste ist derzeit leer.
                                        </p>
                                    </td>
                                </tr>
                            </table>';
    }

    $body .= '
                            <!-- Closing -->
                            <p style="margin: 30px 0 0 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Viel Spaß beim Wichteln! 🎄
                            </p>';
    
    return render_email_template('Wunschliste aktualisiert', 'Wunschliste wurde aktualisiert!', $body);
}

// Funktion zur Generierung der Basis-URL
function get_base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
                $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    return $protocol . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/');
}

/**
 * Führt die Wichtel-Auslosung durch.
 *
 * @param array $participant_ids Liste der Teilnehmer-IDs.
 * @param array $exclusions_map Map von Teilnehmer-ID zu Liste ausgeschlossener Teilnehmer-IDs.
 * @param int $max_attempts Maximale Anzahl der Versuche (Shuffle).
 * @return array|false Gibt das Array der zugewiesenen IDs zurück oder false, wenn keine gültige Auslosung gefunden wurde.
 */
function perform_draw($participant_ids, $exclusions_map = [], $max_attempts = 1000) {
    if (count($participant_ids) < 2) {
        return false;
    }

    $assigned_ids = $participant_ids;
    $attempt = 0;
    $valid_assignment = false;
    $count = count($participant_ids);

    while (!$valid_assignment && $attempt < $max_attempts) {
        shuffle($assigned_ids);
        $valid_assignment = true;

        for ($i = 0; $i < $count; $i++) {
            $giver = $participant_ids[$i];
            $receiver = $assigned_ids[$i];

            // Prüfen ob Person sich selbst zieht
            if ($giver == $receiver) {
                $valid_assignment = false;
                break;
            }

            // Prüfen ob Zuteilung ausgeschlossen
            if (isset($exclusions_map[$giver]) && in_array($receiver, $exclusions_map[$giver])) {
                $valid_assignment = false;
                break;
            }
        }
        $attempt++;
    }

    return $valid_assignment ? $assigned_ids : false;
}

// Funktion zur Generierung einer lesbaren Display-URL (ohne https:// und mit wichtlä.ch statt Punycode)
function get_display_url($path = '') {
    // Konvertiere Punycode zurück zu IDN (internationalisierte Domain)
    $host = $_SERVER['HTTP_HOST'];
    
    // Wenn es xn--wichtl-gua.ch ist, zeige wichtlä.ch
    if (strpos($host, 'xn--wichtl-gua.ch') !== false) {
        $host = str_replace('xn--wichtl-gua.ch', 'wichtlä.ch', $host);
    }
    
    // Entferne führenden Slash vom Pfad
    $path = ltrim($path, '/');
    
    // Baue die Display-URL zusammen (mit https://)
    return 'https://' . $host . ($path ? '/' . $path : '');
}

?>
