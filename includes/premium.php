<?php

/**
 * Premium Firmen-Wichteln.
 * Aus, solange PREMIUM_ENABLED nicht gesetzt ist. Gleiches Muster wie SUPPORT_URL.
 */

function premium_load_config() {
    if (defined('PREMIUM_ENABLED') || defined('DB_HOST')) {
        return;
    }
    $config = __DIR__ . '/config.php';
    if (is_file($config)) {
        require_once $config;
    }
}

function premium_feature_enabled($override = null) {
    if ($override !== null) {
        return (bool) $override;
    }
    if (array_key_exists('premium_enabled_override', $GLOBALS)) {
        return (bool) $GLOBALS['premium_enabled_override'];
    }
    premium_load_config();
    if (!defined('PREMIUM_ENABLED')) {
        return false;
    }
    $raw = PREMIUM_ENABLED;
    if (is_bool($raw)) {
        return $raw;
    }
    if (is_int($raw)) {
        return $raw === 1;
    }
    if (!is_string($raw)) {
        return false;
    }
    return in_array(strtolower(trim($raw)), array('1', 'true', 'yes', 'on'), true);
}

function premium_public_status($override = null) {
    return premium_feature_enabled($override) ? 200 : 404;
}

function premium_unavailable_body() {
    return "<!DOCTYPE html>\n<html lang=\"de\"><head><meta charset=\"UTF-8\"><title>404</title></head><body><p>404</p></body></html>\n";
}

function premium_now($now = null) {
    if (is_int($now)) {
        return $now;
    }
    if (is_string($now) && preg_match('/\A\d+\z/', $now)) {
        return (int) $now;
    }
    return time();
}

function premium_sql_time($now = null) {
    return date('Y-m-d H:i:s', premium_now($now));
}

function premium_tier_definitions($override = null) {
    if (is_array($override)) {
        return $override;
    }
    premium_load_config();
    $tiers = array(
        'verein' => array('price' => 19, 'limit' => 3, 'greeting' => true, 'hide_credit' => true),
        'firma' => array('price' => 49, 'limit' => 10, 'greeting' => true, 'hide_credit' => true),
        'firma_plus' => array('price' => 99, 'limit' => null, 'greeting' => true, 'hide_credit' => true),
    );
    foreach ($tiers as $key => $tier) {
        $price_const = 'PREMIUM_PRICE_' . strtoupper($key);
        $limit_const = 'PREMIUM_LIMIT_' . strtoupper($key);
        if (defined($price_const)) {
            $price = constant($price_const);
            if (is_numeric($price) && (int) $price > 0) {
                $tiers[$key]['price'] = (int) $price;
            }
        }
        if (defined($limit_const)) {
            $limit = constant($limit_const);
            if ($limit === 'unlimited' || $limit === 0 || $limit === '0') {
                $tiers[$key]['limit'] = null;
            } elseif (is_numeric($limit) && (int) $limit > 0) {
                $tiers[$key]['limit'] = (int) $limit;
            }
        }
    }
    return $tiers;
}

function premium_tier($key, $definitions = null) {
    $definitions = ($definitions === null) ? premium_tier_definitions() : $definitions;
    $key = (string) $key;
    if (!isset($definitions[$key]) || !is_array($definitions[$key])) {
        return null;
    }
    return $definitions[$key];
}

function premium_known_tier($key, $definitions = null) {
    return premium_tier($key, $definitions) !== null;
}

function premium_has_room($tier, $used, $definitions = null) {
    $def = premium_tier($tier, $definitions);
    if ($def === null) {
        return false;
    }
    if (!isset($def['limit']) || $def['limit'] === null) {
        return true;
    }
    return (int) $used < (int) $def['limit'];
}

function premium_price_label($amount) {
    return 'CHF ' . (int) $amount;
}

function premium_tier_label($key) {
    $name = t('premium.tier.' . $key);
    if ($name === 'premium.tier.' . $key) {
        return (string) $key;
    }
    return $name;
}

function premium_limit_label($def) {
    if (!is_array($def) || !isset($def['limit']) || $def['limit'] === null) {
        return t('premium.limit.none');
    }
    return t('premium.limit.up_to', array('{count}' => (string) (int) $def['limit']));
}

function premium_feature_keys() {
    return array(
        'premium.feature.csv',
        'premium.feature.brand',
        'premium.feature.rules',
        'premium.feature.copy',
        'premium.feature.hr',
        'premium.feature.greeting',
        'premium.feature.nobrand',
        'premium.feature.domain',
        'premium.feature.overview',
    );
}

function premium_domain_to_ascii($domain) {
    $domain = str_replace('wichtlä.ch', 'xn--wichtl-gua.ch', (string) $domain);
    if (preg_match('/[^\x20-\x7E]/', $domain)) {
        if (function_exists('idn_to_ascii')) {
            if (defined('INTL_IDNA_VARIANT_UTS46') && PHP_VERSION_ID < 80000) {
                $ascii = idn_to_ascii($domain, 0, INTL_IDNA_VARIANT_UTS46);
            } else {
                $ascii = idn_to_ascii($domain);
            }
            if (is_string($ascii) && $ascii !== '') {
                return strtolower($ascii);
            }
        }
        return '';
    }
    return strtolower($domain);
}

function premium_normalize_domain($value) {
    $value = strtolower(trim((string) $value));
    if ($value === '') {
        return '';
    }
    if ($value[0] === '@') {
        $value = substr($value, 1);
    }
    $ascii = premium_domain_to_ascii($value);
    if ($ascii === '' || preg_match('/\A(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}\z/', $ascii) !== 1) {
        return null;
    }
    return $ascii;
}

function premium_normalize_email($email) {
    $email = str_replace(array("\r", "\n", "\t", ' '), '', trim((string) $email));
    if ($email === '' || strlen($email) > 255) {
        return '';
    }
    $at = strrpos($email, '@');
    if ($at === false || $at < 1) {
        return '';
    }
    $local = substr($email, 0, $at);
    $domain = premium_normalize_domain(substr($email, $at + 1));
    if ($domain === null || $domain === '') {
        return '';
    }
    $email = strtolower($local) . '@' . $domain;
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return '';
    }
    return $email;
}

function premium_email_domain($email) {
    $email = premium_normalize_email($email);
    $at = strrpos($email, '@');
    if ($at === false) {
        return '';
    }
    return substr($email, $at + 1);
}

function premium_normalize_admin_email($raw) {
    if (!is_string($raw) || trim($raw) === '') {
        $raw = 'info@xn--wichtl-gua.ch';
    }
    $email = premium_normalize_email($raw);
    if ($email === '') {
        return 'info@xn--wichtl-gua.ch';
    }
    return $email;
}

function premium_invoice_recipient_info() {
    premium_load_config();
    $defined = defined('PREMIUM_INVOICE_EMAIL') && is_string(PREMIUM_INVOICE_EMAIL) && trim(PREMIUM_INVOICE_EMAIL) !== '';
    $raw = $defined ? PREMIUM_INVOICE_EMAIL : '';
    $email = ($raw === '') ? '' : premium_normalize_email($raw);
    if ($email !== '') {
        return array('email' => $email, 'source' => 'config');
    }
    if ($defined) {
        return array('email' => 'info@xn--wichtl-gua.ch', 'source' => 'invalid_fallback');
    }
    return array('email' => 'info@xn--wichtl-gua.ch', 'source' => 'fallback');
}

function premium_invoice_recipient() {
    $info = premium_invoice_recipient_info();
    return $info['email'];
}

function premium_email_display($email) {
    return str_replace('xn--wichtl-gua.ch', 'wichtlä.ch', (string) $email);
}

function premium_normalize_code($code) {
    $code = strtoupper(str_replace(array(' ', '-', '_'), '', trim((string) $code)));
    if (preg_match('/\A[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{12}\z/', $code) !== 1) {
        return '';
    }
    return $code;
}

function premium_format_code($code) {
    $code = premium_normalize_code($code);
    if ($code === '') {
        return '';
    }
    return substr($code, 0, 4) . '-' . substr($code, 4, 4) . '-' . substr($code, 8, 4);
}

function premium_generate_license_code() {
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $max = strlen($alphabet) - 1;
    $raw = '';
    for ($i = 0; $i < 12; $i++) {
        $raw .= $alphabet[random_int(0, $max)];
    }
    return premium_format_code($raw);
}

function premium_secret_hash($value) {
    $value = (string) $value;
    if ($value === '') {
        return '';
    }
    return hash('sha256', 'wichtlae-premium-v1|' . $value);
}

function premium_token_format($token) {
    $token = strtolower(trim((string) $token));
    if (preg_match('/\A[a-f0-9]{64}\z/', $token) !== 1) {
        return '';
    }
    return $token;
}

