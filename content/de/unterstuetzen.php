<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / <?php echo h(t('footer.support')); ?>
        </div>

        <header class="page-header">
            <h1 class="page-title">Freiwillig unterstützen</h1>
            <p class="page-subtitle">Gratis, ohne Werbung und ohne Anmeldung</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Die Seite bleibt gratis</h2>
                <p>
                    <strong>wichtlä.ch</strong> ist gratis, ohne Werbung und ohne Anmeldung. Das soll so bleiben.
                </p>
                <p>
                    Ein Beitrag ist freiwillig. Er ist keine Voraussetzung, um eine Funktion zu nutzen, und es gibt keine Gegenleistung. Ob du etwas beiträgst oder nicht, ändert nichts am Wichteln.
                </p>
            </section>

            <section class="content-section">
                <h2>Wohin die Zahlung geht</h2>
                <p>
                    Wenn du magst, kannst du freiwillig etwas zum Betrieb der Seite beitragen. Die Zahlung geht an <strong>Raths IT</strong>, die Einzelfirma von Patrick Raths. Abgewickelt wird sie über <?php echo h(support_provider_name()); ?>, zum Beispiel mit TWINT oder Karte. Den Betrag legst du dort selbst fest.
                </p>
                <p>
                    Mit dem Klick verlässt du wichtlä.ch. wichtlä.ch erhält keine Zahlungsdaten und gibt keine Daten aus Wichtelgruppen weiter.
                </p>
                <p class="support-page-action"><?php echo support_external_anchor('page', t('support.button'), 'button support-button'); ?></p>
                <p class="support-note"><?php echo h(t('support.fineprint')); ?></p>
                <p>
                    Mehr zur Bearbeitung der Zahlungsdaten steht in der <a href="<?php echo h(i18n_href('datenschutz')); ?>#freiwillige-unterstuetzung">Datenschutzerklärung</a>.
                </p>
            </section>
        </article>
    </div>
