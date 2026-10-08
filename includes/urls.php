<?php

/**
 * Feste Basis-URL. Der Host-Header wird absichtlich nicht verwendet.
 */
function canonical_base_url() {
    $fallback = 'https://xn--wichtl-gua.ch';
    if (!defined('CANONICAL_BASE_URL') || !is_string(CANONICAL_BASE_URL)) {
        return $fallback;
    }
    $url = rtrim(CANONICAL_BASE_URL, '/');
    if (preg_match('#^https://[A-Za-z0-9.\-:\[\]]+$#', $url) !== 1) {
        return $fallback;
    }
    return $url;
}
