<?php
require_once dirname(__DIR__) . '/support.php';
require_once dirname(__DIR__) . '/premium.php';
?>
<!-- Footer -->
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>Wichtlä.ch</h3>
                <p><?php echo h(t('footer.tagline')); ?></p>
            </div>

            <div class="footer-section">
                <h4><?php echo h(t('footer.info')); ?></h4>
                <ul class="footer-links">
                    <li><a href="<?php echo h(i18n_href('was-ist-wichteln')); ?>"><?php echo h(t('nav.what')); ?></a></li>
                    <li><a href="<?php echo h(i18n_href('wichtel-ideen')); ?>"><?php echo h(t('nav.ideas')); ?></a></li>
                    <li><a href="<?php echo h(i18n_href('firmenwichteln-tipps')); ?>"><?php echo h(t('nav.company')); ?></a></li>
                    <li><a href="<?php echo h(i18n_href('faq')); ?>"><?php echo h(t('nav.faq')); ?></a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4><?php echo h(t('footer.legal')); ?></h4>
                <ul class="footer-links">
                    <li><a href="<?php echo h(i18n_href('ueber-uns')); ?>"><?php echo h(t('nav.about')); ?></a></li>
                    <?php echo support_footer_html(); ?>
                    <?php echo premium_footer_html(); ?>
                    <li><a href="<?php echo h(i18n_href('impressum')); ?>"><?php echo h(t('footer.imprint')); ?></a></li>
                    <li><a href="<?php echo h(i18n_href('datenschutz')); ?>"><?php echo h(t('footer.privacy')); ?></a></li>
                </ul>
            </div>

            <div class="footer-section">
                <h4><?php echo h(t('footer.start')); ?></h4>
                <ul class="footer-links">
                    <li><a href="<?php echo h(i18n_href('create_group')); ?>"><?php echo h(t('footer.create')); ?></a></li>
                    <li><a href="<?php echo h(i18n_href('admin-link')); ?>"><?php echo h(t('footer.admin_link')); ?></a></li>
                    <li><a href="<?php echo h(i18n_href('participant')); ?>"><?php echo h(t('footer.participant')); ?></a></li>
                    <li><a href="<?php echo h(i18n_href('index')); ?>"><?php echo h(t('footer.home')); ?></a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> wichtlä.ch – <?php echo h(t('footer.rights')); ?></p>
            <p class="footer-heart"><?php echo h(t('footer.made')); ?></p>
        </div>
    </div>
</footer>
