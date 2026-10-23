<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/functions.php';

function seo_render_public($script, $lang) {
    $get = $_GET;
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : null;
    $previous = function_exists('i18n_current') ? i18n_current() : 'de';
    $_GET = array('lang' => $lang);
    $_SERVER['SCRIPT_NAME'] = '/' . basename($script);
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['HTTP_HOST'] = 'xn--wichtl-gua.ch';
    $_SERVER['REQUEST_URI'] = '/' . basename($script, '.php');
    ob_start();
    try {
        include $script;
        return ob_get_clean();
    } catch (Exception $e) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        throw $e;
    } finally {
        $_GET = $get;
        if ($script_name === null) {
            unset($_SERVER['SCRIPT_NAME']);
        } else {
            $_SERVER['SCRIPT_NAME'] = $script_name;
        }
        i18n_activate($previous);
    }
}

run_test('sitemap lists every language version with hreflang', function () {
    $sitemap = file_get_contents(dirname(__DIR__) . '/public/sitemap.xml');
    preg_match_all('/<loc>([^<]+)<\/loc>/', $sitemap, $locs);
    assert_equals(36, count($locs[1]), '11 existing pages plus the generator, in three languages');
    assert_equals(36, count(array_unique($locs[1])), 'locations are unique');
    assert_equals(144, substr_count($sitemap, 'hreflang='), 'each URL carries four alternates');
    assert_true(strpos($sitemap, 'https://xn--wichtl-gua.ch/wichtel-generator') !== false, 'German generator URL');
    assert_true(strpos($sitemap, 'https://xn--wichtl-gua.ch/fr/wichtel-generator') !== false, 'French generator URL');
    assert_true(strpos($sitemap, 'https://xn--wichtl-gua.ch/en/wichtel-generator') !== false, 'English generator URL');
    assert_true(strpos($sitemap, 'participant') === false, 'token pages stay out');
    assert_true(strpos($sitemap, '/api/') === false, 'API stays out');
    assert_true(strpos($sitemap, '.php') === false, 'no php URLs');
    assert_true(strpos($sitemap, 'admin-link') === false, 'admin-link stays out');
    $sample = strpos($sitemap, '<loc>https://xn--wichtl-gua.ch/fr/faq</loc>');
    assert_true($sample !== false, 'French FAQ is its own URL');
    $chunk = substr($sitemap, $sample, 700);
    assert_true(strpos($chunk, 'hreflang="de-CH"') !== false, 'French FAQ points at German');
    assert_true(strpos($chunk, 'hreflang="fr-CH"') !== false, 'French FAQ points at French');
    assert_true(strpos($chunk, 'hreflang="en"') !== false, 'French FAQ points at English');
    assert_true(strpos($chunk, 'hreflang="x-default"') !== false, 'French FAQ points at x-default');
});

