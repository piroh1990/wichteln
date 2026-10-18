<!-- Navigation -->
<nav class="main-nav">
    <div class="nav-container">
        <a href="<?php echo h(i18n_href('index')); ?>" class="nav-logo">
            <img src="/images/logo.png" alt="Wichtlä.ch Logo" height="40" width="50">
        </a>

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
        <div class="nav-cluster">
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
            </ul>

            <div class="nav-tools">
                <?php if (i18n_show_switcher()): ?>
                    <nav class="lang-switch" aria-label="<?php echo h(t('lang.switch')); ?>">
                        <span class="lang-switch-globe" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" focusable="false" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.8"></circle>
                                <path d="M3 12h18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                                <path d="M12 3c2.6 2.7 3.9 5.7 3.9 9s-1.3 6.3-3.9 9c-2.6-2.7-3.9-5.7-3.9-9s1.3-6.3 3.9-9z" fill="none" stroke="currentColor" stroke-width="1.8"></path>
                            </svg>
                        </span>
                        <ul role="list">
                            <?php foreach (i18n_languages() as $code): ?>
                                <?php $active = ($code === i18n_current()); ?>
                                <li role="listitem">
                                    <a href="<?php echo h(i18n_switch_href($code)); ?>" hreflang="<?php echo h($code); ?>" lang="<?php echo h($code); ?>" aria-label="<?php echo h(t('lang.name.' . $code)); ?>"<?php echo $active ? ' aria-current="true"' : ''; ?>><?php echo h(strtoupper($code)); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <button class="nav-toggle" id="navToggle" type="button" aria-label="<?php echo h(t('nav.menu_open')); ?>" aria-expanded="false" aria-controls="navMenu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var navToggle = document.getElementById('navToggle');
    var navMenu = document.getElementById('navMenu');
    var openLabel = (window.I18N && window.I18N['nav.menu_open']) || 'Menü öffnen';
    var closeLabel = (window.I18N && window.I18N['nav.menu_close']) || 'Menü schliessen';

    function setMenuOpen(isOpen) {
        navToggle.classList.toggle('active', isOpen);
        navMenu.classList.toggle('active', isOpen);
        navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        navToggle.setAttribute('aria-label', isOpen ? closeLabel : openLabel);
    }

    if (navToggle && navMenu) {
        navToggle.addEventListener('click', function() {
            setMenuOpen(!navToggle.classList.contains('active'));
        });

        document.addEventListener('click', function(event) {
            if (!navToggle.contains(event.target) && !navMenu.contains(event.target)) {
                setMenuOpen(false);
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && navToggle.classList.contains('active')) {
                setMenuOpen(false);
                navToggle.focus();
            }
        });
    }
});
</script>
