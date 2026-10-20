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
 * $fail_open bleibt nur für Tests. Produktiv ist der Standard geschlossen:
 * ohne beschreibbaren Zähler wird die Aktion abgelehnt.
 *
 * @return bool true, wenn der Versuch noch im Limit liegt
 */
function consume_rate_limit($bucket, $identity, $max_attempts, $window_seconds, $fail_open = false) {
    $bucket = (string) $bucket;
    $identity = (string) $identity;
    $max_attempts = (int) $max_attempts;
    $window_seconds = (int) $window_seconds;
    if ($bucket === '' || $identity === '' || $max_attempts < 1 || $window_seconds < 1) {
        return false;
    }

    $dir = rate_limit_directory();
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

function rate_limit_directory() {
    return dirname(__DIR__) . '/logs/rate-limit';
}

/**
 * @return array directory, exists, writable
 */
function rate_limit_storage_status() {
    $dir = rate_limit_directory();
    $exists = is_dir($dir);
    if ($exists) {
        $writable = is_writable($dir);
    } else {
        $parent = dirname($dir);
        $writable = is_dir($parent) && is_writable($parent);
    }
    return array(
        'directory' => $dir,
        'exists' => $exists,
        'writable' => $writable,
    );
}

function request_client_ip() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : 'unknown';
    if ($ip === '' || strlen($ip) > 64) {
        return 'unknown';
    }
    return $ip;
}

function create_group_attempt_allowed($ip) {
    return consume_rate_limit('create-group', (string) $ip, 5, 900, false);
}

function registration_attempt_allowed($invite_token, $ip) {
    $by_token = consume_rate_limit('register-token', (string) $invite_token, 10, 3600, false);
    $by_ip = consume_rate_limit('register-ip', (string) $ip, 20, 3600, false);
    return $by_token && $by_ip;
}

function bulk_import_attempt_allowed($admin_token, $ip) {
    $by_token = consume_rate_limit('bulk-import-token', (string) $admin_token, 5, 3600, false);
    $by_ip = consume_rate_limit('bulk-import-ip', (string) $ip, 10, 3600, false);
    return $by_token && $by_ip;
}

function admin_link_recovery_allowed($identity) {
    return consume_rate_limit('admin-link', (string) $identity, 5, 900, false);
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
        false
    );
    if (!$allowed) {
        return 'rate_limited';
    }

    $lang = i18n_row_language($giver);
    if ($lang === null) {
        $lang = 'de';
    }
    $wishlist_text = normalize_wishlist_text($new_wishlist);
    $giver_name = isset($giver['name']) ? $giver['name'] : '';
    $updater_name = isset($participant['name']) ? $participant['name'] : '';
    $subject = wishlist_email_subject($lang);
    $html = create_wishlist_update_email($giver_name, $updater_name, $wishlist_text, $lang);
    $plain = create_wishlist_plain_text($giver_name, $updater_name, $wishlist_text, $lang);

    if ($sender === null) {
        $sender = function ($to, $subject, $html, $plain = null) {
            return send_email($to, $subject, $html, true, $plain);
        };
    }

    $ok = false;
    try {
        $ok = (bool) call_user_func($sender, $email, $subject, $html, $plain);
    } catch (Exception $e) {
        error_log('Wunschliste Versandfehler: ' . $e->getMessage());
    }

    if (!$ok) {
        error_log('Wunschliste konnte nicht an ' . mask_email($email) . ' gesendet werden.');
        return 'failed';
    }

    return 'sent';
}

function wishlist_update_notice($status) {
    if ($status === 'sent') {
        return t('wishlist.notice.sent');
    }
    if ($status === 'unchanged') {
        return t('wishlist.notice.unchanged');
    }
    if ($status === 'not_drawn') {
        return t('wishlist.notice.not_drawn');
    }
    if ($status === 'rate_limited') {
        return t('wishlist.notice.rate_limited');
    }
    if ($status === 'no_email' || $status === 'no_giver') {
        return t('wishlist.notice.no_email');
    }
    return t('wishlist.notice.failed');
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
        return t('checklist.draw.done');
    }
    if ($draw === 'ready') {
        return t('checklist.draw.ready');
    }
    return t('checklist.draw.wait');
}