run_test('generator page is a clean public URL in every language', function () {
    $base = 'https://xn--wichtl-gua.ch';
    assert_equals($base . '/wichtel-generator', seo_legacy_redirect_target('GET', 'xn--wichtl-gua.ch', '/wichtel-generator.php'));
    assert_equals($base . '/fr/wichtel-generator', seo_legacy_redirect_target('GET', 'xn--wichtl-gua.ch', '/fr/wichtel-generator.php'));
    assert_equals($base . '/en/wichtel-generator?x=1', seo_legacy_redirect_target('GET', 'www.wichtlä.ch', '/en/wichtel-generator.php?x=1'));
    assert_equals(null, seo_legacy_redirect_target('GET', 'xn--wichtl-gua.ch', '/wichtel-generator'));
    assert_equals(null, seo_legacy_redirect_target('POST', 'xn--wichtl-gua.ch', '/wichtel-generator.php'));
    $internal = array(
        'REQUEST_METHOD' => 'GET',
        'HTTP_HOST' => 'xn--wichtl-gua.ch',
        'REQUEST_URI' => '/wichtel-generator',
        'SCRIPT_NAME' => '/wichtel-generator.php',
    );
    assert_equals(null, seo_legacy_redirect_from_server($internal), 'internal rewrite does not loop');

    assert_true(in_array('wichtel-generator', i18n_public_slugs(), true), 'hreflang slug');
    assert_true(in_array('wichtel-generator', public_clean_slugs(), true), 'clean slug');
    $nav = file_get_contents(dirname(__DIR__) . '/includes/templates/navigation.php');
    $footer = file_get_contents(dirname(__DIR__) . '/includes/templates/footer.php');
    assert_true(strpos($nav, 'wichtel-generator') === false, 'main menu does not link the generator');
    assert_true(strpos($footer, "i18n_href('wichtel-generator')") !== false, 'footer links the generator');

    $pages = array(
        'de' => array(
            'https://xn--wichtl-gua.ch/wichtel-generator',
            'Wichtel-Generator: Namen online auslosen | wichtlä.ch',
            'Wichtel-Generator: Namen online auslosen',
            '/create_group',
        ),
        'fr' => array(
            'https://xn--wichtl-gua.ch/fr/wichtel-generator',
            'Générateur Secret Santa : tirer les noms | wichtlä.ch',
            'Générateur de tirage au sort',
            '/fr/create_group',
        ),
        'en' => array(
            'https://xn--wichtl-gua.ch/en/wichtel-generator',
            'Secret Santa generator: draw names online | wichtlä.ch',
            'Secret Santa generator: draw names online',
            '/en/create_group',
        ),
    );
    foreach ($pages as $lang => $expect) {
        $html = seo_render_public(dirname(__DIR__) . '/public/wichtel-generator.php', $lang);
        assert_true(strpos($html, '<title>' . $expect[1] . '</title>') !== false, $lang . ' title');
        assert_true(mb_strlen($expect[1], 'UTF-8') <= 65, $lang . ' title length');
        assert_true(strpos($html, 'rel="canonical" href="' . $expect[0] . '"') !== false, $lang . ' canonical');
        assert_true(strpos($html, 'hreflang="de-CH"') !== false, $lang . ' de alternate');
        assert_true(strpos($html, 'hreflang="fr-CH"') !== false, $lang . ' fr alternate');
        assert_true(strpos($html, 'hreflang="en"') !== false, $lang . ' en alternate');
        assert_true(strpos($html, 'hreflang="x-default"') !== false, $lang . ' default alternate');
        assert_true(strpos($html, 'property="og:image"') !== false, $lang . ' og image');
        assert_true(strpos($html, 'https://xn--wichtl-gua.ch/img/og-image.png') !== false, $lang . ' absolute og image');
        assert_true(strpos($html, 'name="twitter:card" content="summary_large_image"') !== false, $lang . ' twitter card');
        assert_true(strpos($html, $expect[2]) !== false, $lang . ' heading');
        assert_true(strpos($html, 'FAQPage') !== false, $lang . ' faq schema');
        assert_true(strpos($html, 'href="' . $expect[3] . '"') !== false, $lang . ' create link');
        assert_true(strpos($html, 'ß') === false, $lang . ' page uses ss');
        if (preg_match('/<meta name="description" content="([^"]*)"/', $html, $match) !== 1) {
            throw new Exception($lang . ' description missing');
        }
        $length = mb_strlen(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'), 'UTF-8');
        assert_true($length >= 140 && $length <= 155, $lang . ' description length ' . $length);
    }
});

run_test('public meta copy stays in range and drops the repeated premium line', function () {
    $keys = array(
        'meta.index.description',
        'meta.index.description.with_premium',
        'meta.faq.description',
        'meta.what.description',
        'meta.ideas.description',
        'meta.company.description',
        'meta.about.description',
        'meta.about.description.with_premium',
        'meta.support.description',
        'meta.support.description.with_premium',
        'meta.privacy.description',
        'meta.imprint.description',
        'meta.create.description',
        'meta.create.description.with_premium',
        'meta.adminlink.description',
        'meta.premium.description',
        'meta.generator.description',
        'meta.notfound.description',
    );
    $banned = array(
        'Extra-Funktionen für Firmen und Vereine sind optional',
        'Des fonctions en plus pour entreprises et associations sont en option',
        'Extra features for companies and clubs are optional',
    );
    $titles = array(
        'meta.about.title',
        'meta.support.title',
        'meta.privacy.title',
        'meta.imprint.title',
        'meta.premium.title',
        'meta.ideas.title',
        'meta.generator.title',
        'meta.adminlink.title',
    );
    foreach (array('de', 'fr', 'en') as $lang) {
        i18n_activate($lang);
        foreach ($keys as $key) {
            $text = i18n_lookup($key, $lang);
            assert_true(is_string($text), $lang . ' ' . $key);
            $length = mb_strlen($text, 'UTF-8');
            assert_true($length >= 140 && $length <= 155, $lang . ' ' . $key . ' is ' . $length);
            assert_true(strpos($text, 'ß') === false, $lang . ' ' . $key . ' uses ss');
            foreach ($banned as $phrase) {
                assert_true(strpos($text, $phrase) === false, $lang . ' ' . $key . ' repeats the optional line');
            }
        }
        foreach ($titles as $key) {
            $title = t($key);
            assert_true(mb_substr($title, -13, null, 'UTF-8') === ' | wichtlä.ch', $lang . ' ' . $key . ' suffix');
            assert_true(mb_strlen($title, 'UTF-8') <= 65, $lang . ' ' . $key . ' length');
        }
    }
    i18n_activate('de');
});

run_test('social image, fonts, cache and the custom 404 stay in place', function () {
    $head = file_get_contents(dirname(__DIR__) . '/includes/templates/head.php');
    assert_true(strpos($head, 'fonts.googleapis.com') === false, 'public head does not call Google Fonts');
    assert_true(strpos($head, 'public_asset_href(\'/css/styles.css\')') !== false, 'stylesheet is versioned');
    assert_true(strpos($head, 'public_asset_href(\'/js/main.js\')') !== false, 'script is versioned');
    assert_true(strpos($head, 'defer') !== false, 'main.js is deferred');
    assert_true(strpos($head, 'og:image:width') !== false, 'og width');
    assert_true(strpos($head, 'og:site_name') !== false, 'og site name');
    assert_true(strpos($head, 'summary_large_image') !== false, 'twitter card');
    assert_true(strpos($head, '/favicon.ico') !== false, 'favicon link');

    $css = file_get_contents(dirname(__DIR__) . '/public/css/styles.css');
    assert_true(strpos($css, "font-display: swap") !== false, 'font-display swap');
    assert_true(strpos($css, '/fonts/roboto-latin.woff2') !== false, 'roboto is local');
    assert_true(strpos($css, '/fonts/playfair-display-700-latin.woff2') !== false, 'playfair is local');
    assert_true(strpos($css, 'font-weight: 300 700') !== false, 'only the used roboto range');
    assert_true(strpos($css, '.faq-icon::after') !== false && strpos($css, '.faq-toggle::after') !== false, 'faq markers are css');
    assert_true(is_file(dirname(__DIR__) . '/public/fonts/roboto-latin.woff2'), 'roboto file');
    assert_true(is_file(dirname(__DIR__) . '/public/fonts/playfair-display-700-latin.woff2'), 'playfair file');
    assert_true(is_file(dirname(__DIR__) . '/public/img/og-image.png'), 'og image');
    assert_true(is_file(dirname(__DIR__) . '/public/favicon.ico'), 'favicon file');
    $logo_png = filesize(dirname(__DIR__) . '/public/images/logo.png');
    $logo_webp = filesize(dirname(__DIR__) . '/public/images/logo.webp');
    assert_true($logo_webp > 0 && $logo_webp < $logo_png, 'webp logo is smaller');

    $info = getimagesize(dirname(__DIR__) . '/public/img/og-image.png');
    assert_equals(1200, $info[0], 'og width');
    assert_equals(630, $info[1], 'og height');

    $policy = baseline_content_security_policy();
    assert_true(strpos($policy, 'fonts.googleapis.com') === false, 'csp drops google styles');
    assert_true(strpos($policy, 'fonts.gstatic.com') === false, 'csp drops google fonts');

    $htaccess = file_get_contents(dirname(__DIR__) . '/public/.htaccess');
    assert_true(strpos($htaccess, 'ErrorDocument 404 /404.php') !== false, 'custom 404');
    assert_true(strpos($htaccess, 'Header set Cache-Control "public, max-age=2592000"') !== false, 'cache control');
    assert_true(strpos($htaccess, '<IfModule mod_expires.c>') !== false, 'expires module is guarded');
    assert_equals(4, substr_count($htaccess, 'RewriteCond %{THE_REQUEST} \s/[^\s?]*\.php[\s?]'), 'php redirects still use the original request');
    assert_true(strpos($htaccess, 'wichtel-generator') !== false, 'generator is in the rewrite list');

    $href = public_asset_href('/css/styles.css');
    assert_true(strpos($href, '/css/styles.css?v=') === 0, 'version query');

    $faq = file_get_contents(dirname(__DIR__) . '/content/de/faq.php');
    assert_true(strpos($faq, 'Häufig gestellte Fragen ?') === false, 'faq heading has no loose question mark');
    assert_true(strpos($faq, 'Häufig gestellte Fragen') !== false, 'faq heading remains');
    assert_true(strpos($faq, '>▼<') === false && strpos($faq, '>+</span>') === false, 'markers left the heading text');
    assert_true(strpos($faq, 'Wir ') === false && strpos($faq, ' wir ') === false, 'faq speaks in the first person');

    $runner = tempnam(sys_get_temp_dir(), 'wichtla404');
    file_put_contents($runner, "<?php\nob_start();\n\$_GET['lang'] = 'de';\n\$_SERVER['REQUEST_METHOD'] = 'GET';\n\$_SERVER['HTTP_HOST'] = 'xn--wichtl-gua.ch';\n\$_SERVER['REQUEST_URI'] = '/fehlt';\n\$_SERVER['SCRIPT_NAME'] = '/404.php';\nrequire " . var_export(dirname(__DIR__) . '/public/404.php', true) . ";\n\$html = ob_get_clean();\necho http_response_code() . \"\\n\" . \$html;\n");
    $rendered = shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($runner));
    unlink($runner);
    $split = explode("\n", (string) $rendered, 2);
    assert_equals('404', $split[0], '404 status is real');
    $html = isset($split[1]) ? $split[1] : '';
    assert_true(strpos($html, 'noindex') !== false, '404 is noindex');
    assert_true(strpos($html, 'href="/wichtel-ideen"') !== false, '404 links ideas');
    assert_true(strpos($html, 'href="/faq"') !== false, '404 links faq');
    assert_true(strpos($html, 'href="/"') !== false, '404 links home');
    assert_true(strpos($html, 'Seite nicht gefunden') !== false, '404 heading');
});
