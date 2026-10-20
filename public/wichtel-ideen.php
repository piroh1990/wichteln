<?php
require_once __DIR__ . '/../includes/templates/public_shell.php';
require_once __DIR__ . '/../includes/ideas_page.php';

public_page_open('wichtel-ideen', 'meta.ideas.title', 'meta.ideas.description', 'article');
echo ideas2_styles();
i18n_include_content('wichtel-ideen');
public_page_close();
