<?php
/**
 * Hilfen für Checkliste, Ausschlüsse, Import, Erinnerung, Kalender und Wunschlisten-Mail.
 * Wird von includes/functions.php geladen.
 */

function normalize_wishlist_text($value) {
    $text = str_replace(array("\r\n", "\r"), "\n", (string) $value);
    return trim($text);
}

function wishlist_has_real_change($old_wishlist, $new_wishlist) {
    return normalize_wishlist_text($old_wishlist) !== normalize_wishlist_text($new_wishlist);
}

/**
 * Zähler unter logs/rate-limit, getrennt vom API-Zähler durch den Bucket.
 * $fail_open: bei nicht beschreibbarem Verzeichnis die Aktion zulassen.
 *
 * @return bool true, wenn der Versuch noch im Limit liegt
 */
function consume_rate_limit($bucket, $identity, $max_attempts, $window_seconds, $fail_open = true) {
    $bucket = (string) $bucket;
    $identity = (string) $identity;
    $max_attempts = (int) $max_attempts;
    $window_seconds = (int) $window_seconds;
    if ($bucket === '' || $identity === '' || $max_attempts < 1 || $window_seconds < 1) {
        return false;
    }

    $dir = dirname(__DIR__) . '/logs/rate-limit';
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        error_log('Rate-Limit-Verzeichnis ist nicht beschreibbar: ' . $dir);
        return (bool) $fail_open;
    }

    $file = $dir . '/' . hash('sha256', $bucket . '|' . $identity) . '.json';
    $handle = @fopen($file, 'c+');
    if ($handle === false) {
        error_log('Rate-Limit-Datei konnte nicht geöffnet werden: ' . $file);
        return (bool) $fail_open;
    }

    $allowed = (bool) $fail_open;
    try {
        if (!flock($handle, LOCK_EX)) {
            error_log('Rate-Limit-Datei konnte nicht gesperrt werden: ' . $file);
            return (bool) $fail_open;
        }

        $raw = stream_get_contents($handle);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        $current_time = time();

        if (!is_array($data) || !isset($data['start_time'], $data['count'])
            || ($current_time - (int) $data['start_time']) > $window_seconds) {
            $data = array('start_time' => $current_time, 'count' => 1);
        } else {
            $data['count'] = (int) $data['count'] + 1;
        }

        $allowed = $data['count'] <= $max_attempts;

        rewind($handle);
        ftruncate($handle, 0);
        fwrite($handle, json_encode($data));
        fflush($handle);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    return $allowed;
}

function admin_link_recovery_allowed($identity) {
    return consume_rate_limit('admin-link', (string) $identity, 5, 900, true);
}

/**
 * @return string sent|failed|rate_limited|unchanged|not_drawn|no_giver|no_email
 */
function dispatch_wishlist_giver_mail($pdo, $participant, $is_drawn, $old_wishlist, $new_wishlist, $sender = null) {
    if (!wishlist_has_real_change($old_wishlist, $new_wishlist)) {
        return 'unchanged';
    }
    if (!$is_drawn) {
        return 'not_drawn';
    }
    if (!is_array($participant) || empty($participant['id']) || empty($participant['group_id'])) {
        return 'no_giver';
    }

    $stmt = $pdo->prepare('SELECT * FROM `participants` WHERE `assigned_to` = ? AND `group_id` = ?');
    $stmt->execute(array($participant['id'], $participant['group_id']));
    $giver = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$giver) {
        return 'no_giver';
    }

    $email = reveal_participant_email($giver);
    if ($email === '') {
        return 'no_email';
    }

    $allowed = consume_rate_limit(
        'wishlist-notify',
        'participant:' . (int) $participant['id'],
        1,
        1800,
        true
    );
    if (!$allowed) {
        return 'rate_limited';
    }

    $subject = 'Wunschliste aktualisiert';
    $html = create_wishlist_update_email(
        isset($giver['name']) ? $giver['name'] : '',
        isset($participant['name']) ? $participant['name'] : '',
        normalize_wishlist_text($new_wishlist)
    );

    if ($sender === null) {
        $sender = function ($to, $subject, $html) {
            return send_email($to, $subject, $html, true);
        };
    }

    $ok = false;
    try {
        $ok = (bool) call_user_func($sender, $email, $subject, $html);
    } catch (Exception $e) {
        error_log('Wunschliste Versandfehler: ' . $e->getMessage());
    }

    if (!$ok) {
        error_log('Wunschliste konnte nicht an ' . $email . ' gesendet werden.');
        return 'failed';
    }

    return 'sent';
}

