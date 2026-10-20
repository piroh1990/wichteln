<?php

function mail_esc($value) {
    return htmlspecialchars((string) $value);
}

function mail_run($lang, $callback) {
    if ($lang === null || $lang === '') {
        return call_user_func($callback);
    }
    return i18n_with_lang($lang, $callback);
}

function mail_data_lang($data) {
    if (!is_array($data) || !isset($data['language'])) {
        return null;
    }
    if (!i18n_is_allowed($data['language'])) {
        return null;
    }
    return i18n_allow($data['language']);
}

function mail_home_url() {
    return i18n_absolute_public_url('index', i18n_current());
}

function render_email_template($title, $preview_text, $body_content) {
    $home = mail_home_url();
    $year_line = t('mail.year', array(
        '{year}' => date('Y'),
        '{tagline}' => t('mail.tagline'),
    ));
    return '<!DOCTYPE html>
<html lang="' . h(i18n_html_lang()) . '">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . mail_esc($title) . '</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, \'Helvetica Neue\', Arial, sans-serif; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <table width="100%" cellpadding="0" cellspacing="0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 16px; box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12); overflow: hidden; max-width: 100%;">
                    <tr>
                        <td style="background: linear-gradient(135deg, #264653 0%, #2a9d8f 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-family: Georgia, serif; font-size: 32px; font-weight: 700;">🎁 ' . mail_esc(t('mail.brand')) . '</h1>
                            <p style="margin: 10px 0 0 0; color: rgba(255, 255, 255, 0.9); font-size: 16px;">' . mail_esc($preview_text) . '</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px 30px;">
                            ' . $body_content . '
                        </td>
                    </tr>
                    <tr>
                        <td style="background: #264653; padding: 25px 30px; text-align: center;">
                            <p style="margin: 0; color: rgba(255, 255, 255, 0.8); font-size: 13px;">' . mail_esc(t('mail.from_signature')) . '</p>
                            <p style="margin: 8px 0 0 0; color: rgba(255, 255, 255, 0.6); font-size: 12px;">' . mail_esc($year_line) . '</p>
                            <p style="margin: 12px 0 0 0; font-size: 12px;"><a href="' . mail_esc($home) . '" style="color: #ffffff;">' . mail_esc(t('mail.home')) . '</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
}

function mail_p($html) {
    return '<p style="margin: 0 0 16px 0; color: #2b2d42; font-size: 16px; line-height: 1.6;">' . $html . '</p>';
}

function mail_button($url, $label) {
    $url = (string) $url;
    if ($url === '') {
        return '';
    }
    return '<p style="margin: 24px 0; text-align: center;"><a href="' . mail_esc($url) . '" style="display: inline-block; background: #e63946; color: #ffffff; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-weight: 700;">' . mail_esc($label) . '</a></p>';
}

function mail_details_html($budget, $description, $gift_date) {
    $row = function ($label, $value) {
        return '<tr><td style="padding: 8px 0; color: #6c757d; width: 40%;">' . mail_esc($label) . '</td><td style="padding: 8px 0; color: #2b2d42; font-weight: 600;">' . mail_esc($value) . '</td></tr>';
    };
    return '<div style="background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;">'
        . '<h3 style="margin: 0 0 12px 0; color: #2b2d42;">' . mail_esc(t('mail.details')) . '</h3>'
        . '<table style="width: 100%; border-collapse: collapse;">'
        . $row(t('mail.budget'), $budget)
        . $row(t('mail.description'), $description)
        . $row(t('mail.gift'), $gift_date)
        . '</table></div>';
}

function email_calendar_paragraph($url) {
    $url = (string) $url;
    if ($url === '') {
        return '';
    }
    return mail_button($url, t('mail.calendar'));
}

