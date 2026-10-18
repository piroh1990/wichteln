<?php
/**
 * Werte für die Matomo-Einwilligung im Browser.
 *
 * Lädt selbst keine Konfiguration und kein Drittscript.
 */

if (!function_exists('consent_client_config')) {
    /**
     * Werte, die das First-Party-Skript im Browser braucht.
     *
     * @return array
     */
    function consent_client_config() {
        $matomoUrl = defined('MATOMO_URL') ? (string) MATOMO_URL : '';
        if ($matomoUrl !== '' && substr($matomoUrl, -1) !== '/') {
            $matomoUrl .= '/';
        }
        return array(
            'matomoUrl' => $matomoUrl,
            'matomoSiteId' => defined('MATOMO_SITE_ID') ? (string) MATOMO_SITE_ID : '',
        );
    }
}
