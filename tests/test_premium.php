<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/functions.php';

function premium_test_pdo() {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('CREATE TABLE `groups` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `name` TEXT NOT NULL,
        `admin_token` TEXT NOT NULL,
        `invite_token` TEXT NOT NULL,
        `admin_email` TEXT,
        `language` TEXT DEFAULT \'de\',
        `budget` REAL,
        `description` TEXT,
        `gift_exchange_date` TEXT,
        `is_drawn` INTEGER DEFAULT 0,
        `reveal_sent_at` TEXT,
        `reminder_sent_at` TEXT,
        `premium_license_id` INTEGER,
        `created_at` TEXT
    )');
    $pdo->exec('CREATE TABLE `participants` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `group_id` INTEGER NOT NULL,
        `name` TEXT NOT NULL,
        `email` TEXT,
        `language` TEXT DEFAULT \'de\',
        `participant_token` TEXT NOT NULL,
        `assigned_to` INTEGER,
        `wishlist` TEXT,
        `created_at` TEXT
    )');
    $pdo->exec('CREATE TABLE `exclusions` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `group_id` INTEGER NOT NULL,
        `participant_id` INTEGER NOT NULL,
        `excluded_participant_id` INTEGER NOT NULL,
        UNIQUE (`group_id`, `participant_id`, `excluded_participant_id`)
    )');
    $pdo->exec('CREATE TABLE `premium_licenses` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `tier` TEXT NOT NULL,
        `status` TEXT NOT NULL,
        `buyer_email` TEXT NOT NULL,
        `company_name` TEXT,
        `company_address` TEXT,
        `contact_name` TEXT,
        `domain` TEXT,
        `code_hash` TEXT UNIQUE,
        `access_token` TEXT UNIQUE,
        `greeting` TEXT,
        `logo_file` TEXT,
        `color_primary` TEXT,
        `color_accent` TEXT,
        `stripe_session_id` TEXT UNIQUE,
        `stripe_event_id` TEXT,
        `season_year` INTEGER NOT NULL,
        `language` TEXT NOT NULL DEFAULT \'de\',
        `created_at` TEXT NOT NULL,
        `activated_at` TEXT,
        `notified_at` TEXT
    )');
    $pdo->exec('CREATE TABLE `premium_confirmations` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `license_id` INTEGER NOT NULL,
        `group_id` INTEGER NOT NULL,
        `email` TEXT NOT NULL,
        `purpose` TEXT NOT NULL,
        `token_hash` TEXT NOT NULL,
        `code_hash` TEXT NOT NULL,
        `language` TEXT NOT NULL DEFAULT \'de\',
        `expires_at` TEXT NOT NULL,
        `created_at` TEXT NOT NULL,
        `consumed_at` TEXT,
        FOREIGN KEY (`license_id`) REFERENCES `premium_licenses`(`id`) ON DELETE CASCADE
    )');
    return $pdo;
}

function premium_test_group(PDO $pdo, $name) {
    return i18n_insert_group(
        $pdo,
        $name,
        bin2hex(random_bytes(16)),
        bin2hex(random_bytes(16)),
        'admin@example.com',
        20,
        'Beschreibung der Gruppe',
        '2026-12-24',
        'de'
    );
}

function premium_test_person(PDO $pdo, $group_id, $name, $email) {
    return i18n_insert_participant($pdo, $group_id, $name, $email, bin2hex(random_bytes(16)), 'de');
}

