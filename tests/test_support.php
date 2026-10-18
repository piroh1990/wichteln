<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/group_tools.php';
require_once __DIR__ . '/../includes/support.php';

function support_read($relative) {
    $path = dirname(__DIR__) . '/' . $relative;
    $text = file_get_contents($path);
    if (!is_string($text)) {
        throw new Exception('missing ' . $relative);
    }
    return $text;
}

run_test('support url empty hides everything and the page is 404', function () {
    assert_equals('', support_normalize_url(''));
    assert_equals('', support_normalize_url('   '));
    assert_equals('', support_normalize_url('http://buy.stripe.com/test'));
    assert_equals('', support_normalize_url('javascript:alert(1)'));
    assert_equals('https://buy.stripe.com/test', support_normalize_url('https://buy.stripe.com/test'));
    assert_equals(404, support_status_code(''));
    assert_equals(404, support_status_code('   '));
    assert_equals(200, support_status_code('https://buy.stripe.com/test'));
    assert_equals('', support_card_html('reveal', ''));
    assert_equals('', support_about_html(''));
    assert_equals('', support_footer_html(''));

    $page = support_read('public/unterstuetzen.php');
    $status = strpos($page, 'http_response_code(404)');
    $open = strpos($page, 'public_page_open');
    assert_true($status !== false && $open !== false && $status < $open, '404 happens before the page shell');
    assert_true(strpos($page, '!support_enabled()') !== false, 'page checks SUPPORT_URL');
    assert_true(strpos(support_unavailable_body(), '<p>404</p>') !== false, 'unavailable body is a 404');

    if (!support_enabled()) {
        $php = defined('PHP_BINARY') ? PHP_BINARY : 'php';
        $script = dirname(__DIR__) . '/public/unterstuetzen.php';
        $output = shell_exec(escapeshellarg($php) . ' ' . escapeshellarg($script) . ' 2>/dev/null');
        assert_true(is_string($output), 'support page can be executed');
        assert_true(strpos($output, '<p>404</p>') !== false, 'empty SUPPORT_URL returns 404');
        assert_true(strpos($output, 'support-card') === false, '404 page has no card');
        assert_true(strpos($output, 'buy.stripe.com') === false, '404 page has no payment link');
    }

    $example = support_read('includes/config.example.php');
    assert_true(strpos($example, "define('SUPPORT_URL', '');") !== false, 'example leaves the url empty');
    assert_true(strpos($example, "define('SUPPORT_PROVIDER_NAME', 'Stripe');") !== false, 'provider defaults to Stripe');
    assert_equals('Stripe', support_provider_name());
});

run_test('support card only after reveal and payment links stay plain anchors', function () {
    $participant = support_read('public/participant.php');
    $reveal = strpos($participant, 'reveal-pair-list');
    $card = strpos($participant, "support_card_html('reveal')");
    $waiting = strpos($participant, 'participant.waiting_title');
    assert_true($reveal !== false && $card !== false && $card > $reveal, 'card is under the reveal result');
    assert_true($waiting !== false && $card < $waiting, 'card is not on the waiting state');
    assert_equals(1, substr_count($participant, 'support_card_html'), 'card is rendered once');
    assert_true(strpos($participant, "if (!empty(\$group['reveal_sent_at']))") !== false, 'card waits for the reveal');

    foreach (array('public/index.php', 'public/create_group.php', 'public/register.php', 'content/de/index.php') as $relative) {
        $text = support_read($relative);
        assert_true(strpos($text, 'support_card_html') === false, $relative . ' has no support card');
        assert_true(strpos($text, 'support-card') === false, $relative . ' has no support card markup');
    }

    $mail = support_read('includes/mail_messages.php');
    assert_true(strpos($mail, 'unterstuetzen') === false, 'mails do not link to support');
    assert_true(strpos($mail, 'support_card') === false, 'mails have no support card');
    assert_true(strpos($mail, 'SUPPORT_URL') === false, 'mails have no support url');

    $url = 'https://buy.stripe.com/test';
    foreach (array('de', 'fr', 'en') as $lang) {
        i18n_activate($lang);
        $html = support_card_html('reveal', $url);
        assert_true(strpos($html, 'class="support-card"') !== false, $lang . ' card');
        assert_true(strpos($html, 'href="' . $url . '"') !== false, $lang . ' href');
        assert_true(strpos($html, 'target="_blank"') !== false, $lang . ' target');
        assert_true(strpos($html, 'rel="noopener noreferrer"') !== false, $lang . ' rel');
        assert_true(strpos($html, 'window._paq') !== false, $lang . ' checks _paq');
        assert_true(strpos($html, "trackEvent','Support','Click','reveal'") !== false, $lang . ' reveal event');
        assert_true(strpos($html, '<iframe') === false, $lang . ' card has no iframe');
        assert_true(strpos($html, '<img') === false, $lang . ' card has no image');
        assert_true(strpos($html, 'stripe.js') === false, $lang . ' card loads no stripe script');
        assert_true(strpos($html, h(t('support.button'))) !== false, $lang . ' button label');
        assert_true(strpos($html, h(t('support.fineprint'))) !== false, $lang . ' fine print');

        $about = support_about_html($url);
        assert_true(strpos($about, "trackEvent','Support','Click','about'") !== false, $lang . ' about event');
        assert_true(strpos($about, 'class="support-card"') === false, $lang . ' about is a paragraph, not the card');

        $footer = support_footer_html($url);
        assert_true(strpos($footer, "trackEvent','Support','Click','footer'") !== false, $lang . ' footer event');
        assert_true(strpos($footer, 'target="_blank"') === false, $lang . ' footer stays on the site');
        assert_true(strpos($footer, h(i18n_href('unterstuetzen'))) !== false, $lang . ' footer href');
    }

    i18n_activate('de');
    $page = support_external_anchor('page', t('support.button'), 'button support-button', $url);
    assert_true(strpos($page, "trackEvent','Support','Click','page'") !== false, 'page event');
    assert_true(strpos($page, 'target="_blank"') !== false, 'page link opens a new tab');
    assert_true(strpos($page, 'rel="noopener noreferrer"') !== false, 'page link rel');
});

