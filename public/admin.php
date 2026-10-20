<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/templates/public_shell.php';

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
    header('Referrer-Policy: no-referrer');
}

start_secure_session();

$admin_token = isset($_GET['token']) ? (string) $_GET['token'] : '';
$pdo = db_connect();

$stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `admin_token` = ?");
$stmt->execute(array($admin_token));
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    i18n_boot_token(null);
    die(h(t('error.token')));
}

i18n_apply_explicit_choice($pdo, 'groups', (int) $group['id']);
i18n_boot_token(i18n_row_language($group));

if (isset($_GET['email_error'])) {
    $email_error = t('admin.email_error_banner');
}

$stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `group_id` = ?");
$stmt->execute(array($group['id']));
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

$csrf_error = null;
$csrf_ok = true;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    $csrf_ok = false;
    $csrf_error = csrf_failure_message();
}

if ($csrf_ok && premium_feature_enabled() && isset($_POST['premium_action'])) {
    $premium_handled = premium_admin_handle($pdo, $group, $_POST, $_FILES, null, request_client_ip());
    if ($premium_handled['redirect'] !== '') {
        if ($premium_handled['notice']['text'] !== '') {
            $_SESSION['admin_flash'] = $premium_handled['notice'];
        }
        header('Location: ' . $premium_handled['redirect']);
        exit();
    }
    if (!empty($premium_handled['reload'])) {
        $_SESSION['admin_flash'] = $premium_handled['notice'];
        header('Location: /admin.php?token=' . urlencode($admin_token) . '#premium');
        exit();
    }
    if ($premium_handled['notice']['text'] !== '') {
        $_SESSION['admin_flash'] = $premium_handled['notice'];
    }
}

if ($csrf_ok && isset($_POST['reset_draw'])) {
    $clear_reveal = mysql_column_exists($pdo, 'groups', 'reveal_sent_at')
        ? ', `reveal_sent_at` = NULL'
        : '';
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE `groups` SET `is_drawn` = 0" . $clear_reveal . " WHERE `id` = ?");
        $stmt->execute(array($group['id']));
        $stmt = $pdo->prepare("UPDATE `participants` SET `assigned_to` = NULL WHERE `group_id` = ?");
        $stmt->execute(array($group['id']));
        $pdo->commit();
        $reset_success = t('admin.reset_ok');
        $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `admin_token` = ?");
        $stmt->execute(array($admin_token));
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Auslosung zurücksetzen: ' . $e->getMessage());
        $reset_error = t('admin.reset_fail');
    }
}

if ($csrf_ok && isset($_POST['delete_group'])) {
    $pdo->beginTransaction();
    try {
        $group_id = $group['id'];
        $stmt = $pdo->prepare("UPDATE `participants` SET `assigned_to` = NULL WHERE `group_id` = ?");
        $stmt->execute(array($group_id));
        $stmt = $pdo->prepare("DELETE FROM `exclusions` WHERE `group_id` = ?");
        $stmt->execute(array($group_id));
        $stmt = $pdo->prepare("DELETE FROM `participants` WHERE `group_id` = ?");
        $stmt->execute(array($group_id));
        $stmt = $pdo->prepare("DELETE FROM `groups` WHERE `id` = ?");
        $stmt->execute(array($group_id));
        $pdo->commit();
        $home = i18n_public_url('index');
        $separator = (strpos($home, '?') === false) ? '?' : '&';
        header('Location: ' . $home . $separator . 'deleted=1');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('Gruppe löschen: ' . $e->getMessage());
        $delete_error = t('admin.delete_fail');
    }
}

if ($csrf_ok && isset($_POST['update_participant_email'])) {
    $participant_id = intval($_POST['participant_id']);
    $new_email = trim($_POST['participant_email']);
    if ($new_email !== '' && ($length_error = limit_text_error($new_email, 255, t('field.email'))) !== '') {
        $participant_error = $length_error;
    } elseif (!empty($new_email) && !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $participant_error = t('error.email_invalid');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM `participants` WHERE `id` = ? AND `group_id` = ?");
        $stmt->execute(array($participant_id, $group['id']));
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE `participants` SET `email` = ? WHERE `id` = ?");
            $stmt->execute(array($new_email ?: null, $participant_id));
            $participant_success = t('admin.email_updated');
            $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `group_id` = ?");
            $stmt->execute(array($group['id']));
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $participant_error = t('admin.person_missing');
        }
    }
}

if ($csrf_ok && isset($_POST['resend_email'])) {
    $participant_id = intval($_POST['participant_id']);
    if (!$group['is_drawn']) {
        $email_error = t('admin.not_drawn');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `id` = ? AND `group_id` = ?");
        $stmt->execute(array($participant_id, $group['id']));
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$participant) {
            $email_error = t('admin.person_missing');
        } elseif (empty($participant['email'])) {
            $email_error = t('admin.no_email');
        } elseif (empty($participant['assigned_to'])) {
            $email_error = t('admin.no_assignment');
        } else {
            $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `id` = ?");
            $stmt->execute(array($participant['assigned_to']));
            $assigned = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($assigned) {
                $lang = mail_recipient_lang($participant);
                if (send_partner_mail(
                    $participant['email'],
                    $participant['name'],
                    $assigned['name'],
                    isset($assigned['wishlist']) ? $assigned['wishlist'] : '',
                    $group,
                    $lang,
                    isset($participant['participant_token']) ? $participant['participant_token'] : ''
                )) {
                    $email_success = t('admin.mail_sent', array('{name}' => $participant['name']));
                } else {
                    $email_error = t('admin.mail_fail', array('{name}' => $participant['name']));
                }
            } else {
                $email_error = t('admin.assigned_missing');
            }
        }
    }
}

if ($csrf_ok && isset($_POST['delete_participant'])) {
    $participant_id = intval($_POST['delete_participant']);
    if (!$group['is_drawn']) {
        $stmt = $pdo->prepare("DELETE FROM `participants` WHERE `id` = ? AND `group_id` = ?");
        $stmt->execute(array($participant_id, $group['id']));
    }
    header('Location: /admin.php?token=' . urlencode($admin_token));
    exit();
}

if ($csrf_ok && isset($_POST['add_exclusion'])) {
    $participant_id = intval($_POST['participant_id']);
    $excluded_id = intval($_POST['excluded_participant_id']);
    $bidirectional = isset($_POST['bidirectional']) && $_POST['bidirectional'] === '1';
    $member_ids = array();
    foreach ($participants as $member) {
        $member_ids[(int) $member['id']] = true;
    }

    if ($group['is_drawn']) {
        $exclusion_error = t('admin.exclusion_locked');
    } elseif (!isset($member_ids[$participant_id]) || !isset($member_ids[$excluded_id])) {
        $exclusion_error = t('admin.exclusion_invalid');
    } else {
        try {
            $forward = insert_group_exclusion($pdo, $group['id'], $participant_id, $excluded_id);
            $reverse = null;
            if ($bidirectional && $forward !== 'invalid') {
                $reverse = insert_group_exclusion($pdo, $group['id'], $excluded_id, $participant_id);
            }
            $exclusion_message = exclusion_save_message($forward, $reverse);
            if ($exclusion_message['type'] === 'success') {
                $exclusion_success = $exclusion_message['text'];
            } else {
                $exclusion_error = $exclusion_message['text'];
            }
        } catch (PDOException $e) {
            error_log('Ausschluss konnte nicht gespeichert werden: ' . $e->getMessage());
            $exclusion_error = t('admin.exclusion_fail');
        }
    }
}

