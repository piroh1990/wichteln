<?php
/**
 * Alte Entwurfsadresse. Der Inhalt liegt auf /wichtel-ideen.
 */

function ideas_draft_redirect_target($get) {
    if (!is_array($get)) {
        $get = array();
    }
    $lang = '';
    if (isset($get['lang'])) {
        $candidate = strtolower(trim((string) $get['lang']));
        if ($candidate === 'fr' || $candidate === 'en') {
            $lang = $candidate;
        }
    }
    $target = ($lang === '') ? '/wichtel-ideen' : '/' . $lang . '/wichtel-ideen';
    $query = $get;
    unset($query['lang']);
    if ($query) {
        $target .= '?' . http_build_query($query);
    }
    return $target;
}

if (!defined('WICHTEL_IDEEN2_LIBRARY')) {
    header('Location: ' . ideas_draft_redirect_target($_GET), true, 301);
    exit;
}
