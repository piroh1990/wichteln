<?php
require_once __DIR__ . '/../includes/templates/public_shell.php';
public_page_open('ueber-uns', 'meta.about.title', 'meta.about.description', 'website');
i18n_include_content('ueber-uns');
public_page_close();
