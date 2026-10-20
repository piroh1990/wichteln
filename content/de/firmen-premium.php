<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / <?php echo h(t('footer.premium')); ?>
        </div>

        <header class="page-header">
            <h1 class="page-title">Premium Firmen-Wichteln</h1>
            <p class="page-subtitle">Guthaben für Premium-Gruppen</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Wofür ich das gebaut habe</h2>
                <p>
                    Die normale Gruppe bleibt gratis. Auch Firmen können ganz normal und gratis wichteln. Die Zusatzfunktionen sind optional. Premium ist für Vereine und Firmen, die mehrere Gruppen führen oder mehr Funktionen wünschen: Abteilungen, Standorte, ein Anlass für die Lernenden und einer für das Team.
                </p>
                <p>
                    Ein Paket ist ein Guthaben an Premium-Gruppen und läuft nicht ab. Jede Durchführung zählt als ein Premium-Wichteln, auch die Kopie fürs nächste Jahr. Es braucht kein Konto. Du verbindest das Guthaben mit der Gruppe, die du gerade anlegst. Ist es aufgebraucht, kannst du ein weiteres Paket kaufen. Ein zweiter Kauf mit derselben E-Mail kommt zum Guthaben dazu.
                </p>
            </section>

            <section class="content-section">
                <h2>Was dabei ist</h2>
                <ul>
<?php foreach (premium_feature_keys() as $feature_key): ?>
                    <li><?php echo h(t($feature_key)); ?></li>
<?php endforeach; ?>
                </ul>
                <?php echo premium_pricing_cards_html(); ?>
                <p><?php echo h(t('premium.not_included')); ?></p>
            </section>

            <section class="content-section">
                <h2>So wird eine Gruppe Premium</h2>
                <p>
                    Beim Anlegen der Gruppe oder später im Admin: trag die E-Mail der Lizenz ein. Ich schicke einen Bestätigungslink und einen sechsstelligen Code an diese Adresse. Erst nach der Bestätigung hängt die Gruppe an der Lizenz. Oder du gibst den Lizenzcode direkt ein.
                </p>
                <p>
                    Logo und Farben liegen einmal auf der Lizenz und gelten für jede neue Gruppe. Den Übersichtslink dazu schicke ich mit dem Code.
                </p>
                <p>
                    Noch keine Lizenz? Unten kannst du mit Karte oder TWINT zahlen, oder ich stelle eine Rechnung.
                </p>
                <p>
                    Mehr zur Bearbeitung steht in der <a href="<?php echo h(i18n_href('datenschutz')); ?>#firmen-premium">Datenschutzerklärung</a>.
                </p>
            </section>
            <?php echo premium_purchase_section_html(); ?>
        </article>
    </div>