function mail_plain_details($budget, $description, $gift_date, $ics_url) {
    $lines = array(
        t('mail.budget') . ' ' . $budget,
        t('mail.description') . ' ' . $description,
        t('mail.gift') . ' ' . $gift_date,
    );
    if ((string) $ics_url !== '') {
        $lines[] = t('mail.calendar_plain', array('{url}' => $ics_url));
    }
    return $lines;
}

function mail_plain_footer() {
    return array(
        t('mail.signoff_plain'),
        t('mail.from_signature'),
        mail_home_url(),
    );
}

function mail_participant_link($token) {
    $token = trim((string) $token);
    if ($token === '') {
        return '';
    }
    return get_display_url('/participant.php?token=' . rawurlencode($token));
}

function mail_area_html($link, $prefix) {
    $link = trim((string) $link);
    if ($link === '') {
        return '';
    }
    return mail_p(mail_esc(t($prefix . '.link_help')))
        . mail_button($link, t($prefix . '.button'))
        . '<p style="margin: 0 0 16px 0; text-align: center; word-break: break-all; font-size: 14px; line-height: 1.5;"><a href="' . mail_esc($link) . '" style="color: #2a9d8f;">' . mail_esc($link) . '</a></p>';
}

function mail_area_plain($link, $prefix) {
    $link = trim((string) $link);
    if ($link === '') {
        return array();
    }
    return array(
        t($prefix . '.link_help'),
        t($prefix . '.button_plain', array('{url}' => $link)),
    );
}

function create_html_email($data) {
    return mail_run(mail_data_lang($data), function () use ($data) {
        $name = isset($data['name']) ? $data['name'] : '';
        $assigned = isset($data['assigned_name']) ? $data['assigned_name'] : '';
        $wishlist = isset($data['wishlist']) ? $data['wishlist'] : '';
        $budget = isset($data['budget']) ? $data['budget'] : '';
        $description = isset($data['description']) ? $data['description'] : '';
        $gift_date = isset($data['gift_date']) ? $data['gift_date'] : '';
        $ics = isset($data['ics_url']) ? $data['ics_url'] : '';

        $body = mail_p(t('mail.greeting', array('{name}' => '<strong>' . mail_esc($name) . '</strong>')));
        $body .= '<div style="background: #fff5f5; border-left: 4px solid #e63946; padding: 16px 18px; margin: 0 0 20px 0;">'
            . '<p style="margin: 0; color: #6c757d; font-size: 13px;">' . mail_esc(t('mail.partner.label')) . '</p>'
            . '<p style="margin: 6px 0 0 0; color: #e63946; font-size: 24px; font-weight: 700;">' . mail_esc($assigned) . '</p>'
            . '</div>';
        if (trim((string) $wishlist) !== '') {
            $body .= '<h3 style="margin: 0 0 8px 0; color: #2b2d42;">' . mail_esc(t('mail.partner.wishlist', array('{name}' => $assigned))) . '</h3>';
            $body .= '<div style="background: #f8f9fa; border-radius: 8px; padding: 16px; margin: 0 0 16px 0; white-space: pre-wrap;">' . nl2br(mail_esc($wishlist)) . '</div>';
        }
        $body .= mail_details_html($budget, $description, $gift_date);
        $body .= email_calendar_paragraph($ics);
        $body .= mail_area_html(isset($data['participant_link']) ? $data['participant_link'] : '', 'mail.partner');
        if (!empty($data['affiliate'])) {
            $body .= affiliate_of_mail_html('mail-draw');
        }
        $body .= mail_p(mail_esc(t('mail.signoff')));
        return render_email_template(t('mail.partner.title'), t('mail.partner.preview'), $body);
    });
}

