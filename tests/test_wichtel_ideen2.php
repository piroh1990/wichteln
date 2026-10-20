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
    assert_true(strpos($html, 'Hochwertige Thermotasse') !== false, 'the ideas themselves stay');
    assert_true(strpos($html, 'href="/wichtel-ideen"') !== false, 'menu still points at the live page');
    assert_true(strpos($html, 'href="/wichtel-ideen2"') === false, 'nothing links the draft as a normal page');
    assert_true(strpos($html, 'href="/wichtel-ideen2?setlang=de"') !== false, 'language switch stays on the draft');
    assert_true(strpos($html, 'href="/fr/wichtel-ideen2?setlang=fr"') !== false, 'French switch stays on the draft');
    assert_true(strpos($html, 'href="/en/wichtel-ideen2?setlang=en"') !== false, 'English switch stays on the draft');
});

run_test('product links use Awin search terms and skip food, diy and experiences', function () {
    $html = ideas2_test_render('de', true);
    $linked = array(
        'Hochwertige Thermotasse' => 'Thermobecher',
        'Schönes Notizbuch' => 'Notizbuch',
        'Lustiges Kartenspiel' => 'Kartenspiel',
        'Malbuch für Erwachsene' => 'Ausmalbuch',
        '3D-Puzzle' => '3D Puzzle',
        'Polaroid-Fotoalbum' => 'Fotoalbum',
        '„Wie werde ich reich"-Bücher' => 'witziges Buch',
    );
    $plain = array(
        'Lustige Socken',
        'Duftkerzen-Set',
        'Schweizer Schokolade',
        'Mini-Pflanze',
        'Witzige Büro-Deko',
        'Witziges T-Shirt',
        'Powerbank',
        'Handy-Gadgets',
        'LED-Lichterkette',
        'Badebomben-Set',
        'Handcreme-Set',
        'Tee-Set Premium',
        'Gewürz-Set',
        'Schweizer Honig',
        'Barista-Kaffee',
        'Einhorn-Produkte',
        'Nutzlose Gadgets',
    );
    foreach ($linked as $title => $term) {
        assert_true(ideas2_card_has_link($html, $title), $title . ' links to Orell Füssli');
        assert_true(strpos($html, ideas2_href(affiliate_of_search_target($term))) !== false, $title . ' searches for ' . $term);
    }
    foreach ($plain as $title) {
        assert_true(!ideas2_card_has_link($html, $title), $title . ' stays without a shop link');
    }

    assert_true(strpos($html, ideas2_href(affiliate_of_search_target('Kugelschreiber'))) !== false, 'pens search for Kugelschreiber');
    assert_true(strpos($html, ideas2_href(affiliate_of_search_target('Gesellschaftsspiel'))) !== false, 'board games search for Gesellschaftsspiel');
    assert_true(strpos($html, ideas2_href(affiliate_of_search_target('Wandkalender'))) !== false, 'calendars search for Wandkalender');
    assert_true(strpos($html, ideas2_href(affiliate_of_gift_url())) !== false, 'banner uses the gift category');
    assert_true(strpos($html, 'clickref') === false, 'draft links do not add a clickref');
    assert_equals(11, substr_count($html, 'awin1.com'), 'banner, seven cards and three list ideas');
    assert_equals(11, substr_count($html, 'rel="sponsored noopener"'), 'every shop link is sponsored');
    assert_equals(11, substr_count($html, 'target="_blank"'), 'every shop link opens in a new tab');
    assert_true(strpos($html, 'Passende Geschenke findest du bei Orell Füssli.') !== false, 'banner copy');
    assert_true(strpos($html, 'Geschenkideen bei Orell Füssli') !== false, 'red button label');
    assert_true(strpos($html, 'Bei Orell Füssli ansehen') !== false, 'product link label');
    assert_true(strpos($html, 'Die Links zu Orell Füssli sind Werbelinks.') !== false, 'note under the ideas');

    $note = strpos($html, '<p class="affiliate-note of-draft-note">');
    $grid = strpos($html, 'ideas-grid');
    assert_true($note !== false && $grid !== false && $note > $grid, 'the note sits below the idea boxes');
    $article_end = strpos($html, '</article>');
    assert_true($article_end !== false && $note > $article_end, 'the note is outside the article');

    $diy = strpos($html, 'DIY-Wichtelgeschenke');
    $groups = strpos($html, 'Wichtelgeschenke für verschiedene Gruppen');
    assert_true(strpos(substr($html, $diy, $groups - $diy), 'awin1.com') === false, 'homemade ideas stay without links');
    $budget = strpos($html, 'Geschenke nach Budget');
    assert_true(strpos(substr($html, $budget, $diy - $budget), 'awin1.com') === false, 'mixed budget lines stay without links');
    assert_true(strpos($html, 'Erlebnis-Gutscheine (Kino, Essen, Events)</li>') !== false, 'experience ideas are not wrapped in a shop link');
});

run_test('french and english drafts translate the affiliate copy', function () {
    $fr = ideas2_test_render('fr', true);
    assert_true(strpos($fr, 'Tu trouves des cadeaux adaptés chez Orell Füssli.') !== false, 'French banner');
    assert_true(strpos($fr, 'Idées cadeaux chez Orell Füssli') !== false, 'French button');
    assert_true(strpos($fr, 'Voir chez Orell Füssli') !== false, 'French product link');
    assert_true(strpos($fr, 'Les liens vers Orell Füssli sont des liens publicitaires.') !== false, 'French note');
    assert_true(strpos($fr, ideas2_href(affiliate_of_search_target('Kartenspiel'))) !== false, 'French page keeps the German shop search');
    assert_equals(11, substr_count($fr, 'rel="sponsored noopener"'), 'French page has the same links');
    assert_true(strpos($fr, 'href="/fr/wichtel-ideen"') !== false, 'French menu still points at the live page');

    $en = ideas2_test_render('en', true);
    assert_true(strpos($en, 'You will find suitable gifts at Orell Füssli.') !== false, 'English banner');
    assert_true(strpos($en, 'Gift ideas at Orell Füssli') !== false, 'English button');
    assert_true(strpos($en, 'View at Orell Füssli') !== false, 'English product link');
    assert_true(strpos($en, 'The links to Orell Füssli are ad links.') !== false, 'English note');
    assert_true(strpos($en, ideas2_href(affiliate_of_search_target('Ausmalbuch'))) !== false, 'English page keeps the German shop search');
    assert_true(strpos($en, 'name="robots" content="noindex, nofollow"') !== false, 'English draft stays noindex');
});
