<?php
require_once __DIR__ . '/../includes/templates/public_shell.php';
public_page_open('faq', 'meta.faq.title', 'meta.faq.description', 'website');
i18n_include_content('faq');
public_page_close();