if ($csrf_ok && isset($_POST['bulk_import'])) {
    if ($group['is_drawn']) {
        $participant_error = t('admin.import_locked');
    } elseif (!bulk_import_attempt_allowed($admin_token, request_client_ip())) {
        $participant_error = t('error.rate_import');
    } else {
        $parsed = parse_bulk_participants(isset($_POST['bulk_participants']) ? $_POST['bulk_participants'] : '');
        if (!empty($parsed['errors'])) {
            $participant_error = implode(' ', $parsed['errors']);
        } elseif (empty($parsed['rows'])) {
            $participant_error = t('admin.import_none');
        } else {
            $created = array();
            $import_lang = mail_recipient_lang($group);
            $pdo->beginTransaction();
            try {
                foreach ($parsed['rows'] as $row) {
                    $participant_token = generate_token();
                    i18n_insert_participant($pdo, $group['id'], $row['name'], $row['email'], $participant_token, $import_lang);
                    $created[] = array(
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'url' => get_display_url('/participant.php?token=' . rawurlencode($participant_token)),
                        'token' => $participant_token,
                    );
                }
                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log('Sammelimport fehlgeschlagen: ' . $e->getMessage());
                $participant_error = t('admin.import_fail');
                $created = array();
            }

            if (!empty($created)) {
                $mailed = 0;
                foreach ($created as $row) {
                    if (empty($row['email'])) {
                        continue;
                    }
                    if (send_registration_mail($row['email'], $row['name'], $group, $row['url'], $import_lang)) {
                        $mailed++;
                    } else {
                        error_log('Einladungsmail nach Sammelimport konnte nicht an ' . mask_email($row['email']) . ' gesendet werden.');
                    }
                }
                $count = count($created);
                $text = tn('admin.import_created', $count);
                if ($mailed > 0) {
                    $text .= ' ' . tn('admin.import_mailed', $mailed);
                }
                $text .= ' ' . t('admin.import_links');
                $_SESSION['admin_flash'] = array('type' => 'success', 'text' => $text);
                $_SESSION['bulk_import_links'] = $created;
                header('Location: /admin.php?token=' . urlencode($admin_token) . '#teilnehmer');
                exit();
            }
        }
    }
}

$reminder_error = null;
if ($csrf_ok && isset($_POST['send_reminder'])) {
    if (!array_key_exists('reminder_sent_at', $group)) {
        $reminder_error = t('admin.reminder_migration');
    } elseif (empty($group['gift_exchange_date'])) {
        $reminder_error = t('admin.reminder_need_date');
    } elseif (!empty($group['reminder_sent_at']) && (!isset($_POST['confirm_reminder_resend']) || $_POST['confirm_reminder_resend'] !== '1')) {
        $reminder_error = t('admin.reminder_need_confirm');
    } else {
        $reminder_result = deliver_gift_reminders($participants, $group);
        if ($reminder_result['status'] === 'ok') {
            $stmt = $pdo->prepare('UPDATE `groups` SET `reminder_sent_at` = NOW() WHERE `id` = ?');
            $stmt->execute(array($group['id']));
        }
        $_SESSION['admin_flash'] = gift_reminder_result_message($reminder_result);
        header('Location: /admin.php?token=' . urlencode($admin_token) . '#erinnerung');
        exit();
    }
}

if ($csrf_ok && isset($_POST['delete_exclusion'])) {
    $exclusion_id = intval($_POST['delete_exclusion']);
    if (!$group['is_drawn']) {
        $stmt = $pdo->prepare("DELETE FROM `exclusions` WHERE `id` = ? AND `group_id` = ?");
        $stmt->execute(array($exclusion_id, $group['id']));
    }
    header('Location: /admin.php?token=' . urlencode($admin_token));
    exit();
}

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
$stmt->execute(array($group['id']));
$exclusions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$base_date = $group['gift_exchange_date'] ? $group['gift_exchange_date'] : date('Y-m-d', strtotime($group['created_at']));
$deletion_date = fmt_date(date('Y-m-d', strtotime($base_date . ' + 3 months')));

if ($csrf_ok && isset($_POST['update_group'])) {
    $new_budget = trim($_POST['budget']) ?: null;
    $new_description = trim($_POST['description']) ?: null;
    $new_gift_exchange_date = trim($_POST['gift_exchange_date']) ?: null;
    $new_language = null;
    if (i18n_has_language_column($pdo, 'groups') && isset($_POST['group_language']) && i18n_is_allowed($_POST['group_language'])) {
        $new_language = i18n_allow($_POST['group_language']);
    }

    if ($new_budget !== null && !is_numeric($new_budget)) {
        $update_error = t('error.budget_number');
    } elseif ($new_description !== null && ($length_error = limit_text_error($new_description, 2000, t('field.description'))) !== '') {
        $update_error = $length_error;
    } elseif ($new_gift_exchange_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_gift_exchange_date)) {
        $update_error = t('error.date_format');
    } else {
        $date_changed = (string) $group['gift_exchange_date'] !== (string) $new_gift_exchange_date;
        $clear_reminder = ($date_changed && array_key_exists('reminder_sent_at', $group))
            ? ', `reminder_sent_at` = NULL'
            : '';
        $sql = "UPDATE `groups` SET `budget` = ?, `description` = ?, `gift_exchange_date` = ?";
        $params = array($new_budget, $new_description, $new_gift_exchange_date);
        if ($new_language !== null) {
            $sql .= ', `language` = ?';
            $params[] = $new_language;
        }
        $sql .= $clear_reminder . " WHERE `id` = ?";
        $params[] = $group['id'];
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $group['budget'] = $new_budget;
        $group['description'] = $new_description;
        $group['gift_exchange_date'] = $new_gift_exchange_date;
        if ($clear_reminder !== '') {
            $group['reminder_sent_at'] = null;
        }
        if ($new_language !== null) {
            $group['language'] = $new_language;
            i18n_activate($new_language);
            $deletion_date = fmt_date(date('Y-m-d', strtotime($base_date . ' + 3 months')));
        }
        $update_success = t('admin.updated');
    }
}

