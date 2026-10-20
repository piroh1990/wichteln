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
    $previous_lang = function_exists('i18n_current') ? i18n_current() : 'de';
    $GLOBALS['affiliate_of_enabled_override'] = $enabled;
    $_GET = array('lang' => $lang);
    $_SERVER['SCRIPT_NAME'] = '/wichtel-ideen.php';
    ob_start();
    try {
        include __DIR__ . '/../public/wichtel-ideen.php';
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
        if (function_exists('i18n_activate')) {
            i18n_activate($previous_lang);
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

run_test('ideas page is indexed and the old address only redirects', function () {
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
        'includes/ideas_page.php',
        'includes/ideas_kept.php',
    ) as $relative) {
        $src = file_get_contents($root . '/' . $relative);
        assert_true(strpos($src, 'wichtel-ideen2') === false, $relative . ' does not keep the draft address');
        assert_true(strpos($src, 'ß') === false, $relative . ' uses Swiss spelling');
    }
    assert_true(!is_file($root . '/content/de/wichtel-ideen2.php'), 'German draft content is gone');
    assert_true(!is_file($root . '/content/fr/wichtel-ideen2.php'), 'French draft content is gone');
    assert_true(!is_file($root . '/content/en/wichtel-ideen2.php'), 'English draft content is gone');

    $sitemap = file_get_contents($root . '/public/sitemap.xml');
    assert_true(strpos($sitemap, 'https://xn--wichtl-gua.ch/wichtel-ideen') !== false, 'sitemap keeps the live ideas URL');
    assert_true(strpos($sitemap, 'wichtel-ideen2') === false, 'sitemap has no draft URL');

    $htaccess = file_get_contents($root . '/public/.htaccess');
    assert_true(strpos($htaccess, 'RewriteRule ^(en|fr)/wichtel-ideen2/?$ /$1/wichtel-ideen [R=301,L,QSA]') !== false, 'language draft redirects');
    assert_true(strpos($htaccess, 'RewriteRule ^wichtel-ideen2/?$ /wichtel-ideen [R=301,L,QSA]') !== false, 'German draft redirects');
    assert_true(strpos($htaccess, 'wichtel-ideen2.php') === false, 'draft is not a page rewrite');
    assert_true(strpos($htaccess, 'datenschutz|wichtel-ideen|firmenwichteln-tipps') !== false, 'the live slug stays in the existing rules');

    $redirect = file_get_contents($root . '/public/wichtel-ideen2.php');
    assert_true(strpos($redirect, "header('Location: ' . ideas_draft_redirect_target(\$_GET), true, 301);") !== false, 'direct php file redirects');
    assert_true(strpos($redirect, 'noindex') === false, 'redirect does not send noindex');
    assert_equals('/wichtel-ideen', ideas_draft_redirect_target(array()), 'German target');
    assert_equals('/fr/wichtel-ideen', ideas_draft_redirect_target(array('lang' => 'fr')), 'French target');
    assert_equals('/en/wichtel-ideen', ideas_draft_redirect_target(array('lang' => 'en')), 'English target');
    assert_equals('/wichtel-ideen?setlang=de', ideas_draft_redirect_target(array('lang' => 'de', 'setlang' => 'de')), 'unknown lang drops and query stays');

    $page = file_get_contents($root . '/public/wichtel-ideen.php');
    assert_true(strpos($page, 'noindex') === false, 'live page is indexable');
    assert_true(strpos($page, "public_page_open('wichtel-ideen', 'meta.ideas.title', 'meta.ideas.description', 'article')") !== false, 'live page keeps title, description and canonical slug');
});