function premium_confirmation_code() {
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function premium_short_code_hash($code, $salt) {
    $code = preg_replace('/\D/', '', (string) $code);
    if (!is_string($code) || strlen($code) !== 6 || !is_string($salt) || $salt === '') {
        return '';
    }
    return hash('sha256', 'wichtlae-confirm-v1|' . $salt . '|' . $code);
}

function premium_normalize_color($value) {
    $value = strtolower(trim((string) $value));
    if ($value === '') {
        return '';
    }
    if (preg_match('/\A#[0-9a-f]{6}\z/', $value) !== 1) {
        return null;
    }
    return $value;
}

function premium_color_picker_value($value, $fallback) {
    $normalized = premium_normalize_color($value);
    if (is_string($normalized) && $normalized !== '') {
        return $normalized;
    }
    $fallback_normalized = premium_normalize_color($fallback);
    if (is_string($fallback_normalized) && $fallback_normalized !== '') {
        return $fallback_normalized;
    }
    return '#000000';
}

function premium_color_field_html($id, $label, $value, $placeholder) {
    $raw = is_string($value) ? $value : '';
    $normalized = premium_normalize_color($raw);
    $shown = (is_string($normalized) && $normalized !== '') ? $normalized : $raw;
    $picker = premium_color_picker_value($raw, $placeholder);
    $html = '<div class="premium-color-field">';
    $html .= '<label for="' . h($id) . '">' . h($label) . '</label>';
    $html .= '<div class="premium-color-controls">';
    $html .= '<input type="color" class="premium-color-picker" value="' . h($picker) . '" aria-label="' . h($label) . '" data-color-for="' . h($id) . '">';
    $html .= '<input type="text" id="' . h($id) . '" name="' . h($id) . '" value="' . h($shown) . '" placeholder="' . h($placeholder) . '" maxlength="7" autocomplete="off" spellcheck="false">';
    $html .= '<span class="premium-color-preview"';
    if (is_string($normalized) && $normalized !== '') {
        $html .= ' style="background-color:' . h($normalized) . '"';
    }
    $html .= ' aria-hidden="true"></span>';
    $html .= '</div></div>';
    return $html;
}

function premium_color_picker_script() {
    return '<script>
(function () {
    function validHex(value) {
        return /^#[0-9a-fA-F]{6}$/.test(value);
    }
    function previewOf(node) {
        return node.parentNode ? node.parentNode.querySelector(".premium-color-preview") : null;
    }
    function syncFromPicker(picker) {
        var field = document.getElementById(picker.getAttribute("data-color-for"));
        var preview = previewOf(picker);
        if (!field) {
            return;
        }
        field.value = picker.value;
        if (preview) {
            preview.style.backgroundColor = picker.value;
        }
    }
    function syncFromField(field) {
        var picker = field.parentNode ? field.parentNode.querySelector(".premium-color-picker") : null;
        var preview = previewOf(field);
        var value = field.value.trim();
        if (validHex(value)) {
            var hex = value.toLowerCase();
            if (picker) {
                picker.value = hex;
            }
            if (preview) {
                preview.style.backgroundColor = hex;
            }
            return;
        }
        if (value === "" && preview) {
            preview.style.backgroundColor = "transparent";
        }
    }
    document.addEventListener("DOMContentLoaded", function () {
        var pickers = document.querySelectorAll(".premium-color-picker");
        var i;
        for (i = 0; i < pickers.length; i++) {
            pickers[i].addEventListener("input", function () { syncFromPicker(this); });
            pickers[i].addEventListener("change", function () { syncFromPicker(this); });
        }
        var fields = document.querySelectorAll(".premium-color-controls input[type=\\"text\\"]");
        for (i = 0; i < fields.length; i++) {
            fields[i].addEventListener("input", function () { syncFromField(this); });
            fields[i].addEventListener("change", function () {
                if (validHex(this.value.trim())) {
                    this.value = this.value.trim().toLowerCase();
                }
                syncFromField(this);
            });
        }
    });
})();
</script>';
}

function premium_clean_greeting($text) {
    $text = str_replace(array("\r\n", "\r"), "\n", (string) $text);
    $text = trim(strip_tags($text));
    if (function_exists('text_length') && text_length($text) > 500) {
        return null;
    }
    if (strlen($text) > 2000) {
        return null;
    }
    return $text;
}

function premium_tables_ready(PDO $pdo) {
    static $cache = array();
    $key = spl_object_hash($pdo);
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    $ok = false;
    try {
        $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE 1 = 0');
        $pdo->query('SELECT `id` FROM `premium_licenses` WHERE 1 = 0');
        $pdo->query('SELECT `id` FROM `premium_confirmations` WHERE 1 = 0');
        $ok = true;
    } catch (Exception $e) {
        $ok = false;
    }
    $cache[$key] = $ok;
    return $ok;
}

function premium_fetch_license(PDO $pdo, $id) {
    $stmt = $pdo->prepare('SELECT * FROM `premium_licenses` WHERE `id` = ?');
    $stmt->execute(array((int) $id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row : null;
}

function premium_fetch_license_by_session(PDO $pdo, $session_id) {
    $stmt = $pdo->prepare('SELECT * FROM `premium_licenses` WHERE `stripe_session_id` = ?');
    $stmt->execute(array((string) $session_id));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row : null;
}

function premium_fetch_license_by_access(PDO $pdo, $token) {
    $token = premium_token_format($token);
    if ($token === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM `premium_licenses` WHERE `access_token` = ?');
    $stmt->execute(array($token));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row : null;
}

function premium_group_count(PDO $pdo, $license_id) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM `groups` WHERE `premium_license_id` = ?');
    $stmt->execute(array((int) $license_id));
    return (int) $stmt->fetchColumn();
}

function premium_license_for_group(PDO $pdo, array $group) {
    if (!premium_feature_enabled() || empty($group['premium_license_id'])) {
        return null;
    }
    if (!premium_tables_ready($pdo)) {
        return null;
    }
    $license = premium_fetch_license($pdo, $group['premium_license_id']);
    if (!$license || !isset($license['status']) || $license['status'] !== 'active') {
        return null;
    }
    return $license;
}

function premium_find_license_for_email(PDO $pdo, $email) {
    $email = premium_normalize_email($email);
    $domain = premium_email_domain($email);
    if ($email === '') {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM `premium_licenses` WHERE `status` = ? AND (`buyer_email` = ? OR `domain` = ?) ORDER BY `id` DESC');
    $stmt->execute(array('active', $email, $domain));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $exact = array();
    $by_domain = array();
    foreach ($rows as $row) {
        if (isset($row['buyer_email']) && strtolower((string) $row['buyer_email']) === $email) {
            $exact[] = $row;
        } elseif ($domain !== '' && isset($row['domain']) && strtolower((string) $row['domain']) === $domain) {
            $by_domain[] = $row;
        }
    }
    if (!empty($exact)) {
        return $exact[0];
    }
    if (!empty($by_domain)) {
        return $by_domain[0];
    }
    return null;
}

function premium_email_matches_license(array $license, $email) {
    $email = premium_normalize_email($email);
    if ($email === '') {
        return false;
    }
    if (isset($license['buyer_email']) && strtolower((string) $license['buyer_email']) === $email) {
        return true;
    }
    $domain = premium_email_domain($email);
    return $domain !== '' && isset($license['domain']) && strtolower((string) $license['domain']) === $domain;
}

function premium_attach_group(PDO $pdo, $license_id, $group_id) {
    $license_id = (int) $license_id;
    $group_id = (int) $group_id;
    if ($license_id < 1 || $group_id < 1) {
        return array('ok' => false, 'error' => 'invalid');
    }
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('UPDATE `premium_licenses` SET `season_year` = `season_year` WHERE `id` = ?');
        $lock->execute(array($license_id));
        $license = premium_fetch_license($pdo, $license_id);
        if (!$license || $license['status'] !== 'active') {
            $pdo->rollBack();
            return array('ok' => false, 'error' => 'inactive');
        }
        $stmt = $pdo->prepare('SELECT `premium_license_id` FROM `groups` WHERE `id` = ?');
        $stmt->execute(array($group_id));
        $current = $stmt->fetchColumn();
        if ($current === false) {
            $pdo->rollBack();
            return array('ok' => false, 'error' => 'missing_group');
        }
        $replace_from = 0;
        if ($current !== null && $current !== '' && (int) $current !== 0) {
            if ((int) $current === $license_id) {
                $pdo->commit();
                return array('ok' => true, 'error' => '');
            }
            $current_license = premium_fetch_license($pdo, (int) $current);
            if ($current_license && isset($current_license['status']) && $current_license['status'] === 'active') {
                $pdo->rollBack();
                return array('ok' => false, 'error' => 'already');
            }
            $replace_from = (int) $current;
        }
        $used = premium_group_count($pdo, $license_id);
        if (!premium_has_room($license['tier'], $used)) {
            $pdo->rollBack();
            return array('ok' => false, 'error' => 'limit');
        }
        $upd = $pdo->prepare('UPDATE `groups` SET `premium_license_id` = ? WHERE `id` = ? AND (`premium_license_id` IS NULL OR `premium_license_id` = 0 OR `premium_license_id` = ?)');
        $upd->execute(array($license_id, $group_id, $replace_from));
        if ($upd->rowCount() !== 1) {
            $pdo->rollBack();
            return array('ok' => false, 'error' => 'race');
        }
        $pdo->commit();
        return array('ok' => true, 'error' => '');
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Premium verbinden: ' . $e->getMessage());
        return array('ok' => false, 'error' => 'fail');
    }
}

function premium_code_attempt_allowed($ip, $group_id) {
    $by_ip = consume_rate_limit('premium-code-ip', (string) $ip, 8, 900, false);
    if (!$by_ip) {
        return false;
    }
    return consume_rate_limit('premium-code-group', 'group:' . (int) $group_id, 8, 900, false);
}

function premium_claim_email_allowed($ip, $email) {
    $by_ip = consume_rate_limit('premium-claim-ip', (string) $ip, 8, 900, false);
    if (!$by_ip) {
        return false;
    }
    return consume_rate_limit('premium-claim-mail', strtolower((string) $email), 5, 3600, false);
}

function premium_invoice_attempt_allowed($ip) {
    return consume_rate_limit('premium-invoice', (string) $ip, 5, 3600, false);
}

function premium_overview_resend_allowed($license_id) {
    return consume_rate_limit('premium-overview', 'license:' . (int) $license_id, 5, 3600, false);
}

function premium_claim_with_code(PDO $pdo, $group_id, $code, $ip) {
    if (!premium_code_attempt_allowed($ip, $group_id)) {
        return array('ok' => false, 'error' => 'rate');
    }
    $normalized = premium_normalize_code($code);
    $hash = premium_secret_hash($normalized);
    if ($hash === '') {
        return array('ok' => false, 'error' => 'bad');
    }
    $stmt = $pdo->prepare('SELECT * FROM `premium_licenses` WHERE `code_hash` = ?');
    $stmt->execute(array($hash));
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$license) {
        return array('ok' => false, 'error' => 'bad');
    }
    $attached = premium_attach_group($pdo, $license['id'], $group_id);
    if (!$attached['ok'] && $attached['error'] !== 'rate') {
        if ($attached['error'] === 'limit') {
            return $attached;
        }
        return array('ok' => false, 'error' => 'bad');
    }
    return $attached;
}

function premium_default_sender() {
    return function ($to, $subject, $html, $plain = null) {
        return send_email($to, $subject, $html, true, $plain);
    };
}

function premium_send($sender, $to, $subject, $html, $plain) {
    if ($sender === null) {
        $sender = premium_default_sender();
    }
    try {
        return (bool) call_user_func($sender, $to, $subject, $html, $plain);
    } catch (Exception $e) {
        error_log('Premium-Mail: ' . $e->getMessage());
        return false;
    }
}

function premium_claim_mail($email, $confirm_url, $code, $lang) {
    return mail_run($lang, function () use ($email, $confirm_url, $code) {
        $subject = t('premium.mail.claim.subject');
        $body = mail_p(mail_esc(t('premium.mail.claim.intro', array('{email}' => $email))));
        $body .= mail_p('<strong>' . mail_esc(t('premium.mail.claim.code_label')) . '</strong> ' . mail_esc($code));
        $body .= mail_button($confirm_url, t('premium.mail.claim.link'));
        $body .= mail_p(mail_esc(t('premium.mail.claim.expire')));
        $html = render_email_template(t('premium.mail.claim.title'), t('premium.mail.claim.preview'), $body);
        $plain = t('premium.mail.claim.intro', array('{email}' => $email)) . "\n\n"
            . t('premium.mail.claim.code_label') . ' ' . $code . "\n"
            . $confirm_url . "\n\n"
            . t('premium.mail.claim.expire') . "\n";
        return array($subject, $html, $plain);
    });
}

function premium_license_mail(array $license, $code, $overview_url, $lang) {
    return mail_run($lang, function () use ($license, $code, $overview_url) {
        $tier = premium_tier_label($license['tier']);
        $def = premium_tier($license['tier']);
        $limit = ($def === null) ? '' : premium_limit_label($def);
        $subject = t('premium.mail.license.subject');
        $body = mail_p(mail_esc(t('premium.mail.license.intro', array('{tier}' => $tier, '{limit}' => $limit))));
        $body .= mail_p('<strong>' . mail_esc(t('premium.mail.license.code_label')) . '</strong> ' . mail_esc($code));
        $body .= mail_button($overview_url, t('premium.mail.license.overview'));
        $body .= mail_p(mail_esc(t('premium.mail.license.keep')));
        $html = render_email_template(t('premium.mail.license.title'), t('premium.mail.license.preview'), $body);
        $plain = t('premium.mail.license.intro', array('{tier}' => $tier, '{limit}' => $limit)) . "\n\n"
            . t('premium.mail.license.code_label') . ' ' . $code . "\n"
            . $overview_url . "\n\n"
            . t('premium.mail.license.keep') . "\n";
        return array($subject, $html, $plain);
    });
}

function premium_invoice_mail(array $fields, $license_id) {
    return mail_run('de', function () use ($fields, $license_id) {
        $tier = premium_tier_label($fields['tier']);
        $def = premium_tier($fields['tier']);
        $price = ($def === null) ? '' : premium_price_label($def['price']);
        $company = str_replace(array("\r", "\n"), ' ', $fields['company']);
        $subject = t('premium.mail.invoice.subject', array('{company}' => $company));
        $lines = array(
            t('premium.mail.invoice.id', array('{id}' => (string) (int) $license_id)),
            t('premium.mail.invoice.tier', array('{tier}' => $tier, '{price}' => $price)),
            t('premium.mail.invoice.company', array('{company}' => $fields['company'])),
            t('premium.mail.invoice.address', array('{address}' => $fields['address'])),
            t('premium.mail.invoice.email', array('{email}' => $fields['email'])),
            t('premium.mail.invoice.contact', array('{contact}' => $fields['contact'] === '' ? '–' : $fields['contact'])),
            t('premium.mail.invoice.next'),
        );
        $body = '';
        $plain = '';
        foreach ($lines as $line) {
            $body .= mail_p(nl2br(mail_esc($line)));
            $plain .= $line . "\n";
        }
        $html = render_email_template(t('premium.mail.invoice.title'), t('premium.mail.invoice.preview'), $body);
        return array($subject, $html, $plain);
    });
}

function premium_overview_mail($overview_url, $lang) {
    return mail_run($lang, function () use ($overview_url) {
        $subject = t('premium.mail.overview.subject');
        $body = mail_p(mail_esc(t('premium.mail.overview.intro')));
        $body .= mail_button($overview_url, t('premium.mail.overview.button'));
        $html = render_email_template(t('premium.mail.overview.title'), t('premium.mail.overview.preview'), $body);
        $plain = t('premium.mail.overview.intro') . "\n\n" . $overview_url . "\n";
        return array($subject, $html, $plain);
    });
}

function premium_overview_url($access_token) {
    return get_display_url('/premium-lizenz.php?token=' . urlencode($access_token));
}

function premium_confirm_url($raw_token) {
    return get_display_url('/premium-bestaetigen.php?token=' . urlencode($raw_token));
}

function premium_request_email_claim(PDO $pdo, $group_id, $email, $ip, $sender = null, $now = null) {
    $email = premium_normalize_email($email);
    if ($email === '') {
        return array('status' => 'invalid');
    }
    if (!premium_claim_email_allowed($ip, $email)) {
        return array('status' => 'rate');
    }
    $license = premium_find_license_for_email($pdo, $email);
    if (!$license) {
        return array('status' => 'unknown');
    }
    $raw = bin2hex(random_bytes(32));
    $code = premium_confirmation_code();
    $token_hash = hash('sha256', $raw);
    $code_hash = premium_short_code_hash($code, $token_hash);
    $lang = function_exists('i18n_current') ? i18n_current() : 'de';
    $expires = date('Y-m-d H:i:s', premium_now($now) + 3600);
    $stmt = $pdo->prepare('INSERT INTO `premium_confirmations` (`license_id`, `group_id`, `email`, `purpose`, `token_hash`, `code_hash`, `language`, `expires_at`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute(array(
        (int) $license['id'],
        (int) $group_id,
        $email,
        'claim',
        $token_hash,
        $code_hash,
        i18n_allow($lang),
        $expires,
        premium_sql_time($now),
    ));
    $mail = premium_claim_mail($email, premium_confirm_url($raw), $code, $lang);
    $sent = premium_send($sender, $email, $mail[0], $mail[1], $mail[2]);
    if (!$sent) {
        return array('status' => 'failed');
    }
    return array('status' => 'sent', 'token' => $raw, 'code' => $code, 'to' => $email);
}

function premium_consume_confirmation(PDO $pdo, array $row, $now = null) {
    $stmt = $pdo->prepare('UPDATE `premium_confirmations` SET `consumed_at` = ? WHERE `group_id` = ? AND `consumed_at` IS NULL');
    $stmt->execute(array(premium_sql_time($now), (int) $row['group_id']));
}

function premium_confirmation_is_open(array $row, $now = null) {
    if (!empty($row['consumed_at'])) {
        return false;
    }
    $expires = isset($row['expires_at']) ? strtotime((string) $row['expires_at']) : false;
    if ($expires === false) {
        return false;
    }
    return $expires >= premium_now($now);
}

function premium_finish_confirmation(PDO $pdo, array $row, $now = null) {
    if (!premium_confirmation_is_open($row, $now)) {
        return array('ok' => false, 'error' => 'bad');
    }
    $license = premium_fetch_license($pdo, $row['license_id']);
    if (!$license || !premium_email_matches_license($license, $row['email'])) {
        return array('ok' => false, 'error' => 'bad');
    }
    $attached = premium_attach_group($pdo, $license['id'], $row['group_id']);
    if ($attached['ok']) {
        premium_consume_confirmation($pdo, $row, $now);
    }
    return $attached;
}

function premium_confirm_token(PDO $pdo, $raw_token, $now = null) {
    $raw = premium_token_format($raw_token);
    if ($raw === '') {
        return array('ok' => false, 'error' => 'bad');
    }
    $hash = hash('sha256', $raw);
    $stmt = $pdo->prepare('SELECT * FROM `premium_confirmations` WHERE `token_hash` = ?');
    $stmt->execute(array($hash));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !isset($row['token_hash']) || !hash_equals($row['token_hash'], $hash)) {
        return array('ok' => false, 'error' => 'bad');
    }
    return premium_finish_confirmation($pdo, $row, $now);
}

function premium_confirm_short_code(PDO $pdo, $group_id, $code, $ip, $now = null) {
    if (!premium_code_attempt_allowed($ip, $group_id)) {
        return array('ok' => false, 'error' => 'rate');
    }
    $digits = preg_replace('/\D/', '', (string) $code);
    if (!is_string($digits) || strlen($digits) !== 6) {
        return array('ok' => false, 'error' => 'bad');
    }
    $stmt = $pdo->prepare('SELECT * FROM `premium_confirmations` WHERE `group_id` = ? AND `purpose` = ? AND `consumed_at` IS NULL');
    $stmt->execute(array((int) $group_id, 'claim'));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        if (!premium_confirmation_is_open($row, $now)) {
            continue;
        }
        $expected = premium_short_code_hash($digits, $row['token_hash']);
        if ($expected !== '' && isset($row['code_hash']) && hash_equals($row['code_hash'], $expected)) {
            return premium_finish_confirmation($pdo, $row, $now);
        }
    }
    return array('ok' => false, 'error' => 'bad');
}

function premium_public_claim_message($status) {
    if ($status === 'rate') {
        return array('error', t('premium.claim.rate'));
    }
    if ($status === 'invalid') {
        return array('error', t('premium.claim.email_invalid'));
    }
    if ($status === 'failed') {
        return array('error', t('premium.claim.mail_fail'));
    }
    return array('success', t('premium.claim.sent'));
}

function premium_public_code_message($error) {
    if ($error === 'rate') {
        return array('error', t('premium.claim.rate'));
    }
    if ($error === '' || $error === 'ok') {
        return array('success', t('premium.claim.ok'));
    }
    return array('error', t('premium.claim.code_bad'));
}

function premium_issue_access_token(PDO $pdo, array $license) {
    $existing = isset($license['access_token']) ? premium_token_format($license['access_token']) : '';
    if ($existing !== '') {
        return $existing;
    }
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare('UPDATE `premium_licenses` SET `access_token` = ? WHERE `id` = ?');
    $stmt->execute(array($token, (int) $license['id']));
    return $token;
}

function premium_issue_code(PDO $pdo, $license_id) {
    $code = premium_generate_license_code();
    $hash = premium_secret_hash(premium_normalize_code($code));
    $stmt = $pdo->prepare('UPDATE `premium_licenses` SET `code_hash` = ? WHERE `id` = ?');
    $stmt->execute(array($hash, (int) $license_id));
    return $code;
}

