<?php
/**
 * Hinweis zur Matomo-Einwilligung.
 * Schaltet kein AdSense frei. Dafür ist die Google-CMP nötig
 * (GOOGLE_CMP_ENABLED und echte ca-pub-ID). Solange die CMP aktiv ist,
 * bleibt dieser Hinweis aus; die Nachricht kommt von Funding Choices.
 */
?>
<div id="cookie-banner" class="cookie-banner" hidden style="display: none;" role="dialog" aria-labelledby="cookie-banner-title" aria-describedby="cookie-banner-text">
    <div class="cookie-banner-content">
        <div class="cookie-banner-copy">
            <p id="cookie-banner-title" class="cookie-banner-title">Einwilligung zur Reichweitenmessung</p>
            <p id="cookie-banner-text" class="cookie-banner-text">
                Matomo wertet Besuche aus. «Akzeptieren» erlaubt ein Matomo-Cookie.
                «Ablehnen» lässt die Messung ohne Tracking-Cookies.
                Werbeanzeigen schaltet dieser Hinweis nicht frei.
                <a href="datenschutz.php">Datenschutz</a>
            </p>
        </div>
        <div class="cookie-banner-actions">
            <button type="button" class="cookie-banner-reject" onclick="wichtelnSetConsent('rejected')">Ablehnen</button>
            <button type="button" class="cookie-banner-accept" onclick="wichtelnSetConsent('accepted')">Akzeptieren</button>
        </div>
    </div>
</div>

<style>
.cookie-banner {
    position: fixed;
    left: 1rem;
    right: 1rem;
    bottom: 1rem;
    z-index: 9999;
    max-width: 46rem;
    margin: 0 auto;
    background: rgba(38, 70, 83, 0.98);
    color: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    padding: 1rem 1.1rem;
    font-size: 0.95rem;
}

.cookie-banner-content {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.cookie-banner-copy {
    flex: 1;
    min-width: 0;
}

.cookie-banner-title {
    margin: 0 0 0.35rem;
    font-size: 1rem;
    font-weight: 700;
}

.cookie-banner-text {
    margin: 0;
    line-height: 1.45;
}

.cookie-banner-text a {
    color: #06ffa5;
}

.cookie-banner-actions {
    display: flex;
    flex-shrink: 0;
    gap: 0.5rem;
}

.cookie-banner-accept,
.cookie-banner-reject {
    border-radius: 8px;
    padding: 0.55rem 0.9rem;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}

.cookie-banner-accept {
    background: #2a9d8f;
    color: #fff;
    border: 0;
}

.cookie-banner-reject {
    background: transparent;
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.7);
}

.cookie-banner-accept:hover,
.cookie-banner-reject:hover {
    transform: translateY(-1px);
}

@media (max-width: 700px) {
    .cookie-banner {
        left: 0.5rem;
        right: 0.5rem;
        bottom: 0.5rem;
    }

    .cookie-banner-content {
        flex-direction: column;
        align-items: stretch;
    }

    .cookie-banner-actions {
        justify-content: flex-end;
    }
}
</style>