run_test('support page is routed like the other public pages', function () {
    assert_equals('/unterstuetzen', i18n_public_url('unterstuetzen', 'de'));
    assert_equals('/fr/unterstuetzen', i18n_public_url('unterstuetzen', 'fr'));
    assert_equals('/en/unterstuetzen', i18n_public_url('unterstuetzen', 'en'));
    assert_true(in_array('unterstuetzen', i18n_public_slugs(), true), 'hreflang slug');
    assert_true(in_array('unterstuetzen', public_clean_slugs(), true), 'clean url slug');

    $sitemap = support_read('public/sitemap.xml');
    assert_true(strpos($sitemap, 'https://xn--wichtl-gua.ch/unterstuetzen') !== false, 'sitemap de');
    assert_true(strpos($sitemap, 'https://xn--wichtl-gua.ch/fr/unterstuetzen') !== false, 'sitemap fr');
    assert_true(strpos($sitemap, 'https://xn--wichtl-gua.ch/en/unterstuetzen') !== false, 'sitemap en');

    foreach (array('de', 'fr', 'en') as $lang) {
        assert_true(is_file(dirname(__DIR__) . '/content/' . $lang . '/unterstuetzen.php'), $lang . ' content');
        $about = support_read('content/' . $lang . '/ueber-uns.php');
        assert_true(strpos($about, 'support_about_html()') !== false, $lang . ' about paragraph is optional');
        $privacy = support_read('content/' . $lang . '/datenschutz.php');
        assert_true(strpos($privacy, 'id="freiwillige-unterstuetzung"') !== false, $lang . ' privacy section');
        assert_true(strpos($privacy, 'https://stripe.com/privacy') !== false, $lang . ' stripe privacy link');
        assert_true(strpos($privacy, 'Raths IT') !== false, $lang . ' privacy names Raths IT');
        assert_true(strpos($privacy, 'TWINT') !== false, $lang . ' privacy names TWINT');
        assert_true(stripos($privacy, 'Spendenabzug') === false, $lang . ' privacy has no deduction claim');
        $imprint = support_read('content/' . $lang . '/impressum.php');
        assert_true(strpos($imprint, 'Raths IT') !== false, $lang . ' imprint names the company');
        assert_true(strpos($imprint, 'Patrick Raths') !== false, $lang . ' imprint keeps the person');
        assert_true(strpos($imprint, 'Erlenstrasse 4b') !== false, $lang . ' imprint keeps the address');
    }

    $german = support_read('content/de/unterstuetzen.php') . support_read('content/de/datenschutz.php') . support_read('lang/de.php');
    assert_true(strpos($german, 'ß') === false, 'new German copy uses ss');
    assert_true(strpos($german, 'Spendenabzug') === false, 'no tax deduction wording');
    assert_true(strpos($german, 'Spende an') === false, 'not phrased as a gift to a private person');
});
