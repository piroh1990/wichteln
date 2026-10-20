<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Accueil</a> / À propos
        </div>

        <header class="page-header">
            <h1 class="page-title">À propos</h1>
            <p class="page-subtitle">Pourquoi j’ai construit wichtlä.ch</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Ce qui m’importe</h2>
                <p>
                    Je veux que le Secret Santa soit simple. Pas de bouts de papier en désordre, pas d’application et pas de frais cachés : tu crées un groupe, tu invites tout le monde, et les noms sont tirés de façon équitable.
                </p>
                <p>
                    Ça convient pour la famille, le cercle d’amis ou l’équipe au travail. Le Secret Santa habituel est gratuit, sans publicité et sans compte.<?php if (function_exists('premium_feature_enabled') && premium_feature_enabled()): ?> <?php echo str_replace('fonctions en plus', '<a href="' . h(i18n_href('firmen-premium')) . '">fonctions en plus</a>', t('premium.extra.about')); ?><?php endif; ?>
                </p>
            </section>

            <section class="content-section">
                <h2>Qui est derrière</h2>
                <p>
                    Je suis Patrick Raths, et wichtlä.ch est mon passe-temps. J’ai construit le site moi-même et je le fais tourner avec ma société <a href="https://raths-it.ch"><strong>Raths IT</strong></a> en <strong>Suisse</strong>. L’idée est venue d’un problème que beaucoup de gens connaissent : chaque année, on écrit des noms sur des papiers, on les jette dans un chapeau et on espère que personne ne tire son propre nom. Quand tout le monde n’est pas au même endroit, ça se complique vite.
                </p>
                <p>
                    C’est pour ça que j’ai construit wichtlä.ch. C’est un outil léger, sans fioritures et sans compte, et je ne collecte que les données dont le Secret Santa a besoin.
                </p>
            </section>

            <section class="content-section">
                <h2>Pourquoi Wichtlä.ch ?</h2>
                <p>Voici ce à quoi j’ai fait attention en construisant le site :</p>

                <ul>
                    <li><?php if (function_exists('premium_feature_enabled') && premium_feature_enabled()): ?><strong>Gratuit :</strong> le Secret Santa habituel reste sans publicité et sans compte. <?php echo str_replace('fonctions en plus', '<a href="' . h(i18n_href('firmen-premium')) . '">fonctions en plus</a>', t('premium.extra.bullet')); ?><?php else: ?><strong>Gratuit :</strong> le Secret Santa habituel reste sans publicité et sans compte.<?php endif; ?></li>
                    <li><strong>Aucun compte :</strong> tu peux commencer tout de suite.</li>
                    <li><strong>Protection des données selon la LPD suisse :</strong> je traite tes données de façon confidentielle.</li>
                    <li><strong>Simple :</strong> en moins de deux minutes, ton groupe est créé et les invitations sont envoyées.</li>
                    <li><strong>Tirage équitable :</strong> l’attribution est aléatoire, et tu peux définir des exclusions.</li>
                    <li><strong>Résultat par e-mail :</strong> tout le monde reçoit son résultat directement par e-mail.</li>
                    <li><strong>Pour tous les appareils :</strong> ça fonctionne sur smartphone, tablette et ordinateur, sans application.</li>
                </ul>
            </section>

            <?php
            require_once dirname(__DIR__, 2) . '/includes/support.php';
            echo support_about_html();
            ?>

            <section class="content-section">
                <h2>Contact</h2>
                <p>
                    Tu as des questions, des idées ou un retour ? Chaque message me fait plaisir. Écris-moi simplement à :
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
                    Tu trouveras d’autres informations juridiques dans les <a href="<?php echo h(i18n_href('impressum')); ?>">mentions légales</a> et dans la <a href="<?php echo h(i18n_href('datenschutz')); ?>">déclaration de protection des données</a>.
                </p>
            </section>

            <div class="cta-section">
                <h2>Prêt pour ton Secret Santa ?</h2>
                <p><?php echo (function_exists('premium_feature_enabled') && premium_feature_enabled()) ? h(t('premium.extra.cta')) : 'Organise ton Secret Santa en moins de 2 minutes : gratuit, simple, et sans compte !'; ?></p>
                <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button-white">Créer un groupe Secret Santa →</a>
            </div>
        </article>
    </div>
