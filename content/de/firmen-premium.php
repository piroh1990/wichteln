<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / <?php echo h(t('footer.premium')); ?>
        </div>

        <header class="page-header">
            <h1 class="page-title">Premium Firmen-Wichteln</h1>
            <p class="page-subtitle">Mehrere Gruppen, eine Lizenz</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Wofür ich das gebaut habe</h2>
                <p>
                    Die normale Gruppe bleibt gratis. Premium ist für Vereine und Firmen, die in einer Saison mehrere Gruppen führen: Abteilungen, Standorte, ein Anlass für die Lernenden und einer für das Team.
                </p>
                <p>
                    Eine Lizenz gilt für die laufende Saison und deckt so viele Gruppen ab, wie das Paket hergibt. Es braucht kein Konto. Du verbindest die Lizenz mit der Gruppe, die du gerade anlegst.
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
        </article>
    </div>
