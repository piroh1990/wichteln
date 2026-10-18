<!-- Navigation -->
<nav class="main-nav">
    <div class="nav-container">
        <a href="<?php echo h(i18n_href('index')); ?>" class="nav-logo">
            <img src="/images/logo.png" alt="Wichtlä.ch Logo" height="40">
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="<?php echo h(t('nav.menu_open')); ?>" aria-expanded="false" aria-controls="navMenu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <?php
        $current_script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
        $current_page = basename($current_script !== '' ? $current_script : (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : ''));
        if (strpos($current_script, '/admin/') !== false) {
            $current_page = '';
        }
        $nav_items = array(
            array('index', 'nav.home', 'index.php'),
            array('was-ist-wichteln', 'nav.what', 'was-ist-wichteln.php'),
            array('wichtel-ideen', 'nav.ideas', 'wichtel-ideen.php'),
            array('firmenwichteln-tipps', 'nav.company', 'firmenwichteln-tipps.php'),
            array('faq', 'nav.faq', 'faq.php'),
            array('ueber-uns', 'nav.about', 'ueber-uns.php'),
            array('create_group', 'nav.create', 'create_group.php'),
        );
        ?>
        <ul class="nav-menu" id="navMenu">
            <?php foreach ($nav_items as $item): ?>
                <?php
                $classes = 'nav-link';
                if ($item[2] === 'create_group.php') {
                    $classes .= ' nav-link-primary';
                }
                $current = ($current_page === $item[2]) ? ' aria-current="page"' : '';
                ?>
                <li><a href="<?php echo h(i18n_href($item[0])); ?>" class="<?php echo h($classes); ?>"<?php echo $current; ?>><?php echo h(t($item[1])); ?></a></li>
            <?php endforeach; ?>
            <?php if (i18n_show_switcher()): ?>
                <li class="lang-switch-item">
                    <ul class="lang-switch" aria-label="<?php echo h(t('lang.switch')); ?>">
                        <?php foreach (i18n_languages() as $code): ?>
                            <?php $active = ($code === i18n_current()); ?>
                            <li>
                                <a href="<?php echo h(i18n_switch_href($code)); ?>" hreflang="<?php echo h($code); ?>" lang="<?php echo h($code); ?>"<?php echo $active ? ' aria-current="true"' : ''; ?>><?php echo h(strtoupper($code)); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var navToggle = document.getElementById('navToggle');
    var navMenu = document.getElementById('navMenu');
    var openLabel = (window.I18N && window.I18N['nav.menu_open']) || 'Menü öffnen';
    var closeLabel = (window.I18N && window.I18N['nav.menu_close']) || 'Menü schliessen';

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            navToggle.classList.toggle('active');
            navMenu.classList.toggle('active');
            var isExpanded = navToggle.classList.contains('active');
            navToggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
            navToggle.setAttribute('aria-label', isExpanded ? closeLabel : openLabel);
        });

        document.addEventListener('click', function(event) {
            if (!navToggle.contains(event.target) && !navMenu.contains(event.target)) {
                navToggle.classList.remove('active');
                navMenu.classList.remove('active');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.setAttribute('aria-label', openLabel);
            }
        });
    }
});
</script>