function create_partner_plain_text($data) {
    return mail_run(mail_data_lang($data), function () use ($data) {
        $name = isset($data['name']) ? $data['name'] : '';
        $assigned = isset($data['assigned_name']) ? $data['assigned_name'] : '';
        $wishlist = isset($data['wishlist']) ? trim((string) (isset($data['wishlist']) ? $data['wishlist'] : '')) : '';
        $lines = array(
            t('mail.greeting', array('{name}' => $name)),
            '',
            t('mail.partner.label') . ': ' . $assigned,
        );
        if ($wishlist !== '') {
            $lines[] = '';
            $lines[] = t('mail.partner.wishlist', array('{name}' => $assigned));
            $lines[] = $wishlist;
        }
        $lines[] = '';
        $lines = array_merge($lines, mail_plain_details(
            isset($data['budget']) ? $data['budget'] : '',
            isset($data['description']) ? $data['description'] : '',
            isset($data['gift_date']) ? $data['gift_date'] : '',
            isset($data['ics_url']) ? $data['ics_url'] : ''
        ));
        $area = mail_area_plain(isset($data['participant_link']) ? $data['participant_link'] : '', 'mail.partner');
        if (count($area) > 0) {
            $lines[] = '';
            $lines = array_merge($lines, $area);
        }
        if (!empty($data['affiliate'])) {
            $promo = affiliate_of_mail_plain('mail-draw');
            if (count($promo) > 0) {
                $lines[] = '';
                $lines = array_merge($lines, $promo);
            }
        }
        $lines[] = '';
        $lines = array_merge($lines, mail_plain_footer());
        return implode("\n", $lines);
    });
}

function partner_email_subject($lang = null) {
    return mail_run($lang, function () {
        return t('mail.partner.subject');
    });
}

function create_registration_email($data) {
    return mail_run(mail_data_lang($data), function () use ($data) {
        $name = isset($data['name']) ? $data['name'] : '';
        $group = isset($data['group_name']) ? $data['group_name'] : '';
        $link = isset($data['participant_link']) ? $data['participant_link'] : '';
        $body = mail_p(t('mail.greeting', array('{name}' => '<strong>' . mail_esc($name) . '</strong>')));
        $body .= mail_p(t('mail.registration.intro', array('{group}' => mail_esc($group))));
        $body .= mail_details_html(
            isset($data['budget']) ? $data['budget'] : '',
            isset($data['description']) ? $data['description'] : '',
            isset($data['gift_date']) ? $data['gift_date'] : ''
        );
        $body .= '<h3 style="margin: 0 0 8px 0;">' . mail_esc(t('mail.registration.link')) . '</h3>';
        $body .= mail_p(mail_esc(t('mail.registration.link_help')));
        $body .= '<p style="word-break: break-all;"><a href="' . mail_esc($link) . '">' . mail_esc($link) . '</a></p>';
        $body .= mail_button($link, t('mail.registration.button'));
        $body .= mail_p(mail_esc(t('mail.registration.tip')));
        $body .= mail_p(mail_esc(t('mail.signoff')));
        return render_email_template(t('mail.registration.title'), t('mail.registration.preview'), $body);
    });
}

function create_registration_plain_text($data) {
    return mail_run(mail_data_lang($data), function () use ($data) {
        $link = isset($data['participant_link']) ? $data['participant_link'] : '';
        $lines = array(
            t('mail.greeting', array('{name}' => isset($data['name']) ? $data['name'] : '')),
            '',
            t('mail.registration.intro', array('{group}' => isset($data['group_name']) ? $data['group_name'] : '')),
            '',
        );
        $lines = array_merge($lines, mail_plain_details(
            isset($data['budget']) ? $data['budget'] : '',
            isset($data['description']) ? $data['description'] : '',
            isset($data['gift_date']) ? $data['gift_date'] : '',
            ''
        ));
        $lines[] = '';
        $lines[] = t('mail.registration.link');
        $lines[] = t('mail.registration.link_help');
        $lines[] = $link;
        $lines[] = t('mail.registration.button_plain', array('{url}' => $link));
        $lines[] = '';
        $lines[] = t('mail.registration.tip');
        $lines[] = '';
        $lines = array_merge($lines, mail_plain_footer());
        return implode("\n", $lines);
    });
}