function premium_deliver_license_mail(PDO $pdo, array $license, $code, $sender = null) {
    $token = premium_issue_access_token($pdo, $license);
    $license['access_token'] = $token;
    $lang = isset($license['language']) ? $license['language'] : 'de';
    $mail = premium_license_mail($license, $code, premium_overview_url($token), $lang);
    $ok = premium_send($sender, $license['buyer_email'], $mail[0], $mail[1], $mail[2]);
    if ($ok) {
        $stmt = $pdo->prepare('UPDATE `premium_licenses` SET `notified_at` = ? WHERE `id` = ?');
        $stmt->execute(array(premium_sql_time(), (int) $license['id']));
    }
    return $ok;
}

function premium_invoice_destination($recipient = null) {
    if (is_string($recipient) && trim($recipient) !== '') {
        $email = premium_normalize_email($recipient);
        if ($email !== '') {
            return array('email' => $email, 'source' => 'argument');
        }
        return array('email' => premium_normalize_admin_email($recipient), 'source' => 'invalid_fallback');
    }
    return premium_invoice_recipient_info();
}

function premium_submit_invoice(PDO $pdo, array $post, $ip, $sender = null, $now = null, $recipient = null) {
    if (!premium_invoice_attempt_allowed($ip)) {
        return array('ok' => false, 'error' => 'rate');
    }
    $tier = isset($post['tier']) ? (string) $post['tier'] : '';
    if (!premium_known_tier($tier)) {
        return array('ok' => false, 'error' => 'invalid');
    }
    $company = trim(isset($post['company']) ? (string) $post['company'] : '');
    $address = trim(isset($post['address']) ? (string) $post['address'] : '');
    $contact = trim(isset($post['contact']) ? (string) $post['contact'] : '');
    $email = premium_normalize_email(isset($post['email']) ? $post['email'] : '');
    $company = str_replace(array("\r", "\n"), ' ', $company);
    $contact = str_replace(array("\r", "\n"), ' ', $contact);
    $address = str_replace(array("\r\n", "\r"), "\n", $address);
    if ($company === '' || strlen($company) < 2 || strlen($company) > 255) {
        return array('ok' => false, 'error' => 'invalid');
    }
    if ($address === '' || strlen($address) < 5 || strlen($address) > 1000) {
        return array('ok' => false, 'error' => 'invalid');
    }
    if ($contact !== '' && strlen($contact) > 255) {
        return array('ok' => false, 'error' => 'invalid');
    }
    if ($email === '') {
        return array('ok' => false, 'error' => 'invalid');
    }
    $fields = array(
        'tier' => $tier,
        'company' => $company,
        'address' => $address,
        'contact' => $contact,
        'email' => $email,
        'language' => function_exists('i18n_current') ? i18n_current() : 'de',
    );
    $stmt = $pdo->prepare('INSERT INTO `premium_licenses` (`tier`, `status`, `buyer_email`, `company_name`, `company_address`, `contact_name`, `season_year`, `language`, `created_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute(array(
        $tier,
        'pending',
        $email,
        $company,
        $address,
        ($contact === '' ? null : $contact),
        (int) date('Y', premium_now($now)),
        i18n_allow($fields['language']),
        premium_sql_time($now),
    ));
    $id = (int) $pdo->lastInsertId();
    $mail = premium_invoice_mail($fields, $id);
    $invoice_to = premium_invoice_destination($recipient);
    if ($sender === null) {
        error_log('Premium-Rechnung an ' . $invoice_to['email'] . ' quelle=' . $invoice_to['source']);
    }
    $sent = premium_send($sender, $invoice_to['email'], $mail[0], $mail[1], $mail[2]);
    if (!$sent) {
        $pdo->prepare('DELETE FROM `premium_licenses` WHERE `id` = ? AND `status` = ?')->execute(array($id, 'pending'));
        return array('ok' => false, 'error' => 'mail');
    }
    return array('ok' => true, 'error' => '', 'id' => $id);
}

function premium_activate_license(PDO $pdo, $license_id, $domain_raw, $sender = null, $now = null) {
    $license = premium_fetch_license($pdo, $license_id);
    if (!$license || $license['status'] !== 'pending') {
        return array('ok' => false, 'error' => 'missing', 'code' => '');
    }
    $domain = premium_normalize_domain($domain_raw);
    if ($domain === null) {
        return array('ok' => false, 'error' => 'domain', 'code' => '');
    }
    $code = premium_generate_license_code();
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare('UPDATE `premium_licenses` SET `status` = ?, `domain` = ?, `code_hash` = ?, `access_token` = ?, `activated_at` = ? WHERE `id` = ? AND `status` = ?');
    $stmt->execute(array(
        'active',
        ($domain === '' ? null : $domain),
        premium_secret_hash(premium_normalize_code($code)),
        $token,
        premium_sql_time($now),
        (int) $license_id,
        'pending',
    ));
    if ($stmt->rowCount() !== 1) {
        return array('ok' => false, 'error' => 'missing', 'code' => '');
    }
    $license = premium_fetch_license($pdo, $license_id);
    $mailed = premium_deliver_license_mail($pdo, $license, $code, $sender);
    return array('ok' => true, 'error' => $mailed ? '' : 'mail', 'code' => $mailed ? '' : $code);
}

function premium_set_domain(PDO $pdo, $license_id, $domain_raw) {
    $domain = premium_normalize_domain($domain_raw);
    if ($domain === null) {
        return false;
    }
    $stmt = $pdo->prepare('UPDATE `premium_licenses` SET `domain` = ? WHERE `id` = ? AND `status` = ?');
    $stmt->execute(array($domain === '' ? null : $domain, (int) $license_id, 'active'));
    return $stmt->rowCount() === 1;
}

function premium_master_log($action, $license_id, $ok) {
    error_log('Master-Admin Premium: ' . $action . ', Lizenz ' . (int) $license_id . ($ok ? ', ok' : ', abgelehnt'));
}

function premium_unlink_license_groups(PDO $pdo, $license_id) {
    $stmt = $pdo->prepare('UPDATE `groups` SET `premium_license_id` = NULL WHERE `premium_license_id` = ?');
    $stmt->execute(array((int) $license_id));
}

function premium_delete_license_confirmations(PDO $pdo, $license_id) {
    $stmt = $pdo->prepare('DELETE FROM `premium_confirmations` WHERE `license_id` = ?');
    $stmt->execute(array((int) $license_id));
}

function premium_reject_pending_license(PDO $pdo, $license_id) {
    $license_id = (int) $license_id;
    if ($license_id < 1) {
        premium_master_log('Anfrage ablehnen', $license_id, false);
        return false;
    }
    $pdo->beginTransaction();
    try {
        $license = premium_fetch_license($pdo, $license_id);
        if (!$license || !isset($license['status']) || $license['status'] !== 'pending') {
            $pdo->rollBack();
            premium_master_log('Anfrage ablehnen', $license_id, false);
            return false;
        }
        premium_unlink_license_groups($pdo, $license_id);
        premium_delete_license_confirmations($pdo, $license_id);
        $del = $pdo->prepare('DELETE FROM `premium_licenses` WHERE `id` = ? AND `status` = ?');
        $del->execute(array($license_id, 'pending'));
        if ($del->rowCount() !== 1) {
            $pdo->rollBack();
            premium_master_log('Anfrage ablehnen', $license_id, false);
            return false;
        }
        $pdo->commit();
        premium_master_log('Anfrage ablehnen', $license_id, true);
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Premium ablehnen: ' . $e->getMessage());
        return false;
    }
}

/**
 * Widerruf setzt nur den Status auf revoked.
 * Gruppen, Teilnehmer und groups.premium_license_id bleiben.
 * Premium-Funktionen gelten nur bei status active, deshalb sind sie aus.
 * Logos bleiben in der Zeile und als Datei auf dem Server.
 * Eine Gruppe mit widerrufener Lizenz kann später eine andere aktive Lizenz verbinden.
 */
function premium_revoke_license(PDO $pdo, $license_id) {
    $license_id = (int) $license_id;
    if ($license_id < 1) {
        premium_master_log('Lizenz widerrufen', $license_id, false);
        return false;
    }
    $stmt = $pdo->prepare('UPDATE `premium_licenses` SET `status` = ? WHERE `id` = ? AND `status` = ?');
    $stmt->execute(array('revoked', $license_id, 'active'));
    $ok = $stmt->rowCount() === 1;
    premium_master_log('Lizenz widerrufen', $license_id, $ok);
    return $ok;
}

function premium_delete_revoked_license(PDO $pdo, $license_id) {
    $license_id = (int) $license_id;
    if ($license_id < 1) {
        premium_master_log('Widerrufene Lizenz löschen', $license_id, false);
        return false;
    }
    $pdo->beginTransaction();
    try {
        $license = premium_fetch_license($pdo, $license_id);
        if (!$license || !isset($license['status']) || $license['status'] !== 'revoked') {
            $pdo->rollBack();
            premium_master_log('Widerrufene Lizenz löschen', $license_id, false);
            return false;
        }
        premium_unlink_license_groups($pdo, $license_id);
        premium_delete_license_confirmations($pdo, $license_id);
        $del = $pdo->prepare('DELETE FROM `premium_licenses` WHERE `id` = ? AND `status` = ?');
        $del->execute(array($license_id, 'revoked'));
        if ($del->rowCount() !== 1) {
            $pdo->rollBack();
            premium_master_log('Widerrufene Lizenz löschen', $license_id, false);
            return false;
        }
        $pdo->commit();
        premium_master_log('Widerrufene Lizenz löschen', $license_id, true);
        return true;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Premium löschen: ' . $e->getMessage());
        return false;
    }
}

function premium_resend_code(PDO $pdo, $license_id, $sender = null) {
    $license = premium_fetch_license($pdo, $license_id);
    if (!$license || $license['status'] !== 'active') {
        return false;
    }
    $code = premium_issue_code($pdo, $license_id);
    $license = premium_fetch_license($pdo, $license_id);
    return premium_deliver_license_mail($pdo, $license, $code, $sender);
}

function premium_clean_config_text($value) {
    if (is_int($value) || is_float($value)) {
        $value = (string) $value;
    }
    if (is_bool($value)) {
        return $value ? '1' : '';
    }
    if (!is_string($value)) {
        return '';
    }
    if (strncmp($value, "\xEF\xBB\xBF", 3) === 0) {
        $value = substr($value, 3);
    }
    $value = trim($value);
    $length = strlen($value);
    if ($length >= 2) {
        $first = $value[0];
        $last = $value[$length - 1];
        if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
            $value = trim(substr($value, 1, -1));
        }
    }
    return $value;
}

function premium_config_value($name) {
    if (!defined($name)) {
        return '';
    }
    return premium_clean_config_text(constant($name));
}

function premium_stripe_config($override = null) {
    if (!is_array($override)) {
        premium_load_config();
        $override = array(
            'secret' => premium_config_value('PREMIUM_STRIPE_SECRET_KEY'),
            'webhook' => premium_config_value('PREMIUM_STRIPE_WEBHOOK_SECRET'),
            'prices' => array(
                'verein' => premium_config_value('PREMIUM_STRIPE_PRICE_VEREIN'),
                'firma' => premium_config_value('PREMIUM_STRIPE_PRICE_FIRMA'),
                'firma_plus' => premium_config_value('PREMIUM_STRIPE_PRICE_FIRMA_PLUS'),
            ),
        );
    }
    $prices = (isset($override['prices']) && is_array($override['prices'])) ? $override['prices'] : array();
    return array(
        'secret' => premium_clean_config_text(isset($override['secret']) ? $override['secret'] : ''),
        'webhook' => premium_clean_config_text(isset($override['webhook']) ? $override['webhook'] : ''),
        'prices' => array(
            'verein' => premium_clean_config_text(isset($prices['verein']) ? $prices['verein'] : ''),
            'firma' => premium_clean_config_text(isset($prices['firma']) ? $prices['firma'] : ''),
            'firma_plus' => premium_clean_config_text(isset($prices['firma_plus']) ? $prices['firma_plus'] : ''),
        ),
    );
}

function premium_stripe_secret_status($secret) {
    $secret = premium_clean_config_text($secret);
    if ($secret === '') {
        return 'missing';
    }
    if (preg_match('/\Apk_(?:test|live)_/', $secret) === 1) {
        return 'publishable';
    }
    if (preg_match('/\A(sk_test|sk_live|rk_test|rk_live)_/', $secret, $match) === 1 && strlen($secret) >= 12) {
        return $match[1];
    }
    return 'invalid';
}

function premium_stripe_status($config = null) {
    $config = ($config === null) ? premium_stripe_config() : $config;
    $secret = premium_stripe_secret_status(isset($config['secret']) ? $config['secret'] : '');
    $webhook_raw = premium_clean_config_text(isset($config['webhook']) ? $config['webhook'] : '');
    if ($webhook_raw === '') {
        $webhook = 'missing';
    } elseif (strpos($webhook_raw, 'whsec_') === 0 && strlen($webhook_raw) >= 12) {
        $webhook = 'ok';
    } else {
        $webhook = 'invalid';
    }
    $prices_in = (isset($config['prices']) && is_array($config['prices'])) ? $config['prices'] : array();
    $prices = array();
    foreach (array('verein', 'firma', 'firma_plus') as $tier) {
        $value = premium_clean_config_text(isset($prices_in[$tier]) ? $prices_in[$tier] : '');
        if ($value === '') {
            $prices[$tier] = 'missing';
        } elseif (strpos($value, 'price_') === 0 && strlen($value) >= 8) {
            $prices[$tier] = 'ok';
        } else {
            $prices[$tier] = 'invalid';
        }
    }
    $secret_ok = in_array($secret, array('sk_test', 'sk_live', 'rk_test', 'rk_live'), true);
    $ready = $secret_ok && $webhook === 'ok';
    foreach ($prices as $state) {
        if ($state !== 'ok') {
            $ready = false;
        }
    }
    return array(
        'ready' => $ready,
        'secret' => $secret,
        'webhook' => $webhook,
        'prices' => $prices,
    );
}

function premium_stripe_is_configured(array $config) {
    return !empty(premium_stripe_status($config)['ready']);
}

function premium_stripe_checkout_ready($config = null) {
    if (!premium_feature_enabled()) {
        return false;
    }
    $config = ($config === null) ? premium_stripe_config() : $config;
    return premium_stripe_is_configured($config);
}

function premium_stripe_signature_valid($payload, $header, $secret, $now = null, $tolerance = 300) {
    if (!is_string($payload) || !is_string($header) || !is_string($secret) || $secret === '') {
        return false;
    }
    $parts = array();
    foreach (explode(',', $header) as $piece) {
        $piece = trim($piece);
        $eq = strpos($piece, '=');
        if ($eq === false) {
            continue;
        }
        $name = substr($piece, 0, $eq);
        $value = substr($piece, $eq + 1);
        if (!isset($parts[$name])) {
            $parts[$name] = array();
        }
        $parts[$name][] = $value;
    }
    if (empty($parts['t'][0]) || empty($parts['v1']) || !preg_match('/\A\d+\z/', $parts['t'][0])) {
        return false;
    }
    $timestamp = (int) $parts['t'][0];
    if (abs(premium_now($now) - $timestamp) > (int) $tolerance) {
        return false;
    }
    $expected = hash_hmac('sha256', $parts['t'][0] . '.' . $payload, $secret);
    foreach ($parts['v1'] as $signature) {
        if (is_string($signature) && hash_equals($expected, $signature)) {
            return true;
        }
    }
    return false;
}