function wishlist_update_notice($status) {
    if ($status === 'sent') {
        return 'Deine Wunschliste wurde gespeichert. Dein Wichtel wurde per E-Mail informiert.';
    }
    if ($status === 'unchanged') {
        return 'Deine Wunschliste ist unverändert.';
    }
    if ($status === 'not_drawn') {
        return 'Deine Wunschliste wurde erfolgreich gespeichert.';
    }
    if ($status === 'rate_limited') {
        return 'Deine Wunschliste wurde gespeichert. Eine weitere E-Mail an deinen Wichtel folgt erst nach einer Pause, damit niemand zu viele Mails bekommt.';
    }
    if ($status === 'no_email' || $status === 'no_giver') {
        return 'Deine Wunschliste wurde gespeichert. Dein Wichtel hat keine E-Mail-Adresse, deshalb ging keine Benachrichtigung raus.';
    }
    return 'Deine Wunschliste wurde gespeichert. Die Benachrichtigung an deinen Wichtel konnte nicht gesendet werden.';
}

/**
 * @return array participants, with_email, with_wishlist, draw (not_ready|ready|done), reveal (open|sent)
 */
function admin_group_checklist($participants, $group) {
    if (!is_array($participants)) {
        $participants = array();
    }
    if (!is_array($group)) {
        $group = array();
    }

    $with_email = 0;
    $with_wishlist = 0;
    foreach ($participants as $participant) {
        if (!is_array($participant)) {
            continue;
        }
        if (reveal_participant_email($participant) !== '') {
            $with_email++;
        }
        $wishlist = isset($participant['wishlist']) ? $participant['wishlist'] : '';
        if (normalize_wishlist_text($wishlist) !== '') {
            $with_wishlist++;
        }
    }

    $total = count($participants);
    if (!empty($group['is_drawn'])) {
        $draw = 'done';
    } elseif ($total >= 2) {
        $draw = 'ready';
    } else {
        $draw = 'not_ready';
    }

    return array(
        'participants' => $total,
        'with_email' => $with_email,
        'with_wishlist' => $with_wishlist,
        'draw' => $draw,
        'reveal' => !empty($group['reveal_sent_at']) ? 'sent' : 'open',
    );
}

function admin_checklist_draw_label($draw) {
    if ($draw === 'done') {
        return 'Auslosung erledigt';
    }
    if ($draw === 'ready') {
        return 'Auslosung bereit';
    }
    return 'Auslosung noch nicht bereit';
}

function admin_checklist_reveal_label($reveal) {
    return ($reveal === 'sent') ? 'Auflösung gesendet' : 'Auflösung offen';
}

/**
 * @param string $forward added|duplicate|invalid
 * @param string|null $reverse added|duplicate|invalid, null = nur eine Richtung
 * @return array type, text
 */
function exclusion_save_message($forward, $reverse = null) {
    if ($forward === 'invalid' || $reverse === 'invalid') {
        return array('type' => 'error', 'text' => 'Ungültige Auswahl.');
    }
    if ($reverse === null) {
        if ($forward === 'added') {
            return array('type' => 'success', 'text' => 'Ausschluss erfolgreich hinzugefügt.');
        }
        return array('type' => 'error', 'text' => 'Dieser Ausschluss existiert bereits.');
    }

    $added = (($forward === 'added') ? 1 : 0) + (($reverse === 'added') ? 1 : 0);
    $duplicate = (($forward === 'duplicate') ? 1 : 0) + (($reverse === 'duplicate') ? 1 : 0);
    if ($added === 2) {
        return array('type' => 'success', 'text' => 'Ausschluss in beide Richtungen hinzugefügt.');
    }
    if ($added === 1 && $duplicate === 1) {
        return array(
            'type' => 'success',
            'text' => 'Eine Richtung war schon gespeichert. Die Gegenrichtung wurde ergänzt.',
        );
    }
    if ($duplicate === 2) {
        return array('type' => 'error', 'text' => 'Dieser Ausschluss existiert bereits.');
    }
    return array('type' => 'error', 'text' => 'Fehler beim Hinzufügen des Ausschlusses.');
}

