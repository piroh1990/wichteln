<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / Über uns
        </div>

        <header class="page-header">
            <h1 class="page-title">Über uns</h1>
            <p class="page-subtitle">Warum ich wichtlä.ch gebaut habe</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Worum es mir geht</h2>
                <p>
                    Ich möchte, dass Wichteln einfach ist. Kein Zettelchaos, keine App und keine versteckten Kosten: Du erstellst eine Gruppe, lädst alle ein, und die Namen werden fair ausgelost.
                </p>
                <p>
                    Das passt für die Familie, den Freundeskreis oder das Team im Geschäft. Das normale Wichteln ist gratis, ohne Werbung und ohne Konto.<?php if (function_exists('premium_feature_enabled') && premium_feature_enabled()): ?> <?php echo str_replace('Zusatzfunktionen', '<a href="' . h(i18n_href('firmen-premium')) . '">Zusatzfunktionen</a>', t('premium.extra.about')); ?><?php endif; ?>
                </p>
            </section>

            <section class="content-section">
                <h2>Wer dahintersteckt</h2>
                <p>
                    Ich bin Patrick Raths, und wichtlä.ch ist mein Hobby. Ich habe die Seite selbst gebaut und betreibe sie über meine Firma <a href="https://raths-it.ch"><strong>Raths IT</strong></a> in der <strong>Schweiz</strong>. Die Idee kam aus einem Problem, das wohl viele kennen: Jedes Jahr schreibt man Zettel, wirft sie in einen Hut und hofft, dass niemand sich selbst zieht. Wenn nicht alle am selben Ort sind, wird es schnell kompliziert.
                </p>
                <p>
                    Deshalb habe ich wichtlä.ch gebaut. Es ist ein schlankes Tool ohne Schnickschnack und ohne Konto, und ich sammle nur die Daten, die es fürs Wichteln braucht.
                </p>
            </section>

            <section class="content-section">
                <h2>Warum Wichtlä.ch?</h2>
                <p>Darauf habe ich beim Bauen geachtet:</p>

                <ul>
                    <li><?php if (function_exists('premium_feature_enabled') && premium_feature_enabled()): ?><strong>Gratis:</strong> Das normale Wichteln bleibt ohne Werbung und ohne Konto. <?php echo str_replace('Zusatzfunktionen', '<a href="' . h(i18n_href('firmen-premium')) . '">Zusatzfunktionen</a>', t('premium.extra.bullet')); ?><?php else: ?><strong>Gratis:</strong> Das normale Wichteln bleibt ohne Werbung und ohne Konto.<?php endif; ?></li>
                    <li><strong>Kein Konto nötig:</strong> Du kannst sofort loslegen.</li>
                    <li><strong>Datenschutz nach Schweizer DSG:</strong> Deine Daten behandle ich vertraulich.</li>
                    <li><strong>Einfach:</strong> In weniger als zwei Minuten ist deine Gruppe erstellt und die Einladungen sind verschickt.</li>
                    <li><strong>Faire Auslosung:</strong> Die Zuteilung ist zufällig, und du kannst Ausschlüsse festlegen.</li>
                    <li><strong>Los per E-Mail:</strong> Alle erhalten ihr Los bequem per E-Mail.</li>
                    <li><strong>Für alle Geräte:</strong> Es läuft auf Smartphone, Tablet und Computer, ohne App.</li>
                </ul>
            </section>

            <?php
            require_once dirname(__DIR__, 2) . '/includes/support.php';
            echo support_about_html();
            ?>

            <section class="content-section">
                <h2>Kontakt</h2>
                <p>
                    Hast du Fragen, Ideen oder Feedback? Ich freue mich über jede Nachricht. Schreib mir einfach an:
                </p>
                <p>
                    <strong>E-Mail:</strong> <span id="email-address"></span>
                </p>

                <script>
                    // E-Mail-Adresse vor Spambots schützen
                    const user = 'kontakt';
                    const displayDomain = 'wichtlä.ch';
                    const mailDomain = 'xn--wichtl-gua.ch';
                    const email = user + '@' + displayDomain;
                    const emailElement = document.getElementById('email-address');
                    const link = document.createElement('a');
                    link.href = 'mailto:' + user + '@' + mailDomain;
                    link.textContent = email;
                    emailElement.appendChild(link);
                </script>

                <p>
                    Weitere rechtliche Infos findest du im <a href="<?php echo h(i18n_href('impressum')); ?>">Impressum</a> und in der <a href="<?php echo h(i18n_href('datenschutz')); ?>">Datenschutzerklärung</a>.
                </p>
            </section>

            <div class="cta-section">
                <h2>Bereit für dein Wichtel-Event?</h2>
                <p><?php echo (function_exists('premium_feature_enabled') && premium_feature_enabled()) ? h(t('premium.extra.cta')) : 'Organisiere dein Wichteln in weniger als 2 Minuten – kostenlos, einfach und ohne Konto!'; ?></p>
                <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button-white">Jetzt Wichtel-Gruppe erstellen →</a>
            </div>
        </article>
    </div>