function registration_email_subject($lang = null) {
    return mail_run($lang, function () {
        return t('mail.registration.subject');
    });
}

function create_admin_email($data) {
    return mail_run(mail_data_lang($data), function () use ($data) {
        $group = isset($data['group_name']) ? $data['group_name'] : '';
        $admin = isset($data['admin_link']) ? $data['admin_link'] : '';
        $invite = isset($data['invite_link']) ? $data['invite_link'] : '';
        $body = mail_p(mail_esc(t('mail.greeting_admin')));
        $body .= mail_p(t('mail.admin.intro', array('{group}' => mail_esc($group))));
        $body .= mail_details_html(
            isset($data['budget']) ? $data['budget'] : '',
            isset($data['description']) ? $data['description'] : '',
            isset($data['gift_date']) ? $data['gift_date'] : ''
        );
        $body .= '<h3>' . mail_esc(t('mail.admin.box')) . '</h3>';
        $body .= mail_p(mail_esc(t('mail.admin.help')));
        $body .= '<p style="word-break: break-all;"><a href="' . mail_esc($admin) . '">' . mail_esc($admin) . '</a></p>';
        $body .= mail_button($admin, t('mail.admin.button'));
        $body .= mail_p(mail_esc(t('mail.admin.warning')));
        $body .= '<h3>' . mail_esc(t('mail.admin.invite')) . '</h3>';
        $body .= mail_p(mail_esc(t('mail.admin.invite_help')));
        $body .= '<p style="word-break: break-all;"><a href="' . mail_esc($invite) . '">' . mail_esc($invite) . '</a></p>';
        $body .= '<h3>' . mail_esc(t('mail.admin.next')) . '</h3><ol>'
            . '<li>' . mail_esc(t('mail.admin.step1')) . '</li>'
            . '<li>' . mail_esc(t('mail.admin.step2')) . '</li>'
            . '<li>' . mail_esc(t('mail.admin.step3')) . '</li>'
            . '<li>' . mail_esc(t('mail.admin.step4')) . '</li>'
            . '</ol>';
        $body .= mail_p(mail_esc(t('mail.signoff')));
        return render_email_template(t('mail.admin.title'), t('mail.admin.preview'), $body);
    });
}

function create_admin_plain_text($data) {
    return mail_run(mail_data_lang($data), function () use ($data) {
        $admin = isset($data['admin_link']) ? $data['admin_link'] : '';
        $invite = isset($data['invite_link']) ? $data['invite_link'] : '';
        $lines = array(
            t('mail.greeting_admin'),
            '',
            t('mail.admin.intro', array('{group}' => isset($data['group_name']) ? $data['group_name'] : '')),
            '',
        );
        $lines = array_merge($lines, mail_plain_details(
            isset($data['budget']) ? $data['budget'] : '',
            isset($data['description']) ? $data['description'] : '',
            isset($data['gift_date']) ? $data['gift_date'] : '',
            ''
        ));
        $lines[] = '';
        $lines[] = t('mail.admin.box');
        $lines[] = t('mail.admin.help');
        $lines[] = t('mail.admin.button_plain', array('{url}' => $admin));
        $lines[] = t('mail.admin.warning');
        $lines[] = '';
        $lines[] = t('mail.admin.invite');
        $lines[] = t('mail.admin.invite_help');
        $lines[] = t('mail.admin.invite_plain', array('{url}' => $invite));
        $lines[] = '';
        $lines[] = t('mail.admin.next');
        $lines[] = '1. ' . t('mail.admin.step1');
        $lines[] = '2. ' . t('mail.admin.step2');
        $lines[] = '3. ' . t('mail.admin.step3');
        $lines[] = '4. ' . t('mail.admin.step4');
        $lines[] = '';
        $lines = array_merge($lines, mail_plain_footer());
        return implode("\n", $lines);
    });
}

function admin_created_email_subject($group_name, $lang = null) {
    return mail_run($lang, function () use ($group_name) {
        return t('mail.admin.subject', array('{group}' => $group_name));
    });
}

