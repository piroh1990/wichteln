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
                <div class="hero-badge"><span aria-hidden="true">✨</span> Free &amp; simple</div>
                <h1 class="hero-title">
                    <span class="hero-title-main">Secret Santa made easy</span>
                    <span class="hero-title-sub">Organise your Secret Santa online</span>
                </h1>
                <p class="hero-subtitle">The simplest way to organise your Secret Santa. No account needed, no hidden costs — just start!</p>
                
                <div class="hero-features">
                    <div class="hero-feature">
                        <span class="feature-icon-small" aria-hidden="true">📧</span>
                        <span>Draw names by email</span>
                    </div>
                    <div class="hero-feature">
                        <span class="feature-icon-small" aria-hidden="true">⚖️</span>
                        <span>Exclusions available</span>
                    </div>
                    <div class="hero-feature">
                        <span class="feature-icon-small" aria-hidden="true">💰</span>
                        <span>100% free</span>
                    </div>
                </div>
                
                <div class="hero-cta">
                    <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button cta-button-primary">
                        <span>Create a group now</span>
                        <span class="cta-arrow" aria-hidden="true">→</span>
                    </a>
                    <a href="<?php echo h(i18n_href('participant')); ?>" class="cta-button cta-button-secondary">
                        <span>Go to the participant area</span>
                        <span class="cta-arrow" aria-hidden="true">→</span>
                    </a>
                    <p class="cta-subtext">No account needed • Start right away</p>
                </div>
            </div>
        </div>
    </section>

    <?php if (function_exists('wichteln_render_home_ad')) { wichteln_render_home_ad(); } ?>

    <!-- How it works -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">How it works</h2>
            
            <div class="steps">
                <div class="step">
                    <span class="step-number">1</span>
                    <span class="step-icon" aria-hidden="true">📝</span>
                    <h3>Create a group</h3>
                    <p>Create a Secret Santa group with a name, a budget and the gift-exchange date. You get an admin link straight away so you can manage everything.</p>
                </div>
                
                <div class="step">
                    <span class="step-number">2</span>
                    <span class="step-icon" aria-hidden="true">👥</span>
                    <h3>Invite participants</h3>
                    <p>Share the invite link with everyone. Each person signs up on their own, with a name and an optional email address.</p>
                </div>
                
                <div class="step">
                    <span class="step-number">3</span>
                    <span class="step-icon" aria-hidden="true">🎯</span>
                    <h3>Set exclusions</h3>
                    <p>Decide who should not buy for whom. Perfect for couples or siblings who do not want to draw each other.</p>
                </div>
                
                <div class="step">
                    <span class="step-number">4</span>
                    <span class="step-icon" aria-hidden="true">🎲</span>
                    <h3>Draw names</h3>
                    <p>One click draws the names. Every participant automatically receives an email with the person they’re giving a gift to.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Why Wichtlä.ch?</h2>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">🔒</span> Safe &amp; private</h3>
                    <p>Until the reveal, each person only sees who they’re giving a gift to. The admin sends the full list to everyone only with the reveal email.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">⚡</span> Lightning fast</h3>
                    <p>Your Secret Santa group is created and the first invites are sent in under 2 minutes. No account needed!</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">🎨</span> Modern &amp; polished</h3>
                    <p>A modern, welcoming interface that works perfectly on every device, from phone to desktop.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">💝</span> Exclusions</h3>
                    <p>Decide who cannot draw whom. Ideal for couples, siblings or housemates who should not draw each other.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">📧</span> Email notification</h3>
                    <p>Every participant automatically receives an email with the name they drew, including the important group details.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">💰</span> Completely free</h3>
                    <p>No hidden costs, no premium features. Use it for free, with no limits.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="faq">
        <div class="container">
            <h2 class="section-title">Frequently asked questions</h2>
            
            <div class="faq-list">
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-1" id="faq-question-1" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">How does the draw work?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-1" class="faq-answer" role="region" aria-labelledby="faq-question-1">
                        <p>After you click “Draw names”, the names are matched automatically and at random. Nobody draws themselves, and every exclusion is respected. Each participant then receives an email with the person they’re giving a gift to.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-2" id="faq-question-2" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Can I set exclusions?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-2" class="faq-answer" role="region" aria-labelledby="faq-question-2">
                        <p>Yes! As the admin, you can decide before the draw which people should not draw each other. That is especially useful for couples or siblings. You can define as many exclusions as you like.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-3" id="faq-question-3" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">What happens after the draw?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-3" class="faq-answer" role="region" aria-labelledby="faq-question-3">
                        <p>Every participant with an email address automatically receives a message naming the person they’re giving a gift to. Their personal link lets them look it up again at any time. The full list stays secret until the admin sends the reveal to everyone.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-4" id="faq-question-4" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Do I need an account?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-4" class="faq-answer" role="region" aria-labelledby="faq-question-4">
                        <p>No! You can start straight away without an account. After you create a group, you receive an admin link that you should save. Participants do not need an account either.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-5" id="faq-question-5" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Can I reset the draw?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-5" class="faq-answer" role="region" aria-labelledby="faq-question-5">
                        <p>Yes. As the admin, you can reset the draw at any time. All matches are removed and you can draw again, for example when new participants have joined.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-6" id="faq-question-6" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">How many participants are possible?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-6" class="faq-answer" role="region" aria-labelledby="faq-question-6">
                        <p>In theory, there is no limit! The draw works from 2 participants and also handles larger groups of 20, 30 or more without trouble.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer call to action -->
    <section class="footer-cta">
        <div class="container">
            <h2>Ready for your Secret Santa?</h2>
            <p>Start now and organise your perfect Secret Santa in a few minutes</p>
            <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button">Create a group for free →</a>
        </div>
    </section>
