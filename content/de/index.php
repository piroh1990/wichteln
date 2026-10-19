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
                <div class="hero-badge"><span aria-hidden="true">✨</span> <?php echo (function_exists('premium_feature_enabled') && premium_feature_enabled()) ? h(t('premium.extra.badge')) : 'Kostenlos & Einfach'; ?></div>
                <h1 class="hero-title">
                    <span class="hero-title-main">Wichteln leicht gemacht</span>
                    <span class="hero-title-sub">Organisiere dein Wichteln online</span>
                </h1>
                <p class="hero-subtitle">Die einfachste Art, dein Wichteln zu organisieren. Kein Konto nötig, keine versteckten Kosten – einfach starten!</p>
                
                <div class="hero-features">
                    <div class="hero-feature">
                        <span class="feature-icon-small" aria-hidden="true">📧</span>
                        <span>Namen ziehen per E-Mail</span>
                    </div>
                    <div class="hero-feature">
                        <span class="feature-icon-small" aria-hidden="true">⚖️</span>
                        <span>Ausschlüsse möglich</span>
                    </div>
                    <div class="hero-feature">
                        <span class="feature-icon-small" aria-hidden="true">💰</span>
                        <span><?php echo (function_exists('premium_feature_enabled') && premium_feature_enabled()) ? h(t('premium.extra.hero')) : '100% kostenlos'; ?></span>
                    </div>
                </div>
                
                <div class="hero-cta">
                    <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button cta-button-primary">
                        <span>Jetzt Gruppe erstellen</span>
                        <span class="cta-arrow" aria-hidden="true">→</span>
                    </a>
                    <a href="/participant.php" class="cta-button cta-button-secondary">
                        <span>Zum Teilnehmerbereich</span>
                        <span class="cta-arrow" aria-hidden="true">→</span>
                    </a>
                    <p class="cta-subtext">Kein Konto nötig • Sofort loslegen</p>
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
                    <span class="step-icon" aria-hidden="true">📝</span>
                    <h3>Gruppe erstellen</h3>
                    <p>Erstelle eine Wichtel-Gruppe mit Namen, Budget und Datum der Geschenkübergabe. Erhalte sofort einen Admin-Link zur Verwaltung.</p>
                </div>
                
                <div class="step">
                    <span class="step-number">2</span>
                    <span class="step-icon" aria-hidden="true">👥</span>
                    <h3>Teilnehmer einladen</h3>
                    <p>Teile den Einladungslink mit allen Teilnehmern. Jeder trägt sich selbst ein - ganz einfach mit Name und optional E-Mail.</p>
                </div>
                
                <div class="step">
                    <span class="step-number">3</span>
                    <span class="step-icon" aria-hidden="true">🎯</span>
                    <h3>Ausschlüsse festlegen</h3>
                    <p>Lege fest, wer wem nicht wichteln soll - perfekt für Paare oder Geschwister, die sich nicht gegenseitig beschenken möchten.</p>
                </div>
                
                <div class="step">
                    <span class="step-number">4</span>
                    <span class="step-icon" aria-hidden="true">🎲</span>
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
                    <h3><span class="feature-icon" aria-hidden="true">🔒</span> Sicher & Privat</h3>
                    <p>Bis zur Auflösung sieht jede Person nur, wen sie beschenkt. Die komplette Liste verschickt der Admin erst mit der Auflösungs-Mail an alle Teilnehmer.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">⚡</span> Blitzschnell</h3>
                    <p>In weniger als 2 Minuten ist deine Wichtel-Gruppe erstellt und die ersten Einladungen verschickt. Kein Konto nötig!</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">🎨</span> Modern & Schön</h3>
                    <p>Moderne, ansprechende Oberfläche, die auf allen Geräten perfekt funktioniert - vom Smartphone bis zum Desktop.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">💝</span> Ausschlüsse</h3>
                    <p>Lege fest, wer wem nicht wichteln kann. Ideal für Paare, Geschwister oder Mitbewohner, die sich nicht gegenseitig ziehen sollen.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">📧</span> E-Mail Benachrichtigung</h3>
                    <p>Alle Teilnehmer erhalten automatisch eine E-Mail mit ihrem gezogenen Namen - inklusive aller wichtigen Gruppendetails.</p>
                </div>
                
                <div class="feature-card">
