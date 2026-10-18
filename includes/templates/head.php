<?php

if (!function_exists('canonical_base_url')) {
    require_once dirname(__DIR__) . '/urls.php';
}

function render_head($options = array()) {
    if (!is_array($options)) {
        $options = array();
    }
    $title = isset($options['title']) ? (string) $options['title'] : 'Wichtlä.ch';
    $description = isset($options['description']) ? (string) $options['description'] : '';
    $slug = isset($options['slug']) ? (string) $options['slug'] : '';
    $robots = isset($options['robots']) ? (string) $options['robots'] : '';
    $og_type = isset($options['og_type']) ? (string) $options['og_type'] : 'website';
    $json_ld = isset($options['json_ld']) ? $options['json_ld'] : null;
    $referrer = !empty($options['referrer']);

    $canonical = '';
    $alternates = array();
    if ($slug !== '' && function_exists('i18n_public_url') && in_array($slug, i18n_public_slugs(), true)) {
        $canonical = canonical_base_url() . i18n_public_url($slug, i18n_current());
        foreach (i18n_languages() as $lang) {
            $alternates[$lang] = canonical_base_url() . i18n_public_url($slug, $lang);
        }
        $alternates['x-default'] = canonical_base_url() . i18n_public_url($slug, 'de');
    } elseif (!empty($options['canonical_path']) && function_exists('i18n_allow')) {
        $path = (string) $options['canonical_path'];
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }
        $canonical = canonical_base_url() . $path;
    }

    $html_lang = function_exists('i18n_html_lang') ? i18n_html_lang() : 'de';
    $og_locale = function_exists('i18n_locale') ? i18n_locale() : 'de_CH';
    ?>
<!DOCTYPE html>
<html lang="<?php echo h($html_lang); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo h($title); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($description !== ''): ?>
    <meta name="description" content="<?php echo h($description); ?>">
    <?php endif; ?>
    <?php if ($robots !== ''): ?>
    <meta name="robots" content="<?php echo h($robots); ?>">
    <?php endif; ?>
    <?php if ($canonical !== ''): ?>
    <link rel="canonical" href="<?php echo h($canonical); ?>">
    <?php foreach ($alternates as $hreflang => $href): ?>
    <link rel="alternate" hreflang="<?php echo h($hreflang); ?>" href="<?php echo h($href); ?>">
    <?php endforeach; ?>
    <meta property="og:type" content="<?php echo h($og_type); ?>">
    <meta property="og:url" content="<?php echo h($canonical); ?>">
    <meta property="og:title" content="<?php echo h($title); ?>">
    <?php if ($description !== ''): ?>
    <meta property="og:description" content="<?php echo h($description); ?>">
    <?php endif; ?>
    <meta property="og:locale" content="<?php echo h($og_locale); ?>">
    <?php
    if (function_exists('i18n_languages')) {
        foreach (i18n_languages() as $lang) {
            $locale = ($lang === 'fr') ? 'fr_CH' : (($lang === 'en') ? 'en_GB' : 'de_CH');
            if ($locale === $og_locale) {
                continue;
            }
            echo '<meta property="og:locale:alternate" content="' . h($locale) . '">' . "\n";
        }
    }
    ?>
    <?php endif; ?>
    <?php if ($referrer && function_exists('token_page_referrer_meta')): ?>
    <?php echo token_page_referrer_meta(); ?>
    <?php endif; ?>
    <link rel="apple-touch-icon" sizes="57x57" href="/images/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/images/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/images/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/images/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/images/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/images/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/images/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/images/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/images/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="/images/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/images/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css">
    <script src="/js/main.js"></script>
    <?php
    if (function_exists('i18n_script_tag')) {
        echo i18n_script_tag();
    }
    $track = !array_key_exists('matomo', $options) || !empty($options['matomo']);
    $matomo = dirname(__DIR__) . '/templates/matomo_tracking.php';
    if ($track && is_file($matomo)) {
        include $matomo;
    }
    if (is_array($json_ld)) {
        $encoded = json_encode($json_ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
        if (is_string($encoded)) {
            echo '<script type="application/ld+json">' . $encoded . '</script>';
        }
    } elseif (is_string($json_ld) && $json_ld !== '') {
        echo $json_ld;
    }
    ?>
</head>
<body>
    <?php
}
