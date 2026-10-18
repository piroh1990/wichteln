<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / <?php echo h(t('footer.support')); ?>
        </div>

        <header class="page-header">
            <h1 class="page-title">Freiwillig unterstützen</h1>
            <p class="page-subtitle">Ein Hobby aus der Schweiz</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Warum ich die Seite gebaut habe</h2>
                <p>
                    wichtlä.ch ist mein Hobby. Ich habe die Seite selbst gebaut, weil mir keine der bestehenden Wichtelseiten richtig gefallen hat: zu viel Werbung, zu kompliziert, oft mit Anmeldung. Ich wollte etwas Einfaches und Sauberes.
                </p>
                <p>
                    Server und Betrieb kosten etwas Geld und viel Freizeit. Wenn du magst, hilft ein freiwilliger Beitrag dabei, dass die Seite gratis und werbefrei bleibt.
                </p>
            </section>

            <section class="content-section">
                <h2>Wohin die Zahlung geht</h2>
                <p>
                    Die Zahlung geht an <strong>Raths IT</strong>, meine Einzelfirma. Abgewickelt wird sie über <?php echo h(support_provider_name()); ?>, zum Beispiel mit TWINT oder Karte. Den Betrag legst du dort selbst fest.
                </p>
                <p>
                    Ein Beitrag ist freiwillig und keine Voraussetzung, um eine Funktion zu nutzen.
                </p>
                <p>
                    Mit dem Klick verlässt du wichtlä.ch. Ich erhalte keine Zahlungsdaten, und es gehen keine Daten aus Wichtelgruppen an den Zahlungsdienst.
                </p>
                <p class="support-page-action"><?php echo support_external_anchor('page', t('support.button'), 'button support-button'); ?></p>
                <p class="support-note"><?php echo h(t('support.fineprint')); ?></p>
                <p>
                    Mehr zur Bearbeitung der Zahlungsdaten steht in der <a href="<?php echo h(i18n_href('datenschutz')); ?>#freiwillige-unterstuetzung">Datenschutzerklärung</a>.
                </p>
            </section>
        </article>
    </div>
