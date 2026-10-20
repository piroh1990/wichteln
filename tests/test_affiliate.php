<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/functions.php';

function affiliate_test_with_flag($enabled, $callback) {
    $had = array_key_exists('affiliate_of_enabled_override', $GLOBALS);
    $previous = $had ? $GLOBALS['affiliate_of_enabled_override'] : null;
    $previous_lang = isset($GLOBALS['i18n_lang']) ? $GLOBALS['i18n_lang'] : 'de';
    $GLOBALS['affiliate_of_enabled_override'] = $enabled;
    $GLOBALS['i18n_lang'] = 'de';
    try {
        call_user_func($callback);
    } finally {
        if ($had) {
            $GLOBALS['affiliate_of_enabled_override'] = $previous;
        } else {
            unset($GLOBALS['affiliate_of_enabled_override']);
        }
        $GLOBALS['i18n_lang'] = $previous_lang;
    }
}

run_test('awin link encodes the target and keeps a safe clickref', function () {
    $term = affiliate_of_search_term("  Rote Tasse \n ");
    assert_equals('Rote Tasse', $term, 'search term is trimmed');
    $long = 'Ein sehr langes Geschenk mit vielen Worten damit die Suche nicht ausufert und der Link kurz bleibt extra';
    $short = affiliate_of_search_term($long);
    assert_true(strlen($short) <= 80, 'search term stays within 80 bytes when the text is ascii');
    assert_true(strpos($short, 'extra') === false, 'the term ends on a word');
    $target = affiliate_of_search_target('Rote Tasse');
    assert_equals('https://www.orellfuessli.ch/suche?sq=Rote%20Tasse', $target, 'the search term is appended encoded');
    $link = affiliate_of_link($target, 'wish');
    $expected = 'https://www.awin1.com/cread.php?awinmid=13971&awinaffid=3128240&ued='
        . rawurlencode($target) . '&clickref=wish';
    assert_equals($expected, $link, 'deep link wraps the target');
    assert_equals(affiliate_of_link($target, ''), substr($expected, 0, strpos($expected, '&clickref=')), 'an empty clickref is left off');
    assert_true(strpos(affiliate_of_link($target, 'bad ref'), 'clickref') === false, 'a clickref with a space is dropped');
    assert_equals('', affiliate_of_link('javascript:alert(1)', 'wish'), 'only https targets are wrapped');
    $lines = affiliate_of_wish_lines("Buch\n\n- Spiel\n2. Tee");
    assert_equals(array('Buch', 'Spiel', 'Tee'), $lines, 'empty lines and list markers are skipped');
});

run_test('affiliate output stays empty until the feature is on', function () {
    affiliate_test_with_flag(false, function () {
        $free = array('premium_license_id' => null);
        assert_true(!affiliate_of_show_for_group($free), 'a free group stays quiet while the feature is off');
        assert_equals('', affiliate_of_participant_html($free, "Buch"), 'the participant page prints nothing');
        $mail = create_html_email(array(
            'name' => 'Max',
            'assigned_name' => 'Erika',
            'wishlist' => 'Buch',
            'language' => 'de',
            'affiliate' => false,
        ));
        assert_true(strpos($mail, 'awin1.com') === false, 'the draw mail has no affiliate link');
        $plain = create_partner_plain_text(array(
            'name' => 'Max',
            'assigned_name' => 'Erika',
            'wishlist' => 'Buch',
            'language' => 'de',
        ));
        assert_true(strpos($plain, 'awin1.com') === false, 'the plain draw mail has no affiliate link');
    });
});