run_test('affiliate links stay hidden while the feature is off', function () {
    $html = ideas2_test_render('de', false);
    assert_true(strpos($html, 'noindex') === false, 'live page has no noindex');
    assert_true(strpos($html, 'rel="canonical" href="https://xn--wichtl-gua.ch/wichtel-ideen"') !== false, 'canonical points at the ideas page');
    assert_true(strpos($html, 'rel="alternate" hreflang="de-CH" href="https://xn--wichtl-gua.ch/wichtel-ideen"') !== false, 'German alternate');
    assert_true(strpos($html, 'rel="alternate" hreflang="fr-CH" href="https://xn--wichtl-gua.ch/fr/wichtel-ideen"') !== false, 'French alternate');
    assert_true(strpos($html, 'rel="alternate" hreflang="en" href="https://xn--wichtl-gua.ch/en/wichtel-ideen"') !== false, 'English alternate');
    assert_true(strpos($html, 'rel="alternate" hreflang="x-default" href="https://xn--wichtl-gua.ch/wichtel-ideen"') !== false, 'default alternate');
    assert_true(strpos($html, '<title>Wichtelgeschenke: Ideen nach Budget | wichtlä.ch</title>') !== false, 'title matches the page');
    assert_true(strpos($html, 'content="Wichtelgeschenk-Ideen für die Schweiz, grob nach Budget sortiert. Kurze Tipps für Familie, Freunde und Teams, ohne dass du ein Konto brauchst."') !== false, 'description matches the page');
    assert_true(strpos($html, '"url":"https://xn--wichtl-gua.ch/wichtel-ideen"') !== false, 'structured data uses the live URL');
    assert_true(strpos($html, 'wichtel-ideen2') === false, 'rendered page does not mention the draft');
    assert_true(strpos($html, 'awin1.com') === false, 'no Awin URL');
    assert_true(strpos($html, '<aside class="of-draft-banner"') === false, 'no banner');
    assert_true(strpos($html, '<p class="affiliate-note of-draft-note">') === false, 'no advertising note');
    assert_true(strpos($html, 'Bei Orell Füssli ansehen') === false, 'no product link text');
    assert_true(strpos($html, 'Geschenkideen bei Orell Füssli') === false, 'no shop button');
    assert_true(strpos($html, 'Kugelschreiber') !== false, 'the ideas themselves stay');
    assert_true(strpos($html, 'Bis CHF 10') !== false, 'budget bands stay without the shop');
    assert_true(strpos($html, 'Geschenkkarte') === false, 'no gift-card hint while the feature is off');
    assert_true(strpos($html, 'Tipps für das perfekte Wichtelgeschenk') !== false, 'the old tips stay');
    assert_true(strpos($html, 'Wichtelgeschenke für verschiedene Gruppen') !== false, 'the old group notes stay');
    assert_true(strpos($html, 'href="/wichtel-ideen"') !== false, 'menu still points at the live page');
    assert_true(strpos($html, 'href="/wichtel-ideen?setlang=de"') !== false, 'language switch stays on the ideas page');
    assert_true(strpos($html, 'href="/fr/wichtel-ideen?setlang=fr"') !== false, 'French switch stays on the ideas page');
    assert_true(strpos($html, 'href="/en/wichtel-ideen?setlang=en"') !== false, 'English switch stays on the ideas page');
});

