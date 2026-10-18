<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / Über uns
        </div>

        <header class="page-header">
            <h1 class="page-title">Über uns</h1>
            <p class="page-subtitle">Wer hinter Wichtlä.ch steckt</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Unsere Mission</h2>
                <p>
                    <strong>Wichtlä.ch</strong> hat ein einfaches Ziel: das Organisieren von Wichtel-Events so unkompliziert wie möglich zu machen. Kein Zettel-Chaos, keine umständlichen Apps, keine versteckten Kosten – einfach eine Gruppe erstellen, Teilnehmer einladen und die Namen automatisch und fair auslosen lassen.
                </p>
                <p>
                    Ob für die Familie, den Freundeskreis oder das Firmenwichteln – unser Tool ist für alle gedacht, die Weihnachten mit einer schönen Wichtel-Tradition bereichern möchten. Die gesamte Plattform ist <strong>kostenlos</strong> und erfordert <strong>kein Konto</strong>.
                </p>
            </section>

            <section class="content-section">
                <h2>Wer steckt dahinter?</h2>
                <p>
                    Wichtlä.ch ist das Hobby von Patrick Raths. Er hat die Seite selbst gebaut und betreibt sie über <a href="https://raths-it.ch"><strong>Raths IT</strong></a> aus der <strong>Schweiz</strong>. Die Idee entstand aus einem ganz alltäglichen Problem: Jedes Jahr in der Vorweihnachtszeit das gleiche Spiel – Zettel schreiben, in einen Hut werfen, hoffen, dass niemand sich selbst zieht, und dann die Lose irgendwie an alle verteilen. Besonders wenn nicht alle am selben Ort sind, wird das schnell kompliziert.
                </p>
                <p>
                    Aus diesem Bedarf heraus wurde Wichtlä.ch entwickelt: ein schlankes, schnelles Online-Tool, das genau dieses Problem löst. Ohne unnötigen Schnickschnack, ohne Datensammelei, ohne Konto – einfach Wichteln, wie es sein sollte.
                </p>
            </section>

            <section class="content-section">
                <h2>Warum Wichtlä.ch?</h2>
                <p>Es gibt viele Gründe, warum Wichtlä.ch die richtige Wahl für dein Wichtel-Event ist:</p>

                <ul>
                    <li><strong>100% kostenlos:</strong> Keine versteckten Kosten, keine Premium-Features hinter einer Paywall</li>
                    <li><strong>Kein Konto nötig:</strong> Sofort loslegen, ohne Account oder persönliche Daten angeben zu müssen</li>
                    <li><strong>Datenschutz nach Schweizer DSG:</strong> Deine Daten werden vertraulich behandelt und nach den strengen Schweizer Datenschutzgesetzen geschützt</li>
                    <li><strong>Einfache Bedienung:</strong> In weniger als 2 Minuten ist deine Wichtel-Gruppe erstellt und die Einladungen verschickt</li>
                    <li><strong>Faire Auslosung:</strong> Unser Algorithmus sorgt dafür, dass die Zuordnung zufällig und fair erfolgt – inklusive der Möglichkeit, Ausschlüsse zu definieren</li>
                    <li><strong>Automatische E-Mail-Benachrichtigung:</strong> Jeder Teilnehmer erhält sein Los bequem per E-Mail</li>
                    <li><strong>Für alle Geräte:</strong> Funktioniert auf Smartphone, Tablet und Desktop – ohne App-Download</li>
                </ul>
            </section>

            <?php
            require_once dirname(__DIR__, 2) . '/includes/support.php';
            echo support_about_html();
            ?>

            <section class="content-section">
                <h2>Kontakt</h2>
                <p>
                    Hast du Fragen, Anregungen oder Feedback? Wir freuen uns, von dir zu hören! Schreibe uns einfach eine E-Mail an:
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
                    Weitere rechtliche Informationen findest du auf unserer <a href="<?php echo h(i18n_href('impressum')); ?>">Impressum-Seite</a> und in unserer <a href="<?php echo h(i18n_href('datenschutz')); ?>">Datenschutzerklärung</a>.
                </p>
            </section>

            <div class="cta-section">
                <h2>Bereit für dein Wichtel-Event?</h2>
                <p>Organisiere dein Wichteln in weniger als 2 Minuten – kostenlos, einfach und ohne Konto!</p>
                <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button-white">Jetzt Wichtel-Gruppe erstellen →</a>
            </div>
        </article>
    </div>
