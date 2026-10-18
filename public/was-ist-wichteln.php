<?php
require_once __DIR__ . '/../includes/templates/public_shell.php';
public_page_open('was-ist-wichteln', 'meta.what.title', 'meta.what.description', 'article');
i18n_include_content('was-ist-wichteln');
$position = 2;
$label = t('ads.explainer');
include __DIR__ . '/../includes/templates/google_ads.php';
public_page_close();
