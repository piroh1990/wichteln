<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/support.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/urls.php';
require_once __DIR__ . '/i18n.php';
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
    return t('error.csrf');
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
    echo '<!DOCTYPE html><html lang="' . h(i18n_html_lang()) . '"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Sicherheitsprüfung</title></head><body><p>'
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
    return fmt_date($timestamp, true);
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
    $group_name = isset($group['name']) ? $group['name'] : '';

    foreach ($participants as $participant) {
        $email = reveal_participant_email($participant);
        if ($email === '') {
            $skipped++;
            continue;
        }

        $lang = i18n_row_language($participant);
        if ($lang === null) {
            $lang = 'de';
        }
        $payload = array(
            'name' => isset($participant['name']) ? $participant['name'] : '',
            'group_name' => $group_name,
            'pairs' => $pairs,
            'language' => $lang,
        );
        $subject = reveal_email_subject($lang);
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
        return array('type' => 'error', 'text' => t('reveal.flash.incomplete'));
    }
    if ($status === 'no_recipients') {
        return array('type' => 'error', 'text' => t('reveal.flash.no_recipients'));
    }
    if ($status === 'failed') {
        return array(
            'type' => 'error',
            'text' => delivery_flash_text('reveal.flash.sent', 'reveal.flash.none', $status, $sent, $failed, $skipped),
        );
    }

    return array(
        'type' => ($failed > 0) ? 'warning' : 'success',
        'text' => delivery_flash_text('reveal.flash.sent', 'reveal.flash.none', $status, $sent, $failed, $skipped),
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
        return t('error.too_long', array('{label}' => $label, '{max}' => (string) $max));
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

require_once __DIR__ . '/mail_messages.php';
require_once __DIR__ . '/group_tools.php';

?>