function premium_http_post_form($url, array $fields, array $headers, $transport = null) {
    if ($transport !== null) {
        return call_user_func($transport, $url, $fields, $headers);
    }
    if (!function_exists('curl_init')) {
        return array('ok' => false, 'status' => 0, 'body' => '');
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return array(
        'ok' => is_string($body) && $status >= 200 && $status < 300,
        'status' => $status,
        'body' => is_string($body) ? $body : '',
    );
}

function premium_checkout_urls() {
    $lang = function_exists('i18n_current') ? i18n_current() : 'de';
    $path = function_exists('i18n_public_url') ? i18n_public_url('firmen-premium', $lang) : '/firmen-premium';
    $base = function_exists('canonical_base_url') ? canonical_base_url() : 'https://xn--wichtl-gua.ch';
    return array(
        'success' => $base . $path . '?checkout=ok',
        'cancel' => $base . $path . '?checkout=cancel',
    );
}

function premium_stripe_error_note($body) {
    $decoded = json_decode((string) $body, true);
    if (!is_array($decoded) || !isset($decoded['error']) || !is_array($decoded['error'])) {
        return '';
    }
    $type = isset($decoded['error']['type']) ? (string) $decoded['error']['type'] : '';
    $code = isset($decoded['error']['code']) ? (string) $decoded['error']['code'] : '';
    $message = isset($decoded['error']['message']) ? (string) $decoded['error']['message'] : '';
    $message = preg_replace('/(?:sk|rk|pk)_(?:live|test)_[A-Za-z0-9]+/', '[key]', $message);
    $message = str_replace(array("\r", "\n"), ' ', (string) $message);
    if (strlen($message) > 180) {
        $message = substr($message, 0, 180);
    }
    return ' type=' . $type . ' code=' . $code . ' message=' . $message;
}

function premium_stripe_start_checkout($tier, $email, $transport = null, $config = null, $now = null) {
    $config = premium_stripe_config($config);
    if (!premium_stripe_is_configured($config) || !premium_known_tier($tier)) {
        return array('ok' => false, 'url' => '', 'error' => 'config');
    }
    $email = premium_normalize_email($email);
    if ($email === '') {
        return array('ok' => false, 'url' => '', 'error' => 'email');
    }
    $urls = premium_checkout_urls();
    $lang = function_exists('i18n_current') ? i18n_current() : 'de';
    $fields = array(
        'mode' => 'payment',
        'customer_email' => $email,
        'success_url' => $urls['success'],
        'cancel_url' => $urls['cancel'],
        'line_items[0][price]' => $config['prices'][$tier],
        'line_items[0][quantity]' => '1',
        'payment_method_types[0]' => 'card',
        'payment_method_types[1]' => 'twint',
        'metadata[tier]' => $tier,
        'metadata[buyer_email]' => $email,
        'metadata[language]' => i18n_allow($lang),
        'metadata[price_id]' => $config['prices'][$tier],
        'client_reference_id' => substr(hash('sha256', $tier . '|' . $email), 0, 32),
    );
    $idem = hash('sha256', $email . '|' . $tier . '|' . date('Y-m-d', premium_now($now)));
    $headers = array(
        'Authorization: Bearer ' . $config['secret'],
        'Idempotency-Key: ' . $idem,
    );
    $response = premium_http_post_form('https://api.stripe.com/v1/checkout/sessions', $fields, $headers, $transport);
    if (empty($response['ok'])) {
        error_log('Stripe Checkout fehlgeschlagen, HTTP ' . (isset($response['status']) ? (int) $response['status'] : 0) . premium_stripe_error_note(isset($response['body']) ? $response['body'] : ''));
        return array('ok' => false, 'url' => '', 'error' => 'stripe');
    }
    $decoded = json_decode($response['body'], true);
    $url = (is_array($decoded) && isset($decoded['url'])) ? (string) $decoded['url'] : '';
    if (strpos($url, 'https://checkout.stripe.com/') !== 0) {
        return array('ok' => false, 'url' => '', 'error' => 'stripe');
    }
    return array('ok' => true, 'url' => $url, 'error' => '');
}

function premium_apply_checkout_event(PDO $pdo, array $event, $sender = null, $now = null, $config = null) {
    $config = premium_stripe_config($config);
    $type = isset($event['type']) ? (string) $event['type'] : '';
    $object = (isset($event['data']['object']) && is_array($event['data']['object'])) ? $event['data']['object'] : array();
    if ($type !== 'checkout.session.completed') {
        return array('http' => 200, 'status' => 'ignored', 'code' => '');
    }
    $payment = isset($object['payment_status']) ? (string) $object['payment_status'] : '';
    if ($payment !== 'paid' && $payment !== 'no_payment_required') {
        return array('http' => 200, 'status' => 'ignored', 'code' => '');
    }
    $session_id = isset($object['id']) ? (string) $object['id'] : '';
    if (preg_match('/\A[A-Za-z0-9_]{5,255}\z/', $session_id) !== 1) {
        return array('http' => 400, 'status' => 'bad_session', 'code' => '');
    }
    $metadata = (isset($object['metadata']) && is_array($object['metadata'])) ? $object['metadata'] : array();
    $tier = isset($metadata['tier']) ? (string) $metadata['tier'] : '';
    $price = isset($metadata['price_id']) ? (string) $metadata['price_id'] : '';
    $expected = (isset($config['prices'][$tier])) ? (string) $config['prices'][$tier] : '';
    if ($expected === '' || $price === '' || !hash_equals($expected, $price)) {
        return array('http' => 200, 'status' => 'ignored', 'code' => '');
    }
    $email = isset($metadata['buyer_email']) ? (string) $metadata['buyer_email'] : '';
    if ($email === '' && isset($object['customer_email'])) {
        $email = (string) $object['customer_email'];
    }
    if ($email === '' && isset($object['customer_details']['email'])) {
        $email = (string) $object['customer_details']['email'];
    }
    $email = premium_normalize_email($email);
    if ($email === '') {
        return array('http' => 200, 'status' => 'ignored', 'code' => '');
    }
    $lang = isset($metadata['language']) ? i18n_allow($metadata['language']) : 'de';
    $existing = premium_fetch_license_by_session($pdo, $session_id);
    if ($existing) {
        if (!empty($existing['notified_at'])) {
            return array('http' => 200, 'status' => 'duplicate', 'code' => '');
        }
        $code = premium_issue_code($pdo, $existing['id']);
        $existing = premium_fetch_license($pdo, $existing['id']);
        premium_deliver_license_mail($pdo, $existing, $code, $sender);
        return array('http' => 200, 'status' => 'duplicate', 'code' => '');
    }
    $code = premium_generate_license_code();
    $token = bin2hex(random_bytes(32));
    $event_id = isset($event['id']) ? substr((string) $event['id'], 0, 255) : '';
    try {
        $stmt = $pdo->prepare('INSERT INTO `premium_licenses` (`tier`, `status`, `buyer_email`, `code_hash`, `access_token`, `stripe_session_id`, `stripe_event_id`, `season_year`, `language`, `created_at`, `activated_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute(array(
            $tier,
            'active',
            $email,
            premium_secret_hash(premium_normalize_code($code)),
            $token,
            $session_id,
            ($event_id === '' ? null : $event_id),
            (int) date('Y', premium_now($now)),
            $lang,
            premium_sql_time($now),
            premium_sql_time($now),
        ));
    } catch (Exception $e) {
        $again = premium_fetch_license_by_session($pdo, $session_id);
        if ($again) {
            return array('http' => 200, 'status' => 'duplicate', 'code' => '');
        }
        error_log('Premium-Webhook: ' . $e->getMessage());
        return array('http' => 500, 'status' => 'error', 'code' => '');
    }
    $license = premium_fetch_license_by_session($pdo, $session_id);
    if ($license) {
        premium_deliver_license_mail($pdo, $license, $code, $sender);
    }
    return array('http' => 200, 'status' => 'created', 'code' => $code);
}

function premium_webhook_response(array $result) {
    $status = isset($result['status']) ? (string) $result['status'] : 'error';
    $http = isset($result['http']) ? (int) $result['http'] : 400;
    return array(
        'http' => $http,
        'body' => json_encode(array('status' => $status)),
    );
}

function premium_handle_webhook(PDO $pdo, $payload, $header, $secret, $sender = null, $now = null, $config = null) {
    if (!premium_stripe_signature_valid($payload, $header, $secret, $now)) {
        return array('http' => 400, 'status' => 'invalid_signature', 'code' => '');
    }
    $event = json_decode((string) $payload, true);
    if (!is_array($event)) {
        return array('http' => 400, 'status' => 'bad_json', 'code' => '');
    }
    return premium_apply_checkout_event($pdo, $event, $sender, $now, $config);
}

function premium_logo_directory($override = null) {
    static $custom = null;
    if ($override === false) {
        $custom = null;
    } elseif (is_string($override) && $override !== '') {
        $custom = $override;
    }
    if ($custom !== null) {
        return $custom;
    }
    return dirname(__DIR__) . '/storage/premium-logos';
}

function premium_logo_filename_ok($name) {
    return is_string($name) && preg_match('/\A[a-f0-9]{32}\.png\z/', $name) === 1;
}

function premium_image_to_png($image) {
    $width = imagesx($image);
    $height = imagesy($image);
    if ($width < 1 || $height < 1) {
        return '';
    }
    $canvas = imagecreatetruecolor($width, $height);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $clear = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefilledrectangle($canvas, 0, 0, $width, $height, $clear);
    imagealphablending($canvas, true);
    imagecopy($canvas, $image, 0, 0, 0, 0, $width, $height);
    ob_start();
    imagepng($canvas, null, 6);
    $out = ob_get_clean();
    imagedestroy($canvas);
    return is_string($out) ? $out : '';
}

function premium_logo_inspect($binary, $filename, $max_bytes = 2097152) {
    if (!is_string($binary) || $binary === '') {
        return array('ok' => false, 'error' => 'empty', 'binary' => '');
    }
    if (strlen($binary) > (int) $max_bytes) {
        return array('ok' => false, 'error' => 'size', 'binary' => '');
    }
    $name = strtolower((string) $filename);
    if (substr($name, -4) === '.svg' || substr($name, -4) === '.php' || substr($name, -5) === '.html' || substr($name, -4) === '.htm') {
        return array('ok' => false, 'error' => 'type', 'binary' => '');
    }
    $head = strtolower(substr($binary, 0, 512));
    if (strpos($head, '<svg') !== false || strpos($head, '<?php') !== false || strpos($head, '<html') !== false || strpos($head, '<script') !== false) {
        return array('ok' => false, 'error' => 'type', 'binary' => '');
    }
    $magic = false;
    if (substr($binary, 0, 8) === "\x89PNG\r\n\x1a\n") {
        $magic = true;
    } elseif (substr($binary, 0, 3) === "\xFF\xD8\xFF") {
        $magic = true;
    } elseif (substr($binary, 0, 6) === 'GIF87a' || substr($binary, 0, 6) === 'GIF89a') {
        $magic = true;
    } elseif (substr($binary, 0, 4) === 'RIFF' && substr($binary, 8, 4) === 'WEBP') {
        $magic = true;
    }
    if (!$magic || !function_exists('getimagesizefromstring') || !function_exists('imagecreatefromstring')) {
        return array('ok' => false, 'error' => 'type', 'binary' => '');
    }
    $info = @getimagesizefromstring($binary);
    $allowed = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);
    if (defined('IMAGETYPE_WEBP')) {
        $allowed[] = IMAGETYPE_WEBP;
    }
    if (!is_array($info) || !isset($info[2]) || !in_array($info[2], $allowed, true)) {
        return array('ok' => false, 'error' => 'type', 'binary' => '');
    }
    if ($info[0] < 1 || $info[1] < 1 || $info[0] > 4000 || $info[1] > 4000) {
        return array('ok' => false, 'error' => 'dimensions', 'binary' => '');
    }
    $image = @imagecreatefromstring($binary);
    if (!$image) {
        return array('ok' => false, 'error' => 'type', 'binary' => '');
    }
    $png = premium_image_to_png($image);
    imagedestroy($image);
    if ($png === '' || substr($png, 0, 8) !== "\x89PNG\r\n\x1a\n") {
        return array('ok' => false, 'error' => 'type', 'binary' => '');
    }
    return array('ok' => true, 'error' => '', 'binary' => $png);
}

function premium_store_logo(PDO $pdo, $license_id, $binary) {
    $dir = premium_logo_directory();
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        return array('ok' => false, 'error' => 'storage', 'file' => '');
    }
    $name = bin2hex(random_bytes(16)) . '.png';
    $path = $dir . DIRECTORY_SEPARATOR . $name;
    if (file_put_contents($path, $binary) === false) {
        return array('ok' => false, 'error' => 'storage', 'file' => '');
    }
    @chmod($path, 0640);
    $license = premium_fetch_license($pdo, $license_id);
    $old = ($license && isset($license['logo_file'])) ? (string) $license['logo_file'] : '';
    $stmt = $pdo->prepare('UPDATE `premium_licenses` SET `logo_file` = ? WHERE `id` = ?');
    $stmt->execute(array($name, (int) $license_id));
    if (premium_logo_filename_ok($old) && $old !== $name) {
        $old_path = $dir . DIRECTORY_SEPARATOR . $old;
        if (is_file($old_path)) {
            @unlink($old_path);
        }
    }
    return array('ok' => true, 'error' => '', 'file' => $name);
}

function premium_logo_resolve_path($name) {
    if (!premium_logo_filename_ok($name)) {
        return '';
    }
    $base = realpath(premium_logo_directory());
    if ($base === false) {
        return '';
    }
    $real = realpath($base . DIRECTORY_SEPARATOR . $name);
    if ($real === false || strpos($real, $base . DIRECTORY_SEPARATOR) !== 0) {
        return '';
    }
    return $real;
}

function premium_save_branding(PDO $pdo, array $license, array $post, $logo_binary = null, $logo_name = '') {
    if (!isset($license['status']) || $license['status'] !== 'active') {
        return array('ok' => false, 'error' => 'inactive');
    }
    $primary = premium_normalize_color(isset($post['color_primary']) ? $post['color_primary'] : '');
    $accent = premium_normalize_color(isset($post['color_accent']) ? $post['color_accent'] : '');
    if ($primary === null || $accent === null) {
        return array('ok' => false, 'error' => 'color');
    }
    $def = premium_tier($license['tier']);
    $greeting = null;
    if ($def && !empty($def['greeting'])) {
        $greeting = premium_clean_greeting(isset($post['greeting']) ? $post['greeting'] : '');
        if ($greeting === null) {
            return array('ok' => false, 'error' => 'greeting');
        }
    }
    if ($logo_binary !== null && $logo_binary !== '') {
        $inspected = premium_logo_inspect($logo_binary, $logo_name);
        if (!$inspected['ok']) {
            return array('ok' => false, 'error' => 'logo');
        }
        $stored = premium_store_logo($pdo, $license['id'], $inspected['binary']);
        if (!$stored['ok']) {
            return array('ok' => false, 'error' => 'logo');
        }
    }
    $stmt = $pdo->prepare('UPDATE `premium_licenses` SET `color_primary` = ?, `color_accent` = ?, `greeting` = ? WHERE `id` = ?');
    $stmt->execute(array(
        ($primary === '' ? null : $primary),
        ($accent === '' ? null : $accent),
        ($greeting === null || $greeting === '' ? null : $greeting),
        (int) $license['id'],
    ));
    return array('ok' => true, 'error' => '');
}

