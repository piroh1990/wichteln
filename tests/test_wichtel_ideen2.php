<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/functions.php';

if (!defined('WICHTEL_IDEEN2_LIBRARY')) {
    define('WICHTEL_IDEEN2_LIBRARY', true);
}
require_once __DIR__ . '/../public/wichtel-ideen2.php';

function ideas2_test_render($lang, $enabled) {
    $had = array_key_exists('affiliate_of_enabled_override', $GLOBALS);
    $previous = $had ? $GLOBALS['affiliate_of_enabled_override'] : null;
    $get = $_GET;
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : null;
    $GLOBALS['affiliate_of_enabled_override'] = $enabled;
    $_GET = array('lang' => $lang);
    $_SERVER['SCRIPT_NAME'] = '/wichtel-ideen2.php';
    ob_start();
    try {
        ideas2_render();
        return ob_get_clean();
    } catch (Exception $e) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        throw $e;
    } finally {
        $_GET = $get;
        if ($script === null) {
            unset($_SERVER['SCRIPT_NAME']);
        } else {
            $_SERVER['SCRIPT_NAME'] = $script;
        }
        if ($had) {
            $GLOBALS['affiliate_of_enabled_override'] = $previous;
        } else {
            unset($GLOBALS['affiliate_of_enabled_override']);
        }
    }
}

function ideas2_card_has_link($html, $title) {
    $pos = strpos($html, $title);
    assert_true($pos !== false, 'missing title ' . $title);
    $next = strpos($html, 'idea-card', $pos);
    $slice = ($next === false) ? substr($html, $pos, 900) : substr($html, $pos, $next - $pos);
    return strpos($slice, 'awin1.com') !== false;
}

function ideas2_href($target) {
    return htmlspecialchars(affiliate_of_link($target), ENT_QUOTES, 'UTF-8');
}

run_test('draft page stays out of navigation, sitemap and the live page', function () {
    $root = dirname(__DIR__);
    foreach (array(
        'includes/templates/navigation.php',
        'includes/templates/footer.php',
        'public/sitemap.xml',
        'public/robots.txt',
        'public/wichtel-ideen.php',
        'content/de/wichtel-ideen.php',
        'content/fr/wichtel-ideen.php',
        'content/en/wichtel-ideen.php',
        'includes/i18n.php',
        'includes/group_tools.php',
    ) as $relative) {
        $src = file_get_contents($root . '/' . $relative);
        assert_true(strpos($src, 'wichtel-ideen2') === false, $relative . ' does not mention the draft');
    }

    $htaccess = file_get_contents($root . '/public/.htaccess');
    assert_equals(2, substr_count($htaccess, 'wichtel-ideen2'), 'the draft appears only in its own rewrite');
    assert_true(strpos($htaccess, 'RewriteRule ^wichtel-ideen2/?$ wichtel-ideen2.php [L,QSA]') !== false, 'clean URL maps to the php file');
    assert_true(strpos($htaccess, 'datenschutz|wichtel-ideen|firmenwichteln-tipps') !== false, 'the live slug stays in the existing rules');

    $page = file_get_contents($root . '/public/wichtel-ideen2.php');
    assert_true(strpos($page, "header('X-Robots-Tag: noindex, nofollow')") !== false, 'draft sends a noindex header');
    assert_true(strpos($page, 'ß') === false, 'draft shell uses Swiss spelling');
    assert_true(strpos(file_get_contents($root . '/content/de/wichtel-ideen2.php'), 'ß') === false, 'German draft uses Swiss spelling');
});

