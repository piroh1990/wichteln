<?php
/**
 * Geschenkideen nach Budget. Werbelinks zu Orell Füssli nur, wenn der Schalter an ist.
 * Die Seite liegt unter /wichtel-ideen.
 */

require_once __DIR__ . '/affiliate.php';
require_once __DIR__ . '/ideas_kept.php';

function ideas2_lang() {
    $lang = function_exists('i18n_current') ? i18n_current() : 'de';
    if ($lang !== 'fr' && $lang !== 'en') {
        return 'de';
    }
    return $lang;
}

function ideas2_texts() {
    $all = array(
        'de' => array(
            'banner_label' => 'Hinweis zu Orell Füssli',
            'banner' => 'Passende Geschenke findest du bei Orell Füssli. Wenn du über unsere Links bestellst, unterstützt du wichtlä.ch.',
            'button' => 'Geschenkideen bei Orell Füssli',
            'view' => 'Bei Orell Füssli ansehen',
            'note' => 'Die Links zu Orell Füssli sind Werbelinks. Wenn du darüber bestellst, unterstützt du wichtlä.ch. Für dich kostet es nichts extra.',
            'home' => 'Home',
            'crumb' => 'Geschenkideen',
            'h1' => 'Wichtelgeschenke nach Budget',
            'subtitle' => 'Kleine Geschenke zum Bestellen, grob nach Budget sortiert.',
            'intro' => 'Die Stufe ist nur ein Richtwert. Die Preise ändern sich, deshalb steht hier kein genauer Betrag.',
            'band_10' => 'Bis CHF 10',
            'band_20' => 'Bis CHF 20',
            'band_30' => 'Bis CHF 30',
            'band_50' => 'Bis CHF 50',
            'lead_10' => 'Auch für ein tiefes Limit.',
            'lead_20' => 'In dieser Stufe liegen die meisten Runden.',
            'lead_30' => 'Etwas mehr Spielraum, immer noch ein kleines Geschenk.',
            'lead_50' => 'Wenn die Gruppe das Limit höher angesetzt hat.',
            'filter_bereich' => 'Bereich',
            'filter_preis' => 'Preis',
            'filter_all' => 'Alle',
            'filter_price_10' => 'bis CHF 10',
            'filter_price_20' => 'bis CHF 20',
            'filter_price_30' => 'bis CHF 30',
            'filter_price_50' => 'bis CHF 50',
            'count_one' => '{n} Idee',
            'count_many' => '{n} Ideen',
            'empty' => 'Keine Idee passt zu dieser Auswahl.',
            'reset' => 'Filter zurücksetzen',
            'cat_spiele' => 'Spiele & Rätsel',
            'cat_wellness' => 'Gemütlich & Wellness',
            'cat_buero' => 'Büro & Papeterie',
            'cat_kreativ' => 'Kreativ & Basteln',
            'cat_lesen' => 'Lesen & Bücher',
            'cat_alltag' => 'Alltag & Gadgets',
            'cat_kalender' => 'Kalender & Adventskalender',
            'tip_title' => 'Kurz zur Auswahl',
            'tip' => 'Wenn du die Person wenig kennst, bleib bei einer kleinen Stufe. Ein Spiel oder etwas fürs Büro liegt selten daneben.',
            'cta_title' => 'Bereit für dein Wichteln?',
            'cta' => 'Organisiere dein Wichteln in unter zwei Minuten, kostenlos und ohne Konto.',
            'cta_button' => 'Jetzt Wichtel-Gruppe erstellen →',
            'gift' => 'Wenn nichts davon passt, ist eine Geschenkkarte die einfache Notlösung.',
            'schema' => 'Kleine Wichtelgeschenke, grob nach Budget sortiert, mit Tipps für die Auswahl.',
        ),
        'fr' => array(
            'banner_label' => 'Note sur Orell Füssli',
            'banner' => 'Tu trouves des cadeaux adaptés chez Orell Füssli. Si tu commandes via nos liens, tu soutiens wichtlä.ch.',
            'button' => 'Idées cadeaux chez Orell Füssli',
            'view' => 'Voir chez Orell Füssli',
            'note' => 'Les liens vers Orell Füssli sont des liens publicitaires. Si tu commandes par là, tu soutiens wichtlä.ch. Pour toi, cela ne coûte rien en plus.',
            'home' => 'Accueil',
            'crumb' => 'Idées de cadeaux',
            'h1' => 'Idées cadeaux selon le budget',
            'subtitle' => 'De petits cadeaux à commander, classés grosso modo par budget.',
            'intro' => "Le palier n'est qu'un ordre de grandeur. Les prix changent, donc aucun montant précis ici.",
            'band_10' => "Jusqu'à 10 CHF",
            'band_20' => "Jusqu'à 20 CHF",
            'band_30' => "Jusqu'à 30 CHF",
            'band_50' => "Jusqu'à 50 CHF",
            'lead_10' => 'Convient aussi quand la limite est basse.',
            'lead_20' => 'La plupart des groupes se situent ici.',
            'lead_30' => "Un peu plus de marge, et c'est encore un petit cadeau.",
            'lead_50' => 'Quand le groupe a fixé une limite plus haute.',
            'filter_bereich' => 'Catégorie',
            'filter_preis' => 'Prix',
            'filter_all' => 'Tous',
            'filter_price_10' => 'jusqu\'à 10 CHF',
            'filter_price_20' => 'jusqu\'à 20 CHF',
            'filter_price_30' => 'jusqu\'à 30 CHF',
            'filter_price_50' => 'jusqu\'à 50 CHF',
            'count_one' => '{n} idée',
            'count_many' => '{n} idées',
            'empty' => 'Aucune idée ne correspond à cette sélection.',
            'reset' => 'Réinitialiser les filtres',
            'cat_spiele' => 'Jeux et énigmes',
            'cat_wellness' => 'Cocooning et bien-être',
            'cat_buero' => 'Bureau et papeterie',
            'cat_kreativ' => 'Créatif et bricolage',
            'cat_lesen' => 'Lecture et livres',
            'cat_alltag' => 'Quotidien et gadgets',
            'cat_kalender' => 'Calendriers et Avent',
            'tip_title' => 'Pour choisir',
            'tip' => 'Si tu connais peu la personne, reste sur un petit palier. Un jeu ou un objet pour le bureau tombe rarement à côté.',
            'cta_title' => 'Prêt pour ton Secret Santa ?',
            'cta' => 'Organise ton Secret Santa en moins de deux minutes, gratuitement et sans compte.',
            'cta_button' => 'Créer un groupe Secret Santa →',
            'gift' => 'Si rien ne convient, une carte cadeau est la solution simple.',
            'schema' => 'De petites idées de cadeaux, classées grosso modo par budget, avec des conseils pour choisir.',
        ),
        'en' => array(
            'banner_label' => 'Note about Orell Füssli',
            'banner' => 'You will find suitable gifts at Orell Füssli. If you order through our links, you support wichtlä.ch.',
            'button' => 'Gift ideas at Orell Füssli',
            'view' => 'View at Orell Füssli',
            'note' => 'The links to Orell Füssli are ad links. If you order through them, you support wichtlä.ch. It costs you nothing extra.',
            'home' => 'Home',
            'crumb' => 'Gift ideas',
            'h1' => 'Secret Santa gifts by budget',
            'subtitle' => 'Small gifts you can order, sorted roughly by budget.',
            'intro' => 'The band is only a guide. Prices change, so there is no exact amount here.',
            'band_10' => 'Up to CHF 10',
            'band_20' => 'Up to CHF 20',
            'band_30' => 'Up to CHF 30',
            'band_50' => 'Up to CHF 50',
            'lead_10' => 'Works even with a low limit.',
            'lead_20' => 'Most groups land in this band.',
            'lead_30' => 'A bit more room, and it is still a small gift.',
            'lead_50' => 'When the group has set a higher limit.',
            'filter_bereich' => 'Category',
            'filter_preis' => 'Price',
            'filter_all' => 'All',
            'filter_price_10' => 'up to CHF 10',
            'filter_price_20' => 'up to CHF 20',
            'filter_price_30' => 'up to CHF 30',
            'filter_price_50' => 'up to CHF 50',
            'count_one' => '{n} idea',
            'count_many' => '{n} ideas',
            'empty' => 'Nothing matches this selection.',
            'reset' => 'Reset filters',
            'cat_spiele' => 'Games & puzzles',
            'cat_wellness' => 'Cosy & wellness',
            'cat_buero' => 'Office & stationery',
            'cat_kreativ' => 'Creative & crafts',
            'cat_lesen' => 'Reading & books',
            'cat_alltag' => 'Everyday & gadgets',
            'cat_kalender' => 'Calendars & advent calendars',
            'tip_title' => 'A quick way to choose',
            'tip' => 'If you do not know the person well, stay with a small band. A game or something for the office is rarely a miss.',
            'cta_title' => 'Ready for your Secret Santa?',
            'cta' => 'Set up your Secret Santa in under two minutes, free and with no account.',
            'cta_button' => 'Create a Secret Santa group →',
            'gift' => 'If nothing fits, a gift card is the easy fallback.',
            'schema' => 'Small Secret Santa gifts, sorted roughly by budget, with tips for choosing.',
        ),
    );
    return $all[ideas2_lang()];
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

function ideas2_giftcard() {
    $anchor = ideas2_search_anchor('Geschenkkarte');
    if ($anchor === '') {
        return '';
    }
    return '<p class="of-draft-fallback">' . h(ideas2_texts()['gift']) . ' ' . $anchor . '</p>';
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
.of-draft-intro {
    margin: 0 0 2rem;
    max-width: 46rem;
    color: #3a3d4a;
    font-size: 1.05rem;
    line-height: 1.6;
}
.content-section .ideas-grid {
    margin-bottom: 0;
}
.content-section .idea-card p.idea-description {
    margin-bottom: 0.85rem;
    font-size: 0.95rem;
    line-height: 1.55;
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
.of-draft-fallback {
    margin: 0 0 0.75rem;
    max-width: 46rem;
    color: #3a3d4a;
    font-size: 0.98rem;
    line-height: 1.55;
}
.of-draft-fallback a.affiliate-of-link {
    margin-left: 0.35rem;
}
.of-draft-note {
    margin: 0.25rem 0 0;
    max-width: 46rem;
}
.ideas-kept {
    margin-top: 2.75rem;
}
.ideas-kept > .content-section:last-child {
    margin-bottom: 0;
}
.ideas-filter {
    margin: 0 0 2rem;
    padding: 1rem 1.1rem 1.05rem;
    background: #fff;
    border: 1px solid #e6e8eb;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
}
.ideas-filter-row + .ideas-filter-row {
    margin-top: 0.9rem;
}
.ideas-filter-label {
    display: block;
    margin: 0 0 0.45rem;
    color: #3a3d4a;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    line-height: 1.3;
    text-transform: uppercase;
}
.ideas-filter-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    max-width: 100%;
}
button.ideas-filter-chip {
    appearance: none;
    -webkit-appearance: none;
    position: relative;
    overflow: visible;
    margin: 0;
    padding: 0.55rem 0.95rem;
    min-height: 44px;
    max-width: 100%;
    border: 2px solid #c5c9d1;
    border-radius: 999px;
    background: #fff;
    color: #2b2d42;
    box-shadow: none;
    font-family: inherit;
    font-size: 0.95rem;
    font-weight: 600;
    line-height: 1.3;
    text-align: center;
    white-space: normal;
    cursor: pointer;
}
button.ideas-filter-chip::before,
button.ideas-filter-chip:hover::before {
    content: none;
    width: 0;
    height: 0;
}
button.ideas-filter-chip:hover {
    border-color: #d62828;
    color: #9d1724;
    background: #fff;
}
button.ideas-filter-chip:active {
    transform: none;
}
button.ideas-filter-chip[aria-checked="true"] {
    background: #d62828;
    border-color: #d62828;
    color: #fff;
}
button.ideas-filter-chip[aria-checked="true"]:hover {
    background: #b81f28;
    border-color: #b81f28;
    color: #fff;
}
button.ideas-filter-chip:focus-visible {
    outline: 3px solid #2a9d8f;
    outline-offset: 2px;
}
.ideas-filter-status {
    margin-top: 0.9rem;
}
.ideas-filter-count {
    margin: 0;
    color: #2b2d42;
    font-size: 0.98rem;
    font-weight: 700;
    line-height: 1.4;
}
.ideas-filter-empty {
    margin: 0.7rem 0 0;
    padding: 0.85rem 1rem;
    background: #f8f9fa;
    border-left: 4px solid #e63946;
    border-radius: 12px;
    color: #2b2d42;
    font-size: 0.98rem;
    line-height: 1.55;
}
a.ideas-filter-reset {
    color: #9d1724;
    font-weight: 700;
    text-decoration: underline;
    text-underline-offset: 0.15em;
}
a.ideas-filter-reset:hover,
a.ideas-filter-reset:focus {
    color: #d62828;
}
.ideas-catalog .idea-card[hidden],
.ideas-catalog .ideas-band[hidden],
.ideas-filter [hidden] {
    display: none !important;
}
@media (max-width: 640px) {
    .of-draft-banner {
        flex-direction: column;
        align-items: stretch;
    }
    .ideas-filter {
        padding: 0.9rem 0.8rem;
    }
    .ideas-filter-chips {
        flex-wrap: wrap;
    }
}
</style>
HTML;
}

function ideas2_pick($map, $lang) {
    if (isset($map[$lang]) && $map[$lang] !== '') {
        return $map[$lang];
    }
    return $map['de'];
}

function ideas2_items() {
    // Budgetstufen nach dem oberen Preis aus dem Sortiment-Check vom 20.10.2026,
    // auf die nächste grobe Stufe gerundet. Suchbegriffe bleiben deutsch.
    return array(
        array('group' => '10', 'cat' => 'buero', 'icon' => '🖊️', 'search' => 'Kugelschreiber',
            'title' => array('de' => 'Kugelschreiber', 'fr' => 'Stylo à bille', 'en' => 'Ballpoint pen'),
            'text' => array(
                'de' => 'Ein Kugelschreiber fürs Büro oder die Tasche.',
                'fr' => 'Un stylo pour le bureau ou le sac.',
                'en' => 'A pen for the office or a bag.',
            )),
        array('group' => '10', 'cat' => 'lesen', 'icon' => '🔖', 'search' => 'Lesezeichen',
            'title' => array('de' => 'Lesezeichen', 'fr' => 'Marque-page', 'en' => 'Bookmark'),
            'text' => array(
                'de' => 'Für jemanden, der das Buch immer an derselben Stelle aufschlägt.',
                'fr' => 'Pour quelqu’un qui rouvre toujours son livre au même endroit.',
                'en' => 'For someone who always opens the book at the same place.',
            )),
        array('group' => '10', 'cat' => 'wellness', 'icon' => '🧴', 'search' => 'Handcreme',
            'title' => array('de' => 'Handcreme', 'fr' => 'Crème pour les mains', 'en' => 'Hand cream'),
            'text' => array(
                'de' => 'Handcreme für die kalte Jahreszeit, klein und unkompliziert.',
                'fr' => 'Une crème pour les mains pour la saison froide, petite et simple.',
                'en' => 'Hand cream for the cold season, small and straightforward.',
            )),
        array('group' => '10', 'cat' => 'wellness', 'icon' => '🛁', 'search' => 'Badebombe',
            'title' => array('de' => 'Badebombe', 'fr' => 'Bombe de bain', 'en' => 'Bath bomb'),
            'text' => array(
                'de' => 'Eine Badebombe für ein ruhiges Bad nach der Arbeit.',
                'fr' => 'Une bombe de bain pour un moment calme après le travail.',
                'en' => 'A bath bomb for a quiet bath after work.',
            )),
        array('group' => '20', 'cat' => 'buero', 'icon' => '📓', 'search' => 'Notizbuch',
            'title' => array('de' => 'Notizbuch', 'fr' => 'Carnet', 'en' => 'Notebook'),
            'text' => array(
                'de' => 'Ein Notizbuch für Listen, Skizzen oder den nächsten Einfall.',
                'fr' => 'Un carnet pour les listes, les croquis ou la prochaine idée.',
                'en' => 'A notebook for lists, sketches or the next idea.',
            )),
        array('group' => '20', 'cat' => 'lesen', 'icon' => '💡', 'search' => 'Leselampe',
            'title' => array('de' => 'Leselampe', 'fr' => 'Lampe de lecture', 'en' => 'Reading light'),
            'text' => array(
                'de' => 'Eine kleine Leselampe fürs Bett oder das Sofa.',
                'fr' => 'Une petite lampe de lecture pour le lit ou le canapé.',
                'en' => 'A small reading light for bed or the sofa.',
            )),
        array('group' => '20', 'cat' => 'kalender', 'icon' => '📅', 'search' => 'Tischkalender 2027',
            'title' => array('de' => 'Tischkalender 2027', 'fr' => 'Calendrier de bureau 2027', 'en' => 'Desk calendar 2027'),
            'text' => array(
                'de' => 'Ein Tischkalender für 2027, der auf dem Schreibtisch Platz hat.',
                'fr' => 'Un calendrier de bureau pour 2027, qui tient sur la table.',
                'en' => 'A 2027 desk calendar that fits on a desk.',
            )),
        array('group' => '20', 'cat' => 'wellness', 'icon' => '🧦', 'search' => 'Socken',
            'title' => array('de' => 'Socken', 'fr' => 'Chaussettes', 'en' => 'Socks'),
            'text' => array(
                'de' => 'Socken mit einem Motiv, das zur Person passt.',
                'fr' => 'Des chaussettes avec un motif qui va à la personne.',
                'en' => 'Socks with a pattern that suits the person.',
            )),
        array('group' => '20', 'cat' => 'wellness', 'icon' => '✨', 'search' => 'Lichterkette',
            'title' => array('de' => 'Lichterkette', 'fr' => 'Guirlande lumineuse', 'en' => 'String lights'),
            'text' => array(
                'de' => 'Eine kleine Lichterkette fürs Fenster oder das Regal.',
                'fr' => "Une petite guirlande lumineuse pour la fenêtre ou l'étagère.",
                'en' => 'A small string of lights for a window or a shelf.',
            )),
        array('group' => '20', 'cat' => 'alltag', 'icon' => '🔑', 'search' => 'Schlüsselanhänger',
            'title' => array('de' => 'Schlüsselanhänger', 'fr' => 'Porte-clés', 'en' => 'Key ring'),
            'text' => array(
                'de' => 'Ein Schlüsselanhänger, der am Bund nicht untergeht.',
                'fr' => 'Un porte-clés qui se voit sur le trousseau.',
                'en' => 'A key ring that stands out on a bunch of keys.',
            )),
        array('group' => '20', 'cat' => 'alltag', 'icon' => '💧', 'search' => 'Trinkflasche',
            'title' => array('de' => 'Trinkflasche', 'fr' => 'Gourde', 'en' => 'Water bottle'),
            'text' => array(
                'de' => 'Eine Trinkflasche fürs Büro oder unterwegs.',
                'fr' => 'Une gourde pour le bureau ou en déplacement.',
                'en' => 'A bottle for the office or on the go.',
            )),
        array('group' => '20', 'cat' => 'kalender', 'icon' => '🍵', 'search' => 'Tee Adventskalender',
            'title' => array('de' => 'Tee-Adventskalender', 'fr' => "Calendrier de l'Avent thé", 'en' => 'Tea advent calendar'),
            'text' => array(
                'de' => 'Ein Tee-Adventskalender mit einer anderen Sorte für jeden Tag.',
                'fr' => "Un calendrier de l'Avent avec un thé différent chaque jour.",
                'en' => 'A tea advent calendar with a different variety each day.',
            )),
        array('group' => '20', 'cat' => 'spiele', 'icon' => '🃏', 'search' => 'Skyjo',
            'title' => array('de' => 'Skyjo', 'fr' => 'Skyjo', 'en' => 'Skyjo'),
            'text' => array(
                'de' => 'Skyjo ist schnell erklärt und funktioniert in fast jeder Runde.',
                'fr' => "Skyjo s'explique vite et passe dans presque tous les groupes.",
                'en' => 'Skyjo is quick to explain and works in almost any group.',
            )),
        array('group' => '20', 'cat' => 'spiele', 'icon' => '🎴', 'search' => 'Flip 7',
            'title' => array('de' => 'Flip 7', 'fr' => 'Flip 7', 'en' => 'Flip 7'),
            'text' => array(
                'de' => 'Flip 7 ist kurz, praktisch wenn am Abend wenig Zeit bleibt.',
                'fr' => 'Flip 7 est court, pratique quand la soirée est déjà bien remplie.',
                'en' => 'Flip 7 is short, handy when the evening is already full.',
            )),
        array('group' => '20', 'cat' => 'spiele', 'icon' => '🎲', 'search' => 'Qwixx',
            'title' => array('de' => 'Qwixx', 'fr' => 'Qwixx', 'en' => 'Qwixx'),
            'text' => array(
                'de' => 'Qwixx braucht wenig Erklärung und passt auf jeden Tisch.',
                'fr' => "Qwixx demande peu d'explications et tient sur n'importe quelle table.",
                'en' => 'Qwixx needs little explaining and fits on any table.',
            )),
        array('group' => '20', 'cat' => 'spiele', 'icon' => '❓', 'search' => 'Quiz Spiel',
            'title' => array('de' => 'Quizspiel', 'fr' => 'Jeu de quiz', 'en' => 'Quiz game'),
            'text' => array(
                'de' => 'Ein Quiz für den Abend, wenn alle mitraten sollen.',
                'fr' => 'Un quiz pour la soirée, quand tout le monde veut répondre.',
                'en' => 'A quiz for the evening, when everyone wants a turn.',
            )),
        array('group' => '20', 'cat' => 'lesen', 'icon' => '📚', 'search' => 'Witzebuch',
            'title' => array('de' => 'Witzebuch', 'fr' => 'Livre de blagues', 'en' => 'Joke book'),
            'text' => array(
                'de' => 'Ein Witzebuch zum Weitergeben, auch wenn nicht jeder Witz sitzt.',
                'fr' => 'Un livre de blagues à faire circuler, même si toutes ne font pas rire.',
                'en' => 'A joke book to pass around, even if not every joke lands.',
            )),
        array('group' => '20', 'cat' => 'spiele', 'icon' => '💬', 'search' => 'DeepTalk',
            'title' => array('de' => 'Gesprächskarten', 'fr' => 'Cartes de conversation', 'en' => 'Conversation cards'),
            'text' => array(
                'de' => 'DeepTalk bringt Gespräche in Gang, die über den Smalltalk hinausgehen.',
                'fr' => 'DeepTalk lance des conversations qui vont plus loin que le small talk.',
                'en' => 'DeepTalk starts conversations that go past small talk.',
            )),
        array('group' => '20', 'cat' => 'spiele', 'icon' => '🧩', 'search' => 'Puzzle 1000',
            'title' => array('de' => 'Puzzle mit 1000 Teilen', 'fr' => 'Puzzle 1000 pièces', 'en' => '1000-piece puzzle'),
            'text' => array(
                'de' => 'Ein Puzzle mit 1000 Teilen für einen ruhigen Abend.',
                'fr' => 'Un puzzle de 1000 pièces pour une soirée tranquille.',
                'en' => 'A 1000-piece puzzle for a quiet evening.',
            )),
        array('group' => '20', 'cat' => 'kreativ', 'icon' => '🖼️', 'search' => 'Bilderrahmen',
            'title' => array('de' => 'Bilderrahmen', 'fr' => 'Cadre photo', 'en' => 'Picture frame'),
            'text' => array(
                'de' => 'Ein Bilderrahmen für ein Foto, das sonst in der Schublade liegt.',
                'fr' => 'Un cadre pour une photo qui resterait sinon dans un tiroir.',
                'en' => 'A frame for a photo that would otherwise stay in a drawer.',
            )),
        array('group' => '20', 'cat' => 'kalender', 'icon' => '🗓️', 'search' => 'Wandkalender 2027',
            'title' => array('de' => 'Wandkalender 2027', 'fr' => 'Calendrier mural 2027', 'en' => 'Wall calendar 2027'),
            'text' => array(
                'de' => 'Ein Wandkalender für 2027, für Küche oder Büro.',
                'fr' => 'Un calendrier mural pour 2027, pour la cuisine ou le bureau.',
                'en' => 'A 2027 wall calendar for the kitchen or the office.',
            )),
        array('group' => '30', 'cat' => 'alltag', 'icon' => '☕', 'search' => 'Tasse',
            'title' => array('de' => 'Tasse', 'fr' => 'Tasse', 'en' => 'Mug'),
            'text' => array(
                'de' => 'Eine Tasse mit einem Motiv, das im Büro stehen bleiben kann.',
                'fr' => 'Une tasse avec un motif qui peut rester au bureau.',
                'en' => 'A mug with a design that can stay at the office.',
            )),
        array('group' => '30', 'cat' => 'alltag', 'icon' => '🔌', 'search' => 'Powerbank',
            'title' => array('de' => 'Powerbank', 'fr' => 'Batterie externe', 'en' => 'Power bank'),
            'text' => array(
                'de' => 'Eine Powerbank, wenn das Handy am Nachmittag leer ist.',
                'fr' => "Une batterie externe pour quand le téléphone est vide l'après-midi.",
                'en' => 'A power bank for when the phone runs out in the afternoon.',
            )),
        array('group' => '30', 'cat' => 'wellness', 'icon' => '🕯️', 'search' => 'Duftkerze',
            'title' => array('de' => 'Duftkerze', 'fr' => 'Bougie parfumée', 'en' => 'Scented candle'),
            'text' => array(
                'de' => 'Eine Duftkerze für den Abend, wenn es draussen früh dunkel wird.',
                'fr' => 'Une bougie parfumée pour le soir, quand la nuit tombe tôt.',
                'en' => 'A scented candle for the evening, when it gets dark early.',
            )),
        array('group' => '30', 'cat' => 'spiele', 'icon' => '🎵', 'search' => 'Hitster',
            'title' => array('de' => 'Hitster', 'fr' => 'Hitster', 'en' => 'Hitster'),
            'text' => array(
                'de' => 'Hitster ist ein Musikspiel, bei dem man das Jahr des Liedes schätzt.',
                'fr' => "Hitster est un jeu musical : on estime l'année de la chanson.",
                'en' => 'Hitster is a music game where you guess the year of the song.',
            )),
        array('group' => '30', 'cat' => 'spiele', 'icon' => '🎉', 'search' => 'Dingsbums',
            'title' => array('de' => 'Partyspiel', 'fr' => 'Jeu de soirée', 'en' => 'Party game'),
            'text' => array(
                'de' => 'Dingsbums ist ein lautes Partyspiel, wenn die Runde schon warm ist.',
                'fr' => 'Dingsbums est un jeu de soirée bruyant, une fois le groupe lancé.',
                'en' => 'Dingsbums is a noisy party game, once the group has warmed up.',
            )),
        array('group' => '30', 'cat' => 'spiele', 'icon' => '🍽️', 'search' => 'Escape Dinner',
            'title' => array('de' => 'Escape Dinner', 'fr' => 'Escape Dinner', 'en' => 'Escape Dinner'),
            'text' => array(
                'de' => 'Escape Dinner verbindet Rätsel mit einem gemeinsamen Essen.',
                'fr' => 'Escape Dinner mêle des énigmes et un repas à partager.',
                'en' => 'Escape Dinner mixes puzzles with a shared meal.',
            )),
        array('group' => '30', 'cat' => 'spiele', 'icon' => '🔍', 'search' => 'Murdoku',
            'title' => array('de' => 'Murdoku', 'fr' => 'Murdoku', 'en' => 'Murdoku'),
            'text' => array(
                'de' => 'Murdoku ist ein Rätselbuch für jemanden, der gern kombiniert.',
                'fr' => "Murdoku est un livre d'énigmes pour quelqu'un qui aime relier les indices.",
                'en' => 'Murdoku is a puzzle book for someone who likes connecting clues.',
            )),
        array('group' => '30', 'cat' => 'kreativ', 'icon' => '🏛️', 'search' => '3D Puzzle',
            'title' => array('de' => '3D-Puzzle', 'fr' => 'Puzzle 3D', 'en' => '3D puzzle'),
            'text' => array(
                'de' => 'Ein 3D-Puzzle, das danach als kleines Objekt im Regal bleibt.',
                'fr' => "Un puzzle 3D qui reste ensuite comme un petit objet sur l'étagère.",
                'en' => 'A 3D puzzle that stays on the shelf as a small object afterwards.',
            )),
        array('group' => '30', 'cat' => 'kreativ', 'icon' => '🎨', 'search' => 'Ausmalbuch',
            'title' => array('de' => 'Ausmalbuch', 'fr' => 'Livre de coloriage', 'en' => 'Colouring book'),
            'text' => array(
                'de' => 'Ein Ausmalbuch für Erwachsene, für eine Stunde ohne Bildschirm.',
                'fr' => 'Un livre de coloriage pour adultes, pour une heure sans écran.',
                'en' => 'A colouring book for adults, for an hour away from a screen.',
            )),
        array('group' => '30', 'cat' => 'kreativ', 'icon' => '✂️', 'search' => 'Bastelset',
            'title' => array('de' => 'Bastelset', 'fr' => 'Kit de bricolage', 'en' => 'Craft kit'),
            'text' => array(
                'de' => 'Ein Bastelset für einen Nachmittag, an dem etwas Fertiges entsteht.',
                'fr' => 'Un kit de bricolage pour un après-midi qui aboutit à quelque chose de fini.',
                'en' => 'A craft kit for an afternoon that ends with something finished.',
            )),
        array('group' => '30', 'cat' => 'kreativ', 'icon' => '🖍️', 'search' => 'Kreativset',
            'title' => array('de' => 'Kreativset', 'fr' => 'Kit créatif', 'en' => 'Creative kit'),
            'text' => array(
                'de' => 'Ein Kreativset für jemanden, der gern mit den Händen arbeitet.',
                'fr' => 'Un kit créatif pour quelqu’un qui aime travailler de ses mains.',
                'en' => 'A creative kit for someone who likes working with their hands.',
            )),
        array('group' => '30', 'cat' => 'kreativ', 'icon' => '🌿', 'search' => 'LEGO Botanicals',
            'title' => array('de' => 'LEGO Botanicals', 'fr' => 'LEGO Botanicals', 'en' => 'LEGO Botanicals'),
            'text' => array(
                'de' => 'LEGO Botanicals ist eine kleine Pflanze aus Steinen, ohne Giessen.',
                'fr' => 'LEGO Botanicals, une petite plante en briques, sans arrosage.',
                'en' => 'LEGO Botanicals: a small plant made of bricks, with no watering.',
            )),
        array('group' => '30', 'cat' => 'kreativ', 'icon' => '📔', 'search' => 'Scrapbooking-Set',
            'title' => array('de' => 'Scrapbooking-Set', 'fr' => 'Kit de scrapbooking', 'en' => 'Scrapbooking kit'),
            'text' => array(
                'de' => 'Ein Scrapbooking-Set zum Einkleben von Fotos und Papieren.',
                'fr' => 'Un kit de scrapbooking pour coller photos et papiers.',
                'en' => 'A scrapbooking kit for sticking in photos and paper.',
            )),
        array('group' => '30', 'cat' => 'kalender', 'icon' => '🎁', 'search' => 'LEGO Adventskalender',
            'title' => array('de' => 'LEGO-Adventskalender', 'fr' => "Calendrier de l'Avent LEGO", 'en' => 'LEGO advent calendar'),
            'text' => array(
                'de' => 'Ein LEGO-Adventskalender mit einer kleinen Sache für jeden Tag.',
                'fr' => "Un calendrier de l'Avent LEGO, avec une petite construction par jour.",
                'en' => 'A LEGO advent calendar with a small build for each day.',
            )),
        array('group' => '50', 'cat' => 'alltag', 'icon' => '🥤', 'search' => 'Thermobecher',
            'title' => array('de' => 'Thermobecher', 'fr' => 'Mug isotherme', 'en' => 'Travel mug'),
            'text' => array(
                'de' => 'Ein Thermobecher, der das Getränk auf dem Weg warm hält.',
                'fr' => 'Un mug isotherme qui garde la boisson chaude en route.',
                'en' => 'A travel mug that keeps a drink warm on the way.',
            )),
        array('group' => '50', 'cat' => 'spiele', 'icon' => '🏡', 'search' => 'Dorfromantik',
            'title' => array('de' => 'Dorfromantik', 'fr' => 'Dorfromantik', 'en' => 'Dorfromantik'),
            'text' => array(
                'de' => 'Dorfromantik ist ein ruhiges Brettspiel, bei dem eine Landschaft wächst.',
                'fr' => 'Dorfromantik est un jeu de société calme, où un paysage se construit.',
                'en' => 'Dorfromantik is a calm board game where a landscape grows.',
            )),
        array('group' => '50', 'cat' => 'kalender', 'icon' => '🔐', 'search' => 'EXIT Adventskalender',
            'title' => array('de' => 'EXIT-Adventskalender', 'fr' => "Calendrier de l'Avent EXIT", 'en' => 'EXIT advent calendar'),
            'text' => array(
                'de' => 'Ein EXIT-Adventskalender mit einem Rätsel pro Tag bis Weihnachten.',
                'fr' => "Un calendrier de l'Avent EXIT, avec une énigme par jour jusqu'à Noël.",
                'en' => 'An EXIT advent calendar with one puzzle a day until Christmas.',
            )),
        array('group' => '50', 'cat' => 'kreativ', 'icon' => '📷', 'search' => 'Fotoalbum',
            'title' => array('de' => 'Fotoalbum', 'fr' => 'Album photo', 'en' => 'Photo album'),
            'text' => array(
                'de' => 'Ein Fotoalbum für Abzüge, die nicht nur auf dem Handy bleiben sollen.',
                'fr' => 'Un album photo pour des tirages qui ne doivent pas rester seulement sur le téléphone.',
                'en' => 'A photo album for prints that should not live only on a phone.',
            )),
        array('group' => '50', 'cat' => 'lesen', 'icon' => '📖', 'search' => 'Kochbuch',
            'title' => array('de' => 'Kochbuch', 'fr' => 'Livre de cuisine', 'en' => 'Cookbook'),
            'text' => array(
                'de' => 'Ein Kochbuch für jemanden, der gern neue Rezepte ausprobiert.',
                'fr' => "Un livre de cuisine pour quelqu'un qui aime essayer de nouvelles recettes.",
                'en' => 'A cookbook for someone who likes trying new recipes.',
            )),
    );
}

function ideas2_area_slugs() {
    return array('spiele', 'wellness', 'buero', 'kreativ', 'lesen', 'alltag', 'kalender');
}

function ideas2_price_slugs() {
    return array('10', '20', '30', '50');
}

function ideas2_filter_options($name, $text) {
    $options = array(array('', $text['filter_all']));
    if ($name === 'bereich') {
        foreach (ideas2_area_slugs() as $slug) {
            $options[] = array($slug, $text['cat_' . $slug]);
        }
        return $options;
    }
    foreach (ideas2_price_slugs() as $slug) {
        $options[] = array($slug, $text['filter_price_' . $slug]);
    }
    return $options;
}

function ideas2_filter_group($name, $label, $options) {
    $label_id = 'ideas-filter-' . $name;
    $html = '<div class="ideas-filter-row">';
    $html .= '<span class="ideas-filter-label" id="' . h($label_id) . '">' . h($label) . '</span>';
    $html .= '<div class="ideas-filter-chips" role="radiogroup" aria-labelledby="' . h($label_id) . '">';
    foreach ($options as $index => $option) {
        $html .= '<button type="button" class="ideas-filter-chip" role="radio"'
            . ' data-filter="' . h($name) . '" data-value="' . h($option[0]) . '"'
            . ' aria-checked="' . ($index === 0 ? 'true' : 'false') . '">'
            . h($option[1]) . '</button>';
    }
    $html .= '</div></div>';
    return $html;
}

function ideas2_filter($text) {
    $count = count(ideas2_items());
    $label = str_replace('{n}', (string) $count, $text['count_many']);
    $html = '<div class="ideas-filter" data-ideas-filter>';
    $html .= ideas2_filter_group('bereich', $text['filter_bereich'], ideas2_filter_options('bereich', $text));
    $html .= ideas2_filter_group('preis', $text['filter_preis'], ideas2_filter_options('preis', $text));
    $html .= '<div class="ideas-filter-status" aria-live="polite" aria-atomic="true">';
    $html .= '<p class="ideas-filter-count" data-ideas-count data-one="' . h($text['count_one']) . '" data-many="' . h($text['count_many']) . '">' . h($label) . '</p>';
    $html .= '<p class="ideas-filter-empty" data-ideas-empty hidden>' . h($text['empty']) . ' ';
    $html .= '<a class="ideas-filter-reset" href="' . h(i18n_href('wichtel-ideen')) . '" data-ideas-reset>' . h($text['reset']) . '</a></p>';
    $html .= '</div></div>';
    return $html;
}

function ideas2_card_html($item, $lang, $text) {
    return '<div class="idea-card" data-bereich="' . h($item['cat']) . '" data-preis="' . h($item['group']) . '">'
        . '<span class="idea-icon" aria-hidden="true">' . h($item['icon']) . '</span>'
        . '<span class="idea-category">' . h($text['cat_' . $item['cat']]) . '</span>'
        . '<h3 class="idea-title">' . h(ideas2_pick($item['title'], $lang)) . '</h3>'
        . '<p class="idea-description">' . h(ideas2_pick($item['text'], $lang)) . '</p>'
        . '<div class="idea-price">' . h($text['band_' . $item['group']]) . '</div>'
        . ideas2_card_link($item['search'])
        . '</div>';
}

function ideas2_page() {
    $lang = ideas2_lang();
    $text = ideas2_texts();
    $base = function_exists('canonical_base_url') ? canonical_base_url() : 'https://xn--wichtl-gua.ch';
    $urls = array(
        'de' => $base . '/wichtel-ideen',
        'fr' => $base . '/fr/wichtel-ideen',
        'en' => $base . '/en/wichtel-ideen',
    );
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $text['h1'],
        'description' => $text['schema'],
        'author' => array('@type' => 'Organization', 'name' => 'Wichtlä.ch'),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => 'Wichtlä.ch',
            'url' => 'https://xn--wichtl-gua.ch/',
        ),
        'url' => $urls[$lang],
        'inLanguage' => $lang,
        'mainEntityOfPage' => $urls[$lang],
    );
    $html = '<script type="application/ld+json">'
        . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        . '</script>';
    $html .= '<div class="content-page">';
    $html .= '<div class="breadcrumb"><a href="' . h(i18n_href('index')) . '">' . h($text['home']) . '</a> / ' . h($text['crumb']) . '</div>';
    $html .= ideas2_banner();
    $html .= '<header class="page-header"><h1 class="page-title">' . h($text['h1']) . '</h1>';
    $html .= '<p class="page-subtitle">' . h($text['subtitle']) . '</p></header>';
    $html .= '<article><p class="of-draft-intro">' . h($text['intro']) . '</p>';
    $html .= '<div class="ideas-catalog" data-ideas-catalog>';
    $html .= ideas2_filter($text);
    foreach (ideas2_price_slugs() as $group) {
        $html .= '<section class="content-section ideas-band" data-preis="' . h($group) . '">';
        $html .= '<h2>' . h($text['band_' . $group]) . '</h2>';
        $html .= '<p>' . h($text['lead_' . $group]) . '</p>';
        $html .= '<div class="ideas-grid">';
        foreach (ideas2_items() as $item) {
            if ($item['group'] !== $group) {
                continue;
            }
            $html .= ideas2_card_html($item, $lang, $text);
        }
        $html .= '</div></section>';
    }
    $html .= '</div>';
    $html .= '<section class="content-section"><div class="highlight-box">';
    $html .= '<h3>' . h($text['tip_title']) . '</h3><p>' . h($text['tip']) . '</p>';
    $html .= '</div></section>';
    $html .= ideas2_kept();
    $cta = $text['cta'];
    if (function_exists('premium_feature_enabled') && premium_feature_enabled()) {
        $cta = t('premium.extra.cta');
    }
    $html .= '<div class="cta-section"><h2>' . h($text['cta_title']) . '</h2><p>' . h($cta) . '</p>';
    $html .= '<a href="' . h(i18n_href('create_group')) . '" class="cta-button-white">' . h($text['cta_button']) . '</a></div>';
    $html .= '</article>';
    $html .= ideas2_giftcard();
    $html .= ideas2_note();
    $html .= '</div>';
    $html .= '<script src="/js/ideas-filter.js" defer></script>';
    return $html;
}