function premium_csv_delimiter_count($line, $delimiter) {
    $count = 0;
    $in_quotes = false;
    $length = strlen($line);
    for ($i = 0; $i < $length; $i++) {
        $char = $line[$i];
        if ($char === '"') {
            if ($in_quotes && $i + 1 < $length && $line[$i + 1] === '"') {
                $i++;
            } else {
                $in_quotes = !$in_quotes;
            }
            continue;
        }
        if (!$in_quotes && $char === $delimiter) {
            $count++;
        }
    }
    return $count;
}

function premium_csv_split_line($line, $delimiter) {
    $cells = array();
    $current = '';
    $in_quotes = false;
    $length = strlen($line);
    for ($i = 0; $i < $length; $i++) {
        $char = $line[$i];
        if ($in_quotes) {
            if ($char === '"') {
                if ($i + 1 < $length && $line[$i + 1] === '"') {
                    $current .= '"';
                    $i++;
                } else {
                    $in_quotes = false;
                }
            } else {
                $current .= $char;
            }
            continue;
        }
        if ($char === '"') {
            $in_quotes = true;
        } elseif ($char === $delimiter) {
            $cells[] = $current;
            $current = '';
        } else {
            $current .= $char;
        }
    }
    $cells[] = $current;
    return $cells;
}

function premium_csv_detect_delimiter($raw) {
    $lines = explode("\n", str_replace(array("\r\n", "\r"), "\n", (string) $raw));
    $sample = '';
    foreach ($lines as $line) {
        if (trim($line) !== '') {
            $sample = $line;
            break;
        }
    }
    $best = ';';
    $best_count = -1;
    foreach (array("\t", ';', ',') as $delimiter) {
        $count = premium_csv_delimiter_count($sample, $delimiter);
        if ($count > $best_count) {
            $best = $delimiter;
            $best_count = $count;
        }
    }
    return $best;
}

function premium_csv_header_map(array $cells) {
    $name_idx = null;
    $email_idx = null;
    foreach ($cells as $index => $cell) {
        $label = strtolower(trim((string) $cell));
        $label = str_replace(array(' ', '-', '_'), '', $label);
        if (in_array($label, array('name', 'nom', 'namen', 'teilnehmer', 'participant', 'fullname', 'vorname'), true)) {
            $name_idx = (int) $index;
        }
        if (in_array($label, array('email', 'mail', 'courriel', 'adresseemail', 'emailaddress'), true)) {
            $email_idx = (int) $index;
        }
    }
    if ($name_idx === null || $email_idx === null || $name_idx === $email_idx) {
        return null;
    }
    return array($name_idx, $email_idx);
}

function premium_csv_validate_row($name, $email) {
    $name = trim((string) $name);
    $email = premium_normalize_email($email);
    if ($name === '' || preg_match('/[\x00-\x1F\x7F]/', $name) || (function_exists('text_length') ? text_length($name) : strlen($name)) > 255) {
        return array('error' => 'name');
    }
    if ($email === '' || strlen($email) > 255) {
        return array('error' => 'email');
    }
    return array('name' => $name, 'email' => $email);
}

function premium_parse_csv($raw, array $existing_emails = array(), $max_rows = 300) {
    $raw = (string) $raw;
    if (substr($raw, 0, 3) === "\xEF\xBB\xBF") {
        $raw = substr($raw, 3);
    }
    $raw = str_replace(array("\r\n", "\r"), "\n", $raw);
    $delimiter = premium_csv_detect_delimiter($raw);
    $lines = explode("\n", $raw);
    $rows = array();
    $errors = array();
    $seen = array();
    foreach ($existing_emails as $existing) {
        $norm = premium_normalize_email($existing);
        if ($norm !== '') {
            $seen[$norm] = true;
        }
    }
    $header = null;
    $count = 0;
    $line_no = 0;
    $max_rows = (int) $max_rows;
    if ($max_rows < 1) {
        $max_rows = 1;
    }
    foreach ($lines as $line) {
        $line_no++;
        if (trim($line) === '') {
            continue;
        }
        $cells = premium_csv_split_line($line, $delimiter);
        if ($header === null) {
            $map = premium_csv_header_map($cells);
            if ($map !== null) {
                $header = $map;
                continue;
            }
            $header = array(0, 1);
        }
        $count++;
        if ($count > $max_rows) {
            $errors[] = array('line' => $line_no, 'error' => 'max');
            break;
        }
        $name = isset($cells[$header[0]]) ? $cells[$header[0]] : '';
        $email = isset($cells[$header[1]]) ? $cells[$header[1]] : '';
        $parsed = premium_csv_validate_row($name, $email);
        if (isset($parsed['error'])) {
            $errors[] = array('line' => $line_no, 'error' => $parsed['error']);
            continue;
        }
        if (isset($seen[$parsed['email']])) {
            $errors[] = array('line' => $line_no, 'error' => 'duplicate');
            continue;
        }
        $seen[$parsed['email']] = true;
        $rows[] = array('name' => $parsed['name'], 'email' => $parsed['email'], 'line' => $line_no);
    }
    return array('rows' => $rows, 'errors' => $errors, 'delimiter' => $delimiter);
}

function premium_csv_error_text(array $error) {
    $code = isset($error['error']) ? (string) $error['error'] : 'name';
    $key = 'premium.csv.err.' . $code;
    $label = t($key);
    if ($label === $key) {
        $label = $code;
    }
    return t('premium.csv.line', array(
        '{line}' => (string) (int) $error['line'],
        '{error}' => $label,
    ));
}

function premium_csv_store_preview($group_id, array $parsed) {
    if (function_exists('start_secure_session')) {
        start_secure_session();
    }
    $token = bin2hex(random_bytes(16));
    $_SESSION['premium_csv'] = array(
        'group_id' => (int) $group_id,
        'token' => $token,
        'rows' => $parsed['rows'],
        'errors' => $parsed['errors'],
    );
    return $token;
}

function premium_csv_preview_matches($session, $group_id, $token) {
    if (!is_array($session) || !isset($session['premium_csv']) || !is_array($session['premium_csv'])) {
        return false;
    }
    $preview = $session['premium_csv'];
    if ((int) $preview['group_id'] !== (int) $group_id) {
        return false;
    }
    if (!isset($preview['token']) || !is_string($token) || !is_string($preview['token']) || !hash_equals($preview['token'], $token)) {
        return false;
    }
    if (!empty($preview['errors']) || empty($preview['rows']) || !is_array($preview['rows'])) {
        return false;
    }
    return true;
}

function premium_add_exclusion(PDO $pdo, $group_id, $from, $to) {
    $from = (int) $from;
    $to = (int) $to;
    $group_id = (int) $group_id;
    if ($from < 1 || $to < 1 || $from === $to || $group_id < 1) {
        return;
    }
    $stmt = $pdo->prepare('SELECT 1 FROM `exclusions` WHERE `group_id` = ? AND `participant_id` = ? AND `excluded_participant_id` = ?');
    $stmt->execute(array($group_id, $from, $to));
    if ($stmt->fetchColumn()) {
        return;
    }
    insert_group_exclusion($pdo, $group_id, $from, $to);
}

function premium_import_rows(PDO $pdo, array $group, array $rows) {
    if (function_exists('i18n_has_language_column')) {
        i18n_has_language_column($pdo, 'participants');
    }
    $lang = function_exists('i18n_row_language') ? i18n_row_language($group) : null;
    if ($lang === null) {
        $lang = 'de';
    }
    $pdo->beginTransaction();
    try {
        $created = array();
        foreach ($rows as $row) {
            if (!isset($row['name'], $row['email'])) {
                throw new Exception('invalid row');
            }
            $token = generate_token();
            $id = i18n_insert_participant($pdo, (int) $group['id'], $row['name'], $row['email'], $token, $lang);
            $created[] = array('id' => $id, 'name' => $row['name'], 'token' => $token);
        }
        $pdo->commit();
        return $created;
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function premium_next_year_date($date) {
    if (!is_string($date) || preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $date) !== 1) {
        return null;
    }
    $dt = DateTime::createFromFormat('!Y-m-d', $date);
    if (!$dt || $dt->format('Y-m-d') !== $date) {
        return null;
    }
    $dt->modify('+1 year');
    return $dt->format('Y-m-d');
}

function premium_exclusions_map(array $rules) {
    $map = array();
    foreach ($rules as $rule) {
        if (!isset($rule['participant_id'], $rule['excluded_participant_id'])) {
            continue;
        }
        $giver = (int) $rule['participant_id'];
        if (!isset($map[$giver])) {
            $map[$giver] = array();
        }
        $map[$giver][] = (int) $rule['excluded_participant_id'];
    }
    return $map;
}

function premium_copy_group_for_next_year(PDO $pdo, $group_id) {
    if (!premium_tables_ready($pdo)) {
        return array('ok' => false, 'error' => 'migration', 'admin_token' => '', 'group_id' => 0);
    }
    $stmt = $pdo->prepare('SELECT * FROM `groups` WHERE `id` = ?');
    $stmt->execute(array((int) $group_id));
    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$group || empty($group['premium_license_id'])) {
        return array('ok' => false, 'error' => 'need', 'admin_token' => '', 'group_id' => 0);
    }
    $license = premium_fetch_license($pdo, $group['premium_license_id']);
    if (!$license || $license['status'] !== 'active') {
        return array('ok' => false, 'error' => 'need', 'admin_token' => '', 'group_id' => 0);
    }
    $people = $pdo->prepare('SELECT * FROM `participants` WHERE `group_id` = ? ORDER BY `id` ASC');
    $people->execute(array((int) $group['id']));
    $participants = $people->fetchAll(PDO::FETCH_ASSOC);
    $rules = $pdo->prepare('SELECT * FROM `exclusions` WHERE `group_id` = ?');
    $rules->execute(array((int) $group['id']));
    $exclusions = $rules->fetchAll(PDO::FETCH_ASSOC);
    if (function_exists('i18n_has_language_column')) {
        i18n_has_language_column($pdo, 'groups');
        i18n_has_language_column($pdo, 'participants');
    }

    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('UPDATE `premium_licenses` SET `season_year` = `season_year` WHERE `id` = ?');
        $lock->execute(array((int) $license['id']));
        $used = premium_group_count($pdo, $license['id']);
        if (!premium_has_room($license['tier'], $used)) {
            $pdo->rollBack();
            return array('ok' => false, 'error' => 'limit', 'admin_token' => '', 'group_id' => 0);
        }
        $admin_token = generate_token();
        $invite_token = generate_token();
        $lang = (isset($group['language']) && function_exists('i18n_is_allowed') && i18n_is_allowed($group['language'])) ? $group['language'] : 'de';
        $new_date = premium_next_year_date(isset($group['gift_exchange_date']) ? (string) $group['gift_exchange_date'] : '');
        $new_id = i18n_insert_group(
            $pdo,
            $group['name'],
            $admin_token,
            $invite_token,
            $group['admin_email'],
            $group['budget'],
            $group['description'],
            $new_date,
            $lang
        );
        try {
            $pdo->exec('SAVEPOINT premium_copy_lang');
            $set_lang = $pdo->prepare('UPDATE `groups` SET `language` = ? WHERE `id` = ?');
            $set_lang->execute(array($lang, (int) $new_id));
            $pdo->exec('RELEASE SAVEPOINT premium_copy_lang');
        } catch (Exception $e) {
            try {
                $pdo->exec('ROLLBACK TO SAVEPOINT premium_copy_lang');
            } catch (Exception $ignored) {
            }
        }
        $link = $pdo->prepare('UPDATE `groups` SET `premium_license_id` = ? WHERE `id` = ?');
        $link->execute(array((int) $license['id'], (int) $new_id));
        $map = array();
        foreach ($participants as $person) {
            $email = (isset($person['email']) && trim((string) $person['email']) !== '') ? $person['email'] : null;
            $person_lang = (isset($person['language']) && function_exists('i18n_is_allowed') && i18n_is_allowed($person['language'])) ? $person['language'] : $lang;
            $new_person = i18n_insert_participant($pdo, $new_id, $person['name'], $email, generate_token(), $person_lang);
            if (isset($person['wishlist']) && $person['wishlist'] !== null && $person['wishlist'] !== '') {
                $wish = $pdo->prepare('UPDATE `participants` SET `wishlist` = ? WHERE `id` = ?');
                $wish->execute(array($person['wishlist'], $new_person));
            }
            $map[(int) $person['id']] = (int) $new_person;
        }
        foreach ($exclusions as $rule) {
            $from = isset($map[(int) $rule['participant_id']]) ? $map[(int) $rule['participant_id']] : 0;
            $to = isset($map[(int) $rule['excluded_participant_id']]) ? $map[(int) $rule['excluded_participant_id']] : 0;
            premium_add_exclusion($pdo, $new_id, $from, $to);
        }
        foreach ($participants as $person) {
            $assigned = isset($person['assigned_to']) ? (int) $person['assigned_to'] : 0;
            $from = isset($map[(int) $person['id']]) ? $map[(int) $person['id']] : 0;
            $to = isset($map[$assigned]) ? $map[$assigned] : 0;
            premium_add_exclusion($pdo, $new_id, $from, $to);
        }
        $pdo->commit();
        return array('ok' => true, 'error' => '', 'admin_token' => $admin_token, 'group_id' => (int) $new_id);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Premium kopieren: ' . $e->getMessage());
        return array('ok' => false, 'error' => 'fail', 'admin_token' => '', 'group_id' => 0);
    }
}

function premium_hr_rows(array $participants, array $group) {
    $reminder_sent = !empty($group['reminder_sent_at']);
    $rows = array();
    foreach ($participants as $person) {
        if (!is_array($person)) {
            continue;
        }
        $email = isset($person['email']) ? trim((string) $person['email']) : '';
        $wishlist = isset($person['wishlist']) ? trim((string) $person['wishlist']) : '';
        if ($email === '') {
            $reminder = 'no_email';
        } elseif ($reminder_sent) {
            $reminder = 'sent';
        } else {
            $reminder = 'open';
        }
        $rows[] = array(
            'name' => isset($person['name']) ? (string) $person['name'] : '',
            'email' => $email,
            'wishlist' => ($wishlist !== ''),
            'reminder' => $reminder,
        );
    }
    usort($rows, function ($left, $right) {
        $a = function_exists('mb_strtolower') ? mb_strtolower($left['name'], 'UTF-8') : strtolower($left['name']);
        $b = function_exists('mb_strtolower') ? mb_strtolower($right['name'], 'UTF-8') : strtolower($right['name']);
        if ($a === $b) {
            return 0;
        }
        return ($a < $b) ? -1 : 1;
    });
    return $rows;
}