function ideas2_expected() {
    return array(
        array('10', 'Kugelschreiber', 'Kugelschreiber', 'buero'),
        array('10', 'Lesezeichen', 'Lesezeichen', 'lesen'),
        array('10', 'Handcreme', 'Handcreme', 'wellness'),
        array('10', 'Badebombe', 'Badebombe', 'wellness'),
        array('20', 'Notizbuch', 'Notizbuch', 'buero'),
        array('20', 'Leselampe', 'Leselampe', 'lesen'),
        array('20', 'Tischkalender 2027', 'Tischkalender 2027', 'kalender'),
        array('20', 'Socken', 'Socken', 'wellness'),
        array('20', 'Lichterkette', 'Lichterkette', 'wellness'),
        array('20', 'Schlüsselanhänger', 'Schlüsselanhänger', 'alltag'),
        array('20', 'Trinkflasche', 'Trinkflasche', 'alltag'),
        array('20', 'Tee-Adventskalender', 'Tee Adventskalender', 'kalender'),
        array('20', 'Skyjo', 'Skyjo', 'spiele'),
        array('20', 'Flip 7', 'Flip 7', 'spiele'),
        array('20', 'Qwixx', 'Qwixx', 'spiele'),
        array('20', 'Quizspiel', 'Quiz Spiel', 'spiele'),
        array('20', 'Witzebuch', 'Witzebuch', 'lesen'),
        array('20', 'Gesprächskarten', 'DeepTalk', 'spiele'),
        array('20', 'Puzzle mit 1000 Teilen', 'Puzzle 1000', 'spiele'),
        array('20', 'Bilderrahmen', 'Bilderrahmen', 'kreativ'),
        array('20', 'Wandkalender 2027', 'Wandkalender 2027', 'kalender'),
        array('30', 'Tasse', 'Tasse', 'alltag'),
        array('30', 'Powerbank', 'Powerbank', 'alltag'),
        array('30', 'Duftkerze', 'Duftkerze', 'wellness'),
        array('30', 'Hitster', 'Hitster', 'spiele'),
        array('30', 'Partyspiel', 'Dingsbums', 'spiele'),
        array('30', 'Escape Dinner', 'Escape Dinner', 'spiele'),
        array('30', 'Murdoku', 'Murdoku', 'spiele'),
        array('30', '3D-Puzzle', '3D Puzzle', 'kreativ'),
        array('30', 'Ausmalbuch', 'Ausmalbuch', 'kreativ'),
        array('30', 'Bastelset', 'Bastelset', 'kreativ'),
        array('30', 'Kreativset', 'Kreativset', 'kreativ'),
        array('30', 'LEGO Botanicals', 'LEGO Botanicals', 'kreativ'),
        array('30', 'Scrapbooking-Set', 'Scrapbooking-Set', 'kreativ'),
        array('30', 'LEGO-Adventskalender', 'LEGO Adventskalender', 'kalender'),
        array('50', 'Thermobecher', 'Thermobecher', 'alltag'),
        array('50', 'Dorfromantik', 'Dorfromantik', 'spiele'),
        array('50', 'EXIT-Adventskalender', 'EXIT Adventskalender', 'kalender'),
        array('50', 'Fotoalbum', 'Fotoalbum', 'kreativ'),
        array('50', 'Kochbuch', 'Kochbuch', 'lesen'),
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
        $open_at = strrpos(substr($html, 0, $pos), '<div class="idea-card"');
        assert_true($open_at !== false, $title . ' has a card');
        $open = substr($html, $open_at, $pos - $open_at);
        assert_true(strpos($open, 'data-bereich="' . $row[3] . '"') !== false, $title . ' area ' . $row[3]);
        assert_true(strpos($open, 'data-preis="' . $row[0] . '"') !== false, $title . ' keeps its budget');
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
    $kept_at = strpos($article, 'class="ideas-kept"');
    $cta = strpos($article, 'cta-section');
    assert_true($kept_at !== false && $cta !== false && $kept_at < $cta, 'notes sit below the ideas and above the call to action');
    $catalog = substr($article, 0, $kept_at);
    $kept = substr($article, $kept_at, $cta - $kept_at);
    assert_true(preg_match('/\d+\.\d{2}/', $catalog) !== 1, 'no exact prices in the ideas');
    assert_true(strpos($catalog, 'awin1.com') !== false, 'shop links live with the ideas');
    assert_true(strpos($kept, 'awin1.com') === false, 'kept notes have no shop link');
    assert_true(strpos($kept, '<a ') === false, 'kept notes stay plain text');
    foreach (array(
        'Honig', 'Kuscheldecke', 'Sukkulente', 'Mini-Pflanze', 'T-Shirt', 'Einhorn',
    ) as $banned) {
        assert_true(strpos($article, $banned) === false, 'removed product stays off the page: ' . $banned);
    }
    foreach (array(
        'Schokolade', 'Gewürz', 'Kaffee', 'DIY', 'Selbstgemacht', 'Erlebnis', 'Insider', 'Tee-Set', 'Gutschein',
    ) as $kept_word) {
        assert_true(strpos($catalog, $kept_word) === false, $kept_word . ' is not a shop card');
        assert_true(strpos($kept, $kept_word) !== false, $kept_word . ' stays in the old notes');
    }
    assert_true(strpos($kept, 'Tipps für das perfekte Wichtelgeschenk') !== false, 'tips heading');
    assert_true(strpos($kept, 'Wichtelgeschenke für verschiedene Gruppen') !== false, 'group notes heading');
    foreach (array(
        'Spiele & Rätsel',
        'Gemütlich & Wellness',
        'Büro & Papeterie',
        'Kreativ & Basteln',
        'Lesen & Bücher',
        'Alltag & Gadgets',
        'Kalender & Adventskalender',
    ) as $category) {
        assert_true(strpos($catalog, htmlspecialchars($category, ENT_QUOTES, 'UTF-8')) !== false, 'category ' . $category);
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
    assert_true(strpos($fr, 'rel="canonical" href="https://xn--wichtl-gua.ch/fr/wichtel-ideen"') !== false, 'French canonical');
    assert_true(strpos($fr, 'Conseils pour le cadeau Secret Santa parfait') !== false, 'French tips');
    assert_true(strpos($fr, 'Des cadeaux Secret Santa selon le groupe') !== false, 'French group notes');
    assert_true(strpos($fr, 'wichtel-ideen2') === false, 'French page does not mention the draft');
    assert_true(strpos($fr, 'noindex') === false, 'French page is indexable');

    $en = ideas2_test_render('en', true);
    assert_true(strpos($en, 'You will find suitable gifts at Orell Füssli.') !== false, 'English banner');
    assert_true(strpos($en, 'Gift ideas at Orell Füssli') !== false, 'English button');
    assert_true(strpos($en, 'View at Orell Füssli') !== false, 'English product link');
    assert_true(strpos($en, 'The links to Orell Füssli are ad links.') !== false, 'English note');
    assert_true(strpos($en, ideas2_href(affiliate_of_search_target('Ausmalbuch'))) !== false, 'English page keeps the German shop search');
    assert_true(strpos($en, 'Scented candle') !== false, 'English candle');
    assert_true(strpos($en, 'If nothing fits, a gift card is the easy fallback.') !== false, 'English gift card');
    assert_true(strpos($en, 'noindex') === false, 'English page is indexable');
    assert_true(strpos($en, 'rel="canonical" href="https://xn--wichtl-gua.ch/en/wichtel-ideen"') !== false, 'English canonical');
    assert_true(strpos($en, 'Tips for the perfect Secret Santa gift') !== false, 'English tips');
    assert_true(strpos($en, 'Secret Santa gifts for different groups') !== false, 'English group notes');
    assert_true(strpos($en, '<title>Secret Santa gift ideas by budget | wichtlä.ch</title>') !== false, 'English title');
    assert_equals(ideas2_awin_hrefs($de), ideas2_awin_hrefs($en), 'English page uses the same shop links');
});

run_test('idea filters stay in the markup and do not hide ideas without javascript', function () {
    $root = dirname(__DIR__);
    $source = file_get_contents($root . '/includes/ideas_page.php');
    assert_true(strpos($source, 'onclick=') === false, 'filter uses no inline handler');
    $js = file_get_contents($root . '/public/js/ideas-filter.js');
    assert_true(strpos($js, "addEventListener('click'") !== false, 'clicks are bound in the script');
    assert_true(strpos($js, 'eval(') === false, 'filter script does not eval');

    $html = ideas2_test_render('de', false);
    assert_true(strpos($html, 'src="/js/ideas-filter.js"') !== false, 'external filter script');
    assert_true(strpos($html, 'role="radiogroup"') !== false, 'chips are a radio group');
    assert_equals(2, substr_count($html, 'role="radiogroup"'), 'area and price are separate groups');
    assert_true(strpos($html, '>Bereich</span>') !== false, 'German area label');
    assert_true(strpos($html, '>Preis</span>') !== false, 'German price label');
    assert_true(strpos($html, '>bis CHF 10</button>') !== false, 'price chip does not reuse the heading capitalisation');
    assert_true(strpos($html, '40 Ideen') !== false, 'count shows every idea');
    assert_true(strpos($html, 'data-ideas-empty hidden') !== false, 'empty message is hidden until a filter matches nothing');
    assert_true(strpos($html, 'Filter zurücksetzen') !== false, 'reset link');
    assert_equals(count(ideas2_expected()), substr_count($html, 'class="idea-card"'), 'every idea is in the html');
    assert_equals(4, substr_count($html, 'class="content-section ideas-band"'), 'budget groups stay in the html');

    $fr = ideas2_test_render('fr', false);
    assert_true(strpos($fr, '>Catégorie</span>') !== false, 'French area label');
    assert_true(strpos($fr, '>Prix</span>') !== false, 'French price label');
    assert_true(strpos($fr, htmlspecialchars("jusqu'à 10 CHF", ENT_QUOTES, 'UTF-8')) !== false, 'French price chip');
    assert_true(strpos($fr, 'Jeux et énigmes') !== false, 'French games area');
    assert_true(strpos($fr, 'Calendriers et Avent') !== false, 'French calendar area');
    assert_true(strpos($fr, '40 idées') !== false, 'French count');
    assert_true(strpos($fr, 'Aucune idée ne correspond à cette sélection.') !== false, 'French empty message');
    assert_true(strpos($fr, 'Réinitialiser les filtres') !== false, 'French reset');
    assert_true(strpos($fr, 'Spiele') === false, 'French page does not show German area names');

    $en = ideas2_test_render('en', false);
    assert_true(strpos($en, '>Category</span>') !== false, 'English area label');
    assert_true(strpos($en, '>Price</span>') !== false, 'English price label');
    assert_true(strpos($en, '>up to CHF 10</button>') !== false, 'English price chip');
    assert_true(strpos($en, htmlspecialchars('Games & puzzles', ENT_QUOTES, 'UTF-8')) !== false, 'English games area');
    assert_true(strpos($en, htmlspecialchars('Cosy & wellness', ENT_QUOTES, 'UTF-8')) !== false, 'English wellness area');
    assert_true(strpos($en, '40 ideas') !== false, 'English count');
    assert_true(strpos($en, 'Nothing matches this selection.') !== false, 'English empty message');
    assert_true(strpos($en, 'Reset filters') !== false, 'English reset');
});
