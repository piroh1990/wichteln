<?php
/**
 * Helfer für Google AdSense und die Einwilligung.
 *
 * Lädt selbst keine Konfiguration und kein Drittscript.
 * Eine CMP gilt nur als konfiguriert, wenn GOOGLE_CMP_ENABLED wahr ist
 * und GOOGLE_ADS_CLIENT eine echte ca-pub-ID ist (keine Platzhalter).
 */

if (!function_exists('google_ads_client_is_real')) {
    /**
     * Echte AdSense-Publisher-ID, z. B. ca-pub-1234567890123456.
     * Platzhalter mit X oder Buchstaben gelten nicht.
     *
     * @param mixed $client
     * @return bool
     */
    function google_ads_client_is_real($client) {
        if (!is_string($client)) {
            return false;
        }
        return preg_match('/^ca-pub-\d{10,20}$/', $client) === 1;
    }
}

if (!function_exists('google_ads_publisher_id')) {
    /**
     * ca-pub-… wird zur Funding-Choices-ID pub-….
     *
     * @param mixed $client
     * @return string
     */
    function google_ads_publisher_id($client) {
        if (!google_ads_client_is_real($client)) {
            return '';
        }
        return substr($client, 3);
    }
}

if (!function_exists('google_cmp_would_run')) {
    /**
     * @param mixed $enabled
     * @param mixed $client
     * @return bool
     */
    function google_cmp_would_run($enabled, $client) {
        return (bool) $enabled && google_ads_client_is_real($client);
    }
}

if (!function_exists('google_cmp_is_configured')) {
    /**
     * Google-CMP (Funding Choices) nur mit Schalter und echter Publisher-ID.
     *
     * @return bool
     */
    function google_cmp_is_configured() {
        $enabled = defined('GOOGLE_CMP_ENABLED') && GOOGLE_CMP_ENABLED;
        $client = defined('GOOGLE_ADS_CLIENT') ? GOOGLE_ADS_CLIENT : '';
        return google_cmp_would_run($enabled, $client);
    }
}

if (!function_exists('google_ads_testing')) {
    /**
     * @return bool
     */
    function google_ads_testing() {
        return defined('GOOGLE_ADS_TESTING') && GOOGLE_ADS_TESTING;
    }
}

if (!function_exists('google_ads_slot_active')) {
    /**
     * @param int $position
     * @return bool
     */
    function google_ads_slot_active($position) {
        $position = (int) $position;
        if ($position < 1 || $position > 3) {
            return false;
        }
        if (!defined('GOOGLE_ADS_ENABLED') || !GOOGLE_ADS_ENABLED) {
            return false;
        }
        $show = 'GOOGLE_ADS_SHOW_OPTION' . $position;
        return defined($show) && constant($show);
    }
}

if (!function_exists('consent_client_config')) {
    /**
     * Werte, die das First-Party-Skript im Browser braucht.
     * Die Publisher-ID geht nur mit konfigurierter CMP an den Client.
     *
     * @return array
     */
    function consent_client_config() {
        $client = defined('GOOGLE_ADS_CLIENT') ? GOOGLE_ADS_CLIENT : '';
        $matomoUrl = defined('MATOMO_URL') ? (string) MATOMO_URL : '';
        if ($matomoUrl !== '' && substr($matomoUrl, -1) !== '/') {
            $matomoUrl .= '/';
        }
        $cmp = google_cmp_is_configured();
        return array(
            'matomoUrl' => $matomoUrl,
            'matomoSiteId' => defined('MATOMO_SITE_ID') ? (string) MATOMO_SITE_ID : '',
            'client' => $cmp ? $client : '',
            'publisherId' => $cmp ? google_ads_publisher_id($client) : '',
            'cmpConfigured' => $cmp,
            'adsEnabled' => defined('GOOGLE_ADS_ENABLED') && GOOGLE_ADS_ENABLED,
            'testing' => google_ads_testing(),
        );
    }
}
