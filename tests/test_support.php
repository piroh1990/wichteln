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
    assert_equals(1, substr_count($participant, 'support_card_html'), 'participant card is rendered once');
    assert_true(strpos($participant, "if (!empty(\$group['reveal_sent_at']))") !== false, 'card waits for the reveal');

    $admin = support_read('public/admin.php');
    $danger = strpos($admin, 'id="gefahrenzone"');
    $admin_card = strpos($admin, "support_card_html('admin')");
    assert_true($danger !== false && $admin_card !== false && $admin_card > $danger, 'admin card sits below the management tools');
    assert_equals(1, substr_count($admin, 'support_card_html'), 'admin card is rendered once');

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

        $admin_html = support_card_html('admin', $url);
        assert_true(strpos($admin_html, "trackEvent','Support','Click','admin'") !== false, $lang . ' admin event');
        assert_true(strpos($admin_html, 'target="_blank"') !== false, $lang . ' admin target');
        assert_true(strpos($admin_html, 'rel="noopener noreferrer"') !== false, $lang . ' admin rel');
        assert_true(strpos($admin_html, 'window._paq') !== false, $lang . ' admin checks _paq');
        assert_true(strpos($admin_html, h(t('support.admin'))) !== false, $lang . ' admin copy');
        assert_true(strpos($admin_html, 'TWINT') === false, $lang . ' admin card stays short');
        assert_true(strpos($admin_html, '<iframe') === false, $lang . ' admin card has no iframe');
        assert_true(strpos($admin_html, 'stripe.js') === false, $lang . ' admin card loads no stripe script');
        assert_equals('', support_card_html('admin', ''), $lang . ' admin card hidden without url');

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

    $stories = array(
        'de' => array('wichtlä.ch ist mein Hobby', 'Wohin die Zahlung geht', '<h2>Die Seite bleibt gratis</h2>', 'zu viel Werbung'),
        'fr' => array('wichtlä.ch est mon passe-temps', 'Où va le paiement', '<h2>Le site reste gratuit</h2>', 'trop de publicité'),
        'en' => array('wichtlä.ch is my hobby', 'Where the payment goes', '<h2>The site stays free</h2>', 'too many ads'),
    );
    foreach ($stories as $lang => $pieces) {
        $page = support_read('content/' . $lang . '/unterstuetzen.php');
        $story = strpos($page, $pieces[0]);
        $payment = strpos($page, $pieces[1]);
        assert_true($story !== false && $payment !== false && $story < $payment, $lang . ' story comes before the payment section');
        assert_true(strpos($page, $pieces[2]) === false, $lang . ' old free-site section is gone');
        assert_true(strpos($page, $pieces[3]) !== false, $lang . ' story names why the other sites did not fit');
        $about = support_read('content/' . $lang . '/ueber-uns.php');
        assert_true(stripos($about, 'Hobby') !== false || stripos($about, 'passe-temps') !== false || stripos($about, 'hobby') !== false, $lang . ' about page names the hobby');
        assert_true(strpos($about, $pieces[3]) === false, $lang . ' about page does not repeat the support story');
        assert_true(strpos($about, 'support_about_html()') !== false, $lang . ' about page keeps the support box');
        assert_true(strpos($about, 'email-address') !== false, $lang . ' about page keeps the email');
        assert_true(strpos($about, 'cta-button-white') !== false, $lang . ' about page keeps the call to action');
    }
    $de_page = support_read('content/de/unterstuetzen.php');
    assert_true(strpos($de_page, ' wir ') === false && strpos($de_page, 'Wir ') === false, 'support page speaks as Patrick, not as we');

    i18n_activate('de');
    $about_de = support_read('content/de/ueber-uns.php');
    assert_true(strpos($about_de, 'ß') === false, 'about page uses ss');
    assert_true(strpos($about_de, 'Worum es mir geht') !== false, 'about page heading is first person');
    assert_true(strpos($about_de, 'Ich bin Patrick Raths, und wichtlä.ch ist mein Hobby.') !== false, 'about page speaks as Patrick');
    assert_true(strpos($about_de, 'Ich freue mich über jede Nachricht.') !== false, 'about contact is first person');
    assert_true(strpos($about_de, 'Er hat') === false && strpos($about_de, 'unser ') === false && strpos($about_de, 'Wir ') === false, 'about page drops the old third person');
    assert_true(strpos($about_de, 'Nebenjob') === false && strpos($about_de, 'Werdegang') === false && stripos($about_de, 'Öffnungszeit') === false, 'about page skips biography and hours');
    assert_equals('Über uns - Warum ich wichtlä.ch gebaut habe', t('meta.about.title'), 'about title is first person');
    assert_true(strpos(t('meta.about.description'), 'Ich habe wichtlä.ch gebaut') === 0, 'about description is first person');
});