run_test('affiliate links stay hidden while the feature is off', function () {
    $html = ideas2_test_render('de', false);
    assert_true(strpos($html, 'name="robots" content="noindex, nofollow"') !== false, 'meta robots is noindex');
    assert_true(strpos($html, 'rel="canonical"') === false, 'draft has no canonical');
    assert_true(strpos($html, '<link rel="alternate"') === false, 'draft does not advertise language versions');
    assert_true(strpos($html, 'awin1.com') === false, 'no Awin URL');
    assert_true(strpos($html, '<aside class="of-draft-banner"') === false, 'no banner');
    assert_true(strpos($html, '<p class="affiliate-note of-draft-note">') === false, 'no advertising note');
    assert_true(strpos($html, 'Bei Orell Füssli ansehen') === false, 'no product link text');
    assert_true(strpos($html, 'Kugelschreiber') !== false, 'the ideas themselves stay');
    assert_true(strpos($html, 'Bis CHF 10') !== false, 'budget bands stay without the shop');
    assert_true(strpos($html, 'Geschenkkarte') === false, 'no gift-card hint while the feature is off');
    assert_true(strpos($html, 'href="/wichtel-ideen"') !== false, 'menu still points at the live page');
    assert_true(strpos($html, 'href="/wichtel-ideen2"') === false, 'nothing links the draft as a normal page');
    assert_true(strpos($html, 'href="/wichtel-ideen2?setlang=de"') !== false, 'language switch stays on the draft');
    assert_true(strpos($html, 'href="/fr/wichtel-ideen2?setlang=fr"') !== false, 'French switch stays on the draft');
    assert_true(strpos($html, 'href="/en/wichtel-ideen2?setlang=en"') !== false, 'English switch stays on the draft');
});

function ideas2_expected() {
    return array(
        array('10', 'Kugelschreiber', 'Kugelschreiber'),
        array('10', 'Lesezeichen', 'Lesezeichen'),
        array('10', 'Handcreme', 'Handcreme'),
        array('10', 'Badebombe', 'Badebombe'),
        array('20', 'Notizbuch', 'Notizbuch'),
        array('20', 'Leselampe', 'Leselampe'),
        array('20', 'Tischkalender 2027', 'Tischkalender 2027'),
        array('20', 'Socken', 'Socken'),
        array('20', 'Lichterkette', 'Lichterkette'),
        array('20', 'Schlüsselanhänger', 'Schlüsselanhänger'),
        array('20', 'Trinkflasche', 'Trinkflasche'),
        array('20', 'Tee-Adventskalender', 'Tee Adventskalender'),
        array('20', 'Skyjo', 'Skyjo'),
        array('20', 'Flip 7', 'Flip 7'),
        array('20', 'Qwixx', 'Qwixx'),
        array('20', 'Quizspiel', 'Quiz Spiel'),
        array('20', 'Witzebuch', 'Witzebuch'),
        array('20', 'Gesprächskarten', 'DeepTalk'),
        array('20', 'Puzzle mit 1000 Teilen', 'Puzzle 1000'),
        array('20', 'Bilderrahmen', 'Bilderrahmen'),
        array('20', 'Wandkalender 2027', 'Wandkalender 2027'),
        array('30', 'Tasse', 'Tasse'),
        array('30', 'Powerbank', 'Powerbank'),
        array('30', 'Duftkerze', 'Duftkerze'),
        array('30', 'Hitster', 'Hitster'),
        array('30', 'Partyspiel', 'Dingsbums'),
        array('30', 'Escape Dinner', 'Escape Dinner'),
        array('30', 'Murdoku', 'Murdoku'),
        array('30', '3D-Puzzle', '3D Puzzle'),
        array('30', 'Ausmalbuch', 'Ausmalbuch'),
        array('30', 'Bastelset', 'Bastelset'),
        array('30', 'Kreativset', 'Kreativset'),
        array('30', 'LEGO Botanicals', 'LEGO Botanicals'),
        array('30', 'Scrapbooking-Set', 'Scrapbooking-Set'),
        array('30', 'LEGO-Adventskalender', 'LEGO Adventskalender'),
        array('50', 'Thermobecher', 'Thermobecher'),
        array('50', 'Dorfromantik', 'Dorfromantik'),
        array('50', 'EXIT-Adventskalender', 'EXIT Adventskalender'),
        array('50', 'Fotoalbum', 'Fotoalbum'),
        array('50', 'Kochbuch', 'Kochbuch'),
    );
}