/**
 * @return string added|duplicate|invalid
 */
function insert_group_exclusion($pdo, $group_id, $participant_id, $excluded_id) {
    $participant_id = (int) $participant_id;
    $excluded_id = (int) $excluded_id;
    $group_id = (int) $group_id;
    if ($participant_id < 1 || $excluded_id < 1 || $participant_id === $excluded_id || $group_id < 1) {
        return 'invalid';
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO `exclusions` (`group_id`, `participant_id`, `excluded_participant_id`) VALUES (?, ?, ?)');
        $stmt->execute(array($group_id, $participant_id, $excluded_id));
        return 'added';
    } catch (PDOException $e) {
        $code = (string) $e->getCode();
        if ($code === '23000' || $code === '19') {
            return 'duplicate';
        }
        throw $e;
    }
}

function bulk_name_email_result($name, $email) {
    $name = trim((string) $name);
    if ($name === '') {
        return array('error' => 'Name fehlt.');
    }
    if (preg_match('/[\x00-\x1F\x7F]/', $name)) {
        return array('error' => 'Der Name enthält ungültige Zeichen.');
    }
    if (strlen($name) > 255) {
        return array('error' => 'Der Name ist länger als 255 Zeichen.');
    }
    if ($email !== null) {
        $email = trim((string) $email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            return array('error' => 'Ungültige E-Mail-Adresse.');
        }
    }
    return array('name' => $name, 'email' => $email);
}

function parse_bulk_participant_line($line) {
    $line = trim((string) $line);
    if (preg_match('/^(.+?)\s*<([^>]+)>\s*$/', $line, $matches)) {
        $email = trim($matches[2]);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return bulk_name_email_result(trim($matches[1]), $email);
        }
    }

    $separators = array("\t", ';', '|', ',');
    foreach ($separators as $separator) {
        $position = strrpos($line, $separator);
        if ($position === false) {
            continue;
        }
        $maybe_email = trim(substr($line, $position + strlen($separator)));
        $maybe_name = trim(substr($line, 0, $position));
        if ($maybe_name !== '' && filter_var($maybe_email, FILTER_VALIDATE_EMAIL)) {
            return bulk_name_email_result($maybe_name, $maybe_email);
        }
    }

    if (filter_var($line, FILTER_VALIDATE_EMAIL)) {
        return array('error' => 'Name fehlt, es steht nur eine E-Mail-Adresse in der Zeile.');
    }

    return bulk_name_email_result($line, null);
}

/**
 * @return array rows, errors
 */
function parse_bulk_participants($raw, $max_rows = 100) {
    $raw = str_replace(array("\r\n", "\r"), "\n", (string) $raw);
    $lines = explode("\n", $raw);
    $rows = array();
    $errors = array();
    $seen_emails = array();
    $nonempty = 0;
    $max_rows = (int) $max_rows;
    if ($max_rows < 1) {
        $max_rows = 1;
    }

    foreach ($lines as $index => $line) {
        $trimmed = trim($line);
        if ($trimmed === '') {
            continue;
        }
        $nonempty++;
        $line_no = $index + 1;
        if ($nonempty > $max_rows) {
            $errors[] = 'Höchstens ' . $max_rows . ' Teilnehmer auf einmal.';
            break;
        }

        $parsed = parse_bulk_participant_line($trimmed);
        if (isset($parsed['error'])) {
            $errors[] = 'Zeile ' . $line_no . ': ' . $parsed['error'];
            continue;
        }

        $email_key = ($parsed['email'] !== null) ? strtolower($parsed['email']) : '';
        if ($email_key !== '' && isset($seen_emails[$email_key])) {
            $errors[] = 'Zeile ' . $line_no . ': Die E-Mail-Adresse kommt in der Liste mehrfach vor.';
            continue;
        }
        if ($email_key !== '') {
            $seen_emails[$email_key] = true;
        }

        $rows[] = array(
            'name' => $parsed['name'],
            'email' => $parsed['email'],
            'line' => $line_no,
        );
    }

    return array('rows' => $rows, 'errors' => $errors);
}

