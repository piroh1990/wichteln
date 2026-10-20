<?php
/**
 * Entwurf der Geschenkideen mit Werbelinks zu Orell Füssli.
 * Nicht in Navigation, Footer oder Sitemap. noindex.
 * Erreichbar als /wichtel-ideen2, /fr/wichtel-ideen2 und /en/wichtel-ideen2.
 */

require_once __DIR__ . '/../includes/templates/public_shell.php';
require_once __DIR__ . '/../includes/affiliate.php';

function ideas2_texts() {
    $lang = function_exists('i18n_current') ? i18n_current() : 'de';
    $all = array(
        'de' => array(
            'banner_label' => 'Hinweis zu Orell Füssli',
            'banner' => 'Passende Geschenke findest du bei Orell Füssli. Wenn du über unsere Links bestellst, unterstützt du wichtlä.ch.',
            'button' => 'Geschenkideen bei Orell Füssli',
            'view' => 'Bei Orell Füssli ansehen',
            'note' => 'Die Links zu Orell Füssli sind Werbelinks. Wenn du darüber bestellst, unterstützt du wichtlä.ch. Für dich kostet es nichts extra.',
        ),
        'fr' => array(
            'banner_label' => 'Note sur Orell Füssli',
            'banner' => 'Tu trouves des cadeaux adaptés chez Orell Füssli. Si tu commandes via nos liens, tu soutiens wichtlä.ch.',
            'button' => 'Idées cadeaux chez Orell Füssli',
            'view' => 'Voir chez Orell Füssli',
            'note' => 'Les liens vers Orell Füssli sont des liens publicitaires. Si tu commandes par là, tu soutiens wichtlä.ch. Pour toi, cela ne coûte rien en plus.',
        ),
        'en' => array(
            'banner_label' => 'Note about Orell Füssli',
            'banner' => 'You will find suitable gifts at Orell Füssli. If you order through our links, you support wichtlä.ch.',
            'button' => 'Gift ideas at Orell Füssli',
            'view' => 'View at Orell Füssli',
            'note' => 'The links to Orell Füssli are ad links. If you order through them, you support wichtlä.ch. It costs you nothing extra.',
        ),
    );
    if (!isset($all[$lang])) {
        return $all['de'];
    }
    return $all[$lang];
}

function ideas2_on() {
    return function_exists('affiliate_of_enabled') && affiliate_of_enabled();
}

function ideas2_search_anchor($term) {
    if (!ideas2_on() || !function_exists('affiliate_of_link') || !function_exists('affiliate_of_anchor')) {
        return '';
    }
    $url = affiliate_of_link(affiliate_of_search_target($term));
    if ($url === '') {
        return '';
    }
    return affiliate_of_anchor($url, ideas2_texts()['view']);
}

function ideas2_card_link($term) {
    $anchor = ideas2_search_anchor($term);
    if ($anchor === '') {
        return '';
    }
    return '<p class="idea-shop">' . $anchor . '</p>';
}

function ideas2_inline_link($term) {
    $anchor = ideas2_search_anchor($term);
    if ($anchor === '') {
        return '';
    }
    return ' ' . $anchor;
}

function ideas2_banner() {
    if (!ideas2_on() || !function_exists('affiliate_of_link') || !function_exists('affiliate_of_gift_url')) {
        return '';
    }
    $url = affiliate_of_link(affiliate_of_gift_url());
    if ($url === '') {
        return '';
    }
    $text = ideas2_texts();
    return '<aside class="of-draft-banner" aria-label="' . h($text['banner_label']) . '">'
        . '<p>' . h($text['banner']) . '</p>'
        . '<a class="of-draft-button" href="' . h($url) . '" rel="sponsored noopener" target="_blank">' . h($text['button']) . '</a>'
        . '</aside>';
}

function ideas2_note() {
    if (!ideas2_on() || !function_exists('affiliate_of_link') || !function_exists('affiliate_of_gift_url')) {
        return '';
    }
    if (affiliate_of_link(affiliate_of_gift_url()) === '') {
        return '';
    }
    return '<p class="affiliate-note of-draft-note">' . h(ideas2_texts()['note']) . '</p>';
}

