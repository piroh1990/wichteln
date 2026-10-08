<?php
/**
 * Einwilligung vor Drittscripts.
 *
 * Matomo: bis zur Einwilligung kein Tracking-Cookie (disableCookies und
 * requireCookieConsent). Nach «Akzeptieren» Cookies, nach «Ablehnen» weiter
 * ohne Cookies.
 *
 * AdSense: adsbygoogle.js nur nach Einwilligung über die Google-CMP
 * (Funding Choices) und nur bei echter ca-pub-ID. Ohne CMP kein AdSense-Skript.
 * Der eigene Hinweis schaltet keine Werbung frei.
 *
 * Die CMP-Nachricht selbst richtet der Betreiber in AdSense unter
 * Privacy & messaging für Schweiz, EWR und UK ein.
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
    var settled = false;

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
        if (cfg.cmpConfigured) {
            return;
        }
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

    function loadAdsense() {
        if (window.__wichtelnAdsLoaded) {
            return;
        }
        if (!cfg.cmpConfigured || cfg.testing || !cfg.adsEnabled || !cfg.client) {
            return;
        }
        if (!document.querySelector('ins.adsbygoogle')) {
            return;
        }
        window.__wichtelnAdsLoaded = true;
        var script = document.createElement('script');
        script.async = true;
        script.crossOrigin = 'anonymous';
        script.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(cfg.client);
        script.onload = function () {
            var nodes = document.querySelectorAll('ins.adsbygoogle');
            var i;
            for (i = 0; i < nodes.length; i++) {
                (window.adsbygoogle = window.adsbygoogle || []).push({});
            }
        };
        document.head.appendChild(script);
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
        if (cfg.cmpConfigured) {
            return;
        }
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
        if (cfg.cmpConfigured && window.googlefc && typeof window.googlefc.showRevocationMessage === 'function') {
            settled = false;
            window.googlefc.showRevocationMessage();
            return;
        }
        window.location.reload();
    };

    function settleFromCmp(adsOk, analyticsOk) {
        if (settled) {
            return;
        }
        settled = true;
        writeCookie(CONSENT_COOKIE, analyticsOk ? 'accepted' : 'rejected', 180);
        if (window.__wichtelnMatomoLoaded) {
            window.location.reload();
            return;
        }
        loadMatomo(!!analyticsOk);
        if (adsOk) {
            loadAdsense();
        }
    }

    function purposeConsent(map, id) {
        if (!map) {
            return false;
        }
        return !!(map[id] || map[String(id)]);
    }

    function signalGooglefcPresent() {
        if (window.frames['googlefcPresent']) {
            return;
        }
        if (!document.body) {
            setTimeout(signalGooglefcPresent, 0);
            return;
        }
        var iframe = document.createElement('iframe');
        iframe.style.cssText = 'width:0;height:0;border:none;z-index:-1000;left:-1000px;top:-1000px;display:none';
        iframe.name = 'googlefcPresent';
        iframe.title = 'Google Consent';
        document.body.appendChild(iframe);
    }

    function readConsentMode() {
        var result = { ads: false, analytics: false, known: false };
        if (!window.googlefc || typeof window.googlefc.getGoogleConsentModeValues !== 'function' || !window.googlefc.ConsentModePurposeStatusEnum) {
            return result;
        }
        var values = window.googlefc.getGoogleConsentModeValues();
        var E = window.googlefc.ConsentModePurposeStatusEnum;
        if (!values || !E) {
            return result;
        }
        var granted = E.CONSENT_MODE_PURPOSE_STATUS_GRANTED;
        var denied = E.CONSENT_MODE_PURPOSE_STATUS_DENIED;
        var ad = values.adStoragePurposeConsentStatus;
        var analytics = values.analyticsStoragePurposeConsentStatus;
        if (ad === granted || ad === denied || analytics === granted || analytics === denied) {
            result.known = true;
        }
        result.ads = ad === granted;
        result.analytics = analytics === granted;
        if (ad === denied) {
            result.ads = false;
        }
        return result;
    }

    var tcfBound = false;

    function evaluateCmp() {
        var mode = readConsentMode();
        if (typeof window.__tcfapi !== 'function') {
            if (mode.known) {
                settleFromCmp(mode.ads, mode.analytics);
            }
            return;
        }
        if (tcfBound) {
            return;
        }
        tcfBound = true;
        window.__tcfapi('addEventListener', 2, function (tcData, success) {
            var current = readConsentMode();
            if (!success || !tcData) {
                if (current.known) {
                    settleFromCmp(current.ads, current.analytics);
                }
                return;
            }
            var status = tcData.eventStatus;
            if (status !== 'tcloaded' && status !== 'useractioncomplete') {
                return;
            }
            if (tcData.gdprApplies) {
                var purposes = tcData.purpose && tcData.purpose.consents;
                var vendors = tcData.vendor && tcData.vendor.consents;
                var storage = purposeConsent(purposes, 1);
                var vendorOk = !vendors || purposeConsent(vendors, 755);
                settleFromCmp(storage && vendorOk, storage);
                return;
            }
            if (current.known) {
                settleFromCmp(current.ads, current.analytics);
                return;
            }
            setTimeout(function () {
                var later = readConsentMode();
                if (later.known) {
                    settleFromCmp(later.ads, later.analytics);
                } else {
                    settleFromCmp(false, false);
                }
            }, 400);
        });
    }

    function startGoogleCmp() {
        if (!cfg.publisherId) {
            settleFromCmp(false, false);
            return;
        }
        window.googlefc = window.googlefc || {};
        window.googlefc.callbackQueue = window.googlefc.callbackQueue || [];
        window.googlefc.callbackQueue.push({
            CONSENT_DATA_READY: function () {
                evaluateCmp();
            }
        });
        window.googlefc.callbackQueue.push({
            CONSENT_MODE_DATA_READY: function () {
                evaluateCmp();
            }
        });

        var cmpLoaded = false;
        var script = document.createElement('script');
        script.async = true;
        script.src = 'https://fundingchoicesmessages.google.com/i/' + cfg.publisherId + '?ers=1';
        script.onload = function () {
            cmpLoaded = true;
            signalGooglefcPresent();
        };
        script.onerror = function () {
            settleFromCmp(false, false);
        };
        document.head.appendChild(script);

        setTimeout(function () {
            if (!cmpLoaded) {
                settleFromCmp(false, false);
            }
        }, 8000);
    }

    function boot() {
        var revoke = document.getElementById('consent-revoke');
        if (revoke && !revoke.__wichtelnBound) {
            revoke.__wichtelnBound = true;
            revoke.addEventListener('click', function () {
                window.wichtelnRevokeConsent();
            });
        }

        if (cfg.cmpConfigured) {
            hideBanner();
            startGoogleCmp();
            return;
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