if ($csrf_ok && isset($_POST['draw'])) {
    if (!empty($group['is_drawn'])) {
        $draw_error = t('admin.draw_already');
    } elseif (count($participants) < 2) {
        $draw_error = t('admin.draw_need_two');
    } else {
        $stmt = $pdo->prepare("SELECT participant_id, excluded_participant_id FROM `exclusions` WHERE `group_id` = ?");
        $stmt->execute(array($group['id']));
        $exclusion_rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $exclusions_map = array();
        foreach ($exclusion_rules as $rule) {
            if (!isset($exclusions_map[$rule['participant_id']])) {
                $exclusions_map[$rule['participant_id']] = array();
            }
            $exclusions_map[$rule['participant_id']][] = $rule['excluded_participant_id'];
        }
        $participant_ids = array_column($participants, 'id');
        $assigned_ids = perform_draw($participant_ids, $exclusions_map);
        if ($assigned_ids === false) {
            $draw_error = t('admin.draw_impossible');
        } else {
            try {
                $saved = save_draw_assignment($pdo, $group['id'], $participant_ids, $assigned_ids);
            } catch (Exception $e) {
                error_log('Auslosung speichern: ' . $e->getMessage());
                $saved = false;
                $draw_error = t('admin.draw_save_fail');
            }
            if (empty($saved)) {
                if (!isset($draw_error)) {
                    $draw_error = t('admin.draw_already');
                }
            } else {
                $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `group_id` = ?");
                $stmt->execute(array($group['id']));
                $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $participants_by_id = array_column($participants, null, 'id');
                foreach ($participants as $participant) {
                    if (empty($participant['email']) || empty($participant['assigned_to'])) {
                        if (empty($participant['assigned_to'])) {
                            error_log("Teilnehmer mit ID {$participant['id']} hat keinen zugewiesenen Teilnehmer.");
                        }
                        continue;
                    }
                    $assigned = isset($participants_by_id[$participant['assigned_to']]) ? $participants_by_id[$participant['assigned_to']] : null;
                    if (!$assigned) {
                        error_log("Zugewiesener Teilnehmer mit ID {$participant['assigned_to']} nicht gefunden.");
                        continue;
                    }
                    $lang = mail_recipient_lang($participant);
                    if (!send_partner_mail(
                        $participant['email'],
                        $participant['name'],
                        $assigned['name'],
                        isset($assigned['wishlist']) ? $assigned['wishlist'] : '',
                        $group,
                        $lang,
                        isset($participant['participant_token']) ? $participant['participant_token'] : ''
                    )) {
                        error_log('E-Mail konnte nicht an ' . mask_email($participant['email']) . ' gesendet werden.');
                    }
                }
                header('Location: /admin.php?token=' . urlencode($admin_token));
                exit();
            }
        }
    }
}

$reveal_error = null;
if ($csrf_ok && isset($_POST['send_reveal'])) {
    if (!$group['is_drawn']) {
        $reveal_error = t('admin.reveal_need_draw');
    } elseif (!mysql_column_exists($pdo, 'groups', 'reveal_sent_at')) {
        $reveal_error = t('admin.reveal_need_migration');
    } elseif (!empty($group['reveal_sent_at']) && (!isset($_POST['confirm_resend']) || $_POST['confirm_resend'] !== '1')) {
        $reveal_error = t('admin.reveal_need_confirm');
    } else {
        $reveal_result = deliver_group_reveal($participants, $group);
        if ($reveal_result['status'] === 'ok') {
            $stmt = $pdo->prepare("UPDATE `groups` SET `reveal_sent_at` = NOW() WHERE `id` = ?");
            $stmt->execute(array($group['id']));
        }
        $_SESSION['admin_flash'] = reveal_result_message($reveal_result);
        header('Location: /admin.php?token=' . urlencode($admin_token));
        exit();
    }
}

$admin_flash = null;
if (isset($_SESSION['admin_flash']) && is_array($_SESSION['admin_flash'])) {
    $admin_flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
}
$bulk_links = array();
if (isset($_SESSION['bulk_import_links']) && is_array($_SESSION['bulk_import_links'])) {
    $bulk_links = $_SESSION['bulk_import_links'];
    unset($_SESSION['bulk_import_links']);
}

$group_language_editable = i18n_has_language_column($pdo, 'groups');
$premium_license = premium_license_for_group($pdo, $group);
$premium_from_create = isset($_GET['premium']) && (string) $_GET['premium'] === '1';
$premium_claim_retry = $csrf_ok && isset($_POST['premium_action']) && in_array((string) $_POST['premium_action'], array('claim_email', 'confirm_short', 'claim_code'), true);
$invite_url = get_display_url('/register.php?token=' . urlencode($group['invite_token']));
$admin_url = get_display_url('/admin.php?token=' . urlencode($admin_token));
$whatsapp_text = t('admin.whatsapp_intro') . "\n\n"
    . t('admin.whatsapp_group', array('{name}' => $group['name'])) . "\n\n"
    . t('admin.whatsapp_join', array('{url}' => $invite_url));