function build_created_group_mail(array $group) {
    $email = isset($group['admin_email']) ? trim((string) $group['admin_email']) : '';
    $admin_token = isset($group['admin_token']) ? (string) $group['admin_token'] : '';
    $invite_token = isset($group['invite_token']) ? (string) $group['invite_token'] : '';
    if ($email === '' || $admin_token === '' || $invite_token === '') {
        return null;
    }
    $lang = 'de';
    if (isset($group['language']) && function_exists('i18n_is_allowed') && i18n_is_allowed($group['language'])) {
        $lang = $group['language'];
    }
    $group_name = isset($group['name']) ? (string) $group['name'] : '';
    $admin_link = get_display_url('/admin.php?token=' . urlencode($admin_token));
    $invite_link = get_display_url('/register.php?token=' . urlencode($invite_token));
    $fields = mail_present_group($group, $lang);
    $mail_data = array(
        'group_name' => $group_name,
        'admin_link' => $admin_link,
        'invite_link' => $invite_link,
        'budget' => $fields['budget'],
        'description' => $fields['description'],
        'gift_date' => $fields['gift_date'],
        'ics_url' => $fields['ics_url'],
        'language' => $lang,
    );
    return array(
        'to' => $email,
        'subject' => admin_created_email_subject($group_name, $lang),
        'html' => create_admin_email($mail_data),
        'plain' => create_admin_plain_text($mail_data),
        'admin_link' => $admin_link,
    );
}

function send_created_group_mail(array $group) {
    $mail = build_created_group_mail($group);
    if ($mail === null) {
        return false;
    }
    $sent = send_email($mail['to'], $mail['subject'], $mail['html'], true, $mail['plain']);
    if (!$sent && function_exists('mask_email')) {
        error_log('E-Mail mit Admin-Link konnte nicht an ' . mask_email($mail['to']) . ' gesendet werden.');
    }
    return $sent;
}

function create_wishlist_update_email($giver_name, $updater_name, $wishlist, $lang = null) {
    return mail_run($lang, function () use ($giver_name, $updater_name, $wishlist) {
        $wishlist = (string) $wishlist;
        $body = mail_p(t('mail.greeting', array('{name}' => '<strong>' . mail_esc($giver_name) . '</strong>')));
        $body .= mail_p(t('mail.wishlist.intro', array('{name}' => mail_esc($updater_name))));
        $body .= '<h3>' . mail_esc(t('mail.wishlist.current', array('{name}' => $updater_name))) . '</h3>';
        if (trim($wishlist) === '') {
            $body .= mail_p(mail_esc(t('mail.wishlist.empty')));
        } else {
            $body .= '<div style="background: #f8f9fa; border-radius: 8px; padding: 16px; white-space: pre-wrap;">' . nl2br(mail_esc($wishlist)) . '</div>';
        }
        $body .= mail_p(mail_esc(t('mail.signoff')));
        return render_email_template(t('mail.wishlist.title'), t('mail.wishlist.preview'), $body);
    });
}

function create_wishlist_plain_text($giver_name, $updater_name, $wishlist, $lang = null) {
    return mail_run($lang, function () use ($giver_name, $updater_name, $wishlist) {
        $wishlist = trim((string) $wishlist);
        $lines = array(
            t('mail.greeting', array('{name}' => $giver_name)),
            '',
            t('mail.wishlist.intro', array('{name}' => $updater_name)),
            '',
            t('mail.wishlist.current', array('{name}' => $updater_name)),
            ($wishlist === '') ? t('mail.wishlist.empty') : $wishlist,
            '',
        );
        $lines = array_merge($lines, mail_plain_footer());
        return implode("\n", $lines);
    });
}

function wishlist_email_subject($lang = null) {
    return mail_run($lang, function () {
        return t('mail.wishlist.subject');
    });
}

