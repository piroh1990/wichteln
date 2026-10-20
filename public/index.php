<?php
require_once __DIR__ . '/../includes/templates/public_shell.php';

i18n_boot_public();
i18n_set_public_page('index');

$home_url = canonical_base_url() . i18n_public_url('index', i18n_current());
render_head(array(
    'title' => t('meta.index.title'),
    'description' => t('meta.index.description'),
    'slug' => 'index',
    'og_type' => 'website',
    'json_ld' => array(
        '@context' => 'https://schema.org',
        '@graph' => array(
            array(
                '@type' => 'WebSite',
                'name' => 'Wichtlä.ch',
                'url' => $home_url,
                'description' => t('meta.index.description'),
                'inLanguage' => i18n_hreflang(i18n_current()),
                'publisher' => array(
                    '@type' => 'Organization',
                    'name' => 'Wichtlä.ch',
                    'url' => canonical_base_url() . '/',
                ),
            ),
            array(
                '@type' => 'WebApplication',
                'name' => 'Wichtlä.ch',
                'url' => $home_url,
                'applicationCategory' => 'UtilitiesApplication',
                'operatingSystem' => 'Web',
                'browserRequirements' => 'Requires a web browser. No install.',
                'description' => t('meta.index.description'),
                'inLanguage' => i18n_hreflang(i18n_current()),
                'offers' => array(
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'CHF',
                ),
            ),
        ),
    ),
));
include __DIR__ . '/../includes/templates/navigation.php';

if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    echo '<div class="container"><div class="notification success" role="status"><span aria-hidden="true">✓</span> '
        . h(t('home.deleted')) . '</div></div>';
}

i18n_include_content('index');
?>
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -50px 0px' });
        document.querySelectorAll('.step, .feature-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
<?php
public_page_close();
