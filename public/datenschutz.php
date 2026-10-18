<?php
require_once __DIR__ . '/../includes/templates/public_shell.php';
public_page_open('datenschutz', 'meta.privacy.title', 'meta.privacy.description', 'website');
i18n_include_content('datenschutz');
public_page_close();
