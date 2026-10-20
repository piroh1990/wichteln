<?php
/**
 * Werbelinks zu Orell Füssli über Awin.
 * Auf der Seite liegt kein Skript und kein Cookie. Es sind nur Links.
 */

function affiliate_of_enabled() {
    if (array_key_exists('affiliate_of_enabled_override', $GLOBALS)) {
        return (bool) $GLOBALS['affiliate_of_enabled_override'];
    }
    if (!defined('AFFILIATE_OF_ENABLED')) {
        return false;
    }
    $value = strtolower(trim((string) AFFILIATE_OF_ENABLED));
    return in_array($value, array('1', 'true', 'yes', 'on'), true);
}

function affiliate_of_setting($name, $default) {
    if (!defined($name)) {
        return $default;
    }
    $value = trim((string) constant($name));
    if ($value === '') {
        return $default;
    }
    if (strlen($value) >= 3 && $value[0] === "\xEF" && $value[1] === "\xBB" && $value[2] === "\xBF") {
        $value = trim(substr($value, 3));
    }
    $value = trim($value, " \t\"'");
    return $value === '' ? $default : $value;
}

function affiliate_of_ids() {
    $mid = affiliate_of_setting('AFFILIATE_OF_AWINMID', '13971');
    $affid = affiliate_of_setting('AFFILIATE_OF_AWINAFFID', '3128240');
    if (preg_match('/\A[0-9]{1,12}\z/', $mid) !== 1 || preg_match('/\A[0-9]{1,12}\z/', $affid) !== 1) {
        return null;
    }
    return array('mid' => $mid, 'affid' => $affid);
}

function affiliate_of_http_url($url) {
    $url = trim((string) $url);
    if (preg_match('#\Ahttps://#', $url) !== 1) {
        return '';
    }
    if (preg_match('/[\s<>"\']/', $url) === 1) {
        return '';
    }
    return $url;
}

function affiliate_of_search_base() {
    return affiliate_of_http_url(affiliate_of_setting(
        'AFFILIATE_OF_SEARCH_URL',
        'https://www.orellfuessli.ch/suche?sq='
    ));
}

function affiliate_of_gift_url() {
    return affiliate_of_http_url(affiliate_of_setting(
        'AFFILIATE_OF_GIFT_URL',
        'https://www.orellfuessli.ch/kategorie/geschenke-trends-17794/'
    ));
}

function affiliate_of_clickref($clickref) {
    $clickref = strtolower(trim((string) $clickref));
    if (preg_match('/\A[a-z0-9-]{1,40}\z/', $clickref) !== 1) {
        return '';
    }
    return $clickref;
}

function affiliate_of_link($target, $clickref = '') {
    $ids = affiliate_of_ids();
    $target = affiliate_of_http_url($target);
    if ($ids === null || $target === '') {
        return '';
    }
    $url = 'https://www.awin1.com/cread.php?awinmid=' . $ids['mid']
        . '&awinaffid=' . $ids['affid']
        . '&ued=' . rawurlencode($target);
    $ref = affiliate_of_clickref($clickref);
    if ($ref !== '') {
        $url .= '&clickref=' . rawurlencode($ref);
    }
    return $url;
}

function affiliate_of_search_term($text) {
    $text = str_replace(array("\r", "\n", "\t"), ' ', (string) $text);
    $text = preg_replace('/\s+/u', ' ', $text);
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }
    if (!function_exists('mb_strlen') || !function_exists('mb_substr')) {
        if (strlen($text) <= 80) {
            return $text;
        }
        $short = substr($text, 0, 80);
        $space = strrpos($short, ' ');
        if ($space !== false && $space >= 40) {
            $short = substr($short, 0, $space);
        }
        return rtrim($short, " \t.,;:-");
    }
    if (mb_strlen($text, 'UTF-8') <= 80) {
        return $text;
    }
    $short = mb_substr($text, 0, 80, 'UTF-8');
    $space = mb_strrpos($short, ' ', 0, 'UTF-8');
    if ($space !== false && $space >= 40) {
        $short = mb_substr($short, 0, $space, 'UTF-8');
    }
    return rtrim($short, " \t.,;:-");
}

function affiliate_of_wish_lines($wishlist) {
    $parts = preg_split("/\r\n|\n|\r/", (string) $wishlist);
    if (!is_array($parts)) {
        return array();
    }
    $lines = array();
    foreach ($parts as $line) {
        $line = trim((string) $line);
        $line = preg_replace('/^(?:[-*•]+|\d+[.)])\s+/u', '', $line);
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }
        $lines[] = $line;
    }
    return $lines;
}

function affiliate_of_search_target($wish) {
    $base = affiliate_of_search_base();
    $term = affiliate_of_search_term($wish);
    if ($base === '' || $term === '') {
        return '';
    }
    return $base . rawurlencode($term);
}

