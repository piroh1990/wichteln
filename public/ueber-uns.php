<?php
if (file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Über uns - Wer steckt hinter Wichtlä.ch | Wichtlä.ch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Erfahre, wer hinter Wichtlä.ch steckt. Ein kostenloses Schweizer Projekt für einfaches Online-Wichteln - ohne Registrierung, ohne versteckte Kosten.">
    <meta name="keywords" content="Wichtlä.ch, Über uns, Online Wichteln Schweiz, Wichteln kostenlos, Wichtel-Tool">
    <link rel="canonical" href="https://wichtlä.ch/ueber-uns.php">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://wichtlä.ch/ueber-uns.php">
    <meta property="og:title" content="Über uns - Wer steckt hinter Wichtlä.ch">
    <meta property="og:description" content="Erfahre, wer hinter Wichtlä.ch steckt und warum wir dieses Projekt gestartet haben.">

    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon/favicon-16x16.png">

    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">

    <?php include __DIR__ . '/../includes/templates/matomo_tracking.php'; ?>

</head>
<body>
    <?php include __DIR__ . '/../includes/templates/navigation.php'; ?>

    <div class="content-page">
        <div class="breadcrumb">
            <a href="index.php">Home</a> / Über uns
        </div>

        <header class="page-header">
            <h1 class="page-title">Über uns</h1>
            <p class="page-subtitle">Erfahre, wer hinter Wichtlä.ch steckt und warum wir dieses Projekt gestartet haben</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Unsere Mission</h2>
                <p>
                    <strong>Wichtlä.ch</strong> hat ein einfaches Ziel: das Organisieren von Wichtel-Events so unkompliziert wie möglich zu machen. Kein Zettel-Chaos, keine umständlichen Apps, keine versteckten Kosten – einfach eine Gruppe erstellen, Teilnehmer einladen und die Namen automatisch und fair auslosen lassen.
                </p>
                <p>
                    Ob für die Familie, den Freundeskreis oder das Firmenwichteln – unser Tool ist für alle gedacht, die Weihnachten mit einer schönen Wichtel-Tradition bereichern möchten. Die gesamte Plattform ist <strong>kostenlos</strong> und erfordert <strong>keine Registrierung</strong>.
                </p>
            </section>

            <section class="content-section">
                <h2>Wer steckt dahinter?</h2>
                <p>
                    Wichtlä.ch wurde von <strong>Patrick Raths</strong> ins Leben gerufen und wird aus der <strong>Schweiz</strong> betrieben. Die Idee entstand aus einem ganz alltäglichen Problem: Jedes Jahr in der Vorweihnachtszeit das gleiche Spiel – Zettel schreiben, in einen Hut werfen, hoffen, dass niemand sich selbst zieht, und dann die Lose irgendwie an alle verteilen. Besonders wenn nicht alle am selben Ort sind, wird das schnell kompliziert.
                </p>
                <p>
                    Aus diesem Bedarf heraus wurde Wichtlä.ch entwickelt: ein schlankes, schnelles Online-Tool, das genau dieses Problem löst. Ohne unnötigen Schnickschnack, ohne Datensammelei, ohne Registrierungszwang – einfach Wichteln, wie es sein sollte.
                </p>
            </section>

            <section class="content-section">
                <h2>Warum Wichtlä.ch?</h2>
                <p>Es gibt viele Gründe, warum Wichtlä.ch die richtige Wahl für dein Wichtel-Event ist:</p>

                <ul>
                    <li><strong>100% kostenlos:</strong> Keine versteckten Kosten, keine Premium-Features hinter einer Paywall</li>
                    <li><strong>Keine Registrierung:</strong> Sofort loslegen, ohne Account oder persönliche Daten angeben zu müssen</li>
                    <li><strong>Datenschutz nach Schweizer DSG:</strong> Deine Daten werden vertraulich behandelt und nach den strengen Schweizer Datenschutzgesetzen geschützt</li>
                    <li><strong>Einfache Bedienung:</strong> In weniger als 2 Minuten ist deine Wichtel-Gruppe erstellt und die Einladungen verschickt</li>
                    <li><strong>Faire Auslosung:</strong> Unser Algorithmus sorgt dafür, dass die Zuordnung zufällig und fair erfolgt – inklusive der Möglichkeit, Ausschlüsse zu definieren</li>
                    <li><strong>Automatische E-Mail-Benachrichtigung:</strong> Jeder Teilnehmer erhält sein Los bequem per E-Mail</li>
                    <li><strong>Für alle Geräte:</strong> Funktioniert auf Smartphone, Tablet und Desktop – ohne App-Download</li>
                </ul>
            </section>

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
                    const domain = 'wichtlä.ch';
                    const email = user + '@' + domain;
                    const emailElement = document.getElementById('email-address');
                    const link = document.createElement('a');
                    link.href = 'mailto:' + email;
                    link.textContent = email;
                    emailElement.appendChild(link);
                </script>

                <p>
                    Weitere rechtliche Informationen findest du auf unserer <a href="impressum.php">Impressum-Seite</a> und in unserer <a href="datenschutz.php">Datenschutzerklärung</a>.
                </p>
            </section>

            <div class="cta-section">
                <h2>Bereit für dein Wichtel-Event?</h2>
                <p>Organisiere dein Wichteln in weniger als 2 Minuten – kostenlos, einfach und ohne Registrierung!</p>
                <a href="create_group.php" class="cta-button-white">Jetzt Wichtel-Gruppe erstellen →</a>
            </div>
        </article>
    </div>

    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Wichteln</h4>
                    <ul class="footer-links">
                        <li><a href="was-ist-wichteln.php">Was ist Wichteln?</a></li>
                        <li><a href="wichtel-ideen.php">Geschenkideen</a></li>
                        <li><a href="firmenwichteln-tipps.php">Firmenwichteln</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Loslegen</h4>
                    <ul class="footer-links">
                        <li><a href="create_group.php">Gruppe erstellen</a></li>
                        <li><a href="participant.php">Teilnehmerbereich</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Rechtliches</h4>
                    <ul class="footer-links">
                        <li><a href="ueber-uns.php">Über uns</a></li>
                        <li><a href="impressum.php">Impressum</a></li>
                        <li><a href="datenschutz.php">Datenschutz</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> wichtlä.ch</p>
            </div>
        </div>
    </footer>

    <?php include __DIR__ . '/cookie-banner.php'; ?>
</body>
</html>