run_test('free groups get wish links and mails, premium groups stay off', function () {
    affiliate_test_with_flag(true, function () {
        $free = array('id' => 1);
        assert_true(affiliate_of_show_for_group($free), 'a free group shows links when the feature is on');
        $premium = array('id' => 2, 'premium_license_id' => 9, 'affiliate_of' => 0);
        assert_true(!affiliate_of_show_for_group($premium), 'a premium group is off by default');
        assert_true(!affiliate_of_show_for_group(array('premium_license_id' => 9)), 'a premium group without the column stays off');
        $opt = array('id' => 3, 'premium_license_id' => 9, 'affiliate_of' => '1');
        assert_true(affiliate_of_show_for_group($opt), 'a premium group can opt in');

        $html = affiliate_of_participant_html($free, "Buch\nSpiel");
        assert_true(strpos($html, 'Bei Orell Füssli suchen') !== false, 'each wish gets a search link');
        assert_true(substr_count($html, 'affiliate-of-link') === 2, 'two wishes, two links');
        assert_true(strpos($html, 'rel="sponsored noopener"') !== false && strpos($html, 'target="_blank"') !== false, 'links are sponsored and open in a new tab');
        assert_true(strpos($html, 'clickref=wish') !== false, 'wish links name their source');
        assert_true(strpos($html, 'sq=Buch') !== false || strpos($html, rawurlencode('sq=Buch')) !== false, 'the wish is the search term');
        assert_true(strpos($html, 'affiliate-note') === false, 'the wish list markup has no advertising note');
        assert_true(strpos($html, '<script') === false && strpos($html, '<img') === false, 'no script and no image');
        $note = affiliate_of_note_html();
        assert_true(strpos($note, 'Bei Orell Füssli suchen') !== false, 'the page note names the search links');
        assert_true(strpos($note, 'Werbelinks') !== false, 'the page note calls them ad links');
        assert_true(strpos(affiliate_of_note_html('one'), 'diesen Link') !== false, 'one link uses the singular note');

        $empty = affiliate_of_participant_html($free, '');
        assert_true(strpos($empty, 'Geschenkideen bei Orell Füssli') !== false, 'an empty wishlist gets the gift link');
        assert_true(strpos($empty, 'clickref=reveal-page') !== false, 'the gift link names the page');
        assert_true(strpos($empty, 'affiliate-note') === false, 'the empty gift block has no note inside it');
        assert_equals('', affiliate_of_participant_html($premium, 'Buch'), 'a premium group without opt-in gets nothing');

        foreach (array(
            'de' => array('lead' => 'Noch kein Geschenk?', 'partner' => 'Dein Wichtelpartner', 'wish' => 'Wunschliste von', 'details' => 'Gruppendetails', 'button' => 'Zum Teilnehmerbereich', 'signoff' => 'Viel Spass beim Wichteln', 'note' => 'diesen Link', 'plural' => 'diese Links', 'link' => 'Geschenkideen bei Orell Füssli'),
            'fr' => array('lead' => 'Pas encore de cadeau', 'partner' => 'Tu offres un cadeau', 'wish' => 'Liste de souhaits', 'details' => 'Détails du groupe', 'button' => 'espace participant', 'signoff' => 'Amuse-toi bien', 'note' => 'ce lien', 'plural' => 'ces liens', 'link' => 'Idées cadeaux chez Orell Füssli'),
            'en' => array('lead' => 'No gift yet?', 'partner' => 'giving a gift to', 'wish' => 'Wishlist of', 'details' => 'Group details', 'button' => 'Go to your participant area', 'signoff' => 'Have fun with your Secret Santa', 'note' => 'this link', 'plural' => 'these links', 'link' => 'Gift ideas at Orell Füssli'),
        ) as $lang => $copy) {
            $GLOBALS['i18n_lang'] = $lang;
            $mail = create_html_email(array(
                'name' => 'Max',
                'assigned_name' => 'Erika',
                'wishlist' => 'Buch',
                'participant_link' => 'https://example.test/participant',
                'language' => $lang,
                'affiliate' => true,
            ));
            $lead_at = strpos($mail, $copy['lead']);
            $partner_at = strpos($mail, '>Erika</p>');
            $wish_at = strpos($mail, $copy['wish']);
            $details_at = strpos($mail, $copy['details']);
            $button_at = strpos($mail, $copy['button']);
            $signoff_at = strpos($mail, $copy['signoff']);
            $note_at = strpos($mail, $copy['note']);
            assert_true($lead_at !== false, $lang . ' draw mail has the short paragraph');
            assert_true($partner_at !== false && $lead_at > $partner_at, $lang . ' offer sits under the partner name');
            assert_true($wish_at !== false && $lead_at < $wish_at, $lang . ' offer sits above the wishlist');
            assert_true($details_at !== false && $lead_at < $details_at, $lang . ' offer sits above the group details');
            assert_true($button_at !== false && strpos($mail, 'white-space: nowrap') !== false, $lang . ' participant button stays on one line');
            assert_true(strpos($mail, 'Teilnehmerbe reich') === false && strpos($mail, '&shy;') === false, $lang . ' button label is not split');
            assert_true(strpos($mail, 'clickref=mail-draw') !== false, $lang . ' draw mail names the source');
            assert_true(strpos($mail, 'rel="sponsored noopener"') !== false, $lang . ' draw mail link is sponsored');
            $link_at = strpos($mail, $copy['link']);
            $button_chunk = $link_at === false ? '' : substr($mail, max(0, $link_at - 500), 500);
            assert_true(strpos($button_chunk, 'background: #e63946') !== false, $lang . ' gift link is a red button');
            assert_true($signoff_at !== false && $note_at !== false && $note_at > $signoff_at, $lang . ' singular note sits under the signoff');
            assert_true(strpos($mail, $copy['plural']) === false, $lang . ' mail does not talk about several links');
            $plain = create_partner_plain_text(array(
                'name' => 'Max',
                'assigned_name' => 'Erika',
                'wishlist' => 'Buch',
                'participant_link' => 'https://example.test/participant',
                'language' => $lang,
                'affiliate' => true,
            ));
            $plain_lead = strpos($plain, $copy['lead']);
            $plain_wish = strpos($plain, $copy['wish']);
            $plain_signoff = strpos($plain, $copy['signoff']);
            $plain_note = strpos($plain, $copy['note']);
            assert_true(strpos($plain, 'clickref=mail-draw') !== false, $lang . ' plain draw mail has the link');
            assert_true($plain_lead !== false && $plain_wish !== false && $plain_lead < $plain_wish, $lang . ' plain offer sits above the wishlist');
            assert_true($plain_signoff !== false && $plain_note !== false && $plain_note > $plain_signoff, $lang . ' plain note sits under the signoff');
            assert_true(strpos($plain, $copy['plural']) === false, $lang . ' plain mail does not talk about several links');
            $reminder = create_gift_reminder_email(array(
                'name' => 'Max',
                'sentence' => 'Morgen ist Übergabe.',
                'group_name' => 'Team',
                'gift_date' => '24.12.2026',
                'participant_link' => 'https://example.test/p',
                'language' => $lang,
                'affiliate' => true,
            ));
            $reminder_lead = strpos($reminder, $copy['lead']);
            $reminder_button = strpos($reminder, $copy['button']);
            $reminder_signoff = strpos($reminder, $copy['signoff']);
            $reminder_note = strpos($reminder, $copy['note']);
            assert_true(strpos($reminder, 'clickref=mail-reminder') !== false, $lang . ' reminder names the source');
            assert_true($reminder_lead !== false && $reminder_button !== false && $reminder_lead < $reminder_button, $lang . ' reminder offer sits above the participant button');
            assert_true($reminder_signoff !== false && $reminder_note !== false && $reminder_note > $reminder_signoff, $lang . ' reminder note sits under the signoff');
            assert_true(strpos($reminder, '<img') === false || strpos($reminder, 'awin') === false, $lang . ' reminder does not use an affiliate image');
            $reminder_plain = create_gift_reminder_plain_text(array(
                'name' => 'Max',
                'sentence' => 'Morgen ist Übergabe.',
                'group_name' => 'Team',
                'gift_date' => '24.12.2026',
                'participant_link' => 'https://example.test/p',
                'language' => $lang,
                'affiliate' => true,
            ));
            $reminder_plain_lead = strpos($reminder_plain, $copy['lead']);
            $reminder_plain_link = strpos($reminder_plain, 'https://example.test/p');
            $reminder_plain_note = strpos($reminder_plain, $copy['note']);
            assert_true(strpos($reminder_plain, 'clickref=mail-reminder') !== false, $lang . ' plain reminder has the link');
            assert_true($reminder_plain_lead !== false && $reminder_plain_link !== false && $reminder_plain_lead < $reminder_plain_link, $lang . ' plain reminder offer sits above the participant link');
            assert_true($reminder_plain_note !== false && strpos($reminder_plain, $copy['signoff']) !== false && $reminder_plain_note > strpos($reminder_plain, $copy['signoff']), $lang . ' plain reminder note sits under the signoff');
        }

        $quiet = create_html_email(array(
            'name' => 'Max',
            'assigned_name' => 'Erika',
            'wishlist' => 'Buch',
            'language' => 'de',
        ));
        assert_true(strpos($quiet, 'awin1.com') === false, 'a mail without the flag stays clean');
    });
});