function premium_hr_table_html(array $rows) {
    $html = '<table class="premium-hr-table"><thead><tr><th>' . h(t('premium.hr.name')) . '</th><th>' . h(t('premium.hr.email')) . '</th><th>' . h(t('premium.hr.wishlist')) . '</th><th>' . h(t('premium.hr.reminder')) . '</th></tr></thead><tbody>';
    if (count($rows) === 0) {
        $html .= '<tr><td colspan="4">' . h(t('premium.hr.empty')) . '</td></tr>';
    }
    foreach ($rows as $row) {
        $wish = !empty($row['wishlist']) ? t('premium.hr.wishlist_yes') : t('premium.hr.wishlist_no');
        $reminder_key = 'premium.hr.reminder_' . (isset($row['reminder']) ? $row['reminder'] : 'open');
        $html .= '<tr><td>' . h($row['name']) . '</td><td>' . h($row['email']) . '</td><td>' . h($wish) . '</td><td>' . h(t($reminder_key)) . '</td></tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}

function premium_reveal_steps(array $participants) {
    $by_id = array();
    foreach ($participants as $person) {
        if (is_array($person) && isset($person['id'])) {
            $by_id[(int) $person['id']] = $person;
        }
    }
    $steps = array();
    foreach ($participants as $person) {
        if (!is_array($person) || empty($person['assigned_to']) || !isset($by_id[(int) $person['assigned_to']])) {
            return array();
        }
        $receiver = $by_id[(int) $person['assigned_to']];
        $steps[] = array(
            'giver' => function_exists('reveal_display_name') ? reveal_display_name($person['name']) : trim((string) $person['name']),
            'receiver' => function_exists('reveal_display_name') ? reveal_display_name($receiver['name']) : trim((string) $receiver['name']),
        );
    }
    usort($steps, function ($left, $right) {
        $a = function_exists('mb_strtolower') ? mb_strtolower($left['giver'], 'UTF-8') : strtolower($left['giver']);
        $b = function_exists('mb_strtolower') ? mb_strtolower($right['giver'], 'UTF-8') : strtolower($right['giver']);
        if ($a === $b) {
            return 0;
        }
        return ($a < $b) ? -1 : 1;
    });
    return $steps;
}

function premium_reveal_allowed($session, $group_id, $now = null) {
    if (!is_array($session) || empty($session['premium_reveal']) || !is_array($session['premium_reveal'])) {
        return false;
    }
    $key = (int) $group_id;
    if (!isset($session['premium_reveal'][$key])) {
        return false;
    }
    return (int) $session['premium_reveal'][$key] >= premium_now($now);
}

function premium_reveal_unlock(array $post, array $session, $group_id, $csrf_ok, $now = null) {
    if (!$csrf_ok) {
        return array('session' => $session, 'error' => 'csrf');
    }
    if (!isset($post['confirm_reveal']) || (string) $post['confirm_reveal'] !== '1') {
        return array('session' => $session, 'error' => 'confirm');
    }
    if (!isset($session['premium_reveal']) || !is_array($session['premium_reveal'])) {
        $session['premium_reveal'] = array();
    }
    $session['premium_reveal'][(int) $group_id] = premium_now($now) + 7200;
    return array('session' => $session, 'error' => '');
}

function premium_brand_state($license) {
    if (!is_array($license)) {
        return array('class' => '', 'style' => '', 'logo' => '', 'greeting' => '', 'credit' => false);
    }
    $primary = premium_normalize_color(isset($license['color_primary']) ? $license['color_primary'] : '');
    $accent = premium_normalize_color(isset($license['color_accent']) ? $license['color_accent'] : '');
    $style = '';
    if ($primary !== null && $primary !== '') {
        $style .= '--premium-primary:' . $primary . ';';
    }
    if ($accent !== null && $accent !== '') {
        $style .= '--premium-accent:' . $accent . ';';
    }
    $logo = '';
    if (!empty($license['logo_file']) && premium_logo_filename_ok($license['logo_file'])) {
        $logo = '/premium-logo.php?name=' . rawurlencode($license['logo_file']);
    }
    $def = premium_tier(isset($license['tier']) ? $license['tier'] : '');
    $greeting = '';
    if ($def && !empty($def['greeting']) && !empty($license['greeting'])) {
        $greeting = (string) $license['greeting'];
    }
    $credit = !($def && !empty($def['hide_credit']));
    return array(
        'class' => ' premium-branded',
        'style' => $style,
        'logo' => $logo,
        'greeting' => $greeting,
        'credit' => $credit,
    );
}

function premium_page_class($license) {
    $state = premium_brand_state($license);
    return $state['class'];
}

function premium_page_style_attr($license) {
    $state = premium_brand_state($license);
    if ($state['style'] === '') {
        return '';
    }
    return ' style="' . h($state['style']) . '"';
}

function premium_hero_extras_html($license) {
    $state = premium_brand_state($license);
    if ($state['class'] === '') {
        return '';
    }
    $html = '';
    if ($state['logo'] !== '') {
        $html .= '<img class="premium-logo" src="' . h($state['logo']) . '" alt="">';
    }
    if ($state['greeting'] !== '') {
        $html .= '<p class="premium-greeting">' . nl2br(h($state['greeting'])) . '</p>';
    }
    if ($state['credit']) {
        $html .= '<p class="premium-credit">powered by wichtlä.ch</p>';
    }
    return $html;
}

function premium_create_form_html($enabled = null, $definitions = null, $checked = false, array $values = array()) {
    if (!premium_feature_enabled($enabled)) {
        return '';
    }
    $definitions = ($definitions === null) ? premium_tier_definitions() : $definitions;
    $href = function_exists('i18n_href') ? i18n_href('firmen-premium') : '/firmen-premium';
    $email = isset($values['email']) ? (string) $values['email'] : '';
    $code = isset($values['code']) ? (string) $values['code'] : '';
    $items = '';
    foreach (premium_feature_keys() as $key) {
        $items .= '<li>' . h(t($key)) . '</li>';
    }
    $prices = array();
    foreach ($definitions as $key => $def) {
        if (!is_array($def) || !isset($def['price'])) {
            continue;
        }
        $prices[] = premium_tier_label($key) . ' ' . premium_price_label($def['price']) . ', ' . premium_limit_label($def);
    }
    $html = '<div class="form-group premium-opt-in">';
    $html .= '<input class="premium-opt-in-check" type="checkbox" id="firmen-premium-toggle" name="firmen_premium" value="1"' . ($checked ? ' checked' : '') . ' aria-controls="firmen-premium-box">';
    $html .= '<label for="firmen-premium-toggle">' . h(t('premium.create.label')) . '</label>';
    $html .= '<div class="premium-opt-in-box" id="firmen-premium-box">';
    $html .= '<p class="premium-opt-in-lead">' . h(t('premium.create.hint')) . '</p>';
    $html .= '<ul class="premium-opt-in-features">' . $items . '</ul>';
    $html .= '<p class="premium-opt-in-prices">' . h(implode(' · ', $prices)) . '</p>';
    $html .= '<p class="premium-opt-in-same">' . h(t('premium.tier.same')) . '</p>';
    $html .= '<p>' . h(t('premium.create.activate')) . '</p>';
    $html .= '<div class="premium-opt-in-fields">';
    $html .= '<label for="premium_license_email">' . h(t('premium.claim.email')) . '</label>';
    $html .= '<input type="email" id="premium_license_email" name="premium_license_email" value="' . h($email) . '" autocomplete="email">';
    $html .= '<label for="premium_license_code">' . h(t('premium.claim.code')) . '</label>';
    $html .= '<input type="text" id="premium_license_code" name="premium_license_code" value="' . h($code) . '" autocomplete="off" spellcheck="false">';
    $html .= '</div>';
    $html .= '<p class="premium-opt-in-buy"><a href="' . h($href) . '#kaufen">' . h(t('premium.create.buy')) . '</a></p>';
    $html .= '</div></div>';
    return $html;
}

function premium_after_group_created(PDO $pdo, $group_id, array $post, $sender = null, $ip = 'unknown') {
    if (!premium_feature_enabled() || !isset($post['firmen_premium']) || (string) $post['firmen_premium'] !== '1') {
        return null;
    }
    if (!premium_tables_ready($pdo)) {
        return array('type' => 'error', 'text' => t('premium.claim.need_migration'));
    }
    $code = isset($post['premium_license_code']) ? trim((string) $post['premium_license_code']) : '';
    $email = isset($post['premium_license_email']) ? trim((string) $post['premium_license_email']) : '';
    if ($code !== '') {
        $result = premium_claim_with_code($pdo, $group_id, $code, $ip);
        $message = premium_public_code_message($result['ok'] ? '' : $result['error']);
        return array('type' => $message[0], 'text' => $message[1]);
    }
    if ($email !== '') {
        $result = premium_request_email_claim($pdo, $group_id, $email, $ip, $sender);
        $message = premium_public_claim_message($result['status']);
        return array('type' => $message[0], 'text' => $message[1]);
    }
    return array('type' => 'success', 'text' => t('premium.create.later'));
}

function premium_read_csv_upload(array $file) {
    if (!isset($file['error']) || (int) $file['error'] === UPLOAD_ERR_NO_FILE) {
        return array('ok' => false, 'error' => 'empty', 'raw' => '');
    }
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        return array('ok' => false, 'error' => 'upload', 'raw' => '');
    }
    $name = isset($file['name']) ? strtolower((string) $file['name']) : '';
    if ($name !== '' && preg_match('/\.(csv|txt)\z/', $name) !== 1) {
        return array('ok' => false, 'error' => 'type', 'raw' => '');
    }
    if (!isset($file['size']) || (int) $file['size'] > 1048576 || (int) $file['size'] < 1) {
        return array('ok' => false, 'error' => 'size', 'raw' => '');
    }
    $tmp = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return array('ok' => false, 'error' => 'upload', 'raw' => '');
    }
    $raw = file_get_contents($tmp);
    if (!is_string($raw)) {
        return array('ok' => false, 'error' => 'upload', 'raw' => '');
    }
    return array('ok' => true, 'error' => '', 'raw' => $raw);
}

function premium_group_is_premium(PDO $pdo, array $group) {
    return premium_license_for_group($pdo, $group) !== null;
}

function premium_admin_handle(PDO $pdo, array $group, array $post, array $files = array(), $sender = null, $ip = 'unknown') {
    $empty = array('redirect' => '', 'reload' => false, 'notice' => array('type' => 'success', 'text' => ''));
    if (!premium_feature_enabled()) {
        return $empty;
    }
    $action = isset($post['premium_action']) ? (string) $post['premium_action'] : '';
    if (!premium_tables_ready($pdo)) {
        $empty['notice'] = array('type' => 'error', 'text' => t('premium.claim.need_migration'));
        return $empty;
    }
    if ($action === 'claim_email') {
        $result = premium_request_email_claim($pdo, $group['id'], isset($post['license_email']) ? $post['license_email'] : '', $ip, $sender);
        $message = premium_public_claim_message($result['status']);
        $empty['notice'] = array('type' => $message[0], 'text' => $message[1]);
        return $empty;
    }
    if ($action === 'claim_code') {
        $result = premium_claim_with_code($pdo, $group['id'], isset($post['license_code']) ? $post['license_code'] : '', $ip);
        $message = premium_public_code_message($result['ok'] ? '' : $result['error']);
        $empty['notice'] = array('type' => $message[0], 'text' => $message[1]);
        $empty['reload'] = !empty($result['ok']);
        return $empty;
    }
    if ($action === 'confirm_short') {
        $result = premium_confirm_short_code($pdo, $group['id'], isset($post['short_code']) ? $post['short_code'] : '', $ip);
        $message = premium_public_code_message($result['ok'] ? '' : $result['error']);
        $empty['notice'] = array('type' => $message[0], 'text' => $message[1]);
        $empty['reload'] = !empty($result['ok']);
        return $empty;
    }
    $license = premium_license_for_group($pdo, $group);
    if ($action === 'send_overview') {
        if (!$license) {
            $empty['notice'] = array('type' => 'error', 'text' => t('premium.copy.need'));
            return $empty;
        }
        if (!premium_overview_resend_allowed($license['id'])) {
            $empty['notice'] = array('type' => 'error', 'text' => t('premium.claim.rate'));
            return $empty;
        }
        $token = premium_issue_access_token($pdo, $license);
        $mail = premium_overview_mail(premium_overview_url($token), isset($license['language']) ? $license['language'] : 'de');
        $ok = premium_send($sender, $license['buyer_email'], $mail[0], $mail[1], $mail[2]);
        $empty['notice'] = $ok
            ? array('type' => 'success', 'text' => t('premium.overview.resent'))
            : array('type' => 'error', 'text' => t('premium.claim.mail_fail'));
        return $empty;
    }
    if ($action === 'copy') {
        if (!isset($post['confirm_copy']) || (string) $post['confirm_copy'] !== '1') {
            $empty['notice'] = array('type' => 'error', 'text' => t('premium.copy.need_confirm'));
            return $empty;
        }
        $copied = premium_copy_group_for_next_year($pdo, $group['id']);
        if (!$copied['ok']) {
            $key = ($copied['error'] === 'limit') ? 'premium.copy.limit' : 'premium.copy.need';
            if ($copied['error'] === 'fail' || $copied['error'] === 'migration') {
                $key = 'premium.copy.fail';
            }
            $empty['notice'] = array('type' => 'error', 'text' => t($key));
            return $empty;
        }
        $new_group = null;
        $loaded = $pdo->prepare('SELECT * FROM `groups` WHERE `id` = ?');
        $loaded->execute(array((int) $copied['group_id']));
        $fetched = $loaded->fetch(PDO::FETCH_ASSOC);
        if (is_array($fetched)) {
            $new_group = $fetched;
        }
        $admin_token = ($new_group && isset($new_group['admin_token'])) ? (string) $new_group['admin_token'] : (string) $copied['admin_token'];
        $mail = ($new_group !== null) ? build_created_group_mail($new_group) : null;
        $mail_ok = true;
        if ($mail !== null) {
            $mail_ok = premium_send($sender, $mail['to'], $mail['subject'], $mail['html'], $mail['plain']);
            if (!$mail_ok && function_exists('mask_email')) {
                error_log('E-Mail mit Admin-Link konnte nicht an ' . mask_email($mail['to']) . ' gesendet werden.');
            }
        }
        $admin_link = get_display_url('/admin.php?token=' . urlencode($admin_token));
        $empty['notice'] = array(
            'type' => 'success',
            'text' => t('premium.copy.ok'),
            'link' => $admin_link,
        );
        $empty['redirect'] = '/admin.php?token=' . urlencode($admin_token);
        if ($mail !== null && !$mail_ok) {
            $empty['redirect'] .= '&email_error=1';
        }
        return $empty;
    }
    if ($action === 'csv_preview' || $action === 'csv_commit') {
        if (!$license) {
            $empty['notice'] = array('type' => 'error', 'text' => t('premium.copy.need'));
            return $empty;
        }
        if (!empty($group['is_drawn'])) {
            $empty['notice'] = array('type' => 'error', 'text' => t('premium.csv.locked'));
            return $empty;
        }
        if (!function_exists('bulk_import_attempt_allowed') || !bulk_import_attempt_allowed(isset($group['admin_token']) ? $group['admin_token'] : '', $ip)) {
            $empty['notice'] = array('type' => 'error', 'text' => t('premium.claim.rate'));
            return $empty;
        }
    }
    if ($action === 'csv_preview') {
        $raw = isset($post['premium_csv_text']) ? (string) $post['premium_csv_text'] : '';
        if (isset($files['premium_csv_file']) && is_array($files['premium_csv_file']) && (int) $files['premium_csv_file']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = premium_read_csv_upload($files['premium_csv_file']);
            if (!$upload['ok']) {
                $empty['notice'] = array('type' => 'error', 'text' => t('premium.csv.bad_file'));
                return $empty;
            }
            $raw = $upload['raw'];
        }
        $existing = array();
        $stmt = $pdo->prepare('SELECT `email` FROM `participants` WHERE `group_id` = ?');
        $stmt->execute(array((int) $group['id']));
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['email'])) {
                $existing[] = $row['email'];
            }
        }
        $parsed = premium_parse_csv($raw, $existing);
        premium_csv_store_preview($group['id'], $parsed);
        if (count($parsed['rows']) === 0 && count($parsed['errors']) === 0) {
            $empty['notice'] = array('type' => 'error', 'text' => t('premium.csv.empty'));
            return $empty;
        }
        $empty['notice'] = array('type' => count($parsed['errors']) ? 'error' : 'success', 'text' => count($parsed['errors']) ? t('premium.csv.errors') : t('premium.csv.ready'));
        return $empty;
    }
    if ($action === 'csv_commit') {
        $session = (isset($_SESSION) && is_array($_SESSION)) ? $_SESSION : array();
        $token = isset($post['preview_token']) ? (string) $post['preview_token'] : '';
        if (!premium_csv_preview_matches($session, $group['id'], $token)) {
            $empty['notice'] = array('type' => 'error', 'text' => t('premium.csv.errors'));
            return $empty;
        }
        try {
            $created = premium_import_rows($pdo, $group, $session['premium_csv']['rows']);
        } catch (Exception $e) {
            error_log('Premium CSV: ' . $e->getMessage());
            $empty['notice'] = array('type' => 'error', 'text' => t('premium.csv.fail'));
            return $empty;
        }
        unset($_SESSION['premium_csv']);
        $empty['reload'] = true;
        $empty['notice'] = array('type' => 'success', 'text' => t('premium.csv.imported', array('{count}' => (string) count($created))));
        return $empty;
    }
    return $empty;
}

