<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Wichtlä.ch - Online Wichteln leicht gemacht</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Organisiere dein Wichteln online - einfach, schnell und kostenlos. Erstelle Gruppen, ziehe Namen und verschicke die Lose per E-Mail.">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- CSS Stylesheet -->
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
        <div class="notification success" style="position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 1000; max-width: 500px; width: 90%;">
            Die Gruppe wurde erfolgreich gelöscht.
        </div>
        <script>
            setTimeout(function() {
                var notification = document.querySelector('.notification');
                if (notification) {
                    notification.style.opacity = '0';
                    notification.style.transition = 'opacity 0.5s ease';
                    setTimeout(function() { notification.remove(); }, 500);
                }
            }, 3000);
        </script>
    <?php endif; ?>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-background">
            <div class="snowflake">❄</div>
            <div class="snowflake">❄</div>
            <div class="snowflake">❄</div>
            <div class="snowflake">❄</div>
            <div class="snowflake">❄</div>
        </div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">✨ Kostenlos & Einfach</div>
                <h1 class="hero-title">
                    <span class="hero-title-main">Wichteln leicht gemacht</span>
                    <span class="hero-title-sub">Organisiere dein Wichteln online</span>
                </h1>
                <p class="hero-subtitle">Die einfachste Art, dein Wichteln zu organisieren. Keine Registrierung, keine versteckten Kosten – einfach starten!</p>
                
                <div class="hero-features">
                    <div class="hero-feature">
                        <span class="feature-icon-small">📧</span>
                        <span>Namen ziehen per E-Mail</span>
                    </div>
                    <div class="hero-feature">
                        <span class="feature-icon-small">⚖️</span>
                        <span>Auschlüsse möglich</span>
                    </div>
                    <div class="hero-feature">
                        <span class="feature-icon-small">💰</span>
                        <span>100% kostenlos</span>
                    </div>
                </div>
                
                <div class="hero-cta">
                    <a href="create_group.php" class="cta-button cta-button-primary">
                        <span>Jetzt Gruppe erstellen</span>
                        <span class="cta-arrow">→</span>
                    </a>
                    <a href="participant.php" class="cta-button cta-button-secondary">
                        <span>Zum Teilnehmerbereich</span>
                        <span class="cta-arrow">→</span>
                    </a>
                    <p class="cta-subtext">Keine Registrierung erforderlich • Sofort loslegen</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">So funktioniert's</h2>
            
            <div class="steps">
                <div class="step">
                    <span class="step-number">1</span>
                    <span class="step-icon">📝</span>
                    <h3>Gruppe erstellen</h3>
                    <p>Erstelle eine Wichtel-Gruppe mit Namen, Budget und Datum der Geschenkübergabe. Erhalte sofort einen Admin-Link zur Verwaltung.</p>
                </div>
                
                <div class="step">
                    <span class="step-number">2</span>
                    <span class="step-icon">👥</span>
                    <h3>Teilnehmer einladen</h3>
                    <p>Teile den Einladungslink mit allen Teilnehmern. Jeder trägt sich selbst ein - ganz einfach mit Name und optional E-Mail.</p>
                </div>
                
                <div class="step">
                    <span class="step-number">3</span>
                    <span class="step-icon">🎯</span>
                    <h3>Ausschlüsse festlegen</h3>
                    <p>Lege fest, wer wem nicht wichteln soll - perfekt für Paare oder Geschwister, die sich nicht gegenseitig beschenken möchten.</p>
                </div>
                
                <div class="step">
                    <span class="step-number">4</span>
                    <span class="step-icon">🎲</span>
                    <h3>Auslosen</h3>
                    <p>Mit einem Klick werden die Namen ausgelost. Jeder Teilnehmer erhält automatisch eine E-Mail mit seinem Wichtelpartner.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Warum Wichtlä.ch?</h2>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h3><span class="feature-icon">🔒</span> Sicher & Privat</h3>
                    <p>Deine Daten bleiben sicher. Niemand außer dir kennt alle Zuordnungen. Jeder Teilnehmer sieht nur seinen eigenen Wichtelpartner.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon">⚡</span> Blitzschnell</h3>
                    <p>In weniger als 2 Minuten ist deine Wichtel-Gruppe erstellt und die ersten Einladungen verschickt. Keine Registrierung notwendig!</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon">🎨</span> Modern & Schön</h3>
                    <p>Moderne, ansprechende Oberfläche, die auf allen Geräten perfekt funktioniert - vom Smartphone bis zum Desktop.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon">💝</span> Ausschlüsse</h3>
                    <p>Lege fest, wer wem nicht wichteln kann. Ideal für Paare, Geschwister oder Mitbewohner, die sich nicht gegenseitig ziehen sollen.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon">📧</span> E-Mail Benachrichtigung</h3>
                    <p>Alle Teilnehmer erhalten automatisch eine E-Mail mit ihrem gezogenen Namen - inklusive aller wichtigen Gruppendetails.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon">💰</span> Komplett Kostenlos</h3>
                    <p>Keine versteckten Kosten, keine Premium-Features, keine Werbung. Einfach kostenlos und ohne Einschränkungen nutzen.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq">
        <div class="container">
            <h2 class="section-title">Häufig gestellte Fragen</h2>
            
            <div class="faq-list">
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-question">
                        <h3>Wie funktioniert die Auslosung?</h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Nach dem Klick auf "Auslosen" werden die Namen automatisch und zufällig zugeordnet. Dabei wird sichergestellt, dass niemand sich selbst zieht und alle Ausschlüsse berücksichtigt werden. Jeder Teilnehmer erhält dann eine E-Mail mit seinem Wichtelpartner.</p>
                    </div>
                </div>
                
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-question">
                        <h3>Kann ich Ausschlüsse einstellen?</h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Ja! Als Admin kannst du vor der Auslosung festlegen, welche Personen sich nicht gegenseitig ziehen sollen. Dies ist besonders praktisch für Paare oder Geschwister. Du kannst beliebig viele Ausschlüsse definieren.</p>
                    </div>
                </div>
                
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-question">
                        <h3>Was passiert nach der Auslosung?</h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Alle Teilnehmer mit E-Mail-Adresse erhalten automatisch eine Nachricht mit dem Namen ihres Wichtelpartners. Außerdem können sie jederzeit über ihren persönlichen Link nachschauen, wen sie beschenken.</p>
                    </div>
                </div>
                
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-question">
                        <h3>Ist eine Registrierung notwendig?</h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Nein! Du kannst sofort ohne Registrierung loslegen. Nach dem Erstellen einer Gruppe erhältst du einen Admin-Link, den du dir speichern solltest. Teilnehmer benötigen ebenfalls keine Registrierung.</p>
                    </div>
                </div>
                
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-question">
                        <h3>Kann ich die Auslosung zurücksetzen?</h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Ja, als Admin kannst du die Auslosung jederzeit zurücksetzen. Dabei werden alle Zuordnungen gelöscht und du kannst erneut auslosen - zum Beispiel wenn neue Teilnehmer hinzugekommen sind.</p>
                    </div>
                </div>
                
                <div class="faq-item" onclick="toggleFAQ(this)">
                    <div class="faq-question">
                        <h3>Wie viele Teilnehmer sind möglich?</h3>
                        <span class="faq-toggle">+</span>
                    </div>
                    <div class="faq-answer">
                        <p>Theoretisch unbegrenzt! Die Auslosung funktioniert ab 2 Teilnehmern und kann problemlos auch mit größeren Gruppen von 20, 30 oder mehr Personen durchgeführt werden.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer CTA -->
    <section class="footer-cta">
        <div class="container">
            <h2>Bereit für dein Wichteln?</h2>
            <p>Starte jetzt und organisiere dein perfektes Wichteln in wenigen Minuten</p>
            <a href="create_group.php" class="cta-button">Kostenlos Gruppe erstellen »</a>
        </div>
    </section>

    <!-- Simple Footer -->
    <footer style="background: var(--secondary-dark); color: white; text-align: center; padding: 2rem;">
        <p style="margin: 0; color: white; opacity: 1;">© <?php echo date('Y'); ?> wichtlä.ch - Online Wichteln leicht gemacht</p>
    </footer>

    <script>
        function toggleFAQ(element) {
            const isActive = element.classList.contains('active');
            
            // Schließe alle anderen FAQs
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Öffne/Schließe die geklickte FAQ
            if (!isActive) {
                element.classList.add('active');
            }
        }

        // Smooth scroll für CTA Buttons
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animation beim Scrollen
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Beobachte alle Schritte und Feature-Cards
        document.querySelectorAll('.step, .feature-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>