function affiliate_of_column_ready(PDO $pdo) {
    try {
        $pdo->query('SELECT `affiliate_of` FROM `groups` WHERE 1 = 0');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function affiliate_of_is_premium_group($group) {
    return is_array($group) && !empty($group['premium_license_id']);
}

function affiliate_of_opt_in($group) {
    if (!is_array($group) || !isset($group['affiliate_of'])) {
        return false;
    }
    return (string) $group['affiliate_of'] === '1';
}

function affiliate_of_show_for_group($group) {
    if (!affiliate_of_enabled() || !is_array($group)) {
        return false;
    }
    if (affiliate_of_is_premium_group($group)) {
        return affiliate_of_opt_in($group);
    }
    return true;
}

function affiliate_of_anchor($url, $label) {
    if ($url === '' || !function_exists('h')) {
        return '';
    }
    return '<a class="affiliate-of-link" href="' . h($url) . '" rel="sponsored noopener" target="_blank">' . h($label) . '</a>';
}

function affiliate_of_note_html() {
    return '<p class="affiliate-note">' . h(t('affiliate.note')) . '</p>';
}

function affiliate_of_participant_html($group, $wishlist) {
    if (!affiliate_of_show_for_group($group) || !function_exists('t')) {
        return '';
    }
    $lines = affiliate_of_wish_lines($wishlist);
    if (count($lines) === 0) {
        $url = affiliate_of_link(affiliate_of_gift_url(), 'reveal-page');
        if ($url === '') {
            return '';
        }
        return '<p class="affiliate-of-gift">' . affiliate_of_anchor($url, t('affiliate.gift')) . '</p>' . affiliate_of_note_html();
    }
    $html = '<ul class="affiliate-wish-list">';
    foreach ($lines as $line) {
        $url = affiliate_of_link(affiliate_of_search_target($line), 'wish');
        $html .= '<li><span class="affiliate-wish-text">' . h($line) . '</span>';
        if ($url !== '') {
            $html .= affiliate_of_anchor($url, t('affiliate.wish'));
        }
        $html .= '</li>';
    }
    $html .= '</ul>';
    $html .= affiliate_of_note_html();
    return $html;
}

function affiliate_of_mail_html($clickref) {
    if (!function_exists('t') || !function_exists('mail_esc')) {
        return '';
    }
    $url = affiliate_of_link(affiliate_of_gift_url(), $clickref);
    if ($url === '') {
        return '';
    }
    $html = '<p style="margin: 18px 0 8px 0; color: #6c757d; font-size: 14px;">' . mail_esc(t('affiliate.mail.lead')) . '</p>';
    $html .= '<p style="margin: 0 0 8px 0; font-size: 14px;"><a href="' . mail_esc($url) . '" rel="sponsored noopener" target="_blank">' . mail_esc(t('affiliate.mail.link')) . '</a></p>';
    $html .= '<p style="margin: 0 0 16px 0; color: #6c757d; font-size: 12px;">' . mail_esc(t('affiliate.note')) . '</p>';
    return $html;
}

function affiliate_of_mail_plain($clickref) {
    if (!function_exists('t')) {
        return array();
    }
    $url = affiliate_of_link(affiliate_of_gift_url(), $clickref);
    if ($url === '') {
        return array();
    }
    return array(
        t('affiliate.mail.lead'),
        t('affiliate.mail.link') . ': ' . $url,
        t('affiliate.note'),
    );
}

function affiliate_of_admin_toggle_html(PDO $pdo, array $group) {
    if (!affiliate_of_enabled() || !affiliate_of_is_premium_group($group) || !function_exists('t') || !function_exists('h')) {
        return '';
    }
    if (!affiliate_of_column_ready($pdo)) {
        return '<p class="section-description">' . h(t('affiliate.migration')) . '</p>';
    }
    $checked = affiliate_of_opt_in($group) ? ' checked' : '';
    $html = '<form method="post" class="premium-inline-form affiliate-of-toggle">' . csrf_input();
    $html .= '<input type="hidden" name="premium_action" value="affiliate_of">';
    $html .= '<label class="reveal-confirm-label" for="affiliate-of"><input type="checkbox" id="affiliate-of" name="affiliate_of" value="1"' . $checked . '> ' . h(t('affiliate.toggle')) . '</label>';
    $html .= '<p class="section-description">' . h(t('affiliate.toggle_help')) . '</p>';
    $html .= '<button type="submit" class="button secondary">' . h(t('affiliate.save')) . '</button></form>';
    return $html;
}

function affiliate_of_admin_save(PDO $pdo, array $group, array $post) {
    $fail = array('ok' => false, 'reload' => false, 'notice' => array('type' => 'error', 'text' => t('affiliate.migration')));
    if (!affiliate_of_enabled() || !affiliate_of_is_premium_group($group)) {
        $fail['notice']['text'] = t('affiliate.toggle_help');
        return $fail;
    }
    if (!affiliate_of_column_ready($pdo)) {
        return $fail;
    }
    $on = (isset($post['affiliate_of']) && (string) $post['affiliate_of'] === '1') ? 1 : 0;
    $stmt = $pdo->prepare('UPDATE `groups` SET `affiliate_of` = ? WHERE `id` = ?');
    $stmt->execute(array($on, (int) $group['id']));
    return array(
        'ok' => true,
        'reload' => true,
        'notice' => array('type' => 'success', 'text' => t('affiliate.saved')),
    );
}