function support_render_about($lang, $premium) {
    require_once dirname(__DIR__) . '/includes/premium.php';
    $previous_lang = i18n_current();
    $had_override = array_key_exists('premium_enabled_override', $GLOBALS);
    $previous_override = $had_override ? $GLOBALS['premium_enabled_override'] : null;
    i18n_activate($lang);
    $GLOBALS['premium_enabled_override'] = $premium;
    try {
        ob_start();
        include dirname(__DIR__) . '/content/' . $lang . '/ueber-uns.php';
        return ob_get_clean();
    } finally {
        i18n_activate($previous_lang);
        if ($had_override) {
            $GLOBALS['premium_enabled_override'] = $previous_override;
        } else {
            unset($GLOBALS['premium_enabled_override']);
        }
    }
}

run_test('about page stays first person and only names extra features when premium is on', function () {
    $off = array(
        'de' => array('Ich möchte, dass Wichteln einfach ist.', 'Ich bin Patrick Raths', 'Darauf habe ich beim Bauen geachtet:', 'Schreib mir einfach an:', 'Für Firmen und Vereine gibt es Zusatzfunktionen.'),
        'fr' => array('Je veux que le Secret Santa soit simple.', 'Je suis Patrick Raths', 'Voici ce à quoi j’ai fait attention', 'Écris-moi simplement à :', 'Pour les entreprises et les associations, il y a des fonctions en plus.'),
        'en' => array('I want Secret Santa to be simple.', 'I am Patrick Raths', 'Here is what I paid attention to while building it:', 'Just write to me at:', 'Companies and clubs get extra features.'),
    );
    foreach ($off as $lang => $pieces) {
        $html = support_render_about($lang, false);
        assert_true(strpos($html, $pieces[0]) !== false, $lang . ' mission is first person');
        assert_true(strpos($html, $pieces[1]) !== false, $lang . ' names Patrick in first person');
        assert_true(strpos($html, $pieces[2]) !== false, $lang . ' reasons are first person');
        assert_true(strpos($html, $pieces[3]) !== false, $lang . ' contact is first person');
        assert_true(strpos($html, $pieces[4]) === false, $lang . ' hides extra features while premium is off');
        assert_true(strpos($html, 'raths-it.ch') !== false, $lang . ' keeps the company link');
        assert_true(strpos($html, 'mailto:') !== false, $lang . ' keeps the email link');
    }

    $on = support_render_about('de', true);
    assert_true(strpos($on, 'Für Firmen und Vereine gibt es <a href="/firmen-premium">Zusatzfunktionen</a>.') !== false, 'premium about sentence links the extra features');
    assert_true(strpos($on, 'Für Firmen und Vereine gibt es optionale <a href="/firmen-premium">Zusatzfunktionen</a>.') !== false, 'premium list links the optional features');
    $fr = support_render_about('fr', true);
    assert_true(strpos($fr, 'il y a des <a href="/fr/firmen-premium">fonctions en plus</a>.') !== false, 'french premium sentence keeps the link');
    $en = support_render_about('en', true);
    assert_true(strpos($en, 'Companies and clubs get <a href="/en/firmen-premium">extra features</a>.') !== false, 'english premium sentence keeps the link');
    assert_true(strpos($en, 'Companies and clubs get optional <a href="/en/firmen-premium">extra features</a>.') !== false, 'english list keeps the link');
});