function ideas2_awin_hrefs($html) {
    preg_match_all('/href="([^"]*awin1\.com[^"]*)"/', $html, $matches);
    $hrefs = $matches[1];
    sort($hrefs);
    return $hrefs;
}

function ideas2_article($html) {
    $start = strpos($html, '<article>');
    $end = strpos($html, '</article>');
    assert_true($start !== false && $end !== false && $end > $start, 'article is present');
    return substr($html, $start, $end - $start);
}

run_test('every card links to an Orell Fuessli search and drops anything the shop does not sell', function () {
    $html = ideas2_test_render('de', true);
    $expected = ideas2_expected();
    $bands = array('10' => 'Bis CHF 10', '20' => 'Bis CHF 20', '30' => 'Bis CHF 30', '50' => 'Bis CHF 50');
    $band_at = array();
    foreach ($bands as $group => $label) {
        $band_at[$group] = strpos($html, $label);
        assert_true($band_at[$group] !== false, 'missing band ' . $label);
    }
    assert_true($band_at['10'] < $band_at['20'] && $band_at['20'] < $band_at['30'] && $band_at['30'] < $band_at['50'], 'bands stay in order');

    foreach ($expected as $row) {
        $next = array('10' => '20', '20' => '30', '30' => '50', '50' => '');
        $title = $row[1];
        $term = $row[2];
        $pos = strpos($html, $title);
        assert_true($pos !== false, 'missing title ' . $title);
        assert_true($pos > $band_at[$row[0]], $title . ' sits in its budget band');
        if ($next[$row[0]] !== '') {
            assert_true($pos < $band_at[$next[$row[0]]], $title . ' does not slip into the next band');
        }
        assert_true(ideas2_card_has_link($html, $title), $title . ' links to Orell Füssli');
        $href = ideas2_href(affiliate_of_search_target($term));
        $slice_end = strpos($html, 'idea-card', $pos);
        $slice = ($slice_end === false) ? substr($html, $pos, 900) : substr($html, $pos, $slice_end - $pos);
        assert_true(strpos($slice, $href) !== false, $title . ' searches for ' . $term);
    }

    $cards = substr_count($html, 'class="idea-card"');
    assert_equals(count($expected), $cards, 'one card per checked idea');
    assert_equals($cards, substr_count($html, 'class="idea-shop"'), 'every card has one shop link');
    $shop_links = $cards + 2;
    assert_equals($shop_links, substr_count($html, 'awin1.com'), 'cards, banner and gift card');
    assert_equals($shop_links, substr_count($html, 'rel="sponsored noopener"'), 'every shop link is sponsored');
    assert_equals($shop_links, substr_count($html, 'target="_blank"'), 'every shop link opens in a new tab');
    $hrefs = ideas2_awin_hrefs($html);
    assert_equals($shop_links, count($hrefs), 'every awin href is found');
    assert_equals($shop_links, count(array_unique($hrefs)), 'search terms are not reused');
    assert_true(in_array(ideas2_href(affiliate_of_gift_url()), $hrefs, true), 'banner uses the gift category');
    assert_true(in_array(ideas2_href(affiliate_of_search_target('Geschenkkarte')), $hrefs, true), 'gift card searches for Geschenkkarte');
    assert_true(strpos($html, 'clickref') === false, 'draft links do not add a clickref');
    assert_true(strpos($html, 'Passende Geschenke findest du bei Orell Füssli.') !== false, 'banner copy');
    assert_true(strpos($html, 'Geschenkideen bei Orell Füssli') !== false, 'red button label');
    assert_true(strpos($html, 'Bei Orell Füssli ansehen') !== false, 'product link label');
    assert_true(strpos($html, 'Die Links zu Orell Füssli sind Werbelinks.') !== false, 'note under the ideas');
    assert_true(strpos($html, 'Wenn nichts davon passt, ist eine Geschenkkarte die einfache Notlösung.') !== false, 'gift card fallback');

    $note = strpos($html, '<p class="affiliate-note of-draft-note">');
    $gift = strpos($html, 'class="of-draft-fallback"');
    $grid = strpos($html, 'ideas-grid');
    $article_end = strpos($html, '</article>');
    assert_true($note !== false && $grid !== false && $note > $grid, 'the note sits below the idea boxes');
    assert_true($article_end !== false && $gift !== false && $gift > $article_end && $note > $gift, 'gift card and note sit outside the article');

    $article = ideas2_article($html);
    assert_true(preg_match('/\d+\.\d{2}/', $article) !== 1, 'no exact prices in the ideas');
    assert_true(strpos($article, 'awin1.com') !== false, 'shop links live with the ideas');
    foreach (array(
        'Schokolade', 'Gewürz', 'Honig', 'Kaffee', 'Kuscheldecke', 'DIY', 'Selbstgemacht',
        'Erlebnis', 'Insider', 'personalisiert', 'Sukkulente', 'Tee-Set', 'Mini-Pflanze',
        'T-Shirt', 'Einhorn', 'Gutschein',
    ) as $banned) {
        assert_true(strpos($article, $banned) === false, 'removed idea stays off the page: ' . $banned);
    }
    foreach (array('Büro', 'Spielspass', 'Gemütlich', 'Kreativ') as $category) {
        assert_true(strpos($article, $category) !== false, 'category ' . $category);
    }
});