run_test('premium toggle saves the group choice and stays hidden while affiliate is off', function () {
    $had_premium = array_key_exists('premium_enabled_override', $GLOBALS);
    $previous_premium = $had_premium ? $GLOBALS['premium_enabled_override'] : null;
    $GLOBALS['premium_enabled_override'] = true;
    set_error_handler(function () {
        return true;
    });
    try {
        affiliate_test_with_flag(false, function () {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('CREATE TABLE `groups` (`id` INTEGER PRIMARY KEY, `premium_license_id` INTEGER, `affiliate_of` INTEGER NOT NULL DEFAULT 0)');
            $pdo->exec('CREATE TABLE `premium_licenses` (`id` INTEGER PRIMARY KEY, `status` TEXT)');
            $pdo->exec('CREATE TABLE `premium_confirmations` (`id` INTEGER PRIMARY KEY)');
            $pdo->exec('INSERT INTO `groups` (`id`, `premium_license_id`, `affiliate_of`) VALUES (4, 8, 0)');
            $group = $pdo->query('SELECT * FROM `groups` WHERE `id` = 4')->fetch(PDO::FETCH_ASSOC);
            assert_equals('', affiliate_of_admin_toggle_html($pdo, $group), 'the toggle is hidden while the feature is off');
        });
        affiliate_test_with_flag(true, function () {
            $pdo = new PDO('sqlite::memory:');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('CREATE TABLE `groups` (`id` INTEGER PRIMARY KEY, `name` TEXT, `admin_token` TEXT, `premium_license_id` INTEGER, `affiliate_of` INTEGER NOT NULL DEFAULT 0)');
            $pdo->exec('CREATE TABLE `premium_licenses` (`id` INTEGER PRIMARY KEY, `tier` TEXT, `status` TEXT, `buyer_email` TEXT)');
            $pdo->exec('CREATE TABLE `premium_confirmations` (`id` INTEGER PRIMARY KEY)');
            $pdo->exec("INSERT INTO `premium_licenses` (`id`, `tier`, `status`, `buyer_email`) VALUES (8, '1x', 'active', 'firma@example.com')");
            $pdo->exec("INSERT INTO `groups` (`id`, `name`, `admin_token`, `premium_license_id`, `affiliate_of`) VALUES (4, 'Firma', 'tok', 8, 0)");
            $group = $pdo->query('SELECT * FROM `groups` WHERE `id` = 4')->fetch(PDO::FETCH_ASSOC);
            $form = affiliate_of_admin_toggle_html($pdo, $group);
            assert_true(strpos($form, 'name="affiliate_of"') !== false, 'the premium section can opt in');
            assert_true(strpos($form, ' checked') === false, 'the box starts unchecked');
            $saved = premium_admin_handle($pdo, $group, array(
                'premium_action' => 'affiliate_of',
                'affiliate_of' => '1',
            ));
            assert_equals('success', $saved['notice']['type'], 'saving the toggle succeeds');
            assert_equals('1', (string) $pdo->query('SELECT `affiliate_of` FROM `groups` WHERE `id` = 4')->fetchColumn(), 'the group is opted in');
            $group['affiliate_of'] = 1;
            $cleared = premium_admin_handle($pdo, $group, array(
                'premium_action' => 'affiliate_of',
            ));
            assert_equals('success', $cleared['notice']['type'], 'clearing the toggle succeeds');
            assert_equals('0', (string) $pdo->query('SELECT `affiliate_of` FROM `groups` WHERE `id` = 4')->fetchColumn(), 'the group is off again');
        });
    } finally {
        restore_error_handler();
        if ($had_premium) {
            $GLOBALS['premium_enabled_override'] = $previous_premium;
        } else {
            unset($GLOBALS['premium_enabled_override']);
        }
    }
});

