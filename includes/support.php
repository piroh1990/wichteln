<?php

/**
 * Freiwillige Unterstützung über einen öffentlichen Zahlungslink.
 * Kein Geheimnis: ist SUPPORT_URL leer, bleibt alles unsichtbar.
 */

function support_load_config() {
    if (defined('SUPPORT_URL') || defined('DB_HOST')) {
        return;
    }
    $config = __DIR__ . '/config.php';
    if (is_file($config)) {
        require_once $config;
    }
}

function support_normalize_url($raw) {
    if (!is_string($raw)) {
        return '';
    }
    $url = trim($raw);
    if ($url === '') {
        return '';
    }
    if (preg_match('@\Ahttps://[A-Za-z0-9.\-]+(?::[0-9]{1,5})?(?:/[A-Za-z0-9._~:/?#\[\]!$&\'()*+,;=%-]*)?\z@', $url) !== 1) {
        return '';
    }
    return $url;
}

function support_url() {
    support_load_config();
    $raw = (defined('SUPPORT_URL') && is_string(SUPPORT_URL)) ? SUPPORT_URL : '';
    return support_normalize_url($raw);
}

function support_resolve_url($raw = null) {
    if ($raw === null) {
        return support_url();
    }
    return support_normalize_url($raw);
}

function support_enabled() {
    return support_url() !== '';
}

function support_status_code($raw = null) {
    return support_resolve_url($raw) === '' ? 404 : 200;
}

function support_provider_name() {
    support_load_config();
    $name = 'Stripe';
    if (defined('SUPPORT_PROVIDER_NAME') && is_string(SUPPORT_PROVIDER_NAME)) {
        $trimmed = trim(SUPPORT_PROVIDER_NAME);
        if (preg_match('/\A[A-Za-z0-9][A-Za-z0-9 ._-]{0,39}\z/', $trimmed) === 1) {
            $name = $trimmed;
        }
    }
    return $name;
}

function support_provider_is_stripe() {
    return strcasecmp(support_provider_name(), 'Stripe') === 0;
}

function support_places() {
    return array('reveal', 'footer', 'about', 'page');
}

function support_place($place) {
    $place = (string) $place;
    if (!in_array($place, support_places(), true)) {
        return 'page';
    }
    return $place;
}

function support_track_js($place) {
    $place = support_place($place);
    return "if(window._paq){window._paq.push(['trackEvent','Support','Click','" . $place . "']);}";
}

function support_heart_svg() {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" focusable="false">'
        . '<path fill="currentColor" d="M12 20.5s-6.4-4.1-8.8-7.7C1.4 10.2 1.8 6.8 4.5 5.4c2.1-1.1 4.2-.4 5.6 1.4L12 8.6l1.9-1.8c1.4-1.8 3.5-2.5 5.6-1.4 2.7 1.4 3.1 4.8 1.3 7.4-2.4 3.6-8.8 7.7-8.8 7.7z"/>'
        . '</svg>';
}

function support_external_anchor($place, $label, $class, $raw = null) {
    $url = support_resolve_url($raw);
    if ($url === '') {
        return '';
    }
    $place = support_place($place);
    return '<a class="' . h($class) . '" href="' . h($url) . '" target="_blank" rel="noopener noreferrer" onclick="' . support_track_js($place) . '">' . h($label) . '</a>';
}

function support_card_html($place, $raw = null) {
    $url = support_resolve_url($raw);
    if ($url === '') {
        return '';
    }
    $button = support_external_anchor($place, t('support.button'), 'button support-button', $url);
    return '<section class="support-card" aria-label="' . h(t('support.about.heading')) . '">'
        . '<span class="support-card-icon" aria-hidden="true">' . support_heart_svg() . '</span>'
        . '<div class="support-card-body">'
        . '<p class="support-card-text">' . h(t('support.card')) . '</p>'
        . '<p class="support-card-actions">' . $button . '</p>'
        . '<p class="support-note">' . h(t('support.fineprint')) . '</p>'
        . '</div>'
        . '</section>';
}

function support_about_html($raw = null) {
    $url = support_resolve_url($raw);
    if ($url === '') {
        return '';
    }
    $button = support_external_anchor('about', t('support.button'), 'button support-button', $url);
    return '<section class="content-section support-about">'
        . '<h2>' . h(t('support.about.heading')) . '</h2>'
        . '<p>' . h(t('support.about.text')) . '</p>'
        . '<p class="support-page-action">' . $button . '</p>'
        . '<p class="support-note">' . h(t('support.fineprint')) . '</p>'
        . '</section>';
}

function support_footer_html($raw = null) {
    if (support_resolve_url($raw) === '' || !function_exists('i18n_href') || !function_exists('t')) {
        return '';
    }
    return '<li><a href="' . h(i18n_href('unterstuetzen')) . '" onclick="' . support_track_js('footer') . '">' . h(t('footer.support')) . '</a></li>';
}

function support_unavailable_body() {
    return "<!DOCTYPE html>\n<html lang=\"de\"><head><meta charset=\"UTF-8\"><title>404</title></head><body><p>404</p></body></html>\n";
}