function gift_days_until($gift_date, $today = null) {
    if (!is_string($gift_date)) {
        return null;
    }
    $gift_date = trim($gift_date);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $gift_date)) {
        return null;
    }
    if ($today === null) {
        $today = date('Y-m-d');
    }
    if (!is_string($today) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $today)) {
        return null;
    }

    $gift = DateTime::createFromFormat('!Y-m-d', $gift_date);
    $now = DateTime::createFromFormat('!Y-m-d', $today);
    if (!$gift || !$now) {
        return null;
    }
    if ($gift->format('Y-m-d') !== $gift_date || $now->format('Y-m-d') !== $today) {
        return null;
    }

    return (int) $now->diff($gift)->format('%r%a');
}

function gift_reminder_sentence($days) {
    if ($days === null) {
        return '';
    }
    $days = (int) $days;
    if ($days > 1) {
        return 'In ' . $days . ' Tagen ist die Geschenkübergabe.';
    }
    if ($days === 1) {
        return 'Morgen ist die Geschenkübergabe.';
    }
    if ($days === 0) {
        return 'Heute ist die Geschenkübergabe.';
    }
    if ($days === -1) {
        return 'Die Geschenkübergabe war gestern.';
    }
    return 'Die Geschenkübergabe war vor ' . abs($days) . ' Tagen.';
}

function create_gift_reminder_email($data) {
    $name = isset($data['name']) ? $data['name'] : '';
    $group_name = isset($data['group_name']) ? $data['group_name'] : '';
    $sentence = isset($data['sentence']) ? $data['sentence'] : '';
    $gift_date = isset($data['gift_date']) ? $data['gift_date'] : '';
    $participant_link = isset($data['participant_link']) ? $data['participant_link'] : '';
    $ics_url = isset($data['ics_url']) ? $data['ics_url'] : '';

    $link_html = '';
    if ($participant_link !== '') {
        $safe_link = htmlspecialchars($participant_link, ENT_QUOTES, 'UTF-8');
        $link_html = '
                            <p style="margin: 0 0 16px 0;">
                                <a href="' . $safe_link . '" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #2a9d8f, #264653); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 14px;">Zum Teilnehmerbereich</a>
                            </p>';
    }

    $body = '
                            <p style="margin: 0 0 20px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Hallo <strong style="color: #e63946;">' . htmlspecialchars($name) . '</strong>,
                            </p>
                            <p style="margin: 0 0 16px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                ' . htmlspecialchars($sentence) . '
                            </p>
                            <p style="margin: 0 0 16px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Gruppe: <strong>' . htmlspecialchars($group_name) . '</strong><br>
                                Datum: <strong>' . htmlspecialchars($gift_date) . '</strong>
                            </p>
                            ' . $link_html . '
                            ' . email_calendar_paragraph($ics_url) . '
                            <p style="margin: 16px 0 0 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Viel Spaß beim Wichteln!
                            </p>';

    return render_email_template('Erinnerung zur Geschenkübergabe', $sentence, $body);
}

function create_gift_reminder_plain_text($data) {
    $lines = array();
    $lines[] = 'Hallo ' . (isset($data['name']) ? $data['name'] : '') . ',';
    $lines[] = '';
    $lines[] = isset($data['sentence']) ? $data['sentence'] : '';
    $lines[] = 'Gruppe: ' . (isset($data['group_name']) ? $data['group_name'] : '');
    $lines[] = 'Datum: ' . (isset($data['gift_date']) ? $data['gift_date'] : '');
    if (!empty($data['participant_link'])) {
        $lines[] = 'Teilnehmerbereich: ' . $data['participant_link'];
    }
    if (!empty($data['ics_url'])) {
        $lines[] = 'In Kalender speichern: ' . $data['ics_url'];
    }
    return implode("\n", $lines);
}

/**
 * @param callable|null $sender function($to, $subject, $html, $plain): bool
 * @return array status, sent, failed, skipped, days
 */
