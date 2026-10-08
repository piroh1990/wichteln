<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/urls.php';
require_once __DIR__ . '/captcha_lib.php';

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
        error_log('Datenbankverbindung fehlgeschlagen: ' . $e->getMessage());
        die('Datenbankverbindung fehlgeschlagen.');
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
    start_secure_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF-Token validieren
 */
function verify_csrf_token($token) {
    start_secure_session();
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Versteckte Formularfelder für den CSRF-Token.
 */
function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8')
        . '">';
}

/**
 * Einheitliche Fehlermeldung, wenn ein CSRF-Token fehlt oder ungültig ist.
 */
function csrf_failure_message() {
    return 'Ungültiger CSRF-Token. Bitte lade die Seite neu und versuche es erneut.';
}

/**
 * Anfrage mit HTTP 403 und der CSRF-Fehlermeldung beenden.
 */
function abort_invalid_csrf() {
    if (!headers_sent()) {
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
    }
    $message = htmlspecialchars(csrf_failure_message(), ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Sicherheitsprüfung</title></head><body><p>'
        . $message
        . '</p></body></html>';
    exit;
}

/**
 * Master-Admin-Token zeitkonstant vergleichen.
 * Leere Tokens gelten nie als gültig.
 */
function master_admin_token_matches($provided) {
    if (!is_string($provided) || $provided === '') {
        return false;
    }
    if (!defined('MASTER_ADMIN_TOKEN') || !is_string(MASTER_ADMIN_TOKEN) || MASTER_ADMIN_TOKEN === '') {
        return false;
    }
    return hash_equals(MASTER_ADMIN_TOKEN, $provided);
}

/**
 * Betreff für mail() kodieren, wenn er nicht ASCII ist.
 * Zeilenumbrüche werden entfernt, damit kein Header eingeschleust wird.
 */
function encode_mail_subject($subject) {
    $subject = str_replace(array("\r", "\n"), '', (string) $subject);
    if ($subject === '') {
        return '';
    }
    if (preg_match('/[^\x20-\x7E]/', $subject)) {
        return '=?UTF-8?B?' . base64_encode($subject) . '?=';
    }
    return $subject;
}

/**
 * Kopfzeilen und Body für mail() bauen.
 * Mit HTML und Klartext entsteht multipart/alternative, Klartext zuerst.
 *
 * @return array headers, body, boundary
 */
function build_outgoing_mail($message, $is_html = false, $plain_text = null) {
    $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . SMTP_FROM_EMAIL . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";

    if ($is_html && is_string($plain_text) && $plain_text !== '') {
        $boundary = 'wichtel_' . bin2hex(random_bytes(12));
        $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . "\"\r\n";

        $body = '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $plain_text . "\r\n";
        $body .= '--' . $boundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= $message . "\r\n";
        $body .= '--' . $boundary . "--\r\n";

        return array(
            'headers' => $headers,
            'body' => $body,
            'boundary' => $boundary,
        );
    }

    if ($is_html) {
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    } else {
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    }

    return array(
        'headers' => $headers,
        'body' => $message,
        'boundary' => null,
    );
}

// E-Mail senden mit PHP's mail() Funktion.
// $plain_text erzeugt bei HTML-Mails eine multipart/alternative-Alternative.
function send_email($to, $subject, $message, $is_html = false, $plain_text = null) {
    $mail = build_outgoing_mail($message, $is_html, $plain_text);
    return mail($to, encode_mail_subject($subject), $mail['body'], $mail['headers']);
}

/**
 * Prüft, ob eine Spalte existiert.
 * Der Vergleich ist exakt, weil LIKE Unterstriche als Platzhalter wertet.
 */
function mysql_column_exists($pdo, $table, $column) {
    if (!is_string($table) || !is_string($column)) {
        return false;
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table) || !preg_match('/^[A-Za-z0-9_]+$/', $column)) {
        return false;
    }
    try {
        $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');
        if (!$stmt) {
            return false;
        }
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['Field']) && $row['Field'] === $column) {
                return true;
            }
        }
    } catch (Exception $e) {
        error_log('Spaltenprüfung fehlgeschlagen: ' . $e->getMessage());
    }
    return false;
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
    $ics_url = $data['ics_url'] ?? '';

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
                            
                            ' . email_calendar_paragraph($ics_url) . '
                            
                            <!-- Closing -->
                            <p style="margin: 30px 0 0 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Viel Spaß beim Wichteln! 🎄
                            </p>';
    
    return render_email_template('Dein Wichtelpartner', 'Dein Wichtelpartner wurde ausgelost!', $body);
}