token_page_open(t('meta.admin.title', array('{group}' => $group['name'])));
?>
    <div class="container app-page<?php echo premium_page_class($premium_license); ?>"<?php echo premium_page_style_attr($premium_license); ?>>
        <?php
        $reveal_already_sent = !empty($group['reveal_sent_at']);
        $reveal_sent_label = $reveal_already_sent ? format_reveal_sent_at($group['reveal_sent_at']) : '';
        if ($reveal_already_sent && $reveal_sent_label === '') {
            $reveal_sent_label = (string) $group['reveal_sent_at'];
        }
        $budget_label = ($group['budget'] !== null && $group['budget'] !== '')
            ? fmt_money($group['budget'])
            : t('value.unset');
        $exchange_label = !empty($group['gift_exchange_date'])
            ? fmt_date($group['gift_exchange_date'])
            : t('value.unset');
        $mail_stats = reveal_recipient_stats($participants);
        $checklist = admin_group_checklist($participants, $group);
        $draw_label = admin_checklist_draw_label($checklist['draw']);
        $reveal_label = admin_checklist_reveal_label($checklist['reveal']);
        $draw_class = ($checklist['draw'] === 'done') ? 'is-done' : (($checklist['draw'] === 'ready') ? 'is-ready' : 'is-wait');
        $reminder_column = array_key_exists('reminder_sent_at', $group);
        $reminder_already_sent = $reminder_column && !empty($group['reminder_sent_at']);
        $reminder_sent_label = $reminder_already_sent ? format_reveal_sent_at($group['reminder_sent_at']) : '';
        $calendar_url = !empty($group['gift_exchange_date']) ? gift_ics_url($group, i18n_current()) : '';
        ?>
        <header class="app-hero">
            <p class="app-kicker"><?php echo h(t('admin.kicker')); ?></p>
            <h1><?php echo h($group['name']); ?></h1>
            <p class="app-hero-lead"><?php echo h(t('admin.lead')); ?></p>
            <?php echo premium_hero_extras_html($premium_license); ?>
            <div class="status-row" id="admin-status-strip">
                <span class="status-pill"><?php echo h(tn('admin.participants', (int) $checklist['participants'])); ?></span>
                <span class="status-pill <?php echo ($checklist['with_email'] === $checklist['participants'] && $checklist['participants'] > 0) ? 'is-done' : 'is-wait'; ?>">
                    <?php echo h(t('admin.with_email', array('{count}' => (string) (int) $checklist['with_email']))); ?>
                </span>
                <span class="status-pill <?php echo ($checklist['with_wishlist'] > 0) ? 'is-done' : 'is-wait'; ?>">
                    <?php echo h(t('admin.with_wishlist', array('{count}' => (string) (int) $checklist['with_wishlist']))); ?>
                </span>
                <span class="status-pill <?php echo $draw_class; ?>"><?php echo h($draw_label); ?></span>
                <span class="status-pill <?php echo ($checklist['reveal'] === 'sent') ? 'is-done' : 'is-wait'; ?>"><?php echo h($reveal_label); ?></span>
            </div>
            <nav class="app-toc" aria-label="<?php echo h(t('admin.toc')); ?>">
                <?php if ($premium_license): ?>
                <a href="#premium"><?php echo h(t('premium.admin.toc')); ?></a>
                <?php endif; ?>
                <a href="#checkliste"><?php echo h(t('admin.toc.checklist')); ?></a>
                <a href="#ueberblick"><?php echo h(t('admin.toc.overview')); ?></a>
                <a href="#gruppendetails"><?php echo h(t('admin.toc.details')); ?></a>
                <a href="#erinnerung"><?php echo h(t('admin.toc.reminder')); ?></a>
                <a href="#einladung"><?php echo h(t('admin.toc.invite')); ?></a>
                <a href="#teilnehmer"><?php echo h(t('admin.toc.people')); ?></a>
                <a href="#ausschluesse"><?php echo h(t('admin.toc.exclusions')); ?></a>
                <a href="#auslosung"><?php echo h(t('admin.toc.draw')); ?></a>
                <a href="#aufloesung"><?php echo h(t('admin.toc.reveal')); ?></a>
                <a href="#gefahrenzone"><?php echo h(t('admin.toc.danger')); ?></a>
            </nav>
        </header>

        <?php
        $notices = array(
            array('error', isset($csrf_error) ? $csrf_error : ''),
            array('error', isset($update_error) ? $update_error : ''),
            array('success', isset($update_success) ? $update_success : ''),
            array('error', isset($draw_error) ? $draw_error : ''),
            array('success', isset($exclusion_success) ? $exclusion_success : ''),
            array('error', isset($exclusion_error) ? $exclusion_error : ''),
            array('success', isset($reset_success) ? $reset_success : ''),
            array('error', isset($reset_error) ? $reset_error : ''),
            array('error', isset($delete_error) ? $delete_error : ''),
            array('success', isset($participant_success) ? $participant_success : ''),
            array('error', isset($participant_error) ? $participant_error : ''),
            array('success', isset($email_success) ? $email_success : ''),
            array('error', isset($email_error) ? $email_error : ''),
            array('error', !empty($reveal_error) ? $reveal_error : ''),
            array('error', !empty($reminder_error) ? $reminder_error : ''),
        );
        foreach ($notices as $notice) {
            if ($notice[1] === '' || $notice[1] === null) {
                continue;
            }
            $role = ($notice[0] === 'error') ? 'alert' : 'status';
            $live = ($notice[0] === 'error') ? 'assertive' : 'polite';
            echo '<div class="notification ' . h($notice[0]) . '" role="' . $role . '" aria-live="' . $live . '">' . h($notice[1]) . '</div>';
        }
        ?>

        <?php if (is_array($admin_flash) && !empty($admin_flash['text'])): ?>
            <?php
            $flash_type = 'success';
            if (isset($admin_flash['type']) && in_array($admin_flash['type'], array('success', 'error', 'warning'), true)) {
                $flash_type = $admin_flash['type'];
            }
            $flash_role = ($flash_type === 'error') ? 'alert' : 'status';
            ?>
            <div class="notification <?php echo h($flash_type); ?>" role="<?php echo $flash_role; ?>" aria-live="<?php echo ($flash_type === 'error') ? 'assertive' : 'polite'; ?>">
                <?php echo h($admin_flash['text']); ?>
                <?php
                $flash_link = '';
                if (isset($admin_flash['link']) && is_string($admin_flash['link']) && preg_match('#\Ahttps://#', $admin_flash['link']) === 1) {
                    $flash_link = $admin_flash['link'];
                }
                ?>
                <?php if ($flash_link !== ''): ?>
                    <p class="flash-link"><a href="<?php echo h($flash_link); ?>"><?php echo h($flash_link); ?></a></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php
        echo premium_admin_section_html($pdo, $group, $_SESSION);
        ?>
        <section class="section-card" id="checkliste" aria-labelledby="checklist-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">✅</span>
                <h2 id="checklist-heading"><?php echo h(t('admin.checklist')); ?></h2>
            </div>
            <p class="section-description"><?php echo h(t('admin.checklist_lead')); ?></p>
            <ul class="admin-checklist" id="admin-checklist">
                <li class="admin-checklist-item">
                    <span class="admin-checklist-label"><?php echo h(t('admin.check.people')); ?></span>
                    <strong><?php echo (int) $checklist['participants']; ?></strong>
                </li>
                <li class="admin-checklist-item <?php echo ($checklist['participants'] > 0 && $checklist['with_email'] === $checklist['participants']) ? 'is-done' : 'is-wait'; ?>">
                    <span class="admin-checklist-label"><?php echo h(t('admin.check.email')); ?></span>
                    <strong><?php echo h(t('admin.check.of', array('{done}' => (string) (int) $checklist['with_email'], '{total}' => (string) (int) $checklist['participants']))); ?></strong>
                </li>
                <li class="admin-checklist-item <?php echo ($checklist['with_wishlist'] > 0) ? 'is-done' : 'is-wait'; ?>">
                    <span class="admin-checklist-label"><?php echo h(t('admin.check.wishlist')); ?></span>
                    <strong><?php echo h(t('admin.check.of', array('{done}' => (string) (int) $checklist['with_wishlist'], '{total}' => (string) (int) $checklist['participants']))); ?></strong>
                </li>
                <li class="admin-checklist-item <?php echo $draw_class; ?>">
                    <span class="admin-checklist-label"><?php echo h(t('admin.check.draw')); ?></span>
                    <strong><?php echo h($draw_label); ?></strong>
                </li>
                <li class="admin-checklist-item <?php echo ($checklist['reveal'] === 'sent') ? 'is-done' : 'is-wait'; ?>">
                    <span class="admin-checklist-label"><?php echo h(t('admin.check.reveal')); ?></span>
                    <strong><?php echo h($reveal_label); ?></strong>
                </li>
            </ul>
        </section>

        <section class="section-card" id="ueberblick" aria-labelledby="overview-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">📌</span>
                <h2 id="overview-heading"><?php echo h(t('admin.overview')); ?></h2>
            </div>
            <div class="status-grid">
                <div class="status-tile">
                    <span class="status-tile-label"><?php echo h(t('admin.budget')); ?></span>
                    <span class="status-tile-value"><?php echo h($budget_label); ?></span>
                </div>
                <div class="status-tile">
                    <span class="status-tile-label"><?php echo h(t('admin.exchange')); ?></span>
                    <span class="status-tile-value"><?php echo h($exchange_label); ?></span>
                    <?php if ($calendar_url !== ''): ?>
                        <a class="button secondary small calendar-link" href="<?php echo h($calendar_url); ?>"><?php echo h(t('mail.calendar')); ?></a>
                    <?php endif; ?>
                </div>
                <div class="status-tile">
                    <span class="status-tile-label"><?php echo h(t('admin.with_email_label')); ?></span>
                    <span class="status-tile-value"><?php echo h(t('admin.check.of', array('{done}' => (string) (int) $mail_stats['with_email'], '{total}' => (string) count($participants)))); ?></span>
                </div>
                <div class="status-tile">
                    <span class="status-tile-label"><?php echo h(t('admin.reveal')); ?></span>
                    <span class="status-tile-value"><?php echo $reveal_already_sent ? h($reveal_sent_label) : h(t('admin.reveal_pending')); ?></span>
                </div>
            </div>
            <div class="admin-info-box warning">
                <div class="admin-info-icon" aria-hidden="true">ℹ️</div>
                <div class="admin-info-content">
                    <h3 class="admin-info-title"><?php echo h(t('admin.privacy_title')); ?></h3>
                    <p class="admin-info-text"><?php echo t('admin.privacy_text', array('{date}' => '<strong>' . h($deletion_date) . '</strong>')); ?></p>
                </div>
            </div>
        </section>

        <section class="section-card" id="gruppendetails" aria-labelledby="details-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">✏️</span>
                <h2 id="details-heading"><?php echo h(t('admin.details')); ?></h2>
            </div>
            <p class="section-description"><?php echo h(t('admin.details_help')); ?></p>
            <form method="POST" id="update-group-form">
                <?php echo csrf_input(); ?>
                <div class="form-group">
                    <label for="budget"><?php echo h(t('create.budget')); ?></label>
                    <input type="number" step="0.01" id="budget" name="budget" value="<?php echo h(isset($group['budget']) ? $group['budget'] : ''); ?>" placeholder="<?php echo h(t('create.budget_ph')); ?>" aria-describedby="budget_hint">
                    <small id="budget_hint" class="form-hint"><?php echo h(t('create.budget_hint')); ?></small>
                </div>
                <div class="form-group">
                    <label for="description"><?php echo h(t('create.description')); ?></label>
                    <textarea id="description" name="description" rows="4" aria-describedby="description_hint"><?php echo h(isset($group['description']) ? $group['description'] : ''); ?></textarea>
                    <small id="description_hint" class="form-hint"><?php echo h(t('create.description_hint')); ?></small>
                </div>
                <div class="form-group">
                    <label for="gift_exchange_date"><?php echo h(t('create.date')); ?></label>
                    <input type="date" id="gift_exchange_date" name="gift_exchange_date" value="<?php echo h(isset($group['gift_exchange_date']) ? $group['gift_exchange_date'] : ''); ?>" min="<?php echo h(date('Y-m-d')); ?>" aria-describedby="gift_exchange_date_hint">
                    <small id="gift_exchange_date_hint" class="form-hint"><?php echo h(t('create.date_hint')); ?></small>
                </div>
                <?php if ($group_language_editable): ?>
                <div class="form-group">
                    <label for="group_language"><?php echo h(t('admin.language')); ?></label>
                    <?php echo i18n_language_select('group_language', i18n_row_language($group), 'group_language'); ?>
                    <small class="form-hint"><?php echo h(t('admin.language_hint')); ?></small>
                </div>
                <?php endif; ?>
                <input type="hidden" name="update_group" value="1">
                <button type="submit" class="button primary">
                    <span aria-hidden="true">💾</span> <?php echo h(t('admin.save_details')); ?>
                </button>
            </form>

            <div class="reminder-block" id="erinnerung">
                <h3><?php echo h(t('admin.reminder_title')); ?></h3>
                <?php if (!$reminder_column): ?>
                    <p class="form-hint"><?php echo h(t('admin.reminder_migration_hint')); ?></p>
                <?php elseif (empty($group['gift_exchange_date'])): ?>
                    <p class="form-hint"><?php echo h(t('admin.reminder_no_date_hint')); ?></p>
                <?php else: ?>
                    <?php
                    $reminder_days = gift_days_until($group['gift_exchange_date']);
                    $reminder_sentence = gift_reminder_sentence($reminder_days);
                    $reminder_confirm_key = $reminder_already_sent ? 'admin.confirm.reminder_again' : 'admin.confirm.reminder';
                    $reminder_confirm_vars = $reminder_already_sent ? array() : array('{sentence}' => $reminder_sentence);
                    ?>
                    <p class="section-description"><?php echo h($reminder_sentence); ?> <?php echo h(t('admin.reminder_help')); ?></p>
                    <?php if ($reminder_already_sent): ?>
                        <p class="reveal-sent-note">
                            <span aria-hidden="true">✓</span>
                            <?php echo h(t('admin.reminder_sent_note', array('{when}' => $reminder_sent_label !== '' ? $reminder_sent_label : (string) $group['reminder_sent_at']))); ?>
                        </p>
                    <?php endif; ?>
                    <form method="POST" id="reminder-form"<?php echo i18n_confirm_attrs($reminder_confirm_key, $reminder_confirm_vars); ?>>
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="send_reminder" value="1">
                        <?php if ($reminder_already_sent): ?>
                            <label class="reveal-confirm-label" for="confirm-reminder-resend">
                                <input type="checkbox" id="confirm-reminder-resend" name="confirm_reminder_resend" value="1" required>
                                <span><?php echo h(t('admin.reminder_resend')); ?></span>
                            </label>
                        <?php endif; ?>
                        <button type="submit" class="button secondary">
                            <?php echo h($reminder_already_sent ? t('admin.reminder_again') : t('admin.reminder_send')); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <section class="section-card" id="einladung" aria-labelledby="invite-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">🔗</span>
                <h2 id="invite-heading"><?php echo h(t('admin.invite')); ?></h2>
            </div>
            <p class="section-description"><?php echo h(t('admin.invite_help')); ?></p>
            <div class="link-box">
                <pre id="participant-link" class="link-display"><?php echo h($invite_url); ?></pre>
                <div class="link-actions">
                    <button type="button" class="button secondary small copy-button" onclick="copyToClipboard('participant-link')" aria-label="<?php echo h(t('admin.copy_invite')); ?>"><?php echo h(t('admin.copy')); ?></button>
                    <a href="https://api.whatsapp.com/send?text=<?php echo rawurlencode($whatsapp_text); ?>"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="button secondary small share-whatsapp"
                       aria-label="<?php echo h(t('admin.whatsapp')); ?>">
                        <?php echo h(t('admin.whatsapp')); ?>
                    </a>
                </div>
            </div>
            <div class="admin-info-box">
                <div class="admin-info-icon" aria-hidden="true">💡</div>
                <div class="admin-info-content">
                    <h3 class="admin-info-title"><?php echo h(t('admin.self_title')); ?></h3>
                    <p class="admin-info-text"><?php echo h(t('admin.self_text')); ?></p>
                </div>
            </div>
            <div class="admin-link-block">
                <h3><?php echo h(t('admin.admin_link')); ?></h3>
                <p class="section-description"><?php echo h(t('admin.admin_link_help')); ?></p>
                <div class="link-box">
                    <pre id="admin-link" class="link-display"><?php echo h($admin_url); ?></pre>
                    <div class="link-actions">
                        <button type="button" class="button secondary small copy-button" onclick="copyToClipboard('admin-link')" aria-label="<?php echo h(t('admin.copy_admin')); ?>"><?php echo h(t('admin.copy')); ?></button>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-card" id="teilnehmer" aria-labelledby="participants-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">👥</span>
                <h2 id="participants-heading"><?php echo h(t('admin.people_heading', array('{count}' => (string) count($participants)))); ?></h2>
            </div>
            <p class="section-description"><?php echo h(t('admin.people_help')); ?></p>

            <?php if (!empty($bulk_links)): ?>
                <div class="bulk-import-result" id="bulk-import-result">
                    <h3><?php echo h(t('admin.links_title')); ?></h3>
                    <p class="section-description"><?php echo h(t('admin.links_help')); ?></p>
                    <?php foreach ($bulk_links as $index => $created): ?>
                        <div class="link-box">
                            <p class="bulk-import-name">
                                <strong><?php echo h($created['name']); ?></strong>
                                <?php if (!empty($created['email'])): ?>
                                    <span><?php echo h($created['email']); ?></span>
                                <?php endif; ?>
                            </p>
                            <pre id="bulk-link-<?php echo (int) $index; ?>" class="link-display"><?php echo h($created['url']); ?></pre>
                            <div class="link-actions">
                                <button type="button" class="button secondary small copy-button" onclick="copyToClipboard('bulk-link-<?php echo (int) $index; ?>')" aria-label="<?php echo h(t('admin.copy_person', array('{name}' => $created['name']))); ?>"><?php echo h(t('admin.copy')); ?></button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$group['is_drawn']): ?>
                <form method="POST" id="bulk-import-form" class="bulk-import-form">
                    <?php echo csrf_input(); ?>
                    <div class="form-group">
                        <label for="bulk_participants"><?php echo h(t('admin.bulk_label')); ?></label>
                        <textarea id="bulk_participants" name="bulk_participants" rows="8" placeholder="<?php echo h(t('admin.bulk_ph')); ?>" aria-describedby="bulk_participants_hint"></textarea>
                        <small id="bulk_participants_hint" class="form-hint"><?php echo h(t('admin.bulk_help')); ?></small>
                    </div>
                    <input type="hidden" name="bulk_import" value="1">
                    <button type="submit" class="button secondary"><?php echo h(t('admin.bulk_submit')); ?></button>
                </form>
            <?php endif; ?>

            <?php if ($participants): ?>
                <div class="participants-grid">
                    <?php foreach ($participants as $p): ?>
                        <div class="participant-card">
                            <div class="participant-header">
                                <?php
                                $avatar_name = (string) (isset($p['name']) ? $p['name'] : '');
                                if (function_exists('mb_substr')) {
                                    $avatar_initial = mb_strtoupper(mb_substr($avatar_name, 0, 1, 'UTF-8'), 'UTF-8');
                                } else {
                                    $avatar_initial = strtoupper(substr($avatar_name, 0, 1));
                                }
                                ?>
                                <span class="participant-avatar" aria-hidden="true"><?php echo h($avatar_initial); ?></span>
                                <div class="participant-info">
                                    <h3 class="participant-name"><?php echo h(isset($p['name']) ? $p['name'] : ''); ?></h3>
                                    <div class="participant-email">
                                        <?php if (!empty($p['email'])): ?>
                                            <span class="email-display"><span aria-hidden="true">✉️</span> <?php echo h($p['email']); ?></span>
                                        <?php else: ?>
                                            <span class="email-missing"><span aria-hidden="true">⚠️</span> <?php echo h(t('admin.email_missing')); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="participant-actions">
                                <?php if (!empty($p['participant_token'])): ?>
                                    <button type="button" class="action-btn copy-btn" onclick="copyToClipboard('participant-link-<?php echo (int) $p['id']; ?>')" title="<?php echo h(t('admin.copy_title')); ?>" aria-label="<?php echo h(t('admin.copy_person', array('{name}' => $p['name']))); ?>">
                                        <span class="btn-icon" aria-hidden="true">📋</span>
                                        <span class="btn-text"><?php echo h(t('admin.copy')); ?></span>
                                    </button>
                                    <span id="participant-link-<?php echo (int) $p['id']; ?>" data-url="<?php echo h(get_display_url('/participant.php?token=' . urlencode($p['participant_token']))); ?>" style="display: none;"></span>
                                <?php endif; ?>
                                <?php $can_send_email = $group['is_drawn'] && !empty($p['email']) && !empty($p['assigned_to']); ?>
                                <form method="POST" class="action-form resend-email-form"<?php echo $can_send_email ? i18n_confirm_attrs('admin.confirm.resend_partner', array('{name}' => $p['name'])) : ''; ?>>
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="participant_id" value="<?php echo (int) $p['id']; ?>">
                                    <input type="hidden" name="resend_email" value="1">
                                    <button type="submit"
                                            class="action-btn email-btn <?php echo !$can_send_email ? 'disabled' : ''; ?>"
                                            title="<?php echo h($can_send_email ? t('admin.resend') : t('admin.email_cannot')); ?>"
                                            aria-label="<?php echo h(t('admin.resend_label', array('{name}' => $p['name']))); ?>"
                                            <?php echo !$can_send_email ? 'disabled' : ''; ?>>
                                        <span class="btn-icon" aria-hidden="true">📧</span>
                                        <span class="btn-text"><?php echo h(t('admin.email_send')); ?></span>
                                    </button>
                                </form>
                                <?php if (!$group['is_drawn']): ?>
                                    <form method="POST" class="action-form"<?php echo i18n_confirm_attrs('admin.confirm.delete_participant', array('{name}' => $p['name'])); ?>>
                                        <?php echo csrf_input(); ?>
                                        <input type="hidden" name="delete_participant" value="<?php echo (int) $p['id']; ?>">
                                        <button type="submit" class="action-btn delete-btn" aria-label="<?php echo h(t('admin.delete_label', array('{name}' => $p['name']))); ?>">
                                            <span class="btn-icon" aria-hidden="true">🗑️</span>
                                            <span class="btn-text"><?php echo h(t('admin.delete')); ?></span>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div class="participant-email-edit">
                                <form method="POST" class="email-edit-form">
                                    <?php echo csrf_input(); ?>
                                    <input type="hidden" name="participant_id" value="<?php echo (int) $p['id']; ?>">
                                    <input type="hidden" name="update_participant_email" value="1">
                                    <div class="email-edit-group">
                                        <input type="email" name="participant_email" value="<?php echo h(isset($p['email']) ? $p['email'] : ''); ?>" placeholder="<?php echo h(t('admin.email_ph')); ?>" class="email-edit-input" aria-label="<?php echo h(t('admin.email_for', array('{name}' => $p['name']))); ?>">
                                        <button type="submit" class="email-edit-btn" title="<?php echo h(t('admin.email_save')); ?>" aria-label="<?php echo h(t('admin.email_save_label', array('{name}' => $p['name']))); ?>">
                                            <span aria-hidden="true">💾</span> <?php echo h(t('admin.save')); ?>
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
                    <p class="empty-text"><?php echo h(t('admin.people_empty_text')); ?></p>
                    <p class="empty-hint" style="margin-bottom: 1rem;"><?php echo h(t('admin.people_empty_hint')); ?></p>
                    <button type="button" class="button primary copy-button" onclick="copyToClipboard('participant-link')" aria-label="<?php echo h(t('admin.copy_invite')); ?>">
                        <span class="btn-icon" aria-hidden="true">📋</span> <?php echo h(t('admin.copy_invite')); ?>
                    </button>
                </div>
            <?php endif; ?>
        </section>

        <section class="section-card" id="ausschluesse" aria-labelledby="exclusions-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">🤝</span>
                <h2 id="exclusions-heading"><?php echo h(t('admin.exclusions')); ?></h2>
            </div>
            <?php if (!$group['is_drawn'] && count($participants) >= 2): ?>
                <p class="section-description"><?php echo h(t('admin.exclusions_help')); ?></p>
                <form method="POST" class="exclusion-form">
                    <?php echo csrf_input(); ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="participant_id"><?php echo h(t('admin.person')); ?><span class="required-indicator" aria-hidden="true" title="<?php echo h(t('captcha.required')); ?>">*</span></label>
                            <select id="participant_id" name="participant_id" required>
                                <option value=""><?php echo h(t('admin.select_person')); ?></option>
                                <?php foreach ($participants as $p): ?>
                                    <option value="<?php echo (int) $p['id']; ?>"><?php echo h($p['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="excluded_participant_id"><?php echo h(t('admin.cannot')); ?><span class="required-indicator" aria-hidden="true" title="<?php echo h(t('captcha.required')); ?>">*</span></label>
                            <select id="excluded_participant_id" name="excluded_participant_id" required>
                                <option value=""><?php echo h(t('admin.select_person')); ?></option>
                                <?php foreach ($participants as $p): ?>
                                    <option value="<?php echo (int) $p['id']; ?>"><?php echo h($p['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="button-align-label" aria-hidden="true"><?php echo h(t('admin.add_exclusion')); ?></label>
                            <input type="hidden" name="add_exclusion" value="1">
                            <button type="submit" class="button primary"><?php echo h(t('admin.add_exclusion')); ?></button>
                        </div>
                    </div>
                    <label class="reveal-confirm-label" for="exclusion-bidirectional">
                        <input type="checkbox" id="exclusion-bidirectional" name="bidirectional" value="1">
                        <span><?php echo h(t('admin.both')); ?> <span class="form-hint"><?php echo h(t('admin.both_hint')); ?></span></span>
                    </label>
                </form>
                <?php if ($exclusions): ?>
                    <h3><?php echo h(t('admin.exclusions_active')); ?></h3>
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th><?php echo h(t('admin.person')); ?></th>
                                <th><?php echo h(t('admin.cannot')); ?></th>
                                <th><?php echo h(t('admin.col.actions')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exclusions as $ex): ?>
                                <tr>
                                    <td data-label="<?php echo h(t('admin.person')); ?>"><?php echo h($ex['participant_name']); ?></td>
                                    <td data-label="<?php echo h(t('admin.cannot')); ?>"><?php echo h($ex['excluded_name']); ?></td>
                                    <td data-label="<?php echo h(t('admin.col.actions')); ?>">
                                        <form method="POST" class="inline-action-form"<?php echo i18n_confirm_attrs('admin.confirm.delete_exclusion'); ?>>
                                            <?php echo csrf_input(); ?>
                                            <input type="hidden" name="delete_exclusion" value="<?php echo (int) $ex['id']; ?>">
                                            <button type="submit" class="button error small" aria-label="<?php echo h(t('admin.exclusion_delete', array('{name}' => $ex['participant_name'], '{other}' => $ex['excluded_name']))); ?>">
                                                <?php echo h(t('admin.delete')); ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon" aria-hidden="true">🤝</div>
                        <p class="empty-text"><?php echo h(t('admin.exclusions_empty')); ?></p>
                        <p class="empty-hint"><?php echo h(t('admin.exclusions_empty_hint')); ?></p>
                    </div>
                <?php endif; ?>
            <?php elseif (!$group['is_drawn']): ?>
                <div class="empty-state">
                    <div class="empty-icon" aria-hidden="true">🤝</div>
                    <p class="empty-text"><?php echo h(t('admin.exclusions_wait')); ?></p>
                    <p class="empty-hint"><?php echo h(t('admin.exclusions_wait_hint')); ?></p>
                </div>
            <?php else: ?>
                <p class="section-description"><?php echo h(t('admin.exclusions_locked_long')); ?></p>
                <?php if ($exclusions): ?>
                    <table class="app-table">
                        <thead>
                            <tr>
                                <th><?php echo h(t('admin.person')); ?></th>
                                <th><?php echo h(t('admin.cannot')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($exclusions as $ex): ?>
                                <tr>
                                    <td data-label="<?php echo h(t('admin.person')); ?>"><?php echo h($ex['participant_name']); ?></td>
                                    <td data-label="<?php echo h(t('admin.cannot')); ?>"><?php echo h($ex['excluded_name']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon" aria-hidden="true">🤝</div>
                        <p class="empty-text"><?php echo h(t('admin.exclusions_none_set')); ?></p>
                        <p class="empty-hint"><?php echo h(t('admin.exclusions_any')); ?></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="section-card" id="auslosung" aria-labelledby="draw-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">🎲</span>
                <h2 id="draw-heading"><?php echo h(t('admin.draw')); ?></h2>
            </div>
            <?php if (!$group['is_drawn']): ?>
                <p class="section-description"><?php echo h(t('admin.draw_ready_help')); ?></p>
                <?php if (count($participants) < 2): ?>
                    <div class="empty-state">
                        <div class="empty-icon" aria-hidden="true">🎲</div>
                        <p class="empty-text"><?php echo h(t('admin.draw_too_few')); ?></p>
                        <p class="empty-hint"><?php echo h(t('admin.draw_too_few_hint')); ?></p>
                    </div>
                <?php endif; ?>
                <form method="POST" id="draw-form"<?php echo i18n_confirm_attrs('admin.confirm.draw'); ?>>
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="draw" value="1">
                    <button type="submit" class="button primary"><?php echo h(t('admin.draw_submit')); ?></button>
                </form>
            <?php else: ?>
                <div class="notification success" role="status" aria-live="polite">
                    <span aria-hidden="true">✓</span> <?php echo h(t('admin.draw_done')); ?>
                </div>
                <p class="section-description"><?php echo h(t('admin.draw_done_help')); ?></p>
            <?php endif; ?>
        </section>

        <?php
        $reveal_stats = reveal_recipient_stats($participants);
        $reveal_confirm_key = $reveal_already_sent ? 'admin.confirm.reveal_again' : 'admin.confirm.reveal';
        $reveal_confirm_vars = $reveal_already_sent ? array('{when}' => $reveal_sent_label) : array();
        ?>
        <section class="section-card reveal-panel" id="aufloesung" aria-labelledby="reveal-heading">
            <div class="reveal-panel-header">
                <span class="reveal-panel-icon" aria-hidden="true">✨</span>
                <div>
                    <h2 id="reveal-heading"><?php echo h(t('admin.reveal')); ?></h2>
                    <p class="reveal-panel-text" id="reveal-description"><?php echo h(t('admin.reveal_help')); ?></p>
                </div>
            </div>
            <?php if (!$group['is_drawn']): ?>
                <p class="form-hint" id="reveal-hint"><?php echo h(t('admin.reveal_wait')); ?></p>
                <button type="button" class="button primary" disabled aria-disabled="true" aria-describedby="reveal-hint">
                    <?php echo h(t('admin.reveal_send')); ?>
                </button>
            <?php else: ?>
                <?php if ($reveal_already_sent): ?>
                    <p class="reveal-sent-note">
                        <span aria-hidden="true">✓</span>
                        <?php echo h(t('admin.reveal_sent_note', array('{when}' => $reveal_sent_label))); ?>
                    </p>
                <?php endif; ?>
                <p class="form-hint" id="reveal-hint">
                    <?php echo h(tn('admin.reveal_mails', (int) $reveal_stats['with_email'])); ?>
                    <?php if ((int) $reveal_stats['without_email'] > 0): ?>
                        <?php echo h(tn('admin.reveal_skip', (int) $reveal_stats['without_email'])); ?>
                    <?php endif; ?>
                </p>
                <form method="POST" id="reveal-form"<?php echo i18n_confirm_attrs($reveal_confirm_key, $reveal_confirm_vars); ?>>
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="send_reveal" value="1">
                    <?php if ($reveal_already_sent): ?>
                        <label class="reveal-confirm-label" for="confirm-resend">
                            <input type="checkbox" id="confirm-resend" name="confirm_resend" value="1" required>
                            <span><?php echo h(t('admin.reveal_resend_label')); ?></span>
                        </label>
                    <?php endif; ?>
                    <button type="submit" class="button primary" aria-describedby="reveal-hint">
                        <span aria-hidden="true"><?php echo $reveal_already_sent ? '↻' : '✨'; ?></span>
                        <?php echo h($reveal_already_sent ? t('admin.reveal_again') : t('admin.reveal_send')); ?>
                    </button>
                </form>
            <?php endif; ?>
        </section>

        <section class="section-card danger-zone" id="gefahrenzone" aria-labelledby="danger-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">⚠️</span>
                <h2 id="danger-heading"><?php echo h(t('admin.danger')); ?></h2>
            </div>
            <p class="section-description"><?php echo h(t('admin.danger_help')); ?></p>
            <?php if ($group['is_drawn']): ?>
                <div class="danger-block">
                    <h3><?php echo h(t('admin.reset')); ?></h3>
                    <p class="text-muted"><?php echo h(t('admin.reset_long')); ?></p>
                    <form method="POST" id="reset-draw-form"<?php echo i18n_confirm_attrs('admin.confirm.reset_draw'); ?>>
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="reset_draw" value="1">
                        <button type="submit" class="button error"><?php echo h(t('admin.reset')); ?></button>
                    </form>
                </div>
            <?php endif; ?>
            <div class="danger-block">
                <h3><?php echo h(t('admin.delete_group')); ?></h3>
                <p class="text-muted"><?php echo h(t('admin.delete_long')); ?></p>
                <form method="POST" id="delete-group-form"<?php echo i18n_confirm_attrs('admin.confirm.delete_group', array('{name}' => $group['name'])); ?>>
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="delete_group" value="1">
                    <button type="submit" class="button error">
                        <span aria-hidden="true">🗑️</span> <?php echo h(t('admin.delete_permanent')); ?>
                    </button>
                </form>
            </div>
        </section>

        <?php
        echo premium_admin_offer_html(
            $pdo,
            $group,
            $_SESSION,
            $premium_from_create || $premium_claim_retry,
            $premium_from_create
        );
        ?>

        <?php echo support_card_html('admin'); ?>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            handleFormSubmit(document.getElementById('draw-form'), <?php echo json_encode(t('js.drawing'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
            handleFormSubmit(document.getElementById('update-group-form'), <?php echo json_encode(t('js.updating'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
            handleFormSubmit(document.getElementById('reset-draw-form'), <?php echo json_encode(t('js.resetting'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
            handleFormSubmit(document.getElementById('delete-group-form'), <?php echo json_encode(t('js.deleting'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
            handleFormSubmit(document.getElementById('reveal-form'), <?php echo json_encode(t('js.sending'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
            handleFormSubmit(document.getElementById('reminder-form'), <?php echo json_encode(t('js.sending'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
            handleFormSubmit(document.getElementById('bulk-import-form'), <?php echo json_encode(t('js.importing'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
            var exclusionForm = document.querySelector('.exclusion-form');
            if (exclusionForm) handleFormSubmit(exclusionForm, <?php echo json_encode(t('js.adding'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
            document.querySelectorAll('.resend-email-form').forEach(function(form) {
                handleFormSubmit(form, <?php echo json_encode(t('js.sending'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
            });
            document.querySelectorAll('.email-edit-form').forEach(function(form) {
                handleFormSubmit(form, <?php echo json_encode(t('js.saving'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>);
            });
        });
    </script>
<?php token_page_close(); ?>
