<?php

require_once __DIR__ . '/framework.php';

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../includes/config.php';
}
require_once __DIR__ . '/../includes/consent.php';

run_test('google_ads_client_is_real: placeholder and real id', function() {
    assert_true(!google_ads_client_is_real('ca-pub-XXXXXXXXXXXXXXXXX'), 'Placeholder is not a real client id');
    assert_true(!google_ads_client_is_real(''), 'Empty client id is not real');
    assert_true(!google_ads_client_is_real('pub-1234567890123456'), 'Publisher id without ca- prefix is not a client id');
    assert_true(google_ads_client_is_real('ca-pub-1234567890123456'), 'Numeric ca-pub id is real');
});

run_test('google_ads_publisher_id: strips ca- prefix only for real ids', function() {
    assert_equals('pub-1234567890123456', google_ads_publisher_id('ca-pub-1234567890123456'));
    assert_equals('', google_ads_publisher_id('ca-pub-XXXXXXXXXXXXXXXXX'));
});

run_test('google_cmp_would_run: needs flag and real client', function() {
    assert_true(!google_cmp_would_run(false, 'ca-pub-1234567890123456'), 'Disabled CMP does not run');
    assert_true(!google_cmp_would_run(true, 'ca-pub-XXXXXXXXXXXXXXXXX'), 'Placeholder client does not enable CMP');
    assert_true(google_cmp_would_run(true, 'ca-pub-1234567890123456'), 'Real client with flag enables CMP');
});

run_test('example config: testing off, option 3 off, cmp not configured', function() {
    $example = file_get_contents(dirname(__DIR__) . '/includes/config.example.php');
    assert_true(strpos($example, "define('GOOGLE_ADS_TESTING', false);") !== false, 'Testing default is false');
    assert_true(strpos($example, "define('GOOGLE_ADS_SHOW_OPTION3', false);") !== false, 'Option 3 stays off');
    assert_true(strpos($example, "define('GOOGLE_CMP_ENABLED', false);") !== false, 'CMP default is false');
    assert_true(strpos($example, "define('GOOGLE_ADS_ENABLED', false);") !== false, 'Ads stay off until approval');
    assert_true(!google_cmp_would_run(false, 'ca-pub-XXXXXXXXXXXXXXXXX'), 'Example values do not enable the CMP');
});

run_test('ad slots: only homepage and was-ist-wichteln', function() {
    $root = dirname(__DIR__) . '/public/';
    $home = file_get_contents($root . 'index.php');
    $explainer = file_get_contents($root . 'was-ist-wichteln.php');
    assert_true(strpos($home, 'google_ads.php') !== false, 'Homepage includes the ad template');
    assert_true(strpos($explainer, 'google_ads.php') !== false, 'Explainer page includes the ad template');
    assert_true(strpos($home, "\$position = 1;") !== false, 'Homepage uses slot 1');
    assert_true(strpos($explainer, "\$position = 2;") !== false, 'Explainer page uses slot 2');

    $blocked = array('participant.php', 'faq.php', 'impressum.php', 'datenschutz.php', 'admin.php', 'admin-link.php');
    foreach ($blocked as $file) {
        $src = file_get_contents($root . $file);
        assert_true(strpos($src, 'google_ads.php') === false, $file . ' must not include ads');
    }
});

run_test('datenschutz: no absolute third-party denial and no opt-out script', function() {
    $src = file_get_contents(dirname(__DIR__) . '/public/datenschutz.php');
    assert_true(strpos($src, 'niemals mit Dritten') === false, 'Absolute no-sharing sentence is gone');
    assert_true(stripos($src, 'Kein Tracking durch Dritte') === false, 'No-third-party-tracking claim is gone');
    assert_true(strpos($src, 'optOutJS') === false, 'Matomo opt-out script is not embedded');
    assert_true(strpos($src, 'id="consent-revoke"') !== false, 'Revoke control is present');
    assert_true(strpos($src, 'mailto:kontakt@xn--wichtl-gua.ch') !== false, 'Privacy mailto uses punycode');
});

run_test('impressum: postal address and punycode mailto', function() {
    $src = file_get_contents(dirname(__DIR__) . '/public/impressum.php');
    assert_true(strpos($src, 'Patrick Raths') !== false, 'Operator name is present');
    assert_true(strpos($src, 'Erlenstrasse 4b') !== false, 'Street and house number are present');
    assert_true(strpos($src, '5462 Siglistorf') !== false, 'Postal code and city are present');
    assert_true(strpos($src, 'Platzhalter') === false, 'No address placeholder remains');
    assert_true(strpos($src, 'mailto:kontakt@xn--wichtl-gua.ch') !== false, 'Mailto uses punycode host');
    assert_true(strpos($src, 'kontakt@wichtlä.ch') !== false, 'Visible address keeps the IDN');
    assert_true(strpos($src, 'google_ads.php') === false, 'Impressum has no ad include');
});
