<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Accueil</a> / À propos
        </div>

        <header class="page-header">
            <h1 class="page-title">À propos</h1>
            <p class="page-subtitle">Qui est derrière Wichtlä.ch</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Notre mission</h2>
                <p>
                    <strong>Wichtlä.ch</strong> a un objectif simple : rendre l’organisation d’un Secret Santa aussi facile que possible. Pas de bouts de papier en désordre, pas d’applications compliquées, pas de frais cachés. Tu crées un groupe, tu invites les participants, et les noms sont tirés automatiquement, de façon équitable. Le nom « Wichtlä » vient de « Wichtel », le mot allemand et suisse allemand pour le Secret Santa.
                </p>
                <p>
                    En famille, entre amis ou pour un Secret Santa en entreprise, l’outil est fait pour toutes les personnes qui veulent enrichir Noël avec cette tradition. Toute la plateforme est <strong>gratuite</strong> et <strong>ne demande aucun compte</strong>.
                </p>
            </section>

            <section class="content-section">
                <h2>Qui est derrière ?</h2>
                <p>
                    Wichtlä.ch est le passe-temps de Patrick Raths. Il a construit le site lui-même et l’exploite via <a href="https://raths-it.ch"><strong>Raths IT</strong></a> depuis la <strong>Suisse</strong>. L’idée est née d’un problème tout simple : chaque année, avant Noël, le même rituel. Écrire des noms, les mettre dans un chapeau, espérer que personne ne tire son propre nom, puis faire parvenir les papiers à tout le monde. Dès que les gens ne sont pas au même endroit, ça se complique vite.
                </p>
                <p>
                    Wichtlä.ch est né de ce besoin : un outil en ligne léger et rapide, qui règle exactement ce problème. Sans fioritures, sans collecte de données inutile, sans obligation de créer un compte. Juste un Secret Santa, comme il devrait l’être.
                </p>
            </section>

            <section class="content-section">
                <h2>Pourquoi Wichtlä.ch ?</h2>
                <p>Il y a beaucoup de raisons de choisir Wichtlä.ch pour ton Secret Santa :</p>

                <ul>
                    <li><strong>100 % gratuit :</strong> pas de frais cachés, pas de fonctions premium derrière un paiement</li>
                    <li><strong>Aucun compte :</strong> tu commences tout de suite, sans donner de données personnelles</li>
                    <li><strong>Protection des données selon la LPD suisse :</strong> tes données sont traitées de façon confidentielle et protégées selon la loi fédérale suisse sur la protection des données</li>
                    <li><strong>Utilisation simple :</strong> en moins de 2 minutes, ton groupe Secret Santa est créé et les invitations sont envoyées</li>
                    <li><strong>Tirage équitable :</strong> notre algorithme attribue les noms au hasard et de façon équitable, avec la possibilité de définir des exclusions</li>
                    <li><strong>Notification automatique par e-mail :</strong> chaque participant reçoit son attribution directement par e-mail</li>
                    <li><strong>Sur tous les appareils :</strong> ça fonctionne sur smartphone, tablette et ordinateur, sans télécharger d’application</li>
                </ul>
            </section>

            <?php
            require_once dirname(__DIR__, 2) . '/includes/support.php';
            echo support_about_html();
            ?>

            <section class="content-section">
                <h2>Contact</h2>
                <p>
                    Tu as une question, une idée ou un retour ? Nous serons contents de te lire. Écris-nous simplement à :
                </p>
                <p>
                    <strong>E-mail :</strong> <span id="email-address"></span>
                </p>

                <script>
                    // Protéger l’adresse e-mail des robots de spam
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
                    Tu trouveras d’autres informations juridiques dans nos <a href="<?php echo h(i18n_href('impressum')); ?>">mentions légales</a> et dans notre <a href="<?php echo h(i18n_href('datenschutz')); ?>">déclaration de protection des données</a>.
                </p>
            </section>

            <div class="cta-section">
                <h2>Prêt pour ton Secret Santa ?</h2>
                <p>Organise ton Secret Santa en moins de 2 minutes : gratuit, simple, et sans compte !</p>
                <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button-white">Créer un groupe Secret Santa →</a>
            </div>
        </article>
    </div>