function premium_admin_context(PDO $pdo, array $group, $session = null) {
    $ready = premium_tables_ready($pdo);
    $license = null;
    $used = 0;
    if ($ready && !empty($group['premium_license_id'])) {
        $fetched = premium_fetch_license($pdo, $group['premium_license_id']);
        if ($fetched && isset($fetched['status']) && $fetched['status'] === 'active') {
            $license = $fetched;
            $used = premium_group_count($pdo, $license['id']);
        }
    }
    $preview = null;
    if (is_array($session) && isset($session['premium_csv']) && is_array($session['premium_csv']) && (int) $session['premium_csv']['group_id'] === (int) $group['id']) {
        $preview = $session['premium_csv'];
    }
    return array('ready' => $ready, 'license' => $license, 'used' => $used, 'preview' => $preview);
}

function premium_slots_text(array $license, $used) {
    $def = premium_tier($license['tier']);
    if ($def && (!isset($def['limit']) || $def['limit'] === null)) {
        return t('premium.overview.slots_open', array('{used}' => (string) (int) $used));
    }
    $limit = ($def && isset($def['limit'])) ? (int) $def['limit'] : 0;
    return t('premium.overview.slots', array('{used}' => (string) (int) $used, '{limit}' => (string) $limit));
}

function premium_admin_section_html(PDO $pdo, array $group, $session = null, $highlight = false) {
    if (!premium_feature_enabled()) {
        return '';
    }
    $ctx = premium_admin_context($pdo, $group, $session);
    $html = '<section class="section-card" id="premium" aria-labelledby="premium-heading">';
    $html .= '<div class="section-card-header"><span class="section-icon" aria-hidden="true">✦</span><h2 id="premium-heading">' . h(t('premium.admin.title')) . '</h2></div>';
    if (!$ctx['ready']) {
        $html .= '<p class="section-description">' . h(t('premium.claim.need_migration')) . '</p></section>';
        return $html;
    }
    if ($highlight && !$ctx['license']) {
        $html .= '<p class="section-description">' . h(t('premium.create.later')) . '</p>';
    }
    if ($ctx['license']) {
        $html .= '<p>' . h(t('premium.admin.active', array('{tier}' => premium_tier_label($ctx['license']['tier'])))) . ' ' . h(premium_slots_text($ctx['license'], $ctx['used'])) . '</p>';
        $html .= '<p class="section-description">' . h(t('premium.admin.brand_hint')) . '</p>';
        $html .= '<p><a class="button secondary" href="/premium-hr.php?token=' . h(urlencode($group['admin_token'])) . '">' . h(t('premium.hr.open')) . '</a></p>';
        $html .= '<form method="post" class="premium-inline-form">' . csrf_input();
        $html .= '<input type="hidden" name="premium_action" value="send_overview">';
        $html .= '<button type="submit" class="button secondary">' . h(t('premium.overview.resend')) . '</button></form>';
        $html .= '<form method="post" class="premium-copy-form">' . csrf_input();
        $html .= '<input type="hidden" name="premium_action" value="copy">';
        $html .= '<p class="section-description">' . h(t('premium.copy.help')) . '</p>';
        $html .= '<label class="reveal-confirm-label" for="confirm-copy"><input type="checkbox" id="confirm-copy" name="confirm_copy" value="1"> ' . h(t('premium.copy.confirm')) . '</label>';
        $html .= '<button type="submit" class="button primary">' . h(t('premium.copy.button')) . '</button></form>';
        if (empty($group['is_drawn'])) {
            $html .= '<form method="post" enctype="multipart/form-data" class="premium-csv-form">' . csrf_input();
            $html .= '<input type="hidden" name="premium_action" value="csv_preview">';
            $html .= '<h3>' . h(t('premium.csv.title')) . '</h3>';
            $html .= '<p class="section-description">' . h(t('premium.csv.help')) . '</p>';
            $html .= '<label for="premium_csv_file">' . h(t('premium.csv.file')) . '</label>';
            $html .= '<input type="file" id="premium_csv_file" name="premium_csv_file" accept=".csv,.txt,text/csv,text/plain">';
            $html .= '<label for="premium_csv_text">' . h(t('premium.csv.paste')) . '</label>';
            $html .= '<textarea id="premium_csv_text" name="premium_csv_text" rows="6"></textarea>';
            $html .= '<button type="submit" class="button secondary">' . h(t('premium.csv.preview')) . '</button></form>';
            if (is_array($ctx['preview'])) {
                $html .= '<div class="premium-csv-preview">';
                foreach ($ctx['preview']['errors'] as $error) {
                    $html .= '<p class="notification error" role="alert">' . h(premium_csv_error_text($error)) . '</p>';
                }
                if (!empty($ctx['preview']['rows'])) {
                    $html .= '<table class="premium-hr-table"><thead><tr><th>' . h(t('premium.hr.name')) . '</th><th>' . h(t('premium.hr.email')) . '</th></tr></thead><tbody>';
                    foreach ($ctx['preview']['rows'] as $row) {
                        $html .= '<tr><td>' . h($row['name']) . '</td><td>' . h($row['email']) . '</td></tr>';
                    }
                    $html .= '</tbody></table>';
                }
                if (empty($ctx['preview']['errors']) && !empty($ctx['preview']['rows'])) {
                    $html .= '<form method="post">' . csrf_input();
                    $html .= '<input type="hidden" name="premium_action" value="csv_commit">';
                    $html .= '<input type="hidden" name="preview_token" value="' . h($ctx['preview']['token']) . '">';
                    $html .= '<button type="submit" class="button primary">' . h(t('premium.csv.commit')) . '</button></form>';
                }
                $html .= '</div>';
            }
        }
    } else {
        $buy = function_exists('i18n_href') ? i18n_href('firmen-premium') : '/firmen-premium';
        $html .= '<p class="section-description">' . h(t('premium.admin.inactive')) . '</p>';
        $html .= '<p><a href="' . h($buy) . '#kaufen">' . h(t('premium.create.buy')) . '</a></p>';
        $html .= '<form method="post" class="premium-inline-form">' . csrf_input();
        $html .= '<input type="hidden" name="premium_action" value="claim_email">';
        $html .= '<label for="license_email">' . h(t('premium.claim.email')) . '</label>';
        $html .= '<input type="email" id="license_email" name="license_email" required autocomplete="email">';
        $html .= '<button type="submit" class="button secondary">' . h(t('premium.claim.email_submit')) . '</button></form>';
        $html .= '<form method="post" class="premium-inline-form">' . csrf_input();
        $html .= '<input type="hidden" name="premium_action" value="confirm_short">';
        $html .= '<label for="short_code">' . h(t('premium.claim.short')) . '</label>';
        $html .= '<input type="text" id="short_code" name="short_code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6">';
        $html .= '<button type="submit" class="button secondary">' . h(t('premium.claim.short_submit')) . '</button></form>';
        $html .= '<form method="post" class="premium-inline-form">' . csrf_input();
        $html .= '<input type="hidden" name="premium_action" value="claim_code">';
        $html .= '<label for="license_code">' . h(t('premium.claim.code')) . '</label>';
        $html .= '<input type="text" id="license_code" name="license_code" autocomplete="off" spellcheck="false">';
        $html .= '<button type="submit" class="button primary">' . h(t('premium.claim.code_submit')) . '</button></form>';
    }
    $html .= '</section>';
    return $html;
}

function premium_pricing_cards_html($definitions = null) {
    $definitions = ($definitions === null) ? premium_tier_definitions() : $definitions;
    $html = '<div class="premium-tier-grid">';
    foreach ($definitions as $key => $def) {
        if (!is_array($def)) {
            continue;
        }
        $html .= '<article class="premium-tier-card">';
        $html .= '<h3>' . h(premium_tier_label($key)) . '</h3>';
        $html .= '<p class="premium-tier-price">' . h(premium_price_label($def['price'])) . '</p>';
        $html .= '<p>' . h(premium_limit_label($def)) . '</p>';
        $html .= '</article>';
    }
    $html .= '</div>';
    $html .= '<p class="premium-tier-same">' . h(t('premium.tier.same')) . '</p>';
    return $html;
}

function premium_tier_choices_html($name) {
    $html = '<div class="premium-tier-choices">';
    $first = true;
    foreach (premium_tier_definitions() as $key => $def) {
        $id = $name . '-' . $key;
        $html .= '<label for="' . h($id) . '"><input type="radio" name="tier" id="' . h($id) . '" value="' . h($key) . '"' . ($first ? ' required' : '') . '> ';
        $html .= h(premium_tier_label($key) . ' · ' . premium_price_label($def['price'])) . '</label>';
        $first = false;
    }
    $html .= '</div>';
    return $html;
}

function premium_purchase_notices_html() {
    $html = '';
    $checkout = isset($GLOBALS['premium_checkout_note']) ? (string) $GLOBALS['premium_checkout_note'] : '';
    $state = isset($GLOBALS['premium_checkout_state']) ? (string) $GLOBALS['premium_checkout_state'] : '';
    if ($checkout !== '') {
        $class = ($state === 'ok') ? 'success' : 'warning';
        $html .= '<div class="notification ' . $class . '" role="status">' . h($checkout) . '</div>';
    }
    $notice = isset($GLOBALS['premium_page_notice']) ? $GLOBALS['premium_page_notice'] : null;
    if (is_array($notice) && !empty($notice['text'])) {
        $class = (isset($notice['type']) && $notice['type'] === 'success') ? 'success' : 'error';
        $html .= '<div class="notification ' . $class . '" role="status">' . h($notice['text']) . '</div>';
    }
    return $html;
}

function premium_purchase_section_html() {
    if (!premium_feature_enabled()) {
        return '';
    }
    $html = '<section class="content-section" id="kaufen">';
    $html .= premium_purchase_notices_html();
    $html .= '<h2>' . h(t('premium.buy.heading')) . '</h2>';
    if (premium_stripe_checkout_ready()) {
        $html .= '<form method="post" class="premium-buy-form">';
        $html .= csrf_input();
        $html .= '<input type="hidden" name="premium_buy" value="stripe">';
        $html .= '<h3>' . h(t('premium.buy.stripe')) . '</h3>';
        $html .= premium_tier_choices_html('stripe_tier');
        $html .= '<label for="stripe_email">' . h(t('premium.checkout.email')) . '</label>';
        $html .= '<input type="email" id="stripe_email" name="email" required autocomplete="email">';
        $html .= '<button type="submit" class="button primary">' . h(t('premium.buy.stripe_submit')) . '</button>';
        $html .= '<p class="support-note">' . h(t('premium.buy.stripe_note')) . '</p>';
        $html .= '</form>';
    } else {
        $html .= '<p class="section-description">' . h(t('premium.checkout.missing')) . '</p>';
    }
    $html .= '<form method="post" class="premium-buy-form">';
    $html .= csrf_input();
    $html .= '<input type="hidden" name="premium_buy" value="invoice">';
    $html .= '<h3>' . h(t('premium.buy.invoice')) . '</h3>';
    $html .= premium_tier_choices_html('invoice_tier');
    $html .= '<label for="invoice_company">' . h(t('premium.invoice.company')) . '</label>';
    $html .= '<input type="text" id="invoice_company" name="company" required maxlength="255">';
    $html .= '<label for="invoice_address">' . h(t('premium.invoice.address')) . '</label>';
    $html .= '<textarea id="invoice_address" name="address" required rows="3" maxlength="1000"></textarea>';
    $html .= '<label for="invoice_email">' . h(t('premium.invoice.email')) . '</label>';
    $html .= '<input type="email" id="invoice_email" name="email" required autocomplete="email">';
    $html .= '<label for="invoice_contact">' . h(t('premium.invoice.name')) . '</label>';
    $html .= '<input type="text" id="invoice_contact" name="contact" maxlength="255">';
    $html .= '<button type="submit" class="button secondary">' . h(t('premium.invoice.submit')) . '</button>';
    $html .= '</form></section>';
    return $html;
}

function premium_company_feature_pairs() {
    return array(
        array('premium.feature.csv', 'premium.company.csv'),
        array('premium.feature.brand', 'premium.company.brand'),
        array('premium.feature.rules', 'premium.company.rules'),
        array('premium.feature.copy', 'premium.company.copy'),
        array('premium.feature.hr', 'premium.company.hr'),
        array('premium.feature.greeting', 'premium.company.greeting'),
        array('premium.feature.nobrand', 'premium.company.nobrand'),
        array('premium.feature.domain', 'premium.company.domain'),
        array('premium.feature.overview', 'premium.company.overview'),
    );
}

function premium_company_extras_html() {
    if (!premium_feature_enabled() || !function_exists('t') || !function_exists('h') || !function_exists('i18n_href')) {
        return '';
    }
    $html = '<section class="content-section" id="extra-funktionen">';
    $html .= '<h2>' . h(t('premium.company.heading')) . '</h2>';
    $html .= '<p>' . h(t('premium.company.lead')) . '</p>';
    foreach (premium_company_feature_pairs() as $pair) {
        $html .= '<h3>' . h(t($pair[0])) . '</h3>';
        $html .= '<p>' . h(t($pair[1])) . '</p>';
    }
    $prices = array();
    foreach (premium_tier_definitions() as $key => $def) {
        if (!is_array($def) || !isset($def['price'])) {
            continue;
        }
        $prices[] = premium_tier_label($key) . ' ' . premium_price_label($def['price']) . ', ' . premium_limit_label($def);
    }
    $html .= '<p>' . h(t('premium.tier.same')) . ' ' . h(implode(' · ', $prices)) . '.</p>';
    $html .= '<p>' . h(t('premium.company.pay')) . '</p>';
    $html .= '<p><a class="cta-button" href="' . h(i18n_href('firmen-premium') . '#kaufen') . '">' . h(t('premium.company.cta')) . '</a></p>';
    $html .= '</section>';
    return $html;
}

