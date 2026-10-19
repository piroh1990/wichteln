<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Accueil</a> / <?php echo h(t('footer.support')); ?>
        </div>

        <header class="page-header">
            <h1 class="page-title">Soutenir librement</h1>
            <p class="page-subtitle">Un passe-temps depuis la Suisse</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Pourquoi j’ai fait ce site</h2>
                <p>
                    wichtlä.ch est mon passe-temps. J’ai construit le site moi-même, parce qu’aucun des sites de Secret Santa existants ne me convenait vraiment : trop de publicité, trop compliqué, souvent avec une inscription. Je voulais quelque chose de simple et de propre.
                </p>
                <p>
                    <?php echo (function_exists('premium_feature_enabled') && premium_feature_enabled()) ? h(t('premium.extra.support')) : 'Le serveur et l’exploitation coûtent un peu d’argent et beaucoup de temps libre. Si tu veux, une contribution libre aide à ce que le site reste gratuit et sans publicité.'; ?>
                </p>
            </section>

            <section class="content-section">
                <h2>Où va le paiement</h2>
                <p>
                    Le paiement va à <strong>Raths IT</strong>, mon entreprise individuelle. Il est traité par <?php echo h(support_provider_name()); ?>, par exemple avec TWINT ou par carte. Tu choisis le montant toi-même sur la page du service de paiement.
                </p>
                <p>
                    Une contribution est libre et n’est pas une condition pour utiliser une fonction.
                </p>
                <p>
                    En cliquant, tu quittes wichtlä.ch. Je ne reçois aucune donnée de paiement, et aucune donnée des groupes Secret Santa ne part vers le service de paiement.
                </p>
                <p class="support-page-action"><?php echo support_external_anchor('page', t('support.button'), 'button support-button'); ?></p>
                <p class="support-note"><?php echo h(t('support.fineprint')); ?></p>
                <p>
                    Tu trouves plus d’informations sur le traitement des données de paiement dans la <a href="<?php echo h(i18n_href('datenschutz')); ?>#freiwillige-unterstuetzung">déclaration de protection des données</a>.
                </p>
            </section>
        </article>
    </div>