function premium_test_license(PDO $pdo, $tier, $email, $code, $domain = null) {
    $now = '2026-10-19 12:00:00';
    $stmt = $pdo->prepare('INSERT INTO `premium_licenses`
        (`tier`, `status`, `buyer_email`, `domain`, `code_hash`, `access_token`, `season_year`, `language`, `created_at`, `activated_at`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute(array(
        $tier,
        'active',
        $email,
        $domain,
        premium_secret_hash(premium_normalize_code($code)),
        bin2hex(random_bytes(32)),
        2026,
        'de',
        $now,
        $now,
    ));
    return (int) $pdo->lastInsertId();
}

function premium_test_sender(&$sent) {
    return function ($to, $subject, $html, $plain) use (&$sent) {
        $sent[] = array(
            'to' => $to,
            'subject' => $subject,
            'html' => $html,
            'plain' => $plain,
        );
        return true;
    };
}

function premium_test_signature($payload, $secret, $timestamp) {
    $sig = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    return 't=' . $timestamp . ',v1=' . $sig;
}

run_test('premium stays hidden until it is configured', function () {
    assert_equals('', premium_create_form_html(false));
    assert_equals(404, premium_public_status(false));
    assert_equals(200, premium_public_status(true));
    assert_true(strpos(premium_unavailable_body(), '<p>404</p>') !== false, 'unavailable body is a 404');

    $example = file_get_contents(dirname(__DIR__) . '/includes/config.example.php');
    assert_true(strpos($example, "define('PREMIUM_ENABLED', '');") !== false, 'example leaves premium off');

    $page = file_get_contents(dirname(__DIR__) . '/public/firmen-premium.php');
    $status = strpos($page, 'http_response_code(404)');
    $open = strpos($page, 'public_page_open');
    assert_true($status !== false && $open !== false && $status < $open, '404 happens before the page shell');
    assert_true(strpos($page, '!premium_feature_enabled()') !== false, 'page checks the feature flag');

    $create = file_get_contents(dirname(__DIR__) . '/public/create_group.php');
    assert_true(strpos($create, 'premium_create_form_html') !== false, 'create form calls the premium box');

    if (!premium_feature_enabled()) {
        $php = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        $pricing = shell_exec(escapeshellarg($php) . ' ' . escapeshellarg(dirname(__DIR__) . '/public/firmen-premium.php') . ' 2>/dev/null');
        assert_true(is_string($pricing) && strpos($pricing, '<p>404</p>') !== false, 'disabled pricing page returns 404');
        $form = shell_exec(escapeshellarg($php) . ' ' . escapeshellarg(dirname(__DIR__) . '/public/create_group.php') . ' 2>/dev/null');
        assert_true(is_string($form) && strpos($form, 'name="group_name"') !== false, 'create page still renders');
        assert_true(strpos($form, 'firmen_premium') === false, 'create page hides the premium option');
    }

    $htaccess = file_get_contents(dirname(__DIR__) . '/public/.htaccess');
    assert_true(strpos($htaccess, 'firmen-premium') !== false, 'clean url rewrite lists the page');
    assert_true(is_file(dirname(__DIR__) . '/public/firmen-premium.php'), 'page file exists');
    $hr = file_get_contents(dirname(__DIR__) . '/public/premium-hr.php');
    assert_true(strpos($hr, 'assigned_to') === false, 'HR page source does not select assignments');
});

run_test('create form lists premium, prices and both ways to activate', function () {
    $previous = isset($GLOBALS['i18n_lang']) ? $GLOBALS['i18n_lang'] : 'de';
    $expected = array(
        'de' => array('Zusatzfunktionen für Firmen und Vereine', 'mehr Funktionen wünschen', 'ganz normal und gratis wichteln', 'Jede Durchführung zählt', 'CSV-Import', 'Eigenes Logo und eigene Farben', 'Gruppe fürs nächste Jahr kopieren', 'HR-Übersicht mit Live-Aufdeckung', 'Eigener Begrüssungstext', 'Domain-Lizenz', 'Lizenzübersicht', 'Dieselben Funktionen', 'CHF 9', 'CHF 19', 'CHF 29', 'Lizenzcode', 'Bestätigung'),
        'fr' => array('Fonctions supplémentaires pour entreprises et associations', 'plus de fonctions', 'gratuitement', 'Chaque édition compte', 'Import CSV', 'Texte d’accueil', 'Licence de domaine', 'Les mêmes fonctions', 'CHF 9', 'CHF 19', 'CHF 29', 'code de licence'),
        'en' => array('Extra features for companies and clubs', 'want more features', 'for free', 'Each run counts', 'CSV import', 'welcome text', 'Domain licence', 'The same features', 'CHF 9', 'CHF 19', 'CHF 29', 'licence code'),
    );
    foreach ($expected as $lang => $needles) {
        $GLOBALS['i18n_lang'] = $lang;
        $html = premium_create_form_html(true, null, true, array(
            'email' => 'a@b.ch"><script>',
            'code' => '<b>CODE</b>',
        ));
        assert_true(strpos($html, 'name="firmen_premium"') !== false, $lang . ' checkbox');
        assert_true(strpos($html, 'id="firmen-premium-toggle"') !== false, $lang . ' toggle');
        assert_true(strpos($html, 'name="premium_license_email"') !== false, $lang . ' email field');
        assert_true(strpos($html, 'name="premium_license_code"') !== false, $lang . ' code field');
        assert_true(strpos($html, 'firmen-premium') !== false && strpos($html, '#kaufen') !== false, $lang . ' buy link');
        assert_true(strpos($html, 'ß') === false, $lang . ' box uses ss');
        assert_true(strpos($html, '<script>') === false, $lang . ' values are escaped');
        assert_true(strpos($html, '&lt;b&gt;CODE&lt;/b&gt;') !== false, $lang . ' code is escaped');
        foreach ($needles as $needle) {
            assert_true(strpos($html, $needle) !== false, $lang . ' box contains ' . $needle);
        }
        $toggle = strpos($html, 'id="firmen-premium-toggle"');
        $row = strpos($html, 'class="premium-opt-in-row"');
        $row_end = strpos($html, '</label>');
        assert_true($row !== false && $toggle !== false && $row_end !== false && $row < $toggle && $toggle < $row_end, $lang . ' checkbox and label share one clickable row');
        assert_true(strpos($html, 'Ausschlussregeln') === false, $lang . ' box does not list exclusion rules');
        assert_true(strpos($html, 'Exclusion rules the draw') === false, $lang . ' box does not list exclusion rules');
        assert_true(strpos($html, 'Règles d’exclusion') === false, $lang . ' box does not list exclusion rules');
        assert_true(stripos($html, 'Saison') === false && stripos($html, 'season') === false && stripos($html, 'saison') === false, $lang . ' box does not sell a season');
        assert_true(strpos($html, 'Firma Plus') === false && strpos($html, 'Company Plus') === false && strpos($html, 'Entreprise Plus') === false, $lang . ' box drops the old tier names');
        assert_true(strpos($html, 'CHF 49') === false && strpos($html, 'CHF 99') === false, $lang . ' box drops the old prices');
        assert_true(strpos($html, 'class="premium-price-list"') !== false, $lang . ' prices are a list');
        assert_true(substr_count($html, '<li>') >= 11, $lang . ' price list keeps the packages');
        assert_true(strpos($html, 'CHF 9,') === false && strpos($html, '1 Gruppe') === false && strpos($html, '1 group') === false && strpos($html, '1 groupe') === false, $lang . ' price list drops the group count');
    }
    $GLOBALS['i18n_lang'] = $previous;

    $de = file_get_contents(dirname(__DIR__) . '/content/de/firmen-premium.php');
    $fr = file_get_contents(dirname(__DIR__) . '/content/fr/firmen-premium.php');
    $en = file_get_contents(dirname(__DIR__) . '/content/en/firmen-premium.php');
    assert_true(strpos($de, 'ß') === false, 'German premium page uses ss');
    assert_true(strpos($de, 'oder mehr Funktionen wünschen') !== false, 'German page offers more than several groups');
    assert_true(strpos($de, 'Jede Durchführung zählt als ein Premium-Wichteln') !== false, 'German page counts each run');
    assert_true(stripos($de, 'Saison') === false, 'German page does not sell a season');
    assert_true(strpos($de, 'ganz normal und gratis wichteln') !== false, 'German page says companies can use the free draw');
    assert_true(strpos($de, 'Ausschlussregeln') === false, 'German premium page does not sell exclusion rules');
    assert_true(strpos($fr, 'souhaitent plus de fonctions') !== false, 'French page offers more than several groups');
    assert_true(strpos($en, 'want more features') !== false, 'English page offers more than several groups');
    assert_true(strpos($de, 'premium_pricing_cards_html') !== false, 'German page shows prices');
    assert_true(strpos($fr, 'premium_pricing_cards_html') !== false, 'French page shows prices');
    assert_true(strpos($en, 'premium_pricing_cards_html') !== false, 'English page shows prices');
    assert_true(strpos(file_get_contents(dirname(__DIR__) . '/content/de/datenschutz.php'), 'id="firmen-premium"') !== false, 'privacy mentions premium');
});

run_test('license limits and configurable tiers', function () {
    $pdo = premium_test_pdo();
    $license = premium_test_license($pdo, 'verein', 'verein@example.com', 'ABCD23456789');
    $attached = 0;
    for ($i = 1; $i <= 3; $i++) {
        $group = premium_test_group($pdo, 'Verein ' . $i);
        $result = premium_attach_group($pdo, $license, $group);
        assert_true($result['ok'], 'verein group ' . $i . ' attaches');
        $attached++;
    }
    $fourth = premium_test_group($pdo, 'Verein 4');
    $blocked = premium_attach_group($pdo, $license, $fourth);
    assert_true(!$blocked['ok'] && $blocked['error'] === 'limit', 'fourth verein group is refused');
    assert_equals(3, premium_group_count($pdo, $license));

    $firma = premium_test_license($pdo, 'firma', 'firma@example.com', 'EFGH23456789');
    for ($i = 1; $i <= 10; $i++) {
        $group = premium_test_group($pdo, 'Firma ' . $i);
        $result = premium_attach_group($pdo, $firma, $group);
        assert_true($result['ok'], 'firma group ' . $i);
    }
    $extra = premium_attach_group($pdo, $firma, premium_test_group($pdo, 'Firma 11'));
    assert_equals('limit', $extra['error'], 'eleventh firma group is refused');

    $plus = premium_test_license($pdo, 'firma_plus', 'plus@example.com', 'JKLM23456789');
    for ($i = 1; $i <= 12; $i++) {
        $result = premium_attach_group($pdo, $plus, premium_test_group($pdo, 'Plus ' . $i));
        assert_true($result['ok'], 'firma plus stays open');
    }

    $custom = array(
        'verein' => array('price' => 19, 'limit' => 1, 'greeting' => false, 'hide_credit' => false),
    );
    assert_true(premium_has_room('verein', 0, $custom), 'custom tier has room for the first group');
    assert_true(!premium_has_room('verein', 1, $custom), 'custom tier stops at its limit');
    assert_equals('info@xn--wichtl-gua.ch', premium_normalize_admin_email('info@wichtlä.ch'));
    assert_equals('info@xn--wichtl-gua.ch', premium_normalize_admin_email(''));
});

run_test('credits add up and a used-up package offers another purchase', function () {
    $had_override = array_key_exists('premium_enabled_override', $GLOBALS);
    $previous_override = $had_override ? $GLOBALS['premium_enabled_override'] : null;
    $previous_lang = isset($GLOBALS['i18n_lang']) ? $GLOBALS['i18n_lang'] : 'de';
    $GLOBALS['premium_enabled_override'] = true;
    $GLOBALS['i18n_lang'] = 'de';
    set_error_handler(function () {
        return true;
    });
    try {
        $pdo = premium_test_pdo();
        $first = premium_test_license($pdo, '1x', 'pool@example.com', 'ABCD2345678P');
        $group = premium_test_group($pdo, 'Erstes');
        assert_true(premium_attach_group($pdo, $first, $group)['ok'], 'the first credit is used');
        $blocked = premium_attach_group($pdo, $first, premium_test_group($pdo, 'Direkt'));
        assert_equals('limit', $blocked['error'], '1x stops at one group');

        $second = premium_test_license($pdo, '1x', 'pool@example.com', 'ABCD2345678Q');
        $copied = premium_copy_group_for_next_year($pdo, $group);
        assert_true($copied['ok'], 'copy uses the next package of the same email');
        $copy_license = (int) $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $copied['group_id'])->fetchColumn();
        assert_equals($second, $copy_license, 'the copy is not stuck on the full package');

        $sent = array();
        $third = premium_test_group($pdo, 'Drittes');
        $claim = premium_request_email_claim($pdo, $third, 'pool@example.com', 'ip-pool-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760000000);
        assert_equals('sent', $claim['status'], 'a known address still gets a confirmation');
        $confirmed = premium_confirm_token($pdo, $claim['token'], 1760000000);
        assert_equals('limit', $confirmed['error'], 'two used credits refuse a third group');

        $older = premium_test_license($pdo, '1x', 'reihe@example.com', 'EFGH2345678P');
        $newer = premium_test_license($pdo, '1x', 'reihe@example.com', 'EFGH2345678Q');
        $one = premium_test_group($pdo, 'Aelteres Paket');
        $ask = premium_request_email_claim($pdo, $one, 'reihe@example.com', 'ip-row-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760001000);
        assert_true(premium_confirm_token($pdo, $ask['token'], 1760001000)['ok'], 'the older package is used first');
        assert_equals($older, (int) $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $one)->fetchColumn());
        $two = premium_test_group($pdo, 'Zweites Paket');
        $ask2 = premium_request_email_claim($pdo, $two, 'reihe@example.com', 'ip-row2-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760002000);
        assert_true(premium_confirm_token($pdo, $ask2['token'], 1760002000)['ok'], 'the newer package takes the next group');
        assert_equals($newer, (int) $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $two)->fetchColumn());

        $refused = premium_submit_invoice($pdo, array(
            'tier' => 'verein',
            'company' => 'Alt AG',
            'address' => 'Bahnhofstrasse 1, 8000 Zürich',
            'email' => 'alt@example.com',
        ), 'ip-old-tier-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760003000);
        assert_equals('invalid', $refused['error'], 'legacy tiers are not for sale');
        $checkout = premium_stripe_start_checkout('verein', 'buyer@example.com', function () {
            return array('ok' => true, 'status' => 200, 'body' => '{"url":"https://checkout.stripe.com/c/pay/cs_test_1"}');
        }, array(
            'secret' => 'sk_test_secret_key',
            'webhook' => 'whsec_test_secret_value',
            'prices' => array('1x' => 'price_1x', '3x' => 'price_3x', '5x' => 'price_5x'),
        ), 1760004000);
        assert_equals('config', $checkout['error'], 'checkout refuses a legacy tier');

        $row = $pdo->query('SELECT * FROM `groups` WHERE `id` = ' . (int) $group)->fetch(PDO::FETCH_ASSOC);
        $html = premium_admin_section_html($pdo, $row);
        assert_true(strpos($html, '1× Premium-Wichteln') !== false, 'the admin names the package');
        assert_true(strpos($html, 'noch 0 frei') !== false, 'the admin shows that nothing is left');
        assert_true(strpos($html, 'Weiteres Paket kaufen') !== false, 'a used-up credit offers another package');
        assert_true(strpos($html, 'firmen-premium') !== false && strpos($html, '#kaufen') !== false, 'the offer links to the purchase');
        assert_true(strpos($html, 'class="section-card"') !== false, 'a licensed group keeps the premium section');
        assert_true(strpos($html, 'premium-admin-offer') === false, 'a licensed group does not get the quiet offer');
        assert_true(strpos($html, 'Verein') === false && strpos($html, 'Firma Plus') === false, 'the admin drops the old tier names');
        assert_equals('', premium_admin_offer_html($pdo, $row), 'a licensed group has no extra offer box');
        $master = premium_master_section_html($pdo, false);
        assert_true(strpos($master, '1× Premium-Wichteln') !== false, 'master admin names the package');
        assert_true(strpos($master, 'noch 0 frei') !== false, 'master admin shows used and remaining groups');
        assert_equals('3× Premium-Wichteln', premium_tier_label('verein'), 'an old verein row is shown by its credit count');
        assert_equals('10× Premium-Wichteln', premium_tier_label('firma'));
        assert_equals('Premium-Wichteln, ohne Limit', premium_tier_label('firma_plus'));
    } finally {
        restore_error_handler();
        if ($had_override) {
            $GLOBALS['premium_enabled_override'] = $previous_override;
        } else {
            unset($GLOBALS['premium_enabled_override']);
        }
        $GLOBALS['i18n_lang'] = $previous_lang;
    }
});

run_test('a free group keeps premium as a closed note at the top of the admin page', function () {
    $had_override = array_key_exists('premium_enabled_override', $GLOBALS);
    $previous_override = $had_override ? $GLOBALS['premium_enabled_override'] : null;
    $previous_lang = isset($GLOBALS['i18n_lang']) ? $GLOBALS['i18n_lang'] : 'de';
    $GLOBALS['premium_enabled_override'] = true;
    set_error_handler(function () {
        return true;
    });
    try {
        $pdo = premium_test_pdo();
        $group_id = premium_test_group($pdo, 'Familie');
        $row = $pdo->query('SELECT * FROM `groups` WHERE `id` = ' . (int) $group_id)->fetch(PDO::FETCH_ASSOC);
        assert_equals('', premium_admin_section_html($pdo, $row), 'a free group has no premium block');
        $closed = premium_admin_offer_html($pdo, $row);
        assert_true(strpos($closed, '<details class="premium-admin-offer" id="premium">') !== false, 'the note is closed');
        assert_true(strpos($closed, ' open') === false, 'the note does not start open');
        assert_true(strpos($closed, 'Premium Firmen-Wichteln') === false, 'the note does not use the old heading');
        $open = premium_admin_offer_html($pdo, $row, null, true, true);
        assert_true(strpos($open, '<details class="premium-admin-offer" id="premium" open>') !== false, 'a fresh premium choice opens the note');
        assert_true(strpos($open, t('premium.create.later')) !== false, 'the opened note says the licence can wait');
        foreach (array(
            'de' => 'Zusatzfunktionen für Firmen und Vereine',
            'fr' => 'Fonctions supplémentaires pour entreprises et associations',
            'en' => 'Extra features for companies and clubs',
        ) as $lang => $title) {
            $GLOBALS['i18n_lang'] = $lang;
            $html = premium_admin_offer_html($pdo, $row);
            assert_true(strpos($html, $title) !== false, $lang . ' note uses the quiet title');
            assert_true(strpos($html, t('premium.create.free')) !== false, $lang . ' note says the normal draw stays free');
            assert_true(strpos($html, 'class="premium-price-list"') !== false, $lang . ' note lists the prices');
            assert_true(strpos($html, 'CHF 9') !== false && strpos($html, 'CHF 19') !== false && strpos($html, 'CHF 29') !== false, $lang . ' note shows the three prices');
            assert_true(strpos($html, '1 Gruppe') === false && strpos($html, '1 group') === false && strpos($html, '1 groupe') === false, $lang . ' note skips the group count');
            assert_true(strpos($html, 'name="license_email"') !== false && strpos($html, 'name="license_code"') !== false, $lang . ' note can unlock by email or code');
            assert_true(strpos($html, 'firmen-premium') !== false && strpos($html, '#kaufen') !== false, $lang . ' note links to the premium page');
            assert_true(substr_count($html, '<li>') >= 11, $lang . ' note keeps the short feature list');
        }
        $admin = file_get_contents(dirname(__DIR__) . '/public/admin.php');
        $section_at = strpos($admin, 'premium_admin_section_html');
        $checklist_at = strpos($admin, 'id="checkliste"');
        $danger_at = strpos($admin, 'id="gefahrenzone"');
        $offer_at = strpos($admin, 'premium_admin_offer_html');
        $support_at = strpos($admin, "support_card_html('admin')");
        assert_true($section_at !== false && $offer_at !== false && $checklist_at !== false && $section_at < $checklist_at && $offer_at < $checklist_at, 'the licensed section and the quiet note sit above the checklist');
        assert_true($danger_at !== false && $support_at !== false && $offer_at < $danger_at && $danger_at < $support_at, 'the quiet note stays above the danger zone');
        assert_true(strpos($admin, 'if ($premium_license)') !== false, 'the premium shortcut is only there for a licensed group');
    } finally {
        restore_error_handler();
        if ($had_override) {
            $GLOBALS['premium_enabled_override'] = $previous_override;
        } else {
            unset($GLOBALS['premium_enabled_override']);
        }
        $GLOBALS['i18n_lang'] = $previous_lang;
    }
});

run_test('email confirmation is required before a group is premium', function () {
    $pdo = premium_test_pdo();
    $license = premium_test_license($pdo, 'firma', 'buyer@firma.ch', 'NPQR23456789', 'firma.ch');
    $group = premium_test_group($pdo, 'Bestätigen');
    $sent = array();
    $claim = premium_request_email_claim($pdo, $group, 'buyer@firma.ch', 'ip-claim-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760000000);
    assert_equals('sent', $claim['status'], 'known address gets a confirmation');
    $still = $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $group)->fetchColumn();
    assert_true($still === null || $still === '', 'group stays free until the link is used');

    $stranger = premium_test_group($pdo, 'Fremd');
    $other = premium_request_email_claim($pdo, $stranger, 'fremd@example.com', 'ip-other-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760000000);
    assert_equals('unknown', $other['status'], 'another address is not told that it missed');
    $stranger_license = $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $stranger)->fetchColumn();
    assert_true($stranger_license === null || $stranger_license === '', 'stranger group is not attached');

    $confirmed = premium_confirm_token($pdo, $claim['token'], 1760000000);
    assert_true($confirmed['ok'], 'confirmation link attaches the group');
    assert_equals($license, (int) $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $group)->fetchColumn());

    $domain_group = premium_test_group($pdo, 'Domain');
    $domain_claim = premium_request_email_claim($pdo, $domain_group, 'sam@firma.ch', 'ip-domain-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760000100);
    assert_equals('sent', $domain_claim['status'], 'same domain can ask');
    $before = $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $domain_group)->fetchColumn();
    assert_true($before === null || $before === '', 'domain match still waits for confirmation');
    $domain_ok = premium_confirm_token($pdo, $domain_claim['token'], 1760000100);
    assert_true($domain_ok['ok'], 'confirmed domain address attaches');

    $sub = premium_request_email_claim($pdo, premium_test_group($pdo, 'Sub'), 'sam@sub.firma.ch', 'ip-sub-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760000200);
    assert_equals('unknown', $sub['status'], 'a subdomain is not the domain licence');
});

run_test('license code activates and a wrong code does not', function () {
    $pdo = premium_test_pdo();
    $code = 'STUV23456789';
    $license = premium_test_license($pdo, 'verein', 'code@example.com', $code);
    $group = premium_test_group($pdo, 'Mit Code');
    $ip = 'ip-code-' . bin2hex(random_bytes(4));
    $bad = premium_claim_with_code($pdo, $group, 'ZZZZ23456789', $ip);
    assert_true(!$bad['ok'], 'wrong code does not attach');
    assert_true($pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $group)->fetchColumn() === null
        || $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $group)->fetchColumn() === '');
    $good = premium_claim_with_code($pdo, $group, 'stuv-2345-6789', $ip);
    assert_true($good['ok'], 'formatted code attaches');
    assert_equals($license, (int) $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $group)->fetchColumn());

    $rate_group = premium_test_group($pdo, 'Limit');
    $rate_ip = 'ip-rate-' . bin2hex(random_bytes(4));
    $last = array('ok' => true, 'error' => '');
    for ($i = 0; $i < 9; $i++) {
        $last = premium_claim_with_code($pdo, $rate_group, 'WRONG2345678', $rate_ip);
    }
    assert_equals('rate', $last['error'], 'code attempts are rate limited');
});

