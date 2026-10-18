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
                <div class="hero-badge"><span aria-hidden="true">✨</span> Gratuit et simple</div>
                <h1 class="hero-title">
                    <span class="hero-title-main">Secret Santa, tout simplement</span>
                    <span class="hero-title-sub">Organise ton Secret Santa en ligne</span>
                </h1>
                <p class="hero-subtitle">La façon la plus simple d’organiser ton Secret Santa. Pas de compte, pas de frais cachés : tu commences tout de suite !</p>
                
                <div class="hero-features">
                    <div class="hero-feature">
                        <span class="feature-icon-small" aria-hidden="true">📧</span>
                        <span>Tirage des noms par e-mail</span>
                    </div>
                    <div class="hero-feature">
                        <span class="feature-icon-small" aria-hidden="true">⚖️</span>
                        <span>Exclusions possibles</span>
                    </div>
                    <div class="hero-feature">
                        <span class="feature-icon-small" aria-hidden="true">💰</span>
                        <span>100 % gratuit</span>
                    </div>
                </div>
                
                <div class="hero-cta">
                    <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button cta-button-primary">
                        <span>Créer un groupe maintenant</span>
                        <span class="cta-arrow" aria-hidden="true">→</span>
                    </a>
                    <a href="<?php echo h(i18n_href('participant')); ?>" class="cta-button cta-button-secondary">
                        <span>Accéder à l’espace participant</span>
                        <span class="cta-arrow" aria-hidden="true">→</span>
                    </a>
                    <p class="cta-subtext">Aucun compte nécessaire • Tu commences tout de suite</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Comment ça marche -->
    <section class="how-it-works">
        <div class="container">
            <h2 class="section-title">Comment ça marche</h2>
            
            <div class="steps">
                <div class="step">
                    <span class="step-number">1</span>
                    <span class="step-icon" aria-hidden="true">📝</span>
                    <h3>Créer un groupe</h3>
                    <p>Crée un groupe Secret Santa avec un nom, un budget et la date de la remise des cadeaux. Tu reçois tout de suite un lien admin pour tout gérer.</p>
                </div>
                
                <div class="step">
                    <span class="step-number">2</span>
                    <span class="step-icon" aria-hidden="true">👥</span>
                    <h3>Inviter les participants</h3>
                    <p>Partage le lien d’invitation avec tout le monde. Chacun s’inscrit tout seul, simplement avec un nom et, en option, une adresse e-mail.</p>
                </div>
                
                <div class="step">
                    <span class="step-number">3</span>
                    <span class="step-icon" aria-hidden="true">🎯</span>
                    <h3>Définir les exclusions</h3>
                    <p>Indique qui ne doit pas offrir un cadeau à qui. Pratique pour les couples ou les frères et sœurs qui ne veulent pas tomber l’un sur l’autre.</p>
                </div>
                
                <div class="step">
                    <span class="step-number">4</span>
                    <span class="step-icon" aria-hidden="true">🎲</span>
                    <h3>Lancer le tirage</h3>
                    <p>Un clic suffit pour tirer les noms. Chaque participant reçoit automatiquement un e-mail avec la personne à qui offrir un cadeau.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Fonctions -->
    <section class="features">
        <div class="container">
            <h2 class="section-title">Pourquoi Wichtlä.ch ?</h2>
            
            <div class="feature-grid">
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">🔒</span> Sûr et privé</h3>
                    <p>Jusqu’à la révélation, chaque personne ne voit que celle à qui elle offre un cadeau. L’admin envoie la liste complète à tout le monde seulement avec l’e-mail de révélation, qui dit qui était le Père Noël secret de qui.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">⚡</span> Ultra rapide</h3>
                    <p>En moins de 2 minutes, ton groupe Secret Santa est créé et les premières invitations partent. Aucun compte nécessaire !</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">🎨</span> Moderne et soigné</h3>
                    <p>Une interface moderne et agréable, qui fonctionne parfaitement sur tous les appareils, du smartphone à l’ordinateur.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">💝</span> Exclusions</h3>
                    <p>Indique qui ne peut pas tirer qui. Idéal pour les couples, les frères et sœurs ou les colocataires qui ne doivent pas tomber l’un sur l’autre.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">📧</span> Notification par e-mail</h3>
                    <p>Tous les participants reçoivent automatiquement un e-mail avec le nom tiré, ainsi que les infos importantes du groupe.</p>
                </div>
                
                <div class="feature-card">
                    <h3><span class="feature-icon" aria-hidden="true">💰</span> Entièrement gratuit</h3>
                    <p>Pas de frais cachés, pas de fonctions premium. Tu l’utilises gratuitement, sans limitation.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="faq">
        <div class="container">
            <h2 class="section-title">Questions fréquentes</h2>
            
            <div class="faq-list">
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-1" id="faq-question-1" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Comment fonctionne le tirage ?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-1" class="faq-answer" role="region" aria-labelledby="faq-question-1">
                        <p>Après un clic sur « Lancer le tirage », les noms sont attribués automatiquement et au hasard. Personne ne tire son propre nom, et toutes les exclusions sont respectées. Chaque participant reçoit ensuite un e-mail avec la personne à qui offrir un cadeau.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-2" id="faq-question-2" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Puis-je définir des exclusions ?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-2" class="faq-answer" role="region" aria-labelledby="faq-question-2">
                        <p>Oui ! En tant qu’admin, tu peux indiquer avant le tirage quelles personnes ne doivent pas tomber l’un sur l’autre. C’est particulièrement pratique pour les couples ou les frères et sœurs. Tu peux définir autant d’exclusions que tu veux.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-3" id="faq-question-3" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Que se passe-t-il après le tirage ?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-3" class="faq-answer" role="region" aria-labelledby="faq-question-3">
                        <p>Tous les participants qui ont une adresse e-mail reçoivent automatiquement un message avec le nom de la personne à qui offrir un cadeau. Avec leur lien personnel, ils peuvent revoir ce nom quand ils veulent. La liste complète reste secrète jusqu’à ce que l’admin envoie la révélation à tout le monde.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-4" id="faq-question-4" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Faut-il s’inscrire ?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-4" class="faq-answer" role="region" aria-labelledby="faq-question-4">
                        <p>Non ! Tu peux commencer tout de suite, sans compte. Après la création d’un groupe, tu reçois un lien admin à conserver. Les participants n’ont pas besoin de compte non plus.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-5" id="faq-question-5" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Puis-je réinitialiser le tirage ?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-5" class="faq-answer" role="region" aria-labelledby="faq-question-5">
                        <p>Oui, en tant qu’admin tu peux réinitialiser le tirage à tout moment. Toutes les attributions sont effacées et tu peux tirer à nouveau, par exemple si de nouvelles personnes ont rejoint le groupe.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-6" id="faq-question-6" onclick="toggleFAQ(this, true)">
                            <span class="faq-title">Combien de participants sont possibles ?</span>
                            <span class="faq-toggle" aria-hidden="true">+</span>
                        </button>
                    </h3>
                    <div id="faq-answer-6" class="faq-answer" role="region" aria-labelledby="faq-question-6">
                        <p>En théorie, il n’y a pas de limite ! Recommandé : au moins 3 personnes ; techniquement possible à partir de 2. Le tirage passe aussi sans problème avec des groupes plus grands, de 20, 30 personnes ou plus.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Appel à l’action -->
    <section class="footer-cta">
        <div class="container">
            <h2>Prêt pour ton Secret Santa ?</h2>
            <p>Commence maintenant et organise ton Secret Santa parfait en quelques minutes</p>
            <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button">Créer un groupe gratuitement →</a>
        </div>
    </section>