/**
 * Button «In Kalender speichern», nur wenn eine Kalender-URL mitgegeben wird.
 */
function email_calendar_paragraph($url) {
    if (!is_string($url) || $url === '') {
        return '';
    }
    $safe = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    return '
                            <p style="margin: 0 0 20px 0;">
                                <a href="' . $safe . '" style="display: inline-block; padding: 12px 24px; background: #264653; color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px;">In Kalender speichern</a>
                            </p>';
}

// Funktion zum Erstellen einer Registrierungs-Bestätigungs-E-Mail
function create_registration_email($data) {
    $name = $data['name'] ?? '';
    $group_name = $data['group_name'] ?? '';
    $participant_link = $data['participant_link'] ?? '';
    $budget = $data['budget'] ?? '';
    $description = $data['description'] ?? '';
    $gift_date = $data['gift_date'] ?? '';
    $ics_url = $data['ics_url'] ?? '';

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
                            
                            ' . email_calendar_paragraph($ics_url) . '
                            
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
    $ics_url = $data['ics_url'] ?? '';

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
                            
                            ' . email_calendar_paragraph($ics_url) . '
                            
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

/**
 * Namen für die Auflösung: Zeilenumbrüche werden zu Leerzeichen.
 */
function reveal_display_name($name) {
    $clean = str_replace(array("\r", "\n", "\t"), ' ', (string) $name);
    return trim($clean);
}

/**
 * Gültige Teilnehmer-Mail oder leerer String.
 */
function reveal_participant_email($participant) {
    if (!is_array($participant) || !isset($participant['email'])) {
        return '';
    }
    $email = trim((string) $participant['email']);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '';
    }
    return $email;
}

/**
 * @return array with_email, without_email
 */
function reveal_recipient_stats($participants) {
    $with_email = 0;
    $without_email = 0;
    if (!is_array($participants)) {
        return array('with_email' => 0, 'without_email' => 0);
    }
    foreach ($participants as $participant) {
        if (reveal_participant_email($participant) !== '') {
            $with_email++;
        } else {
            $without_email++;
        }
    }
    return array('with_email' => $with_email, 'without_email' => $without_email);
}

/**
 * Paare der Auslosung: Geber -> Beschenkter.
 * Unvollständige Zuordnungen fehlen in der Liste.
 *
 * @return array Liste mit giver_id, receiver_id, giver_name, receiver_name
 */
function build_reveal_pairs($participants) {
    if (!is_array($participants)) {
        return array();
    }

    $by_id = array();
    foreach ($participants as $participant) {
        if (!is_array($participant) || !isset($participant['id'])) {
            continue;
        }
        $by_id[$participant['id']] = $participant;
    }

    $pairs = array();
    foreach ($participants as $participant) {
        if (!is_array($participant) || !isset($participant['id'])) {
            continue;
        }
        if (!array_key_exists('assigned_to', $participant) || $participant['assigned_to'] === null || $participant['assigned_to'] === '') {
            continue;
        }
        $receiver_id = $participant['assigned_to'];
        if (!isset($by_id[$receiver_id])) {
            continue;
        }
        $receiver = $by_id[$receiver_id];
        $pairs[] = array(
            'giver_id' => $participant['id'],
            'receiver_id' => $receiver['id'],
            'giver_name' => reveal_display_name(isset($participant['name']) ? $participant['name'] : ''),
            'receiver_name' => reveal_display_name(isset($receiver['name']) ? $receiver['name'] : ''),
        );
    }

    usort($pairs, function ($left, $right) {
        $left_name = function_exists('mb_strtolower') ? mb_strtolower($left['giver_name'], 'UTF-8') : strtolower($left['giver_name']);
        $right_name = function_exists('mb_strtolower') ? mb_strtolower($right['giver_name'], 'UTF-8') : strtolower($right['giver_name']);
        if ($left_name === $right_name) {
            return 0;
        }
        return ($left_name < $right_name) ? -1 : 1;
    });

    return $pairs;
}

function reveal_email_subject() {
    return 'Auflösung: Wer hat wem gewichtelt?';
}

/**
 * onsubmit-Wert für confirm(), sicher in ein doppeltes HTML-Attribut gesetzt.
 * json_encode lässt die umschließenden Anführungszeichen stehen; die werden hier escaped.
 */
function html_onsubmit_confirm($message) {
    $script = 'return confirm(' . json_encode(
        (string) $message,
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) . ');';
    return htmlspecialchars($script, ENT_QUOTES, 'UTF-8');
}