run_test('french and english drafts translate the affiliate copy', function () {
    $fr = ideas2_test_render('fr', true);
    assert_true(strpos($fr, 'Tu trouves des cadeaux adaptés chez Orell Füssli.') !== false, 'French banner');
    assert_true(strpos($fr, 'Idées cadeaux chez Orell Füssli') !== false, 'French button');
    assert_true(strpos($fr, 'Voir chez Orell Füssli') !== false, 'French product link');
    assert_true(strpos($fr, 'Les liens vers Orell Füssli sont des liens publicitaires.') !== false, 'French note');
    assert_true(strpos($fr, ideas2_href(affiliate_of_search_target('Skyjo'))) !== false, 'French page keeps the German shop search');
    assert_true(strpos($fr, 'Marque-page') !== false, 'French title');
    assert_true(strpos($fr, 'Bougie parfumée') !== false, 'French candle');
    assert_true(strpos($fr, 'Si rien ne convient, une carte cadeau est la solution simple.') !== false, 'French gift card');
    assert_true(strpos($fr, 'Ein Kugelschreiber fürs Büro') === false, 'French page does not show the German copy');
    assert_equals(substr_count($fr, 'class="idea-card"'), count(ideas2_expected()), 'French page has the same cards');
    $de = ideas2_test_render('de', true);
    assert_equals(ideas2_awin_hrefs($de), ideas2_awin_hrefs($fr), 'French page uses the same shop links');
    assert_true(strpos($fr, 'href="/fr/wichtel-ideen"') !== false, 'French menu still points at the live page');

    $en = ideas2_test_render('en', true);
    assert_true(strpos($en, 'You will find suitable gifts at Orell Füssli.') !== false, 'English banner');
    assert_true(strpos($en, 'Gift ideas at Orell Füssli') !== false, 'English button');
    assert_true(strpos($en, 'View at Orell Füssli') !== false, 'English product link');
    assert_true(strpos($en, 'The links to Orell Füssli are ad links.') !== false, 'English note');
    assert_true(strpos($en, ideas2_href(affiliate_of_search_target('Ausmalbuch'))) !== false, 'English page keeps the German shop search');
    assert_true(strpos($en, 'Scented candle') !== false, 'English candle');
    assert_true(strpos($en, 'If nothing fits, a gift card is the easy fallback.') !== false, 'English gift card');
    assert_true(strpos($en, 'name="robots" content="noindex, nofollow"') !== false, 'English draft stays noindex');
    assert_equals(ideas2_awin_hrefs($de), ideas2_awin_hrefs($en), 'English page uses the same shop links');
});
