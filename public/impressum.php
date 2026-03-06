<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impressum - Wichtlä.ch</title>
    <link rel="apple-touch-icon" sizes="57x57" href="/images/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/images/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/images/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/images/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/images/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/images/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/images/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192"  href="/images/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/images/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="/images/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="/images/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&family=Roboto&display=swap" rel="stylesheet">
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="css/styles.css">
    <?php include __DIR__ . '/../includes/templates/matomo_tracking.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../includes/templates/navigation.php'; ?>
    
    <header>
        <a href="index.php">
            <img src="images/logo.png" alt="Wichtel Logo">
        </a>
    </header>
    <div class="container">
        <div class="content-card">
            <h1>Impressum</h1>
            
            <h2>Betreiber</h2>
            <p>
                Patrick Raths<br>
                wichtlä.ch<br>
                Schweiz
            </p>
            
            <h2>Kontakt</h2>
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
            
            <h2>Haftungsausschluss</h2>
            
            <h3>Haftung für Inhalte</h3>
            <p>
                Die Inhalte dieser Website wurden mit grösstmöglicher Sorgfalt erstellt. Für die Richtigkeit, Vollständigkeit und Aktualität der Inhalte können wir jedoch keine Gewähr übernehmen.
            </p>
            
            <h3>Haftung für Links</h3>
            <p>
                Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben. Für die Inhalte der verlinkten Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich.
            </p>
            
            <h3>Urheberrecht</h3>
            <p>
                Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem schweizerischen Urheberrecht. Die Vervielfältigung, Bearbeitung, Verbreitung und jede Art der Verwertung ausserhalb der Grenzen des Urheberrechtes bedürfen der schriftlichen Zustimmung des jeweiligen Autors bzw. Erstellers.
            </p>
            
            <hr>
            
            <div style="text-align: center; margin-top: 3rem;">
                <a href="index.php" class="button secondary">🏠 Zurück zur Startseite</a>
            </div>
        </div>
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
    
    <!-- Cookie Banner -->
    <?php include __DIR__ . '/cookie-banner.php'; ?>
</body>
</html>
