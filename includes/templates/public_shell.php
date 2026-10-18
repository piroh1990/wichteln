<?php

require_once dirname(__DIR__) . '/i18n.php';
require_once __DIR__ . '/head.php';

function public_page_open($slug, $title_key, $description_key, $og_type = 'website', $json_ld = null) {
    i18n_boot_public();
    i18n_set_public_page($slug);
    $title = t($title_key);
    $description = t($description_key);
    render_head(array(
        'title' => $title,
        'description' => $description,
        'slug' => $slug,
        'og_type' => $og_type,
        'json_ld' => $json_ld,
    ));
    include __DIR__ . '/navigation.php';
}

function public_page_close() {
    include __DIR__ . '/footer.php';
    include dirname(__DIR__, 2) . '/public/cookie-banner.php';
    echo "</body>\n</html>\n";
}

function token_page_open($title, $description = '', $options = array()) {
    if (!is_array($options)) {
        $options = array();
    }
    $head = array(
        'title' => $title,
        'description' => $description,
        'robots' => 'noindex, nofollow',
        'referrer' => true,
        'matomo' => false,
    );
    foreach ($options as $key => $value) {
        $head[$key] = $value;
    }
    render_head($head);
    include __DIR__ . '/navigation.php';
}

function token_page_close() {
    include __DIR__ . '/footer.php';
    include dirname(__DIR__, 2) . '/public/cookie-banner.php';
    echo "</body>\n</html>\n";
}