run_test('privacy mentions awin and the page does not load a tracking script', function () {
    foreach (array('de', 'fr', 'en') as $lang) {
        $privacy = file_get_contents(__DIR__ . '/../content/' . $lang . '/datenschutz.php');
        assert_true(strpos($privacy, 'id="werbelinks"') !== false, $lang . ' privacy has the affiliate section');
        assert_true(stripos($privacy, 'Awin') !== false, $lang . ' privacy names Awin');
        assert_true(stripos($privacy, 'Orell') !== false, $lang . ' privacy names Orell Füssli');
    }
    $head = file_get_contents(__DIR__ . '/../includes/templates/head.php');
    $page = file_get_contents(__DIR__ . '/../public/participant.php');
    assert_true(strpos($head, 'awin') === false, 'the document head has no awin script');
    assert_true(strpos($page, 'affiliate_of_participant_html') !== false, 'the participant page asks the helper');
    $list_close = strpos($page, 'id="partner-wishlist"');
    $list_note = strpos($page, 'affiliate_of_note_html()');
    $empty_box = strpos($page, 'class="empty-wishlist"');
    $one_note = strpos($page, "affiliate_of_note_html('one')");
    assert_true($list_close !== false && $list_note !== false && $list_note > $list_close, 'the wishlist note is printed after the list box');
    assert_true($empty_box !== false && $one_note !== false && $one_note > $empty_box, 'the empty-list note is printed after that box');
    assert_true(strpos($page, 'awin1.com') === false, 'the participant page does not hard-code a tracking url');
    $example = file_get_contents(__DIR__ . '/../includes/config.example.php');
    assert_true(strpos($example, "define('AFFILIATE_OF_ENABLED', '0');") !== false, 'the feature defaults to off');
    assert_true(strpos($example, '13971') !== false && strpos($example, '3128240') !== false, 'the example lists the awin ids');
});