function premium_json_text($fallback, $key) {
    $text = (string) $fallback;
    if (premium_feature_enabled() && function_exists('t')) {
        $text = t($key);
    }
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags = $flags | JSON_INVALID_UTF8_SUBSTITUTE;
    }
    $encoded = json_encode($text, $flags);
    if (!is_string($encoded)) {
        return '""';
    }
    return $encoded;
}

function premium_extra_link_html() {
    if (!premium_feature_enabled() || !function_exists('i18n_href') || !function_exists('h') || !function_exists('t')) {
        return '';
    }
    return '<a href="' . h(i18n_href('firmen-premium')) . '">' . h(t('premium.extra.label')) . '</a>';
}

function premium_footer_html() {
    if (!premium_feature_enabled() || !function_exists('i18n_href') || !function_exists('t')) {
        return '';
    }
    return '<li><a href="' . h(i18n_href('firmen-premium')) . '">' . h(t('footer.premium')) . '</a></li>';
}

function premium_master_licenses(PDO $pdo, $status) {
    if (!premium_tables_ready($pdo)) {
        return array();
    }
    $stmt = $pdo->prepare('SELECT * FROM `premium_licenses` WHERE `status` = ? ORDER BY `id` DESC LIMIT 50');
    $stmt->execute(array($status));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function premium_send_test_mail($sender = null) {
    $info = premium_invoice_recipient_info();
    $to = $info['email'];
    $subject = 'Test-Mail wichtlä.ch';
    $plain = "Das ist eine Test-Mail von wichtlä.ch.\nEmpfänger: " . $to . "\nQuelle: " . $info['source'] . "\n";
    $html = '<p>Das ist eine Test-Mail von wichtlä.ch.</p>'
        . '<p>Empfänger: ' . mail_esc($to) . '</p>'
        . '<p>Quelle: ' . mail_esc($info['source']) . '</p>';
    if ($sender === null) {
        error_log('Premium-Testmail an ' . $to . ' quelle=' . $info['source']);
    }
    $ok = premium_send($sender, $to, $subject, $html, $plain);
    if ($ok) {
        return array(
            'type' => 'success',
            'text' => 'Test-Mail an ' . $to . ' wurde dem Mailserver übergeben. Steht sie nicht im Posteingang, im Spam und in logs/mail.log nachsehen.',
        );
    }
    return array(
        'type' => 'error',
        'text' => 'Test-Mail an ' . $to . ' wurde abgelehnt. Der Versuch steht in logs/mail.log und im PHP-Fehlerlog.',
    );
}

function premium_diagnostic_label($status) {
    $labels = array(
        'missing' => 'fehlt',
        'invalid' => 'ungültig',
        'ok' => 'gesetzt',
        'publishable' => 'Publishable Key (pk_), wird abgelehnt',
        'sk_test' => 'sk_test vorhanden',
        'sk_live' => 'sk_live vorhanden',
        'rk_test' => 'rk_test vorhanden',
        'rk_live' => 'rk_live vorhanden',
        'config' => 'aus PREMIUM_INVOICE_EMAIL',
        'fallback' => 'PREMIUM_INVOICE_EMAIL fehlt, Fallback',
        'invalid_fallback' => 'PREMIUM_INVOICE_EMAIL ungültig, Fallback',
    );
    return isset($labels[$status]) ? $labels[$status] : (string) $status;
}

function premium_diagnostics() {
    $invoice = premium_invoice_recipient_info();
    $from = (defined('SMTP_FROM_EMAIL') && is_string(SMTP_FROM_EMAIL)) ? trim(SMTP_FROM_EMAIL) : '';
    $sendmail = ini_get('sendmail_path');
    return array(
        'enabled' => premium_feature_enabled(),
        'stripe' => premium_stripe_status(),
        'invoice_email' => $invoice['email'],
        'invoice_source' => $invoice['source'],
        'mail_function' => function_exists('mail'),
        'from' => $from,
        'envelope' => mail_envelope_parameter($from),
        'sendmail' => is_string($sendmail) && trim($sendmail) !== '',
    );
}

function premium_master_diagnostic_html() {
    $diag = premium_diagnostics();
    $stripe = $diag['stripe'];
    $html = '<div class="premium-diagnostic">';
    $html .= '<h3>Diagnose</h3>';
    $html .= '<p>Premium ist ' . ($diag['enabled'] ? 'eingeschaltet' : 'aus') . '. ';
    $html .= 'Kartenzahlung ist ' . (!empty($stripe['ready']) ? 'bereit' : 'nicht bereit') . '.</p>';
    $html .= '<ul>';
    $html .= '<li>Secret Key: ' . h(premium_diagnostic_label($stripe['secret'])) . '. Erlaubt sind sk_test_, sk_live_, rk_test_ und rk_live_. Ein pk_ Key kann kein Checkout starten.</li>';
    $html .= '<li>Webhook: ' . h(premium_diagnostic_label($stripe['webhook'])) . '. Der Wert muss mit whsec_ beginnen.</li>';
    foreach (array('verein' => 'Verein', 'firma' => 'Firma', 'firma_plus' => 'Firma Plus') as $key => $label) {
        $state = isset($stripe['prices'][$key]) ? $stripe['prices'][$key] : 'missing';
        $html .= '<li>Price-ID ' . h($label) . ': ' . h(premium_diagnostic_label($state)) . '. Das ist die Stripe-ID price_…, nicht der Frankenbetrag aus PREMIUM_PRICE_*.</li>';
    }
    $html .= '<li>Rechnungsmail: ' . h($diag['invoice_email']) . ' (' . h(premium_diagnostic_label($diag['invoice_source'])) . ').</li>';
    $html .= '<li>PHP mail(): ' . ($diag['mail_function'] ? 'verfügbar' : 'fehlt') . '. Absender ' . h($diag['from'] !== '' ? $diag['from'] : 'fehlt') . '. ';
    $html .= 'Envelope ' . ($diag['envelope'] !== '' ? h($diag['envelope']) : 'nicht gesetzt') . '. Sendmail-Pfad ' . ($diag['sendmail'] ? 'gesetzt' : 'leer') . '.</li>';
    $html .= '</ul>';
    $html .= '<p>Ein eingeschränkter Key (rk_) braucht die Berechtigung, Checkout Sessions zu erstellen. Sonst lehnt Stripe die Zahlung ab, die Meldung dazu steht im PHP-Fehlerlog (HTTP-Status und Fehlercode, ohne Schlüssel).</p>';
    $html .= '<p>Jede Mail wird in logs/mail.log und im PHP-Fehlerlog festgehalten. Schlägt der Envelope-Absender fehl, folgt ein zweiter Versuch ohne -f.</p>';
    $html .= '<form method="post" class="premium-inline-form">' . csrf_input();
    $html .= '<input type="hidden" name="premium_action" value="test_mail">';
    $html .= '<button type="submit" class="button secondary">Test-Mail senden</button>';
    $html .= '</form>';
    $html .= '<p>Die Test-Mail geht an ' . h($diag['invoice_email']) . '.</p>';
    $html .= '</div>';
    return $html;
}

function premium_master_handle(PDO $pdo, array $post, $sender = null) {
    $action = isset($post['premium_action']) ? (string) $post['premium_action'] : '';
    if ($action === 'test_mail') {
        return premium_send_test_mail($sender);
    }
    if (!premium_tables_ready($pdo)) {
        return array('type' => 'error', 'text' => t('premium.master.missing'));
    }
    $id = isset($post['license_id']) ? (int) $post['license_id'] : 0;
    if ($action === 'activate') {
        $result = premium_activate_license($pdo, $id, isset($post['domain']) ? $post['domain'] : '', $sender);
        if (!$result['ok'] && $result['error'] === 'domain') {
            return array('type' => 'error', 'text' => t('premium.master.domain_bad'));
        }
        if (!$result['ok']) {
            return array('type' => 'error', 'text' => t('premium.master.missing_row'));
        }
        if ($result['error'] === 'mail') {
            return array('type' => 'error', 'text' => t('premium.master.mail_fail', array('{code}' => $result['code'])));
        }
        return array('type' => 'success', 'text' => t('premium.master.activated'));
    }
    if ($action === 'domain') {
        $ok = premium_set_domain($pdo, $id, isset($post['domain']) ? $post['domain'] : '');
        return $ok
            ? array('type' => 'success', 'text' => t('premium.master.domain_saved'))
            : array('type' => 'error', 'text' => t('premium.master.domain_bad'));
    }
    if ($action === 'reject') {
        return premium_reject_pending_license($pdo, $id)
            ? array('type' => 'success', 'text' => t('premium.master.rejected'))
            : array('type' => 'error', 'text' => t('premium.master.missing_row'));
    }
    if ($action === 'revoke') {
        return premium_revoke_license($pdo, $id)
            ? array('type' => 'success', 'text' => t('premium.master.revoked'))
            : array('type' => 'error', 'text' => t('premium.master.missing_row'));
    }
    if ($action === 'delete_revoked') {
        return premium_delete_revoked_license($pdo, $id)
            ? array('type' => 'success', 'text' => t('premium.master.deleted'))
            : array('type' => 'error', 'text' => t('premium.master.missing_row'));
    }
    if ($action === 'resend_code') {
        return premium_resend_code($pdo, $id, $sender)
            ? array('type' => 'success', 'text' => t('premium.master.code_resent'))
            : array('type' => 'error', 'text' => t('premium.master.mail_fail', array('{code}' => '')));
    }
    return array('type' => 'error', 'text' => t('premium.master.missing_row'));
}

function premium_master_pending_count(PDO $pdo) {
    if (!premium_feature_enabled() || !premium_tables_ready($pdo)) {
        return 0;
    }
    $stmt = $pdo->query("SELECT COUNT(*) FROM `premium_licenses` WHERE `status` = 'pending'");
    return (int) $stmt->fetchColumn();
}

function premium_master_section_html(PDO $pdo, $with_diagnostic = true) {
    if (!premium_feature_enabled()) {
        return '';
    }
    $html = '<section class="section-card" id="premium-lizenzen" aria-labelledby="premium-master-heading">';
    $html .= '<div class="section-card-header"><span class="section-icon" aria-hidden="true">✦</span><h2 id="premium-master-heading">' . h(t('premium.master.title')) . '</h2></div>';
    if ($with_diagnostic) {
        $html .= premium_master_diagnostic_html();
    }
    if (!premium_tables_ready($pdo)) {
        $html .= '<p>' . h(t('premium.master.missing')) . '</p></section>';
        return $html;
    }
    $html .= '<p class="section-description premium-master-note">' . h(t('premium.master.hint')) . '</p>';
    $html .= '<h3>' . h(t('premium.master.pending_title')) . '</h3>';
    $pending = premium_master_licenses($pdo, 'pending');
    if (count($pending) === 0) {
        $html .= '<p>' . h(t('premium.master.none')) . '</p>';
    }
    foreach ($pending as $license) {
        $html .= '<form method="post" class="premium-master-row">' . csrf_input();
        $html .= '<input type="hidden" name="license_id" value="' . (int) $license['id'] . '">';
        $html .= '<p><strong>' . h($license['company_name']) . '</strong> · ' . h(premium_tier_label($license['tier'])) . '<br>' . h($license['buyer_email']) . '</p>';
        $html .= '<p>' . nl2br(h($license['company_address'])) . '</p>';
        $html .= '<label>' . h(t('premium.master.domain')) . ' <input type="text" name="domain" placeholder="firma.ch"></label>';
        $html .= '<div class="premium-master-actions">';
        $html .= '<button type="submit" name="premium_action" value="activate" class="button primary">' . h(t('premium.master.activate')) . '</button>';
        $html .= '<button type="submit" name="premium_action" value="reject" class="button error" onclick="' . html_onsubmit_confirm(t('premium.master.reject_confirm')) . '">' . h(t('premium.master.reject')) . '</button>';
        $html .= '</div></form>';
    }
    $active = premium_master_licenses($pdo, 'active');
    if (count($active) > 0) {
        $html .= '<h3>' . h(t('premium.master.active_title')) . '</h3>';
    }
    foreach ($active as $license) {
        $html .= '<div class="premium-master-row"><p><strong>' . h(!empty($license['company_name']) ? $license['company_name'] : $license['buyer_email']) . '</strong> · ' . h(premium_tier_label($license['tier']));
        if (!empty($license['company_name'])) {
            $html .= '<br>' . h($license['buyer_email']);
        }
        if (!empty($license['domain'])) {
            $html .= ' · @' . h($license['domain']);
        }
        $html .= ' · ' . h(t('premium.master.groups', array('{count}' => (string) premium_group_count($pdo, $license['id']))));
        $html .= '</p>';
        $html .= '<form method="post" class="premium-inline-form">' . csrf_input() . '<input type="hidden" name="premium_action" value="domain"><input type="hidden" name="license_id" value="' . (int) $license['id'] . '">';
        $html .= '<input type="text" name="domain" value="' . h(isset($license['domain']) ? $license['domain'] : '') . '">';
        $html .= '<button type="submit" class="button secondary">' . h(t('premium.master.save_domain')) . '</button></form>';
        $html .= '<form method="post" class="premium-inline-form">' . csrf_input() . '<input type="hidden" name="premium_action" value="resend_code"><input type="hidden" name="license_id" value="' . (int) $license['id'] . '">';
        $html .= '<button type="submit" class="button secondary">' . h(t('premium.master.resend_code')) . '</button></form>';
        $html .= '<form method="post" class="premium-inline-form" onsubmit="' . html_onsubmit_confirm(t('premium.master.revoke_confirm')) . '">' . csrf_input() . '<input type="hidden" name="premium_action" value="revoke"><input type="hidden" name="license_id" value="' . (int) $license['id'] . '">';
        $html .= '<button type="submit" class="button error small">' . h(t('premium.master.revoke')) . '</button></form></div>';
    }
    $revoked = premium_master_licenses($pdo, 'revoked');
    if (count($revoked) > 0) {
        $html .= '<h3>' . h(t('premium.master.revoked_title')) . '</h3>';
    }
    foreach ($revoked as $license) {
        $html .= '<div class="premium-master-row"><p><strong>' . h(!empty($license['company_name']) ? $license['company_name'] : $license['buyer_email']) . '</strong> · ' . h(premium_tier_label($license['tier']));
        if (!empty($license['company_name'])) {
            $html .= '<br>' . h($license['buyer_email']);
        }
        $html .= ' · ' . h(t('premium.master.groups', array('{count}' => (string) premium_group_count($pdo, $license['id']))));
        $html .= '</p>';
        $html .= '<form method="post" class="premium-inline-form" onsubmit="' . html_onsubmit_confirm(t('premium.master.delete_confirm')) . '">' . csrf_input();
        $html .= '<input type="hidden" name="premium_action" value="delete_revoked"><input type="hidden" name="license_id" value="' . (int) $license['id'] . '">';
        $html .= '<button type="submit" class="button error small">' . h(t('premium.master.delete')) . '</button></form></div>';
    }
    $html .= '</section>';
    return $html;
}

function premium_groups_for_license(PDO $pdo, $license_id) {
    $stmt = $pdo->prepare('SELECT `id`, `name`, `admin_token`, `is_drawn`, `gift_exchange_date`, `created_at`, (SELECT COUNT(*) FROM `participants` p WHERE p.`group_id` = `groups`.`id`) AS participant_count FROM `groups` WHERE `premium_license_id` = ? ORDER BY `id` DESC');
    $stmt->execute(array((int) $license_id));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
