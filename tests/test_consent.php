<?php

require_once __DIR__ . '/framework.php';

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../includes/config.php';
}
require_once __DIR__ . '/../includes/consent.php';

run_test('consent config: matomo only', function() {
    assert_true(!function_exists('google_ads_client_is_real'), 'AdSense client helper is gone');
    assert_true(!function_exists('google_ads_slot_active'), 'Ad slot helper is gone');
    assert_true(!function_exists('google_cmp_is_configured'), 'Google CMP helper is gone');
    $config = consent_client_config();
    assert_true(isset($config['matomoUrl']), 'Matomo URL is present');
    assert_true(isset($config['matomoSiteId']), 'Matomo site id is present');
    assert_true(!isset($config['adsEnabled']), 'Ads flag is not sent to the browser');
    assert_true(!isset($config['client']), 'AdSense client is not sent to the browser');
    assert_true(!isset($config['publisherId']), 'Publisher id is not sent to the browser');
    assert_true(!isset($config['cmpConfigured']), 'CMP flag is not sent to the browser');
});

run_test('example config: no ad constants', function() {
    $example = file_get_contents(dirname(__DIR__) . '/includes/config.example.php');
    assert_true(strpos($example, 'GOOGLE_ADS_') === false, 'Example config has no ad constants');
    assert_true(strpos($example, 'GOOGLE_CMP_') === false, 'Example config has no CMP constant');
    assert_true(strpos($example, "define('MATOMO_URL'") !== false, 'Matomo URL stays');
    assert_true(strpos($example, "define('MATOMO_SITE_ID'") !== false, 'Matomo site id stays');
});

run_test('no public page includes the ad template', function() {
    $root = dirname(__DIR__) . '/public/';
    $files = array(
        'index.php',
        'was-ist-wichteln.php',
        'participant.php',
        'faq.php',
        'impressum.php',
        'datenschutz.php',
        'admin.php',
        'admin-link.php',
    );
    foreach ($files as $file) {
        $src = file_get_contents($root . $file);
        assert_true(strpos($src, 'google_ads.php') === false, $file . ' must not include ads');
    }
    assert_true(!file_exists(dirname(__DIR__) . '/includes/templates/google_ads.php'), 'Ad template is removed');
    $tracking = file_get_contents(dirname(__DIR__) . '/includes/templates/matomo_tracking.php');
    assert_true(strpos($tracking, 'adsbygoogle') === false, 'Tracking script does not load AdSense');
    assert_true(strpos($tracking, 'pagead') === false, 'Tracking script does not load pagead');
    assert_true(strpos($tracking, 'loadMatomo') !== false, 'Matomo loader stays');
});

run_test('cookie texts keep matomo and drop ads', function() {
    $de = require dirname(__DIR__) . '/lang/de.php';
    $fr = require dirname(__DIR__) . '/lang/fr.php';
    $en = require dirname(__DIR__) . '/lang/en.php';
    assert_true(strpos($de['cookie.text'], 'Matomo') !== false, 'German banner names Matomo');
    assert_true(strpos($de['cookie.text'], 'Akzeptieren') !== false, 'German banner explains accept');
    assert_true(strpos($de['cookie.text'], 'Ablehnen') !== false, 'German banner explains decline');
    assert_true(strpos($de['cookie.text'], 'Werbung') === false, 'German banner does not mention ads');
    assert_true(!isset($de['ads.word']), 'German ad label is gone');
    assert_true(strpos($fr['cookie.text'], 'Matomo') !== false, 'French banner names Matomo');
    assert_true(stripos($fr['cookie.text'], 'publicit') === false, 'French banner does not mention ads');
    assert_true(!isset($fr['ads.word']), 'French ad label is gone');
    assert_true(strpos($en['cookie.text'], 'Matomo') !== false, 'English banner names Matomo');
    assert_true(stripos($en['cookie.text'], 'advert') === false, 'English banner does not mention ads');
    assert_true(stripos($en['cookie.text'], 'ads') === false, 'English banner does not mention ads');
    assert_true(!isset($en['ads.word']), 'English ad label is gone');
});

run_test('datenschutz: no absolute third-party denial and no opt-out script', function() {
    $src = file_get_contents(dirname(__DIR__) . '/content/de/datenschutz.php');
    assert_true(strpos($src, 'niemals mit Dritten') === false, 'Absolute no-sharing sentence is gone');
    assert_true(stripos($src, 'Kein Tracking durch Dritte') === false, 'No-third-party-tracking claim is gone');
    assert_true(strpos($src, 'optOutJS') === false, 'Matomo opt-out script is not embedded');
    assert_true(strpos($src, 'id="consent-revoke"') !== false, 'Revoke control is present');
    assert_true(strpos($src, 'mailto:kontakt@xn--wichtl-gua.ch') !== false, 'Privacy mailto uses punycode');
    assert_true(stripos($src, 'AdSense') === false, 'Privacy policy does not mention AdSense');
    assert_true(strpos($src, 'Werbung') === false, 'Privacy policy does not mention advertising');
    assert_true(strpos($src, '__gac') === false, 'Google conversion cookie is not listed');
    assert_true(strpos($src, '__gads') === false, 'AdSense cookie is not listed');
    assert_true(strpos($src, '_pk_id') !== false, 'Matomo cookies stay listed');
});

run_test('impressum: postal address and punycode mailto', function() {
    $src = file_get_contents(dirname(__DIR__) . '/content/de/impressum.php');
    assert_true(strpos($src, 'Patrick Raths') !== false, 'Operator name is present');
    assert_true(strpos($src, 'Erlenstrasse 4b') !== false, 'Street and house number are present');
    assert_true(strpos($src, '5462 Siglistorf') !== false, 'Postal code and city are present');
    assert_true(strpos($src, 'Platzhalter') === false, 'No address placeholder remains');
    assert_true(strpos($src, 'mailto:kontakt@xn--wichtl-gua.ch') !== false, 'Mailto uses punycode host');
    assert_true(strpos($src, 'kontakt@wichtlä.ch') !== false, 'Visible address keeps the IDN');
    assert_true(strpos($src, 'google_ads.php') === false, 'Impressum has no ad include');
});