function ideas2_styles() {
    return <<<'HTML'
<style>
.of-draft-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem 1.5rem;
    margin: 0 0 1.75rem;
    padding: 0.95rem 1.15rem;
    background: #f8f9fa;
    border: 1px solid #e6e8eb;
    border-left: 4px solid #e63946;
    border-radius: 12px;
}
.of-draft-banner p {
    margin: 0;
    flex: 1 1 16rem;
    color: #3a3d4a;
    font-size: 0.98rem;
    line-height: 1.55;
}
a.of-draft-button {
    flex: 0 0 auto;
    display: inline-block;
    background: #e63946;
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.95rem;
    line-height: 1.3;
    padding: 0.72rem 1.2rem;
    border-radius: 999px;
    box-shadow: 0 4px 12px rgba(230, 57, 70, 0.22);
    text-align: center;
}
a.of-draft-button:hover,
a.of-draft-button:focus {
    background: #d62828;
    color: #fff;
}
a.of-draft-button:focus-visible {
    outline: 2px solid #2b2d42;
    outline-offset: 3px;
}
.content-section .idea-card p.idea-shop {
    margin: 0.85rem 0 0;
}
.idea-shop a.affiliate-of-link,
.content-section li a.affiliate-of-link {
    color: #5f6368;
    font-size: 0.86rem;
    font-weight: 600;
}
.content-section li a.affiliate-of-link {
    margin-left: 0.35rem;
}
.of-draft-note {
    margin: 0.25rem 0 0;
    max-width: 46rem;
}
@media (max-width: 640px) {
    .of-draft-banner {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
HTML;
}

function ideas2_fix_lang($html) {
    return strtr($html, array(
        'href="/?setlang=de"' => 'href="/wichtel-ideen2?setlang=de"',
        'href="/fr/?setlang=fr"' => 'href="/fr/wichtel-ideen2?setlang=fr"',
        'href="/en/?setlang=en"' => 'href="/en/wichtel-ideen2?setlang=en"',
    ));
}

function ideas2_render() {
    $previous_lang = function_exists('i18n_current') ? i18n_current() : 'de';
    $had_slug = array_key_exists('i18n_public_slug', $GLOBALS);
    $previous_slug = $had_slug ? $GLOBALS['i18n_public_slug'] : null;
    $previous_token = !empty($GLOBALS['i18n_is_token_page']);
    if (!headers_sent()) {
        header('X-Robots-Tag: noindex, nofollow');
    }
    $html = '';
    ob_start();
    try {
        i18n_boot_public();
        $GLOBALS['i18n_public_slug'] = 'index';
        $GLOBALS['i18n_is_token_page'] = false;
        render_head(array(
            'title' => t('meta.ideas.title'),
            'description' => t('meta.ideas.description'),
            'robots' => 'noindex, nofollow',
            'og_type' => 'article',
            'slug' => '',
        ));
        include __DIR__ . '/../includes/templates/navigation.php';
        echo ideas2_styles();
        $lang = i18n_current();
        $path = dirname(__DIR__) . '/content/' . $lang . '/wichtel-ideen2.php';
        if (!is_file($path)) {
            $path = dirname(__DIR__) . '/content/de/wichtel-ideen2.php';
        }
        include $path;
        include __DIR__ . '/../includes/templates/footer.php';
        include __DIR__ . '/cookie-banner.php';
        echo "</body>\n</html>\n";
        $html = ob_get_clean();
    } catch (Exception $e) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        throw $e;
    } finally {
        if (function_exists('i18n_activate')) {
            i18n_activate($previous_lang);
        }
        if ($had_slug) {
            $GLOBALS['i18n_public_slug'] = $previous_slug;
        } else {
            unset($GLOBALS['i18n_public_slug']);
        }
        $GLOBALS['i18n_is_token_page'] = $previous_token;
    }
    echo ideas2_fix_lang($html);
}

if (!defined('WICHTEL_IDEEN2_LIBRARY')) {
    ideas2_render();
}