function deliver_gift_reminders($participants, $group, $sender = null, $today = null) {
    if (!is_array($participants)) {
        $participants = array();
    }
    if (!is_array($group)) {
        $group = array();
    }

    $gift_raw = isset($group['gift_exchange_date']) ? (string) $group['gift_exchange_date'] : '';
    $days = gift_days_until($gift_raw, $today);
    $empty = array(
        'status' => 'no_date',
        'sent' => 0,
        'failed' => 0,
        'skipped' => 0,
        'days' => $days,
    );
    if ($days === null) {
        return $empty;
    }

    if ($sender === null) {
        $sender = function ($to, $subject, $html, $plain) {
            return send_email($to, $subject, $html, true, $plain);
        };
    }

    $sentence = gift_reminder_sentence($days);
    $gift_label = date('d.m.Y', strtotime($gift_raw));
    $group_name = isset($group['name']) ? $group['name'] : '';
    $subject = rtrim($sentence, '.');
    if ($group_name !== '') {
        $subject .= ' – ' . str_replace(array("\r", "\n"), ' ', $group_name);
    }

    $sent = 0;
    $failed = 0;
    $skipped = 0;

    foreach ($participants as $participant) {
        if (!is_array($participant)) {
            $skipped++;
            continue;
        }
        $email = reveal_participant_email($participant);
        if ($email === '') {
            $skipped++;
            continue;
        }

        $token = isset($participant['participant_token']) ? (string) $participant['participant_token'] : '';
        $payload = array(
            'name' => isset($participant['name']) ? $participant['name'] : '',
            'group_name' => $group_name,
            'sentence' => $sentence,
            'gift_date' => $gift_label,
            'participant_link' => ($token !== '') ? get_display_url('/participant.php?token=' . rawurlencode($token)) : '',
            'ics_url' => ($token !== '') ? gift_ics_url('teilnehmer', $token) : '',
        );

        $ok = false;
        try {
            $ok = (bool) call_user_func(
                $sender,
                $email,
                $subject,
                create_gift_reminder_email($payload),
                create_gift_reminder_plain_text($payload)
            );
        } catch (Exception $e) {
            error_log('Erinnerung Versandfehler: ' . $e->getMessage());
        }

        if ($ok) {
            $sent++;
        } else {
            $failed++;
            error_log('Erinnerung konnte nicht an ' . $email . ' gesendet werden.');
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
        'days' => $days,
    );
}

function gift_reminder_result_message($result) {
    $status = isset($result['status']) ? $result['status'] : '';
    $sent = isset($result['sent']) ? (int) $result['sent'] : 0;
    $failed = isset($result['failed']) ? (int) $result['failed'] : 0;
    $skipped = isset($result['skipped']) ? (int) $result['skipped'] : 0;

    if ($status === 'no_date') {
        return array(
            'type' => 'error',
            'text' => 'Die Erinnerung kann nicht gesendet werden. Lege zuerst ein Datum der Geschenkübergabe fest.',
        );
    }
    if ($status === 'no_recipients') {
        return array(
            'type' => 'error',
            'text' => 'Die Erinnerung wurde an niemanden gesendet. Kein Teilnehmer hat eine gültige E-Mail-Adresse.',
        );
    }
    if ($status === 'failed') {
        $failed_text = ($failed === 1) ? '1 E-Mail ist fehlgeschlagen.' : ($failed . ' E-Mails sind fehlgeschlagen.');
        return array(
            'type' => 'error',
            'text' => 'Die Erinnerung wurde an niemanden gesendet. ' . $failed_text,
        );
    }

    $sent_text = ($sent === 1)
        ? 'Die Erinnerung wurde an 1 Teilnehmer gesendet.'
        : ('Die Erinnerung wurde an ' . $sent . ' Teilnehmer gesendet.');

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

function create_admin_recovery_email($groups) {
    $items = '';
    if (!is_array($groups)) {
        $groups = array();
    }
    foreach ($groups as $group) {
        if (!is_array($group)) {
            continue;
        }
        $name = htmlspecialchars(isset($group['name']) ? $group['name'] : '', ENT_QUOTES, 'UTF-8');
        $link = htmlspecialchars(isset($group['admin_link']) ? $group['admin_link'] : '', ENT_QUOTES, 'UTF-8');
        $items .= '
                            <p style="margin: 0 0 16px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                <strong>' . $name . '</strong><br>
                                <a href="' . $link . '">' . $link . '</a>
                            </p>';
    }

    $body = '
                            <p style="margin: 0 0 20px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Hallo,
                            </p>
                            <p style="margin: 0 0 20px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                hier sind die Admin-Links zu den Gruppen, die mit dieser E-Mail-Adresse angelegt wurden. Speichere sie als Lesezeichen.
                            </p>
                            ' . $items . '
                            <p style="margin: 16px 0 0 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">
                                Viel Spaß beim Wichteln!
                            </p>';

    return render_email_template('Admin-Link', 'Dein Admin-Link', $body);
}

function create_admin_recovery_plain_text($groups) {
    $lines = array(
        'Hallo,',
        '',
        'hier sind die Admin-Links zu den Gruppen, die mit dieser E-Mail-Adresse angelegt wurden:',
        '',
    );
    if (!is_array($groups)) {
        $groups = array();
    }
    foreach ($groups as $group) {
        if (!is_array($group)) {
            continue;
        }
        $lines[] = isset($group['name']) ? $group['name'] : '';
        $lines[] = isset($group['admin_link']) ? $group['admin_link'] : '';
        $lines[] = '';
    }
    return implode("\n", $lines);
}

function ics_escape($value) {
    $value = str_replace(array("\\", "\r\n", "\n", "\r"), array("\\\\", "\\n", "\\n", ''), (string) $value);
    $value = str_replace(array(';', ','), array('\\;', '\\,'), $value);
    return $value;
}

function ics_fold_line($line) {
    if (strlen($line) <= 75) {
        return $line;
    }

    $result = '';
    $limit = 75;
    $offset = 0;
    $length = strlen($line);
    $first = true;

    while ($offset < $length) {
        $take = $limit;
        if ($offset + $take > $length) {
            $take = $length - $offset;
        } else {
            while ($take > 0 && ($offset + $take) < $length && (ord($line[$offset + $take]) & 0xC0) === 0x80) {
                $take--;
            }
        }
        if ($take < 1) {
            $take = 1;
        }
        $chunk = substr($line, $offset, $take);
        $result .= ($first ? '' : "\r\n ") . $chunk;
        $first = false;
        $offset += $take;
        $limit = 74;
    }

    return $result;
}

function build_gift_ics($group, $dtstamp = null) {
    if (!is_array($group)) {
        return '';
    }
    $gift_date = isset($group['gift_exchange_date']) ? (string) $group['gift_exchange_date'] : '';
    if (gift_days_until($gift_date, $gift_date) === null) {
        return '';
    }

    $start = DateTime::createFromFormat('!Y-m-d', $gift_date);
    $end = clone $start;
    $end->modify('+1 day');

    if ($dtstamp === null) {
        $dtstamp = gmdate('Ymd\THis\Z');
    }
    $dtstamp = preg_replace('/[^0-9TZ]/', '', (string) $dtstamp);
    if ($dtstamp === '') {
        $dtstamp = gmdate('Ymd\THis\Z');
    }

    $group_id = isset($group['id']) ? (int) $group['id'] : 0;
    $uid = 'wichteln-' . ($group_id > 0 ? $group_id : 'datum') . '-' . $start->format('Ymd') . '@wichtlae.ch';
    $name = isset($group['name']) ? (string) $group['name'] : 'Wichteln';
    $summary = 'Geschenkübergabe: ' . $name;
    $description = 'Geschenkübergabe der Gruppe ' . $name . ' am ' . $start->format('d.m.Y') . '.';

    $lines = array(
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//wichtlae.ch//Wichteln//DE',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'UID:' . $uid,
        'DTSTAMP:' . $dtstamp,
        'DTSTART;VALUE=DATE:' . $start->format('Ymd'),
        'DTEND;VALUE=DATE:' . $end->format('Ymd'),
        'SUMMARY:' . ics_escape($summary),
        'DESCRIPTION:' . ics_escape($description),
        'END:VEVENT',
        'END:VCALENDAR',
    );

    $folded = array();
    foreach ($lines as $line) {
        $folded[] = ics_fold_line($line);
    }

    return implode("\r\n", $folded) . "\r\n";
}

function gift_ics_url($rolle, $token) {
    if ($rolle !== 'admin' && $rolle !== 'teilnehmer') {
        return '';
    }
    if (!is_string($token) || $token === '' || !preg_match('/^[a-f0-9]{16,128}$/i', $token)) {
        return '';
    }
    return get_display_url('/kalender.php?rolle=' . rawurlencode($rolle) . '&token=' . rawurlencode($token));
}

function public_clean_slugs() {
    return array(
        'faq',
        'was-ist-wichteln',
        'impressum',
        'datenschutz',
        'wichtel-ideen',
        'firmenwichteln-tipps',
        'ueber-uns',
        'create_group',
        'admin-link',
    );
}