function format_reveal_sent_at($value) {
    if (!is_string($value) && !is_numeric($value)) {
        return '';
    }
    $timestamp = strtotime((string) $value);
    if ($timestamp === false) {
        return '';
    }
    return date('d.m.Y, H:i', $timestamp) . ' Uhr';
}

function create_reveal_plain_text($data) {
    $name = reveal_display_name(isset($data['name']) ? $data['name'] : '');
    $group_name = reveal_display_name(isset($data['group_name']) ? $data['group_name'] : '');
    $pairs = (isset($data['pairs']) && is_array($data['pairs'])) ? $data['pairs'] : array();

    $lines = array();
    $lines[] = 'Hallo ' . $name . ',';
    $lines[] = '';
    $lines[] = 'dein Gruppenadmin hat die Auflösung freigegeben.';
    $lines[] = 'Die Wichtelrunde in der Gruppe "' . $group_name . '" ist aufgelöst.';
    $lines[] = 'Hier ist die komplette Liste, wer wem ein Geschenk gemacht hat';
    $lines[] = '(Geber -> Beschenkter):';
    $lines[] = '';

    if (count($pairs) === 0) {
        $lines[] = 'Es liegen keine Zuordnungen vor.';
    } else {
        foreach ($pairs as $pair) {
            $giver = reveal_display_name(isset($pair['giver_name']) ? $pair['giver_name'] : '');
            $receiver = reveal_display_name(isset($pair['receiver_name']) ? $pair['receiver_name'] : '');
            $lines[] = $giver . ' -> ' . $receiver;
        }
    }

    $lines[] = '';
    $lines[] = 'Viel Spaß beim Wichteln!';
    $lines[] = 'wichtlä.ch';
    $lines[] = '';

    return implode("\n", $lines);
}

function create_reveal_email($data) {
    $name = reveal_display_name(isset($data['name']) ? $data['name'] : '');
    $group_name = reveal_display_name(isset($data['group_name']) ? $data['group_name'] : '');
    $pairs = (isset($data['pairs']) && is_array($data['pairs'])) ? $data['pairs'] : array();
    $pair_count = count($pairs);
    $pair_label = ($pair_count === 1) ? '1 Paar' : ($pair_count . ' Paare');

    $cards = '';
    foreach ($pairs as $pair) {
        $giver = reveal_display_name(isset($pair['giver_name']) ? $pair['giver_name'] : '');
        $receiver = reveal_display_name(isset($pair['receiver_name']) ? $pair['receiver_name'] : '');
        $cards .= '
                            <tr>
                                <td style="padding: 0 0 12px 0;">
                                    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background: #f8f9fa; border-left: 4px solid #2a9d8f; border-radius: 8px;">
                                        <tr>
                                            <td style="padding: 14px 16px;">
                                                <p style="margin: 0 0 4px 0; color: #5f6368; font-size: 11px; letter-spacing: 0.6px; text-transform: uppercase; font-weight: 700;">Geber</p>
                                                <p style="margin: 0; color: #264653; font-size: 17px; font-weight: 700; line-height: 1.35;">' . htmlspecialchars($giver, ENT_QUOTES, 'UTF-8') . '</p>
                                                <p style="margin: 8px 0; color: #e63946; font-size: 14px; font-weight: 700; line-height: 1.3;">↓ beschenkt</p>
                                                <p style="margin: 0 0 4px 0; color: #5f6368; font-size: 11px; letter-spacing: 0.6px; text-transform: uppercase; font-weight: 700;">Beschenkter</p>
                                                <p style="margin: 0; color: #e63946; font-size: 17px; font-weight: 700; line-height: 1.35;">' . htmlspecialchars($receiver, ENT_QUOTES, 'UTF-8') . '</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>';
    }

    if ($cards === '') {
        $cards = '
                            <tr>
                                <td style="padding: 16px; background: #fff8e1; border-left: 4px solid #f4a261; border-radius: 8px; color: #2b2d42; font-size: 15px;">
                                    Es liegen keine Zuordnungen vor.
                                </td>
                            </tr>';
    }

    $body = '
                            <p style="margin: 0 0 16px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Hallo <strong style="color: #e63946;">' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</strong>,
                            </p>
                            <p style="margin: 0 0 8px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                dein Gruppenadmin hat die Auflösung freigegeben.
                            </p>
                            <p style="margin: 0 0 22px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Die Wichtelrunde in der Gruppe <strong>"' . htmlspecialchars($group_name, ENT_QUOTES, 'UTF-8') . '"</strong> ist aufgelöst. Hier ist die komplette Liste, wer wem ein Geschenk gemacht hat.
                            </p>
                            <p style="margin: 0 0 14px 0; color: #2a9d8f; font-size: 13px; letter-spacing: 0.5px; text-transform: uppercase; font-weight: 700;">' . htmlspecialchars($pair_label, ENT_QUOTES, 'UTF-8') . ' · Geber → Beschenkter</p>
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">' . $cards . '
                            </table>
                            <p style="margin: 8px 0 0 0; color: #5f6368; font-size: 14px; line-height: 1.6;">
                                Der Name oben hat der Person darunter ein Geschenk gemacht.
                            </p>
                            <p style="margin: 24px 0 0 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Viel Spaß beim Wichteln! 🎄
                            </p>';

    return render_email_template('Auflösung', 'Wer hat wem gewichtelt?', $body);
}