function reveal_email_subject($lang = null) {
    return mail_run($lang, function () {
        return t('mail.reveal.subject');
    });
}

function create_reveal_email($data) {
    return mail_run(mail_data_lang($data), function () use ($data) {
        $name = isset($data['name']) ? $data['name'] : '';
        $group = isset($data['group_name']) ? $data['group_name'] : '';
        $pairs = (isset($data['pairs']) && is_array($data['pairs'])) ? $data['pairs'] : array();
        $count = count($pairs);
        $legend = t('mail.reveal.legend', array('{pairs}' => tn('mail.reveal.pairs', $count)));

        $body = mail_p(t('mail.greeting', array('{name}' => '<strong>' . h($name) . '</strong>')));
        $body .= mail_p(h(t('mail.reveal.intro')));
        $body .= mail_p(t('mail.reveal.body', array('{group}' => h($group))));
        $body .= mail_p(h($legend));

        if ($count === 0) {
            $body .= mail_p(h(t('mail.reveal.empty')));
        } else {
            $body .= '<table style="width: 100%; border-collapse: collapse;">';
            foreach ($pairs as $pair) {
                $giver = isset($pair['giver_name']) ? $pair['giver_name'] : '';
                $receiver = isset($pair['receiver_name']) ? $pair['receiver_name'] : '';
                $body .= '<tr>'
                    . '<td style="padding: 8px 0;">' . h(t('mail.reveal.giver')) . '<br><strong>' . h($giver) . '</strong></td>'
                    . '<td style="padding: 8px 12px;">' . h(t('mail.reveal.gives')) . '</td>'
                    . '<td style="padding: 8px 0;">' . h(t('mail.reveal.receiver')) . '<br><strong>' . h($receiver) . '</strong></td>'
                    . '</tr>';
            }
            $body .= '</table>';
            $body .= mail_p(h(t('mail.reveal.note')));
        }
        $body .= mail_area_html(isset($data['participant_link']) ? $data['participant_link'] : '', 'mail.reveal');
        $body .= mail_p(h(t('mail.signoff')));
        return render_email_template(t('mail.reveal.title'), t('mail.reveal.preview'), $body);
    });
}

function create_reveal_plain_text($data) {
    return mail_run(mail_data_lang($data), function () use ($data) {
        $name = isset($data['name']) ? $data['name'] : '';
        $group = isset($data['group_name']) ? $data['group_name'] : '';
        $pairs = (isset($data['pairs']) && is_array($data['pairs'])) ? $data['pairs'] : array();
        $lines = array(
            t('mail.greeting', array('{name}' => $name)),
            '',
            t('mail.reveal.plain_intro'),
            t('mail.reveal.plain_body', array('{group}' => $group)),
            t('mail.reveal.plain_legend'),
            t('mail.reveal.plain_arrow'),
        );
        foreach ($pairs as $pair) {
            $lines[] = t('mail.reveal.plain_pair', array(
                '{giver}' => isset($pair['giver_name']) ? $pair['giver_name'] : '',
                '{receiver}' => isset($pair['receiver_name']) ? $pair['receiver_name'] : '',
            ));
        }
        $area = mail_area_plain(isset($data['participant_link']) ? $data['participant_link'] : '', 'mail.reveal');
        if (count($area) > 0) {
            $lines[] = '';
            $lines = array_merge($lines, $area);
        }
        $lines[] = '';
        $lines = array_merge($lines, mail_plain_footer());
        return implode("\n", $lines);
    });
}

function gift_reminder_sentence($days, $lang = null) {
    if ($days === null) {
        return '';
    }
    $days = (int) $days;
    return mail_run($lang, function () use ($days) {
        if ($days > 1) {
            return tn('reminder.in_days', $days);
        }
        if ($days === 1) {
            return t('reminder.tomorrow');
        }
        if ($days === 0) {
            return t('reminder.today');
        }
        if ($days === -1) {
            return t('reminder.yesterday');
        }
        return tn('reminder.past', abs($days));
    });
}

