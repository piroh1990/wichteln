<?php
require_once __DIR__ . '/../includes/templates/public_shell.php';

http_response_code(404);
i18n_boot_public();

render_head(array(
    'title' => t('meta.notfound.title'),
    'description' => t('meta.notfound.description'),
    'robots' => 'noindex, nofollow',
));
include __DIR__ . '/../includes/templates/navigation.php';
?>
<div class="content-page notfound-page">
    <header class="page-header">
        <h1 class="page-title"><?php echo h(t('meta.notfound.heading')); ?></h1>
        <p class="page-subtitle"><?php echo h(t('meta.notfound.description')); ?></p>
    </header>
    <div class="notfound-actions">
        <a class="button primary" href="<?php echo h(i18n_href('index')); ?>"><?php echo h(t('footer.home')); ?></a>
        <a class="button secondary" href="<?php echo h(i18n_href('wichtel-ideen')); ?>"><?php echo h(t('nav.ideas')); ?></a>
        <a class="button secondary" href="<?php echo h(i18n_href('faq')); ?>"><?php echo h(t('nav.faq')); ?></a>
    </div>
</div>
<?php
public_page_close();