function admin_checklist_reveal_label($reveal) {
    return ($reveal === 'sent') ? t('checklist.reveal.sent') : t('checklist.reveal.open');
}

/**
 * @param string $forward added|duplicate|invalid
 * @param string|null $reverse added|duplicate|invalid, null = nur eine Richtung
 * @return array type, text
 */
function exclusion_save_message($forward, $reverse = null) {
    if ($forward === 'invalid' || $reverse === 'invalid') {
        return array('type' => 'error', 'text' => t('admin.exclusion_invalid'));
    }
    if ($reverse === null) {
        if ($forward === 'added') {
            return array('type' => 'success', 'text' => t('admin.exclusion_added'));
        }
        return array('type' => 'error', 'text' => t('admin.exclusion_exists'));
    }

    $added = (($forward === 'added') ? 1 : 0) + (($reverse === 'added') ? 1 : 0);
    $duplicate = (($forward === 'duplicate') ? 1 : 0) + (($reverse === 'duplicate') ? 1 : 0);
    if ($added === 2) {
        return array('type' => 'success', 'text' => t('admin.exclusion_both'));
    }
    if ($added === 1 && $duplicate === 1) {
        return array(
            'type' => 'success',
            'text' => t('admin.exclusion_one_new'),
        );
    }
    if ($duplicate === 2) {
        return array('type' => 'error', 'text' => t('admin.exclusion_exists'));
    }
    return array('type' => 'error', 'text' => t('admin.exclusion_fail'));
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
        return array('error' => t('bulk.name_missing'));
    }
    if (preg_match('/[\x00-\x1F\x7F]/', $name)) {
        return array('error' => t('bulk.name_chars'));
    }
    if (strlen($name) > 255) {
        return array('error' => t('bulk.name_long'));
    }
    if ($email !== null) {
        $email = trim((string) $email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
            return array('error' => t('bulk.email_invalid'));
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
        return array('error' => t('bulk.email_only'));
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
            $errors[] = t('bulk.max', array('{count}' => (string) $max_rows));
            break;
        }

        $parsed = parse_bulk_participant_line($trimmed);
        if (isset($parsed['error'])) {
            $errors[] = t('bulk.line', array('{line}' => (string) $line_no, '{error}' => $parsed['error']));
            continue;
        }

        $email_key = ($parsed['email'] !== null) ? strtolower($parsed['email']) : '';
        if ($email_key !== '' && isset($seen_emails[$email_key])) {
            $errors[] = t('bulk.line', array('{line}' => (string) $line_no, '{error}' => t('bulk.duplicate')));
            continue;
        }
        if ($email_key !== '') {
            $seen_emails[$email_key] = true;
        }

        $name_error = limit_text_error($parsed['name'], 255, 'Name');
        $email_error = ($parsed['email'] !== null) ? limit_text_error($parsed['email'], 255, 'E-Mail') : '';
        if ($name_error !== '' || $email_error !== '') {
            $errors[] = 'Zeile ' . $line_no . ': ' . trim($name_error . ' ' . $email_error);
            continue;
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

    $group_name = isset($group['name']) ? $group['name'] : '';

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

        $lang = i18n_row_language($participant);
        if ($lang === null) {
            $lang = 'de';
        }
        $sentence = gift_reminder_sentence($days, $lang);
        $gift_label = i18n_with_lang($lang, function () use ($gift_raw) {
            return fmt_date($gift_raw);
        });
        $subject = reminder_email_subject($sentence, $group_name, $lang);
        $token = isset($participant['participant_token']) ? (string) $participant['participant_token'] : '';
        $payload = array(
            'name' => isset($participant['name']) ? $participant['name'] : '',
            'group_name' => $group_name,
            'sentence' => $sentence,
            'gift_date' => $gift_label,
            'participant_link' => ($token !== '') ? get_display_url('/participant.php?token=' . rawurlencode($token)) : '',
            'ics_url' => gift_ics_url($group, $lang),
            'language' => $lang,
            'affiliate' => affiliate_of_show_for_group($group),
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
            error_log('Erinnerung konnte nicht an ' . mask_email($email) . ' gesendet werden.');
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
        return array('type' => 'error', 'text' => t('reminder.need_date'));
    }
    if ($status === 'no_recipients') {
        return array('type' => 'error', 'text' => t('reminder.no_recipients'));
    }
    if ($status === 'failed') {
        return array(
            'type' => 'error',
            'text' => delivery_flash_text('reminder.sent', 'reminder.none', $status, $sent, $failed, $skipped),
        );
    }

    return array(
        'type' => ($failed > 0) ? 'warning' : 'success',
        'text' => delivery_flash_text('reminder.sent', 'reminder.none', $status, $sent, $failed, $skipped),
    );
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

function build_gift_ics($group, $dtstamp = null, $lang = null) {
    if (!is_array($group)) {
        return '';
    }
    if ($lang === null) {
        $from_row = i18n_row_language($group);
        $lang = ($from_row === null) ? 'de' : $from_row;
    }
    return i18n_with_lang($lang, function () use ($group, $dtstamp) {
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
    $name = isset($group['name']) ? (string) $group['name'] : t('mail.brand');
    $summary = t('ics.summary', array('{name}' => $name));
    $description = t('ics.description', array('{name}' => $name, '{date}' => fmt_date($gift_date)));

    $lines = array(
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:' . t('ics.prodid'),
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
    });
}

function ics_signing_key() {
    if (defined('ICS_SIGNING_KEY') && is_string(ICS_SIGNING_KEY) && ICS_SIGNING_KEY !== '') {
        return ICS_SIGNING_KEY;
    }
    $material = 'ics-link';
    if (defined('MASTER_ADMIN_TOKEN') && is_string(MASTER_ADMIN_TOKEN) && MASTER_ADMIN_TOKEN !== '') {
        $material .= '|' . MASTER_ADMIN_TOKEN;
    }
    return hash('sha256', $material);
}

function gift_ics_signature($group_id, $gift_date) {
    $payload = (int) $group_id . '|' . (string) $gift_date;
    return hash_hmac('sha256', $payload, ics_signing_key());
}

function gift_ics_signature_valid($group_id, $gift_date, $signature) {
    if (!is_string($signature) || preg_match('/^[a-f0-9]{64}$/', $signature) !== 1) {
        return false;
    }
    if ((int) $group_id < 1 || preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $gift_date) !== 1) {
        return false;
    }
    return hash_equals(gift_ics_signature($group_id, $gift_date), $signature);
}

/**
 * Kalenderlink, der nur den ICS-Eintrag freigibt.
 * Erwartet id und gift_exchange_date der Gruppe.
 */
function gift_ics_url($group, $lang = null) {
    if (!is_array($group)) {
        return '';
    }
    $group_id = isset($group['id']) ? (int) $group['id'] : 0;
    $date = isset($group['gift_exchange_date']) ? (string) $group['gift_exchange_date'] : '';
    if ($group_id < 1 || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
        return '';
    }
    if ($lang === null) {
        $from_row = i18n_row_language($group);
        $lang = ($from_row === null) ? i18n_current() : $from_row;
    }
    $lang = i18n_allow($lang);
    $signature = gift_ics_signature($group_id, $date);
    return get_display_url('/kalender.php?g=' . $group_id . '&d=' . rawurlencode($date) . '&lang=' . $lang . '&s=' . rawurlencode($signature));
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
        'unterstuetzen',
        'firmen-premium',
        'create_group',
        'admin-link',
    );
}