run_test('stripe webhook checks the signature and creates one license', function () {
    $pdo = premium_test_pdo();
    $secret = 'whsec_test_secret_value';
    $config = array(
        'secret' => 'sk_test_secret_key',
        'webhook' => $secret,
        'prices' => array(
            'verein' => 'price_verein_test',
            'firma' => 'price_firma_test',
            'firma_plus' => 'price_plus_test',
        ),
    );
    $event = array(
        'id' => 'evt_premium_1',
        'type' => 'checkout.session.completed',
        'data' => array(
            'object' => array(
                'id' => 'cs_test_session_1',
                'payment_status' => 'paid',
                'metadata' => array(
                    'tier' => '1x',
                    'price_id' => 'price_verein_test',
                    'buyer_email' => 'stripe@example.com',
                    'language' => 'de',
                ),
            ),
        ),
    );
    $payload = json_encode($event);
    $now = 1760000000;
    $sent = array();
    $header = premium_test_signature($payload, $secret, $now);
    $created = premium_handle_webhook($pdo, $payload, $header, $secret, premium_test_sender($sent), $now, $config);
    assert_equals('created', $created['status'], 'paid event creates a license');
    assert_equals(1, (int) $pdo->query('SELECT COUNT(*) FROM `premium_licenses`')->fetchColumn());
    $body = premium_webhook_response($created);
    assert_true(strpos($body['body'], $created['code']) === false, 'response body does not contain the code');
    assert_true(strpos($body['body'], 'created') !== false, 'response only names the status');
    assert_equals(1, count($sent), 'buyer is emailed once');

    $again = premium_handle_webhook($pdo, $payload, $header, $secret, premium_test_sender($sent), $now, $config);
    assert_equals('duplicate', $again['status'], 'replay does not create a second license');
    assert_equals(1, (int) $pdo->query('SELECT COUNT(*) FROM `premium_licenses`')->fetchColumn());

    $bad = premium_handle_webhook($pdo, $payload, 't=' . $now . ',v1=deadbeef', $secret, premium_test_sender($sent), $now, $config);
    assert_equals('invalid_signature', $bad['status']);
    assert_equals(1, (int) $pdo->query('SELECT COUNT(*) FROM `premium_licenses`')->fetchColumn());
    assert_equals('1x', $pdo->query('SELECT `tier` FROM `premium_licenses`')->fetchColumn(), 'a new checkout stores the package key');

    $legacy_event = $event;
    $legacy_event['id'] = 'evt_premium_legacy';
    $legacy_event['data']['object']['id'] = 'cs_test_session_legacy';
    $legacy_event['data']['object']['metadata']['tier'] = 'verein';
    $legacy_event['data']['object']['metadata']['buyer_email'] = 'legacy@example.com';
    $legacy_payload = json_encode($legacy_event);
    $legacy = premium_handle_webhook($pdo, $legacy_payload, premium_test_signature($legacy_payload, $secret, $now), $secret, premium_test_sender($sent), $now, $config);
    assert_equals('created', $legacy['status'], 'an in-flight verein checkout still creates the old quota');
    assert_equals('verein', $pdo->query("SELECT `tier` FROM `premium_licenses` WHERE `buyer_email` = 'legacy@example.com'")->fetchColumn());

    $expired_header = premium_test_signature($payload, $secret, $now - 1000);
    assert_true(!premium_stripe_signature_valid($payload, $expired_header, $secret, $now), 'old signature is rejected');
});