function reminder_email_subject($sentence, $group_name, $lang = null) {
    return mail_run($lang, function () use ($sentence, $group_name) {
        $sentence = rtrim((string) $sentence, '.');
        $group_name = str_replace(array("\r", "\n"), ' ', (string) $group_name);
        if ($group_name === '') {
            return $sentence;
        }
        return t('mail.reminder.subject_group', array(
            '{sentence}' => $sentence,
            '{group}' => $group_name,
        ));
    });
}

function create_gift_reminder_email($data) {
    return mail_run(mail_data_lang($data), function () use ($data) {
        $name = isset($data['name']) ? $data['name'] : '';
        $sentence = isset($data['sentence']) ? $data['sentence'] : '';
        $group = isset($data['group_name']) ? $data['group_name'] : '';
        $date = isset($data['gift_date']) ? $data['gift_date'] : '';
        $link = isset($data['participant_link']) ? $data['participant_link'] : '';
        $ics = isset($data['ics_url']) ? $data['ics_url'] : '';
        $body = mail_p(t('mail.greeting', array('{name}' => '<strong>' . mail_esc($name) . '</strong>')));
        $body .= mail_p(mail_esc($sentence));
        $body .= mail_p(mail_esc(t('mail.group', array('{name}' => $group))));
        $body .= mail_p(mail_esc(t('mail.date_line', array('{date}' => $date))));
        $body .= mail_button($link, t('mail.reminder.button'));
        $body .= email_calendar_paragraph($ics);
        if (!empty($data['affiliate'])) {
            $body .= affiliate_of_mail_html('mail-reminder');
        }
        $body .= mail_p(mail_esc(t('mail.signoff')));
        return render_email_template(t('mail.reminder.title'), $sentence, $body);
    });
}

function create_gift_reminder_plain_text($data) {
    return mail_run(mail_data_lang($data), function () use ($data) {
        $link = isset($data['participant_link']) ? $data['participant_link'] : '';
        $lines = array(
            t('mail.greeting', array('{name}' => isset($data['name']) ? $data['name'] : '')),
            '',
            isset($data['sentence']) ? $data['sentence'] : '',
            t('mail.group', array('{name}' => isset($data['group_name']) ? $data['group_name'] : '')),
            t('mail.date_line', array('{date}' => isset($data['gift_date']) ? $data['gift_date'] : '')),
            '',
            t('mail.reminder.button_plain', array('{url}' => $link)),
        );
        if (!empty($data['ics_url'])) {
            $lines[] = t('mail.calendar_plain', array('{url}' => $data['ics_url']));
        }
        if (!empty($data['affiliate'])) {
            $promo = affiliate_of_mail_plain('mail-reminder');
            if (count($promo) > 0) {
                $lines[] = '';
                $lines = array_merge($lines, $promo);
            }
        }
        $lines[] = '';
        $lines = array_merge($lines, mail_plain_footer());
        return implode("\n", $lines);
    });
}

function create_admin_recovery_email($groups, $lang = null) {
    return mail_run($lang, function () use ($groups) {
        if (!is_array($groups)) {
            $groups = array();
        }
        $body = mail_p(mail_esc(t('mail.greeting_plain')));
        $body .= mail_p(mail_esc(t('mail.recovery.intro')));
        foreach ($groups as $group) {
            $name = isset($group['name']) ? $group['name'] : '';
            $link = isset($group['admin_link']) ? $group['admin_link'] : '';
            $body .= '<h3 style="margin: 18px 0 6px 0;">' . mail_esc($name) . '</h3>';
            $body .= '<p style="word-break: break-all;"><a href="' . mail_esc($link) . '">' . mail_esc($link) . '</a></p>';
        }
        $body .= mail_p(mail_esc(t('mail.signoff')));
        return render_email_template(t('mail.recovery.title'), t('mail.recovery.preview'), $body);
    });
}