/**
 * Verschickt die Auflösung an alle Teilnehmer mit gültiger E-Mail.
 * Der Versand bricht bei einem einzelnen Fehler nicht ab; Fehler werden gezählt
 * und wie bei der Auslosung ins Error-Log geschrieben.
 *
 * @param callable|null $sender function($to, $subject, $html, $plain): bool
 * @return array status, sent, failed, skipped, pairs
 */
function deliver_group_reveal($participants, $group, $sender = null) {
    if (!is_array($participants)) {
        $participants = array();
    }
    if (!is_array($group)) {
        $group = array();
    }

    $pairs = build_reveal_pairs($participants);
    $empty = array(
        'sent' => 0,
        'failed' => 0,
        'skipped' => 0,
        'pairs' => $pairs,
    );

    if (count($participants) < 2 || count($pairs) !== count($participants)) {
        $empty['status'] = 'incomplete';
        return $empty;
    }

    if ($sender === null) {
        $sender = function ($to, $subject, $html, $plain) {
            return send_email($to, $subject, $html, true, $plain);
        };
    }

    $sent = 0;
    $failed = 0;
    $skipped = 0;
    $subject = reveal_email_subject();
    $group_name = isset($group['name']) ? $group['name'] : '';

    foreach ($participants as $participant) {
        $email = reveal_participant_email($participant);
        if ($email === '') {
            $skipped++;
            continue;
        }

        $payload = array(
            'name' => isset($participant['name']) ? $participant['name'] : '',
            'group_name' => $group_name,
            'pairs' => $pairs,
        );
        $html = create_reveal_email($payload);
        $plain = create_reveal_plain_text($payload);

        $ok = false;
        try {
            $ok = (bool) call_user_func($sender, $email, $subject, $html, $plain);
        } catch (Exception $e) {
            $ok = false;
            error_log('Auflösung Versandfehler: ' . $e->getMessage());
        }

        if ($ok) {
            $sent++;
        } else {
            $failed++;
            error_log('Auflösung konnte nicht an ' . mask_email($email) . ' gesendet werden.');
        }
    }

    if ($sent === 0 && $failed === 0) {
        $status = 'no_recipients';
    } elseif ($sent === 0) {
        $status = 'failed';
    } else {
        $status = 'ok';
    }

    return array(
        'status' => $status,
        'sent' => $sent,
        'failed' => $failed,
        'skipped' => $skipped,
        'pairs' => $pairs,
    );
}

/**
 * @return array type (success|warning|error), text
 */
function reveal_result_message($result) {
    $status = isset($result['status']) ? $result['status'] : '';
    $sent = isset($result['sent']) ? (int) $result['sent'] : 0;
    $failed = isset($result['failed']) ? (int) $result['failed'] : 0;
    $skipped = isset($result['skipped']) ? (int) $result['skipped'] : 0;

    if ($status === 'incomplete') {
        return array(
            'type' => 'error',
            'text' => 'Die Auflösung kann nicht gesendet werden, weil nicht für jeden Teilnehmer eine Zuordnung vorliegt.',
        );
    }
    if ($status === 'no_recipients') {
        return array(
            'type' => 'error',
            'text' => 'Die Auflösung wurde an niemanden gesendet. Kein Teilnehmer hat eine gültige E-Mail-Adresse.',
        );
    }
    if ($status === 'failed') {
        $failed_text = ($failed === 1) ? '1 E-Mail ist fehlgeschlagen.' : ($failed . ' E-Mails sind fehlgeschlagen.');
        return array(
            'type' => 'error',
            'text' => 'Die Auflösung wurde an niemanden gesendet. ' . $failed_text,
        );
    }

    $sent_text = ($sent === 1)
        ? 'Die Auflösung wurde an 1 Teilnehmer gesendet.'
        : ('Die Auflösung wurde an ' . $sent . ' Teilnehmer gesendet.');

    $extra = array();
    if ($skipped === 1) {
        $extra[] = '1 Teilnehmer ohne E-Mail wurde übersprungen';
    } elseif ($skipped > 1) {
        $extra[] = $skipped . ' Teilnehmer ohne E-Mail wurden übersprungen';
    }
    if ($failed === 1) {
        $extra[] = '1 E-Mail ist fehlgeschlagen';
    } elseif ($failed > 1) {
        $extra[] = $failed . ' E-Mails sind fehlgeschlagen';
    }

    $text = $sent_text;
    if (count($extra) > 0) {
        $text .= ' ' . implode('. ', $extra) . '.';
    }

    return array(
        'type' => ($failed > 0) ? 'warning' : 'success',
        'text' => $text,
    );
}