run_test('invoice mail reaches the admin and a pending license cannot be claimed', function () {
    $pdo = premium_test_pdo();
    $sent = array();
    $result = premium_submit_invoice($pdo, array(
        'tier' => '3x',
        'company' => 'Beispiel AG',
        'address' => "Bahnhofstrasse 1\n8000 Zürich",
        'email' => 'rechnung@firma.ch',
        'contact' => 'Ada',
    ), 'ip-invoice-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760000000);
    assert_true($result['ok'], 'invoice request is stored');
    assert_equals(1, count($sent), 'admin is emailed');
    assert_equals('info@xn--wichtl-gua.ch', $sent[0]['to'], 'admin address is punycode');
    assert_true(strpos($sent[0]['plain'], 'Beispiel AG') !== false, 'mail names the company');
    assert_true(strpos($sent[0]['plain'], 'Bahnhofstrasse 1') !== false, 'mail names the address');
    assert_true(strpos($sent[0]['plain'], 'rechnung@firma.ch') !== false, 'mail names the buyer');
    $row = $pdo->query('SELECT * FROM `premium_licenses` WHERE `id` = ' . (int) $result['id'])->fetch(PDO::FETCH_ASSOC);
    assert_equals('pending', $row['status']);
    assert_true($row['code_hash'] === null || $row['code_hash'] === '', 'pending license has no code');

    $group = premium_test_group($pdo, 'Rechnung');
    $claim = premium_claim_with_code($pdo, $group, 'ABCD23456789', 'ip-pending-' . bin2hex(random_bytes(3)));
    assert_true(!$claim['ok'], 'pending code cannot be used');

    $sent = array();
    $activated = premium_activate_license($pdo, $result['id'], '', premium_test_sender($sent), 1760001000);
    assert_true($activated['ok'], 'master admin can activate');
    assert_true(preg_match('/[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}/', $sent[0]['plain'], $match) === 1, 'mail contains the code');
    $used = premium_claim_with_code($pdo, $group, $match[0], 'ip-after-' . bin2hex(random_bytes(3)));
    assert_true($used['ok'], 'code works after activation');
});

run_test('csv import validates and does not insert bad rows', function () {
    $bom = "\xEF\xBB\xBF";
    $semi = $bom . "Name;E-Mail\n\"Anna, Team\";anna@example.com\n;leer@example.com\nBob;bob@example.com\n";
    $parsed = premium_parse_csv($semi);
    assert_equals(2, count($parsed['rows']), 'valid semicolon rows are kept');
    assert_true(count($parsed['errors']) >= 1, 'a row without a name is an error');
    assert_equals('Anna, Team', $parsed['rows'][0]['name']);
    assert_equals('anna@example.com', $parsed['rows'][0]['email']);

    $comma = "Name,Email\n\"Ada, GmbH\",ada@example.com\nCleo,not-an-email\n";
    $comma_parsed = premium_parse_csv($comma);
    assert_equals(1, count($comma_parsed['rows']), 'quoted comma field parses');
    assert_equals('Ada, GmbH', $comma_parsed['rows'][0]['name']);
    assert_true(count($comma_parsed['errors']) >= 1, 'invalid email is reported');

    $pdo = premium_test_pdo();
    $group_id = premium_test_group($pdo, 'Import');
    $group = $pdo->query('SELECT * FROM `groups` WHERE `id` = ' . (int) $group_id)->fetch(PDO::FETCH_ASSOC);
    $before = (int) $pdo->query('SELECT COUNT(*) FROM `participants`')->fetchColumn();
    assert_equals($before, (int) $pdo->query('SELECT COUNT(*) FROM `participants`')->fetchColumn(), 'preview does not insert');
    $created = premium_import_rows($pdo, $group, $parsed['rows']);
    assert_equals(2, count($created), 'only valid rows are imported');
    assert_equals(2, (int) $pdo->query('SELECT COUNT(*) FROM `participants`')->fetchColumn());
});

run_test('exclusion draw succeeds and reports an impossible case', function () {
    $pdo = premium_test_pdo();
    $group = premium_test_group($pdo, 'Auslosung');
    $a = premium_test_person($pdo, $group, 'Anna', 'anna@example.com');
    $b = premium_test_person($pdo, $group, 'Ben', 'ben@example.com');
    $c = premium_test_person($pdo, $group, 'Cleo', 'cleo@example.com');
    premium_add_exclusion($pdo, $group, $a, $b);
    $rules = $pdo->query('SELECT * FROM `exclusions` WHERE `group_id` = ' . (int) $group)->fetchAll(PDO::FETCH_ASSOC);
    $drawn = perform_draw(array($a, $b, $c), premium_exclusions_map($rules));
    assert_true(is_array($drawn), 'one exclusion still has a valid draw');
    assert_true($drawn[0] !== $b, 'Anna does not draw Ben');

    $pair = premium_test_group($pdo, 'Unmöglich');
    $x = premium_test_person($pdo, $pair, 'Xena', 'xena@example.com');
    $y = premium_test_person($pdo, $pair, 'Yann', 'yann@example.com');
    premium_add_exclusion($pdo, $pair, $x, $y);
    premium_add_exclusion($pdo, $pair, $y, $x);
    $blocked = $pdo->query('SELECT * FROM `exclusions` WHERE `group_id` = ' . (int) $pair)->fetchAll(PDO::FETCH_ASSOC);
    assert_equals(false, perform_draw(array($x, $y), premium_exclusions_map($blocked)), 'mutual exclusion cannot be drawn');
});

run_test('copying a group excludes last year and respects the limit', function () {
    $pdo = premium_test_pdo();
    $license = premium_test_license($pdo, 'firma_plus', 'copy@example.com', 'WXYZ23456789');
    $group = premium_test_group($pdo, 'Letztes Jahr');
    premium_attach_group($pdo, $license, $group);
    $a = premium_test_person($pdo, $group, 'Anna', 'anna@example.com');
    $b = premium_test_person($pdo, $group, 'Ben', 'ben@example.com');
    $pdo->prepare('UPDATE `participants` SET `wishlist` = ?, `assigned_to` = ? WHERE `id` = ?')->execute(array('Buch', $b, $a));
    $pdo->prepare('UPDATE `participants` SET `wishlist` = ?, `assigned_to` = ? WHERE `id` = ?')->execute(array('Tee', $a, $b));
    $pdo->prepare('UPDATE `groups` SET `is_drawn` = 1 WHERE `id` = ?')->execute(array($group));
    premium_add_exclusion($pdo, $group, $a, $a);

    $copied = premium_copy_group_for_next_year($pdo, $group);
    assert_true($copied['ok'], 'copy succeeds while the licence has room');
    $new = $pdo->query('SELECT * FROM `groups` WHERE `id` = ' . (int) $copied['group_id'])->fetch(PDO::FETCH_ASSOC);
    assert_equals('2027-12-24', $new['gift_exchange_date'], 'gift date moves one year');
    assert_equals('Beschreibung der Gruppe', $new['description']);
    assert_equals(0, (int) $new['is_drawn'], 'the copy is not drawn');
    assert_equals($license, (int) $new['premium_license_id']);
    $people = $pdo->query('SELECT * FROM `participants` WHERE `group_id` = ' . (int) $copied['group_id'] . ' ORDER BY `id`')->fetchAll(PDO::FETCH_ASSOC);
    assert_equals(2, count($people));
    assert_equals('Buch', $people[0]['wishlist']);
    assert_true($people[0]['assigned_to'] === null || $people[0]['assigned_to'] === '', 'assignments are not copied');
    $rules = $pdo->query('SELECT * FROM `exclusions` WHERE `group_id` = ' . (int) $copied['group_id'])->fetchAll(PDO::FETCH_ASSOC);
    $ids = array((int) $people[0]['id'], (int) $people[1]['id']);
    assert_equals(false, perform_draw($ids, premium_exclusions_map($rules)), 'last year pairing blocks the only draw');

    $full = premium_test_license($pdo, 'verein', 'voll@example.com', 'ABCD23456781');
    $first = premium_test_group($pdo, 'Slot 1');
    premium_attach_group($pdo, $full, $first);
    premium_test_person($pdo, $first, 'Eins', 'eins@example.com');
    premium_attach_group($pdo, $full, premium_test_group($pdo, 'Slot 2'));
    premium_attach_group($pdo, $full, premium_test_group($pdo, 'Slot 3'));
    $stopped = premium_copy_group_for_next_year($pdo, $first);
    assert_equals('limit', $stopped['error'], 'copy stops when the licence is full');
});

run_test('copying a group mails its own admin link', function () {
    $had_override = array_key_exists('premium_enabled_override', $GLOBALS);
    $previous_override = $had_override ? $GLOBALS['premium_enabled_override'] : null;
    $GLOBALS['premium_enabled_override'] = true;
    try {
    $pdo = premium_test_pdo();
    $license = premium_test_license($pdo, 'firma_plus', 'copy-mail@example.com', 'CPY23456789A');
    $group_id = premium_test_group($pdo, 'Pine');
    $pdo->prepare('UPDATE `groups` SET `language` = ?, `description` = ?, `admin_email` = ? WHERE `id` = ?')
        ->execute(array('fr', 'A quiet office', 'admin@example.com', $group_id));
    premium_attach_group($pdo, $license, $group_id);
    $old = $pdo->query('SELECT * FROM `groups` WHERE `id` = ' . (int) $group_id)->fetch(PDO::FETCH_ASSOC);
    $sent = array();
    $result = premium_admin_handle($pdo, $old, array(
        'premium_action' => 'copy',
        'confirm_copy' => '1',
    ), array(), premium_test_sender($sent));

    $new = $pdo->query('SELECT * FROM `groups` WHERE `id` != ' . (int) $group_id . ' ORDER BY `id` DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    assert_true(is_array($new), 'the copy is stored');
    assert_true($new['admin_token'] !== $old['admin_token'], 'the copy gets a new admin token');
    assert_true($new['invite_token'] !== $old['invite_token'], 'the copy gets a new invite token');
    assert_true($new['admin_token'] !== '', 'the new admin token is stored');
    $token_in_url = urlencode($new['admin_token']);
    assert_true(strpos($result['redirect'], 'token=' . $token_in_url) !== false, 'success page opens the new group');
    assert_true(strpos($result['redirect'], $old['admin_token']) === false, 'success page does not keep the old token');
    assert_equals('success', $result['notice']['type']);
    assert_true(strpos($result['notice']['link'], 'https://') === 0, 'the shown link uses the configured site URL');
    assert_true(strpos($result['notice']['link'], 'admin.php?token=' . $token_in_url) !== false, 'the success notice shows the new admin link');
    assert_true(strpos($result['notice']['link'], $old['admin_token']) === false, 'the shown link is not the old admin link');

    assert_equals(1, count($sent), 'one admin mail is sent');
    assert_equals('admin@example.com', $sent[0]['to']);
    assert_equals('fr', $new['language'], 'the copy keeps the group language');
    assert_equals(admin_created_email_subject('Pine', 'fr'), $sent[0]['subject'], 'the copy uses the normal admin-mail subject');
    assert_true(strpos($sent[0]['html'], 'lang="fr"') !== false, 'the mail follows the group language');
    assert_true(strpos($sent[0]['html'], 'admin.php?token=' . $token_in_url) !== false, 'html contains the new admin link');
    assert_true(strpos($sent[0]['plain'], 'admin.php?token=' . $token_in_url) !== false, 'plain text contains the new admin link');
    assert_true(strpos($sent[0]['html'], 'register.php?token=' . urlencode($new['invite_token'])) !== false, 'html contains the new invite link');
    assert_true(strpos($sent[0]['html'], $old['admin_token']) === false, 'the mail does not reuse the old admin token');
    $silent = array();
    $pdo->prepare('UPDATE `groups` SET `admin_email` = ? WHERE `id` = ?')->execute(array('', $group_id));
    $again = $pdo->query('SELECT * FROM `groups` WHERE `id` = ' . (int) $group_id)->fetch(PDO::FETCH_ASSOC);
    $empty_mail = premium_admin_handle($pdo, $again, array(
        'premium_action' => 'copy',
        'confirm_copy' => '1',
    ), array(), premium_test_sender($silent));
    assert_equals(0, count($silent), 'a group without an admin email does not send');
    assert_true(strpos($empty_mail['notice']['link'], 'admin.php?token=') !== false, 'the new link is still shown');

    $page = file_get_contents(dirname(__DIR__) . '/public/admin.php');
    assert_true(strpos($page, 'flash-link') !== false, 'the success page renders the admin link');
    $create = file_get_contents(dirname(__DIR__) . '/public/create_group.php');
    assert_true(strpos($create, 'send_created_group_mail(') !== false, 'creating a group uses the same mail function');
    foreach (array('de', 'fr', 'en') as $lang) {
        $built = build_created_group_mail(array(
            'id' => 9,
            'name' => 'Pine',
            'admin_email' => 'admin@example.com',
            'admin_token' => 'new-admin-token',
            'invite_token' => 'new-invite-token',
            'language' => $lang,
            'budget' => 20,
            'description' => 'A quiet office',
            'gift_exchange_date' => '2027-12-24',
        ));
        assert_equals(admin_created_email_subject('Pine', $lang), $built['subject'], $lang . ' admin subject');
        assert_true(strpos($built['html'], 'lang="' . $lang . '"') !== false, $lang . ' admin html language');
        assert_true(strpos($built['html'], 'admin.php?token=new-admin-token') !== false, $lang . ' admin html link');
        assert_true(strpos($built['plain'], 'admin.php?token=new-admin-token') !== false, $lang . ' admin plain link');
        assert_true(strpos($built['admin_link'], 'https://') === 0, $lang . ' admin link uses the site URL');
    }
    } finally {
        if ($had_override) {
            $GLOBALS['premium_enabled_override'] = $previous_override;
        } else {
            unset($GLOBALS['premium_enabled_override']);
        }
    }
});

run_test('hr overview hides assignments and reveal needs confirmation', function () {
    $rows = premium_hr_rows(array(
        array('name' => 'Anna', 'email' => 'anna@example.com', 'wishlist' => 'Buch', 'assigned_to' => 9),
        array('name' => 'Ben', 'email' => 'ben@example.com', 'wishlist' => '', 'assigned_to' => 8),
    ), array('reminder_sent_at' => null));
    $html = premium_hr_table_html($rows);
    assert_true(strpos($html, 'Anna') !== false, 'participant name is visible');
    assert_true(strpos($html, 'assigned_to') === false, 'column name is not rendered');
    assert_true(strpos($html, 'CANARY-RECEIVER') === false, 'assignment canary is absent');
    $session = array();
    $locked = premium_reveal_unlock(array(), $session, 4, true, 1760000000);
    assert_equals('confirm', $locked['error'], 'reveal without the checkbox stays locked');
    assert_true(!premium_reveal_allowed($locked['session'], 4, 1760000000));
    $open = premium_reveal_unlock(array('confirm_reveal' => '1'), $session, 4, true, 1760000000);
    assert_equals('', $open['error']);
    assert_true(premium_reveal_allowed($open['session'], 4, 1760000000));
    assert_true(!premium_reveal_allowed($open['session'], 4, 1760000000 + 7201), 'unlock expires');
});

run_test('logo upload rejects svg and php and reencodes png', function () {
    $svg = premium_logo_inspect('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'logo.svg');
    assert_true(!$svg['ok'], 'svg is rejected');
    $php = premium_logo_inspect('<?php echo 1; ?>', 'logo.php');
    assert_true(!$php['ok'], 'php is rejected');
    $image = imagecreatetruecolor(8, 8);
    $red = imagecolorallocate($image, 230, 57, 70);
    imagefilledrectangle($image, 0, 0, 7, 7, $red);
    ob_start();
    imagepng($image);
    $png = ob_get_clean();
    imagedestroy($image);
    $too_big = premium_logo_inspect($png, 'logo.png', 10);
    assert_equals('size', $too_big['error'], 'oversize file is rejected');
    $ok = premium_logo_inspect($png, 'logo.png');
    assert_true($ok['ok'], 'png is accepted');
    assert_equals("\x89PNG\r\n\x1a\n", substr($ok['binary'], 0, 8), 'stored logo is a png');
});

run_test('every tier includes the same premium functions', function () {
    $definitions = premium_tier_definitions();
    foreach (array('1x', '3x', '5x', 'verein', 'firma', 'firma_plus') as $key) {
        assert_true(!empty($definitions[$key]['greeting']), $key . ' includes a greeting');
        assert_true(!empty($definitions[$key]['hide_credit']), $key . ' hides the credit');
    }
    assert_equals(9, $definitions['1x']['price']);
    assert_equals(19, $definitions['3x']['price']);
    assert_equals(29, $definitions['5x']['price']);
    assert_equals(1, $definitions['1x']['limit']);
    assert_equals(3, $definitions['3x']['limit']);
    assert_equals(5, $definitions['5x']['limit']);
    assert_equals(3, $definitions['verein']['limit']);
    assert_equals(10, $definitions['firma']['limit']);
    assert_true($definitions['firma_plus']['limit'] === null, 'an old firma plus row stays unlimited');
    $previous = isset($GLOBALS['i18n_lang']) ? $GLOBALS['i18n_lang'] : 'de';
    $GLOBALS['i18n_lang'] = 'de';
    $cards = premium_pricing_cards_html();
    assert_true(strpos($cards, 'Dieselben Funktionen in jedem Paket') !== false, 'cards share one sentence');
    assert_true(strpos($cards, 'Jede Durchführung zählt') !== false, 'cards explain that each run counts');
    assert_true(strpos($cards, '1× Premium-Wichteln') !== false, 'cards name the one-group package');
    assert_true(strpos($cards, 'CHF 9') !== false && strpos($cards, 'CHF 29') !== false, 'cards use the new prices');
    assert_true(strpos($cards, '1 Gruppe') === false && strpos($cards, '3 Gruppen') === false, 'cards drop the repeated group count');
    assert_true(strpos($cards, 'Verein') === false && strpos($cards, 'Firma Plus') === false, 'cards drop the old tier names');
    assert_true(stripos($cards, 'Saison') === false, 'cards do not mention a season');
    assert_true(strpos($cards, 'Eigener Begrüssungstext') === false, 'greeting is not a plus-only line');
    assert_true(strpos($cards, 'powered by') === false, 'credit line is not a plus-only line');
    assert_equals(3, substr_count($cards, 'premium-tier-card'), 'three tiers');
    $GLOBALS['i18n_lang'] = $previous;

    $pdo = premium_test_pdo();
    $id = premium_test_license($pdo, 'verein', 'verein-brand@example.com', 'WXYZ23456789');
    $license = premium_fetch_license($pdo, $id);
    $saved = premium_save_branding($pdo, $license, array(
        'color_primary' => '#112233',
        'color_accent' => '#445566',
        'greeting' => 'Willkommen im Team',
    ));
    assert_true($saved['ok'], 'verein can save a greeting');
    $state = premium_brand_state(premium_fetch_license($pdo, $id));
    assert_equals('Willkommen im Team', $state['greeting']);
    assert_true($state['credit'] === false, 'verein hides powered by');
    $long = premium_save_branding($pdo, premium_fetch_license($pdo, $id), array(
        'color_primary' => '#112233',
        'color_accent' => '#445566',
        'greeting' => str_repeat('a', 501),
    ));
    assert_equals('greeting', $long['error'], 'an unusable greeting is refused');
});

function premium_render_content($relative) {
    ob_start();
    include dirname(__DIR__) . '/' . $relative;
    return ob_get_clean();
}

run_test('company page and free claims follow the feature flag', function () {
    $previous_lang = isset($GLOBALS['i18n_lang']) ? $GLOBALS['i18n_lang'] : 'de';
    $had_override = array_key_exists('premium_enabled_override', $GLOBALS);
    $previous_override = $had_override ? $GLOBALS['premium_enabled_override'] : null;
    try {
        unset($GLOBALS['premium_enabled_override']);
        $GLOBALS['i18n_lang'] = 'de';
        assert_true(strpos(t('footer.tagline'), 'kostenlose Lösung') !== false, 'off state keeps the old tagline');
        $home_off = premium_render_content('content/de/index.php');
        assert_true(strpos($home_off, '100% kostenlos') !== false, 'homepage keeps the free claim while premium is off');
        assert_true(strpos($home_off, 'keine Premium-Features') !== false, 'homepage keeps the no-premium claim while premium is off');
        assert_true(strpos($home_off, 'firmen-premium') === false, 'homepage does not link premium while it is off');
        $tips_off = premium_render_content('content/de/firmenwichteln-tipps.php');
        assert_true(strpos($tips_off, 'Extra-Funktionen für Firmen') === false, 'company tips hide the premium section');
        assert_true(strpos($tips_off, 'kostenlos, professionell') !== false, 'company tips keep the old closing line');

        $GLOBALS['premium_enabled_override'] = true;
        foreach (array(
            'de' => 'Die einfache Lösung für dein Wichteln in der Schweiz.',
            'fr' => 'La solution simple pour ton Secret Santa en Suisse.',
            'en' => 'The simple way to run your Secret Santa in Switzerland.',
        ) as $lang => $tagline) {
            $GLOBALS['i18n_lang'] = $lang;
            assert_equals($tagline, t('footer.tagline'), $lang . ' footer keeps only the first sentence');
        }
        foreach (array(
            'de' => 'Extra-Funktionen für Firmen und Vereine',
            'fr' => 'fonctions en plus pour entreprises et associations',
            'en' => 'Extra features for companies and clubs',
        ) as $lang => $label) {
            $GLOBALS['i18n_lang'] = $lang;
            $description = t('meta.index.description');
            assert_true(strpos($description, $label) !== false, $lang . ' meta names the extra functions');
            assert_true(stripos($description, '100%') === false && strpos($description, '100 %') === false, $lang . ' meta drops the absolute free claim');
        }

        $GLOBALS['i18n_lang'] = 'de';
        $home_on = premium_render_content('content/de/index.php');
        assert_true(strpos($home_on, '100% kostenlos') === false, 'homepage drops 100 percent');
        assert_true(strpos($home_on, 'keine Premium-Features') === false, 'homepage drops the no-premium claim');
        assert_true(strpos($home_on, 'firmen-premium') !== false, 'homepage links to premium');
        assert_true(strpos($home_on, 'Extra-Funktionen für Firmen und Vereine') !== false, 'homepage names the extra functions');
        $faq_on = premium_render_content('content/de/faq.php');
        assert_true(strpos($faq_on, '100% kostenlos') === false, 'faq drops 100 percent');
        assert_true(strpos($faq_on, 'keine Premium-Accounts') === false, 'faq drops premium accounts');
        assert_true(strpos($faq_on, 'Extra-Funktionen für Firmen und Vereine') !== false, 'faq names the extra functions');

        $GLOBALS['i18n_lang'] = 'fr';
        $fr_home = premium_render_content('content/fr/index.php');
        assert_true(strpos($fr_home, '100 % gratuit') === false, 'french homepage drops the absolute claim');
        assert_true(strpos($fr_home, 'pas de fonctions premium') === false, 'french homepage drops premium features');
        assert_true(strpos($fr_home, 'Fonctions en plus pour entreprises et associations') !== false, 'french homepage names the extra functions');
        $fr_faq = premium_render_content('content/fr/faq.php');
        assert_true(strpos($fr_faq, '100 % gratuit') === false, 'french faq drops the absolute claim');
        assert_true(strpos($fr_faq, 'pas de comptes premium') === false, 'french faq drops premium accounts');

        $GLOBALS['i18n_lang'] = 'en';
        $en_home = premium_render_content('content/en/index.php');
        assert_true(strpos($en_home, '100% free') === false, 'english homepage drops the absolute claim');
        assert_true(strpos($en_home, 'no premium features') === false, 'english homepage drops premium features');
        assert_true(strpos($en_home, 'Extra features for companies and clubs') !== false, 'english homepage names the extra functions');
        $en_faq = premium_render_content('content/en/faq.php');
        assert_true(strpos($en_faq, '100% free') === false, 'english faq drops the absolute claim');
        assert_true(strpos($en_faq, 'no premium accounts') === false, 'english faq drops premium accounts');

        foreach (array(
            'de' => array('content/de/firmenwichteln-tipps.php', 'Extra-Funktionen für Firmen'),
            'fr' => array('content/fr/firmenwichteln-tipps.php', 'Fonctions en plus pour les entreprises'),
            'en' => array('content/en/firmenwichteln-tipps.php', 'Extra features for companies'),
        ) as $lang => $spec) {
            $GLOBALS['i18n_lang'] = $lang;
            $html = premium_render_content($spec[0]);
            assert_true(strpos($html, $spec[1]) !== false, $lang . ' company page has the section');
            assert_true(strpos($html, 'class="premium-price-list"') !== false, $lang . ' company page lists the prices');
            assert_true(strpos($html, 'CHF 9') !== false && strpos($html, 'CHF 19') !== false && strpos($html, 'CHF 29') !== false, $lang . ' company page lists the prices');
            assert_true(strpos($html, 'CHF 9,') === false, $lang . ' company page does not inline the prices');
            assert_true(strpos($html, 'CHF 49') === false && strpos($html, 'CHF 99') === false, $lang . ' company page drops the old prices');
            assert_true(stripos($html, 'Saison') === false && stripos($html, 'season') === false && stripos($html, 'saison') === false, $lang . ' company page does not sell a season');
            assert_true(strpos($html, 'firmen-premium') !== false && strpos($html, '#kaufen') !== false, $lang . ' company page links to the purchase');
            assert_true(strpos($html, 'ß') === false, $lang . ' company section uses ss');
            assert_true(strpos($html, 'id="extra-funktionen"') !== false, $lang . ' section is present');
            assert_true(strpos($html, 'Ausschlussregeln, die die Auslosung') === false, $lang . ' company page drops the exclusion claim');
            assert_true(strpos($html, 'Exclusion rules the draw') === false, $lang . ' company page drops the exclusion claim');
            assert_true(strpos($html, 'Règles d’exclusion respectées') === false, $lang . ' company page drops the exclusion claim');
            assert_true(strpos($html, t('premium.create.free')) !== false, $lang . ' company page says the normal draw stays free');
        }
        $admin = file_get_contents(dirname(__DIR__) . '/public/admin.php');
        $exclusion = strpos($admin, 'id="ausschluesse"');
        $add = strpos($admin, 'name="add_exclusion"');
        assert_true(strpos($admin, 'premium_group_is_premium') === false, 'normal admin exclusions are not behind a premium licence');
        assert_true($exclusion !== false && $add !== false && $exclusion < $add, 'every group can still save an exclusion');
    } finally {
        if ($had_override) {
            $GLOBALS['premium_enabled_override'] = $previous_override;
        } else {
            unset($GLOBALS['premium_enabled_override']);
        }
        $GLOBALS['i18n_lang'] = $previous_lang;
    }
});

run_test('invoice mail uses the configured recipient exactly', function () {
    assert_equals('kontakt@xn--wichtl-gua.ch', premium_normalize_email('kontakt@xn--wichtl-gua.ch'));
    assert_equals('kontakt@xn--wichtl-gua.ch', premium_normalize_email('Kontakt@wichtlä.ch'));
    $info = premium_invoice_recipient_info();
    assert_equals(premium_invoice_recipient(), $info['email']);
    assert_true($info['source'] === 'config' || $info['source'] === 'fallback' || $info['source'] === 'invalid_fallback');

    $pdo = premium_test_pdo();
    $post = array(
        'tier' => '1x',
        'company' => 'Kontakt AG',
        'address' => 'Seestrasse 2, 8002 Zürich',
        'email' => 'buyer@example.com',
    );
    $sent = array();
    $result = premium_submit_invoice($pdo, $post, 'ip-kontakt-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760000000, 'kontakt@xn--wichtl-gua.ch');
    assert_true($result['ok'], 'invoice to the configured address is stored');
    assert_equals('kontakt@xn--wichtl-gua.ch', $sent[0]['to']);

    $sent = array();
    $unicode = premium_submit_invoice($pdo, $post, 'ip-unicode-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760000000, 'kontakt@wichtlä.ch');
    assert_true($unicode['ok'], 'unicode domain is accepted');
    assert_equals('kontakt@xn--wichtl-gua.ch', $sent[0]['to'], 'unicode domain becomes punycode');

    $flash = premium_send_test_mail(premium_test_sender($sent));
    assert_equals('success', $flash['type'], 'test mail uses the sender');
    assert_true(strpos($flash['text'], premium_invoice_recipient()) !== false, 'test mail names the recipient');
});

run_test('stripe detection accepts restricted keys and rejects publishable keys', function () {
    $prices = array(
        'verein' => 'price_verein_test',
        'firma' => 'price_firma_test',
        'firma_plus' => 'price_plus_test',
    );
    $ready = array(
        'secret' => 'rk_live_restricted_key',
        'webhook' => 'whsec_test_secret_value',
        'prices' => $prices,
    );
    assert_true(premium_stripe_is_configured($ready), 'restricted live key is accepted');
    $status = premium_stripe_status($ready);
    assert_true($status['ready'], 'status is ready');
    assert_equals('rk_live', $status['secret']);
    assert_equals('ok', $status['webhook']);

    $publishable = $ready;
    $publishable['secret'] = 'pk_live_publishable_key';
    assert_true(!premium_stripe_is_configured($publishable), 'publishable key is rejected');
    assert_equals('publishable', premium_stripe_status($publishable)['secret']);

    $quoted = $ready;
    $quoted['secret'] = "\xEF\xBB\xBF" . '"sk_test_secret_key"';
    assert_true(premium_stripe_is_configured($quoted), 'bom and quotes are stripped');
    assert_equals('sk_test', premium_stripe_status($quoted)['secret']);
    assert_true(premium_stripe_is_configured(array(
        'secret' => 'sk_test_secret_key',
        'webhook' => 'whsec_test_secret_value',
        'prices' => $prices,
    )), 'existing test secret stays valid');

    $missing = $ready;
    $missing['prices'] = $prices;
    $missing['prices']['firma'] = '29';
    $bad = premium_stripe_status($missing);
    assert_true(!$bad['ready'], 'a franc amount is not a price id');
    assert_equals('invalid', $bad['prices']['3x']);
    assert_equals('ok', $bad['prices']['1x']);
    assert_equals('missing', premium_stripe_status(array(
        'secret' => '',
        'webhook' => 'whsec_test_secret_value',
        'prices' => array('verein' => 'price_verein_test', 'firma' => '', 'firma_plus' => 'price_plus_test'),
    ))['prices']['3x']);

    assert_equals('sk_test_secret_key', premium_clean_config_text('  "sk_test_secret_key"  '));
    $diag = premium_diagnostics();
    assert_true(isset($diag['invoice_email'], $diag['stripe']['secret'], $diag['from']));
    assert_true(strpos(json_encode($diag), 'sk_live_') === false, 'diagnostics do not contain a secret value');
});

run_test('purchase form sits in the content column and radios are not stretched', function () {
    foreach (array('de', 'fr', 'en') as $lang) {
        $html = file_get_contents(__DIR__ . '/../content/' . $lang . '/firmen-premium.php');
        $pos = strpos($html, 'premium_purchase_section_html');
        $end = strpos($html, '</article>');
        assert_true($pos !== false && $end !== false && $pos < $end, $lang . ' purchase section is inside the article');
    }
    $page = file_get_contents(__DIR__ . '/../public/firmen-premium.php');
    assert_true(strpos($page, 'premium_purchase_section_html') === false, 'the page does not print the form twice');
    $previous_lang = isset($GLOBALS['i18n_lang']) ? $GLOBALS['i18n_lang'] : 'de';
    foreach (array(
        'de' => 'Die Zahlung läuft mit Stripe.',
        'fr' => 'Le paiement se fait avec Stripe.',
        'en' => 'Payment runs with Stripe.',
    ) as $lang => $note) {
        $GLOBALS['i18n_lang'] = $lang;
        assert_equals($note, t('premium.buy.stripe_note'), $lang . ' stripe note is one short sentence');
        assert_true(strpos(t('premium.buy.stripe_note'), 'TWINT') === false, $lang . ' stripe note drops the card details');
    }
    $GLOBALS['i18n_lang'] = $previous_lang;
    $buy = file_get_contents(__DIR__ . '/../includes/premium.php');
    assert_equals(1, substr_count($buy, "t('premium.buy.stripe_note')"), 'the stripe note is printed once');
    $css = file_get_contents(__DIR__ . '/../public/css/styles.css');
    assert_true(strpos($css, '.premium-buy-form input,') === false, 'text input rule does not cover every input');
    assert_true(strpos($css, '.premium-tier-choices input[type="radio"]') !== false, 'radios have their own size');
});

run_test('master admin can reject pending requests and revoke or delete licences', function () {
    $had_override = array_key_exists('premium_enabled_override', $GLOBALS);
    $previous_override = $had_override ? $GLOBALS['premium_enabled_override'] : null;
    $restore_errors = false;
    $GLOBALS['premium_enabled_override'] = true;
    $logo_dir = sys_get_temp_dir() . '/premium-logos-' . bin2hex(random_bytes(4));
    $logo = '';
    mkdir($logo_dir, 0700, true);
    premium_logo_directory($logo_dir);
    try {
        $pdo = premium_test_pdo();
        $sent = array();
        $invoice = premium_submit_invoice($pdo, array(
            'tier' => '3x',
            'company' => 'Offene Rechnung AG',
            'address' => "Bahnhofstrasse 1\n8000 Zürich",
            'email' => 'offen@firma.ch',
            'contact' => 'Ada',
        ), 'ip-reject-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760000000);
        assert_true($invoice['ok'], 'pending invoice exists');
        $pending_id = (int) $invoice['id'];
        $free = premium_test_group($pdo, 'Bleibt frei');
        $pdo->prepare('INSERT INTO `premium_confirmations`
            (`license_id`, `group_id`, `email`, `purpose`, `token_hash`, `code_hash`, `language`, `expires_at`, `created_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute(array(
            $pending_id,
            $free,
            'offen@firma.ch',
            'claim',
            str_repeat('a', 64),
            str_repeat('b', 64),
            'de',
            '2026-10-20 12:00:00',
            '2026-10-19 12:00:00',
        ));

        $rejected = premium_master_handle($pdo, array(
            'premium_action' => 'reject',
            'license_id' => $pending_id,
        ));
        assert_equals('success', $rejected['type'], 'reject flashes success');
        assert_true(strpos($rejected['text'], 'abgelehnt') !== false, 'reject message is German');
        assert_equals(0, (int) $pdo->query('SELECT COUNT(*) FROM `premium_licenses` WHERE `id` = ' . $pending_id)->fetchColumn());
        assert_equals(0, (int) $pdo->query('SELECT COUNT(*) FROM `premium_confirmations`')->fetchColumn());
        assert_equals(1, (int) $pdo->query('SELECT COUNT(*) FROM `groups` WHERE `id` = ' . (int) $free)->fetchColumn());

        $code = 'RVK23456789A';
        $license = premium_test_license($pdo, 'firma', 'aktiv@firma.ch', $code, 'firma.ch');
        $pdo->prepare('UPDATE `premium_licenses` SET `company_name` = ?, `logo_file` = ? WHERE `id` = ?')->execute(array(
            'Aktiv AG',
            '0123456789abcdef0123456789abcdef.png',
            $license,
        ));
        $logo = $logo_dir . '/0123456789abcdef0123456789abcdef.png';
        file_put_contents($logo, 'png');
        $group = premium_test_group($pdo, 'Premium Gruppe');
        $person = premium_test_person($pdo, $group, 'Anna', 'anna@firma.ch');
        assert_true(premium_attach_group($pdo, $license, $group)['ok'], 'group is linked');
        $pdo->prepare('INSERT INTO `premium_confirmations`
            (`license_id`, `group_id`, `email`, `purpose`, `token_hash`, `code_hash`, `language`, `expires_at`, `created_at`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute(array(
            $license,
            $group,
            'aktiv@firma.ch',
            'claim',
            str_repeat('c', 64),
            str_repeat('d', 64),
            'de',
            '2026-10-20 12:00:00',
            '2026-10-19 12:00:00',
        ));

        $keep = premium_master_handle($pdo, array('premium_action' => 'reject', 'license_id' => $license));
        assert_equals('error', $keep['type'], 'an active licence is not rejected');
        assert_equals('active', $pdo->query('SELECT `status` FROM `premium_licenses` WHERE `id` = ' . (int) $license)->fetchColumn());

        $early_delete = premium_master_handle($pdo, array('premium_action' => 'delete_revoked', 'license_id' => $license));
        assert_equals('error', $early_delete['type'], 'an active licence is not deleted');

        $revoked = premium_master_handle($pdo, array('premium_action' => 'revoke', 'license_id' => $license));
        assert_equals('success', $revoked['type'], 'revoke flashes success');
        assert_equals('revoked', $pdo->query('SELECT `status` FROM `premium_licenses` WHERE `id` = ' . (int) $license)->fetchColumn());
        assert_equals($license, (int) $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $group)->fetchColumn());
        assert_equals('Anna', $pdo->query('SELECT `name` FROM `participants` WHERE `id` = ' . (int) $person)->fetchColumn());
        assert_equals('0123456789abcdef0123456789abcdef.png', $pdo->query('SELECT `logo_file` FROM `premium_licenses` WHERE `id` = ' . (int) $license)->fetchColumn());
        assert_true(is_file($logo), 'revoke leaves the logo file in place');
        $group_row = $pdo->query('SELECT * FROM `groups` WHERE `id` = ' . (int) $group)->fetch(PDO::FETCH_ASSOC);
        assert_equals(null, premium_license_for_group($pdo, $group_row));
        $old_code = premium_claim_with_code($pdo, premium_test_group($pdo, 'Neuer Versuch'), $code, 'ip-revoked-' . bin2hex(random_bytes(3)));
        assert_true(!$old_code['ok'], 'a revoked code cannot activate another group');

        $replacement = premium_test_license($pdo, 'firma_plus', 'neu@firma.ch', 'NEW23456789B');
        $moved = premium_attach_group($pdo, $replacement, $group);
        assert_true($moved['ok'], 'a revoked link can be replaced by another active licence');
        assert_equals($replacement, (int) $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $group)->fetchColumn());
        assert_equals('Anna', $pdo->query('SELECT `name` FROM `participants` WHERE `id` = ' . (int) $person)->fetchColumn());

        $again = premium_master_handle($pdo, array('premium_action' => 'revoke', 'license_id' => $license));
        assert_equals('error', $again['type'], 'revoke does not run twice');

        $deleted = premium_master_handle($pdo, array('premium_action' => 'delete_revoked', 'license_id' => $license));
        assert_equals('success', $deleted['type'], 'delete flashes success');
        assert_equals(0, (int) $pdo->query('SELECT COUNT(*) FROM `premium_licenses` WHERE `id` = ' . (int) $license)->fetchColumn());
        assert_equals(0, (int) $pdo->query('SELECT COUNT(*) FROM `premium_confirmations` WHERE `license_id` = ' . (int) $license)->fetchColumn());
        assert_equals(1, (int) $pdo->query('SELECT COUNT(*) FROM `groups` WHERE `id` = ' . (int) $group)->fetchColumn());
        assert_equals($replacement, (int) $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $group)->fetchColumn());
        assert_equals('Anna', $pdo->query('SELECT `name` FROM `participants` WHERE `id` = ' . (int) $person)->fetchColumn());
        assert_true(is_file($logo), 'deleting the licence leaves the logo file in place');

        $linked = premium_test_license($pdo, 'verein', 'haengt@firma.ch', 'HANG2345678C');
        $hanging = premium_test_group($pdo, 'Haengt noch');
        premium_test_person($pdo, $hanging, 'Ben', 'ben@firma.ch');
        assert_true(premium_attach_group($pdo, $linked, $hanging)['ok']);
        assert_true(premium_revoke_license($pdo, $linked));
        assert_true(premium_delete_revoked_license($pdo, $linked));
        $cleared = $pdo->query('SELECT `premium_license_id` FROM `groups` WHERE `id` = ' . (int) $hanging)->fetchColumn();
        assert_true($cleared === null || $cleared === '', 'delete clears premium_license_id first');
        assert_equals(1, (int) $pdo->query('SELECT COUNT(*) FROM `participants` WHERE `group_id` = ' . (int) $hanging)->fetchColumn());

        $open = premium_submit_invoice($pdo, array(
            'tier' => '1x',
            'company' => 'Noch offen',
            'address' => 'Seestrasse 2, 8002 Zürich',
            'email' => 'noch@firma.ch',
        ), 'ip-open-' . bin2hex(random_bytes(3)), premium_test_sender($sent), 1760002000);
        assert_true(premium_revoke_license($pdo, $open['id']) === false, 'a pending request cannot be revoked');
        set_error_handler(function () {
            return true;
        });
        $restore_errors = true;
        $html = premium_master_section_html($pdo);
        assert_true(premium_master_pending_count($pdo) >= 1, 'open requests are counted for the menu');
        $html_licenses = premium_master_section_html($pdo, false);
        assert_true(strpos($html_licenses, 'Ablehnen / Löschen') !== false, 'reject stays on the licence view');
        assert_true(strpos($html_licenses, '<h3>Diagnose</h3>') === false, 'diagnostic leaves the licence view');
        assert_true(strpos($html, 'Ablehnen / Löschen') !== false, 'reject button is shown');
        assert_true(strpos($html, 'name="premium_action" value="reject"') !== false, 'reject is a POST action');
        assert_true(strpos($html, 'name="premium_action" value="activate"') !== false, 'approve stays next to reject');
        assert_true(strpos($html, 'return confirm(') !== false, 'reject asks for confirmation');
        assert_true(strpos($html, 'csrf_token') !== false, 'forms keep the CSRF field');
        assert_true(strpos($html, 'Noch offen') !== false, 'pending company is listed');

        assert_true(premium_activate_license($pdo, $open['id'], 'firma.ch', premium_test_sender($sent), 1760003000)['ok']);
        $html_active = premium_master_section_html($pdo);
        assert_true(strpos($html_active, '>Widerrufen<') !== false, 'revoke button is shown for an active licence');
        assert_true(strpos($html_active, 'Diese Lizenz widerrufen?') !== false, 'revoke asks for confirmation');
        assert_true(premium_revoke_license($pdo, $open['id']));
        $html_revoked = premium_master_section_html($pdo);
        assert_true(strpos($html_revoked, 'Endgültig löschen') !== false, 'a revoked licence can be deleted');
        assert_true(strpos($html_revoked, 'value="delete_revoked"') !== false, 'delete is its own POST action');

        $admin = file_get_contents(dirname(__DIR__) . '/public/admin/index.php');
        $auth = strpos($admin, 'master_admin_authenticated');
        $post = strpos($admin, "REQUEST_METHOD'] === 'POST'");
        $guard = strpos($admin, "isset(\$_POST['premium_action'])");
        $call = strpos($admin, 'premium_master_handle');
        assert_true($auth !== false && $post !== false && $guard !== false && $call !== false, 'admin page wires the handler');
        assert_true($auth < $call && $post < $guard && $guard < $call, 'handler runs after login and only for POST');
    } finally {
        premium_logo_directory(false);
        if (is_file($logo)) {
            unlink($logo);
        }
        if (is_dir($logo_dir)) {
            rmdir($logo_dir);
        }
        if ($had_override) {
            $GLOBALS['premium_enabled_override'] = $previous_override;
        } else {
            unset($GLOBALS['premium_enabled_override']);
        }
        if ($restore_errors) {
            restore_error_handler();
        }
    }
});

run_test('brand colours keep a picker beside the hex field', function () {
    $filled = premium_color_field_html('color_primary', 'Hauptfarbe', '#E63946', '#e63946');
    assert_true(strpos($filled, 'type="color"') !== false, 'native colour picker is present');
    assert_true(strpos($filled, 'data-color-for="color_primary"') !== false, 'picker points at the hex field');
    assert_true(strpos($filled, 'id="color_primary"') !== false, 'hex field keeps its id');
    assert_true(strpos($filled, 'name="color_primary"') !== false, 'hex field is submitted');
    assert_true(preg_match('/<input type="color"[^>]*name=/', $filled) !== 1, 'picker does not override the hex field');
    assert_true(substr_count($filled, 'value="#e63946"') >= 2, 'picker and hex show the stored colour');
    assert_true(strpos($filled, 'style="background-color:#e63946"') !== false, 'preview uses the stored colour');
    assert_true(strpos($filled, 'premium-color-preview') !== false, 'a preview swatch is present');
    assert_true(strpos($filled, 'for="color_primary"') !== false, 'label stays on the hex field');

    $empty = premium_color_field_html('color_accent', 'Akzentfarbe', '', '#2a9d8f');
    assert_true(strpos($empty, 'name="color_accent"') !== false, 'empty accent still submits the hex field');
    assert_true(strpos($empty, 'value=""') !== false, 'empty hex stays empty');
    assert_true(strpos($empty, 'value="#2a9d8f"') !== false, 'picker falls back to the placeholder');
    assert_true(strpos($empty, 'background-color') === false, 'empty hex does not paint the preview');

    $evil = premium_color_field_html('color_primary', 'Hauptfarbe', '"><script>alert(1)</script>', '#e63946');
    assert_true(preg_match('/\sonfocus=/', $evil) !== 1, 'a bad value cannot add an attribute');
    assert_true(strpos($evil, '<script>') === false, 'a bad value is escaped');
    assert_true(strpos($evil, 'background-color') === false, 'an invalid colour does not become the preview');

    assert_equals('#112233', premium_normalize_color(' #112233 '));
    assert_equals('', premium_normalize_color(''));
    assert_equals(null, premium_normalize_color('red'));
    assert_equals(null, premium_normalize_color('#fff'));

    $script = premium_color_picker_script();
    assert_true(strpos($script, 'syncFromPicker') !== false, 'picker writes the hex field');
    assert_true(strpos($script, 'syncFromField') !== false, 'hex writes the picker');
    assert_true(strpos($script, '#[0-9a-fA-F]{6}') !== false, 'only a full hex moves the picker');
    assert_true(strpos($script, 'addEventListener("input"') !== false, 'sync listens while typing');

    $page = file_get_contents(dirname(__DIR__) . '/public/premium-lizenz.php');
    assert_true(strpos($page, 'premium_color_field_html') !== false, 'licence page renders both colour fields');
    assert_true(substr_count($page, 'premium_color_field_html') === 2, 'primary and accent both use the field');
    assert_true(strpos($page, 'premium_color_picker_script') !== false, 'licence page loads the sync script');
    $css = file_get_contents(dirname(__DIR__) . '/public/css/styles.css');
    assert_true(strpos($css, '.premium-color-picker') !== false, 'picker is styled');
    assert_true(strpos($css, '.premium-color-preview') !== false, 'preview is styled');
    assert_true(strpos($css, '#erscheinung .premium-color-controls input[type="text"]') !== false, 'hex field shares the row on small screens');
});

run_test('live reveal is a quiet fullscreen card', function () {
    $page = file_get_contents(dirname(__DIR__) . '/public/premium-aufdeckung.php');
    $css = file_get_contents(dirname(__DIR__) . '/public/css/styles.css');
    assert_true(strpos($page, 'class="premium-reveal-full"') !== false, 'fullscreen control is the small icon button');
    assert_true(strpos($page, 'aria-label="<?php echo h($fullscreen_label); ?>"') !== false, 'fullscreen control has an accessible name');
    assert_true(strpos($page, 'title="<?php echo h($fullscreen_label); ?>"') !== false, 'fullscreen control has a tooltip');
    assert_true(strpos($page, 'id="premium-reveal-full"') !== false, 'fullscreen control keeps its id');
    assert_true(strpos($page, 'class="button secondary" id="premium-reveal-full"') === false, 'fullscreen control is no longer a large button');
    assert_true(strpos($page, 'ArrowLeft') !== false, 'left arrow goes to the previous pair');
    assert_true(strpos($page, 'ArrowRight') !== false, 'right arrow still advances');
    assert_true(strpos($page, "event.key === ' '") !== false, 'space still advances');
    assert_true(strpos($page, 'prefers-reduced-motion: reduce') !== false, 'reduced motion skips the pause');
    assert_true(strpos($page, 'premium_brand_state') !== false, 'the card can use the group logo');
    assert_true(strpos($page, 'premium-reveal-logo') !== false, 'logo markup is present');
    assert_true(strpos($page, 'premium_page_style_attr') !== false, 'brand colours stay on the stage');
    assert_true(strpos($page, 'premium-reveal-frame') !== false, 'centred content is separate from the button');
    assert_true(strpos($page, 'premium-reveal-icon-enter') !== false, 'enter icon points toward the corners');
    assert_true(strpos($page, 'premium-reveal-icon-exit') !== false, 'exit icon is present');
    assert_true(strpos($page, 'fullscreenchange') !== false, 'the icon follows fullscreen changes');
    assert_true(strpos($page, 'token_page_open(') !== false, 'the page uses the shared document head');
    $head = file_get_contents(dirname(__DIR__) . '/includes/templates/head.php');
    assert_true(strpos($head, 'href="/css/styles.css"') !== false, 'the stylesheet is linked');
    assert_true(strpos($css, 'button.premium-reveal-full') !== false, 'fullscreen control is styled on its own');
    assert_true(strpos($css, 'bottom: 0.85rem') !== false, 'the button is anchored to the bottom');
    assert_true(strpos($css, 'right: 0.85rem') !== false, 'the button is anchored to the right');
    assert_true(strpos($css, '.premium-reveal-frame') !== false, 'the frame rule exists in the stylesheet');
    assert_true(strpos($css, '.premium-reveal-icon-exit') !== false, 'the exit icon is styled');
    assert_true(strpos($css, '.premium-reveal-burst') !== false, 'the reveal has a light burst');
    assert_true(strpos($css, '.premium-reveal-card.is-open .premium-reveal-receiver') !== false, 'the receiver appears when the card opens');
    assert_true(strpos($css, 'prefers-reduced-motion: reduce') !== false, 'motion can be turned off');
    $de = i18n_catalog('de');
    $fr = i18n_catalog('fr');
    $en = i18n_catalog('en');
    assert_true(strpos($de['premium.reveal.key'], 'links') !== false, 'German hint mentions the previous pair');
    assert_true(strpos($fr['premium.reveal.key'], 'gauche') !== false, 'French hint mentions the previous pair');
    assert_true(strpos($en['premium.reveal.key'], 'Left arrow') !== false, 'English hint mentions the previous pair');
});

run_test('mail envelope parameter accepts only a safe sender', function () {
    assert_equals('-f noreply@xn--wichtl-gua.ch', mail_envelope_parameter('noreply@xn--wichtl-gua.ch'));
    assert_equals('', mail_envelope_parameter('noreply@xn--wichtl-gua.ch;rm'));
    assert_equals('', mail_envelope_parameter("bad\n@example.com"));
    assert_equals('Wichtel Webseite', encode_mail_display_name('Wichtel Webseite'));
    $mail = build_outgoing_mail('<p>Hallo</p>', true, "Hallo\nAnna -> Bea");
    assert_true(strpos($mail['headers'], 'From: Wichtel Webseite <noreply@xn--wichtl-gua.ch>') !== false, 'ascii from header stays readable');
});