<?php if (function_exists('premium_feature_enabled') && premium_feature_enabled()): ?>
                    <h3><span class="feature-icon" aria-hidden="true">💰</span> <?php echo h(t('premium.extra.feature_title')); ?></h3>
                    <p><?php echo h(t('premium.extra.feature_text')); ?> <?php echo premium_extra_link_html(); ?>.</p>
<?php else: ?>
                    <h3><span class="feature-icon" aria-hidden="true">💰</span> Komplett Kostenlos</h3>
                    <p>Keine versteckten Kosten, keine Premium-Features. Einfach kostenlos und ohne Einschränkungen nutzen.</p>
<?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq">
        <div class="container">
            <h2 class="section-title">Häufig gestellte Fragen</h2>
            
            <div class="faq-list">
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-1" id="faq-question-1" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Wie funktioniert die Auslosung?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-1" class="faq-answer" role="region" aria-labelledby="faq-question-1">
                        <p>Nach dem Klick auf "Auslosen" werden die Namen automatisch und zufällig zugeordnet. Dabei wird sichergestellt, dass niemand sich selbst zieht und alle Ausschlüsse berücksichtigt werden. Jeder Teilnehmer erhält dann eine E-Mail mit seinem Wichtelpartner.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-2" id="faq-question-2" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Kann ich Ausschlüsse einstellen?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-2" class="faq-answer" role="region" aria-labelledby="faq-question-2">
                        <p>Ja! Als Admin kannst du vor der Auslosung festlegen, welche Personen sich nicht gegenseitig ziehen sollen. Dies ist besonders praktisch für Paare oder Geschwister. Du kannst beliebig viele Ausschlüsse definieren.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-3" id="faq-question-3" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Was passiert nach der Auslosung?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-3" class="faq-answer" role="region" aria-labelledby="faq-question-3">
                        <p>Alle Teilnehmer mit E-Mail-Adresse erhalten automatisch eine Nachricht mit dem Namen ihres Wichtelpartners. Über den persönlichen Link können sie jederzeit nachschauen, wen sie beschenken. Die komplette Liste bleibt geheim, bis der Admin die Auflösung an alle schickt.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-4" id="faq-question-4" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Brauche ich ein Konto?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-4" class="faq-answer" role="region" aria-labelledby="faq-question-4">
                        <p>Nein! Kein Konto nötig. Du kannst sofort loslegen. Nach dem Erstellen einer Gruppe erhältst du einen Admin-Link, den du dir speichern solltest. Teilnehmer brauchen ebenfalls kein Konto.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-5" id="faq-question-5" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Kann ich die Auslosung zurücksetzen?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-5" class="faq-answer" role="region" aria-labelledby="faq-question-5">
                        <p>Ja, als Admin kannst du die Auslosung jederzeit zurücksetzen. Dabei werden alle Zuordnungen gelöscht und du kannst erneut auslosen - zum Beispiel wenn neue Teilnehmer hinzugekommen sind.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-6" id="faq-question-6" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Wie viele Teilnehmer sind möglich?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-6" class="faq-answer" role="region" aria-labelledby="faq-question-6">
                        <p>Theoretisch unbegrenzt! Empfohlen sind mindestens 3 Personen, technisch möglich ist die Auslosung ab 2. Auch grössere Gruppen von 20, 30 oder mehr Personen funktionieren problemlos.</p>
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
            <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button">Kostenlos Gruppe erstellen »</a>
        </div>
    </section>