function text_length($value) {
    $value = (string) $value;
    if (function_exists('mb_strlen')) {
        return mb_strlen($value, 'UTF-8');
    }
    return strlen($value);
}

function limit_text_error($value, $max, $label) {
    $max = (int) $max;
    if (text_length($value) > $max) {
        return $label . ' ist zu lang (höchstens ' . $max . ' Zeichen).';
    }
    return '';
}

function mask_email($email) {
    $email = (string) $email;
    $at = strrpos($email, '@');
    if ($at === false || $at < 1) {
        return '***';
    }
    return substr($email, 0, 1) . '***@' . substr($email, $at + 1);
}

function token_page_referrer_meta() {
    return '<meta name="referrer" content="no-referrer">';
}

// Feste Basis-URL aus der Konfiguration, unabhängig vom Host-Header.
function get_base_url() {
    return canonical_base_url();
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

    $assigned_ids = array_values($participant_ids);
    $attempt = 0;
    $valid_assignment = false;
    $count = count($participant_ids);

    while (!$valid_assignment && $attempt < $max_attempts) {
        $assigned_ids = secure_shuffle_values($assigned_ids);
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

/**
 * Fisher-Yates mit random_int.
 *
 * @param array $values
 * @return array
 */
function secure_shuffle_values(array $values) {
    $items = array_values($values);
    $last = count($items) - 1;
    for ($i = $last; $i > 0; $i--) {
        $j = random_int(0, $i);
        $swap = $items[$i];
        $items[$i] = $items[$j];
        $items[$j] = $swap;
    }
    return $items;
}

/**
 * Speichert eine Auslosung nur, wenn die Gruppe noch nicht ausgelost ist.
 *
 * @param PDO $pdo
 * @param int $group_id
 * @param array $participant_ids
 * @param array $assigned_ids
 * @return bool
 */
function save_draw_assignment(PDO $pdo, $group_id, array $participant_ids, array $assigned_ids) {
    $group_id = (int) $group_id;
    if ($group_id < 1 || count($participant_ids) < 2 || count($participant_ids) !== count($assigned_ids)) {
        return false;
    }

    $pdo->beginTransaction();
    try {
        $mark = $pdo->prepare('UPDATE `groups` SET `is_drawn` = 1 WHERE `id` = ? AND (`is_drawn` = 0 OR `is_drawn` IS NULL)');
        $mark->execute(array($group_id));
        if ($mark->rowCount() !== 1) {
            $pdo->rollBack();
            return false;
        }

        $stmt = $pdo->prepare('UPDATE `participants` SET `assigned_to` = ? WHERE `id` = ? AND `group_id` = ?');
        $count = count($participant_ids);
        for ($i = 0; $i < $count; $i++) {
            $stmt->execute(array($assigned_ids[$i], $participant_ids[$i], $group_id));
        }
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

// Lesbare Display-URL auf Basis der festen Basis-URL.
function get_display_url($path = '') {
    $host = parse_url(canonical_base_url(), PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        $host = 'xn--wichtl-gua.ch';
    }
    if (strpos($host, 'xn--wichtl-gua.ch') !== false) {
        $host = str_replace('xn--wichtl-gua.ch', 'wichtlä.ch', $host);
    }
    
    // Entferne führenden Slash vom Pfad
    $path = ltrim($path, '/');
    
    // Baue die Display-URL zusammen (mit https://)
    return 'https://' . $host . ($path ? '/' . $path : '');
}

require_once __DIR__ . '/group_tools.php';

?>