function create_admin_recovery_plain_text($groups, $lang = null) {
    return mail_run($lang, function () use ($groups) {
        if (!is_array($groups)) {
            $groups = array();
        }
        $lines = array(
            t('mail.greeting_plain'),
            '',
            t('mail.recovery.plain_intro'),
            '',
        );
        foreach ($groups as $group) {
            $lines[] = (isset($group['name']) ? $group['name'] : '') . ': ' . (isset($group['admin_link']) ? $group['admin_link'] : '');
        }
        $lines[] = '';
        $lines = array_merge($lines, mail_plain_footer());
        return implode("\n", $lines);
    });
}

function admin_recovery_email_subject($lang = null) {
    return mail_run($lang, function () {
        return t('mail.recovery.subject');
    });
}

function mail_present_group($group, $lang = null) {
    if (!is_array($group)) {
        $group = array();
    }
    if ($lang === null) {
        $lang = i18n_current();
    }
    return i18n_with_lang($lang, function () use ($group) {
        $budget = (isset($group['budget']) && $group['budget'] !== null && $group['budget'] !== '')
            ? fmt_money($group['budget'])
            : t('value.unset');
        $description = (isset($group['description']) && trim((string) $group['description']) !== '')
            ? (string) $group['description']
            : t('value.no_description');
        $gift = !empty($group['gift_exchange_date']) ? fmt_date($group['gift_exchange_date']) : t('value.unset');
        $ics = !empty($group['gift_exchange_date']) ? gift_ics_url($group, i18n_current()) : '';
        return array(
            'budget' => $budget,
            'description' => $description,
            'gift_date' => $gift,
            'ics_url' => $ics,
        );
    });
}

function mail_recipient_lang($row) {
    $lang = i18n_row_language($row);
    if ($lang === null) {
        return 'de';
    }
    return $lang;
}

function send_registration_mail($email, $name, $group, $participant_link, $lang) {
    $lang = i18n_allow($lang);
    $fields = mail_present_group($group, $lang);
    $data = array(
        'name' => $name,
        'group_name' => (is_array($group) && isset($group['name'])) ? $group['name'] : '',
        'participant_link' => $participant_link,
        'budget' => $fields['budget'],
        'description' => $fields['description'],
        'gift_date' => $fields['gift_date'],
        'ics_url' => $fields['ics_url'],
        'language' => $lang,
    );
    return send_email(
        $email,
        registration_email_subject($lang),
        create_registration_email($data),
        true,
        create_registration_plain_text($data)
    );
}

function send_partner_mail($email, $giver_name, $assigned_name, $wishlist, $group, $lang, $participant_token = null) {
    $lang = i18n_allow($lang);
    $fields = mail_present_group($group, $lang);
    $data = array(
        'name' => $giver_name,
        'assigned_name' => $assigned_name,
        'wishlist' => $wishlist,
        'budget' => $fields['budget'],
        'description' => $fields['description'],
        'gift_date' => $fields['gift_date'],
        'ics_url' => $fields['ics_url'],
        'participant_link' => mail_participant_link($participant_token),
        'language' => $lang,
        'affiliate' => affiliate_of_show_for_group(is_array($group) ? $group : array()),
    );
    return send_email(
        $email,
        partner_email_subject($lang),
        create_html_email($data),
        true,
        create_partner_plain_text($data)
    );
}

function delivery_flash_text($sent_key, $none_key, $status, $sent, $failed, $skipped) {
    if ($status === 'failed') {
        return t($none_key) . ' ' . tn('flash.failed_clause', $failed) . '.';
    }
    $text = tn($sent_key, $sent);
    $extra = array();
    if ($skipped > 0) {
        $extra[] = tn('flash.skipped', $skipped);
    }
    if ($failed > 0) {
        $extra[] = tn('flash.failed_clause', $failed);
    }
    if (count($extra) > 0) {
        $text .= ' ' . implode('. ', $extra) . '.';
    }
    return $text;
}
