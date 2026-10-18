<?php
require_once __DIR__ . '/../includes/templates/public_shell.php';
require_once __DIR__ . '/../includes/support.php';

if (!support_enabled()) {
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    header('X-Robots-Tag: noindex, nofollow');
    echo support_unavailable_body();
    exit;
}

public_page_open('unterstuetzen', 'meta.support.title', 'meta.support.description', 'website');
i18n_include_content('unterstuetzen');
public_page_close();
