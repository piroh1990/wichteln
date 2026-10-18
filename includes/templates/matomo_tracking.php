<?php
/**
 * Einwilligung vor dem Matomo-Skript.
 *
 * Bis zur Einwilligung kein Tracking-Cookie (disableCookies und
 * requireCookieConsent). Nach «Akzeptieren» Cookies, nach «Ablehnen» weiter
 * ohne Cookies.
 */

$configFile = __DIR__ . '/../config.php';
if (file_exists($configFile)) {
    require_once $configFile;
}
require_once __DIR__ . '/../consent.php';

$consentConfig = consent_client_config();
$consentJson = json_encode(
    $consentConfig,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
);
if ($consentJson === false) {
    $consentJson = '{}';
}
?>
<script>
(function () {
    var cfg = <?php echo $consentJson; ?>;
    var CONSENT_COOKIE = 'wichteln_consent';

    function readCookie(name) {
        var parts = ('; ' + document.cookie).split('; ' + name + '=');
        if (parts.length < 2) {
            return '';
        }
        return decodeURIComponent(parts.pop().split(';').shift() || '');
    }

    function writeCookie(name, value, days) {
        var expires = '';
        if (typeof days === 'number') {
            var date = new Date();
            date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
            expires = ';expires=' + date.toUTCString();
        }
        var secure = window.location.protocol === 'https:' ? ';Secure' : '';
        document.cookie = name + '=' + encodeURIComponent(value) + expires + ';path=/;SameSite=Lax' + secure;
    }

    function hideBanner() {
        var banner = document.getElementById('cookie-banner');
        if (!banner) {
            return;
        }
        banner.style.display = 'none';
        banner.setAttribute('hidden', 'hidden');
    }

    function showBanner() {
        var banner = document.getElementById('cookie-banner');
        if (!banner) {
            return;
        }
        banner.removeAttribute('hidden');
        banner.style.display = 'block';
    }

    function matomoPageUrl() {
        var url;
        var keys = ['token', 'master_token', 'api_token', 'admin_token', 'invite_token'];
        var i;
        try {
            url = new URL(window.location.href);
        } catch (e) {
            return window.location.pathname || '/';
        }
        for (i = 0; i < keys.length; i++) {
            url.searchParams.delete(keys[i]);
        }
        return url.pathname + url.search + url.hash;
    }

    function loadMatomo(withCookies) {
        if (!cfg.matomoUrl || !cfg.matomoSiteId) {
            return;
        }
        var _paq = window._paq = window._paq || [];
        if (window.__wichtelnMatomoLoaded) {
            if (withCookies) {
                _paq.push(['setCookieConsentGiven']);
            } else {
                _paq.push(['forgetCookieConsentGiven']);
                _paq.push(['disableCookies']);
            }
            return;
        }
        window.__wichtelnMatomoLoaded = true;
        if (withCookies) {
            _paq.push(['requireCookieConsent']);
            _paq.push(['setCookieConsentGiven']);
        } else {
            _paq.push(['disableCookies']);
            _paq.push(['requireCookieConsent']);
        }
        _paq.push(['setReferrerUrl', '']);
        _paq.push(['setCustomUrl', matomoPageUrl()]);
        _paq.push(['setRequestMethod', 'POST']);
        _paq.push(['trackPageView']);
        _paq.push(['enableLinkTracking']);
        _paq.push(['setTrackerUrl', cfg.matomoUrl + 'matomo.php']);
        _paq.push(['setSiteId', String(cfg.matomoSiteId)]);
        var d = document;
        var g = d.createElement('script');
        var s = d.getElementsByTagName('script')[0];
        g.async = true;
        g.src = cfg.matomoUrl + 'matomo.js';
        if (s && s.parentNode) {
            s.parentNode.insertBefore(g, s);
        } else {
            d.head.appendChild(g);
        }
    }

    function applyLocalDecision(decision) {
        if (decision === 'accepted') {
            loadMatomo(true);
        } else {
            loadMatomo(false);
        }
        hideBanner();
    }

    window.wichtelnSetConsent = function (decision) {
        if (decision !== 'accepted' && decision !== 'rejected') {
            return;
        }
        writeCookie(CONSENT_COOKIE, decision, 180);
        applyLocalDecision(decision);
    };

    window.wichtelnRevokeConsent = function () {
        writeCookie(CONSENT_COOKIE, 'rejected', 180);
        if (window._paq) {
            window._paq.push(['forgetCookieConsentGiven']);
            window._paq.push(['disableCookies']);
        }
        window.location.reload();
    };

    function boot() {
        var revoke = document.getElementById('consent-revoke');
        if (revoke && !revoke.__wichtelnBound) {
            revoke.__wichtelnBound = true;
            revoke.addEventListener('click', function () {
                window.wichtelnRevokeConsent();
            });
        }

        var stored = readCookie(CONSENT_COOKIE);
        if (stored === 'accepted' || stored === 'rejected') {
            applyLocalDecision(stored);
            return;
        }
        showBanner();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
