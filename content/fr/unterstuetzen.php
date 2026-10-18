<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Accueil</a> / <?php echo h(t('footer.support')); ?>
        </div>

        <header class="page-header">
            <h1 class="page-title">Soutenir librement</h1>
            <p class="page-subtitle">Gratuit, sans publicité et sans inscription</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Le site reste gratuit</h2>
                <p>
                    <strong>wichtlä.ch</strong> est gratuit, sans publicité et sans inscription. Cela doit rester ainsi.
                </p>
                <p>
                    Une contribution est libre. Elle n’est pas une condition pour utiliser une fonction, et il n’y a pas de contrepartie. Que tu contribues ou non ne change rien au Secret Santa.
                </p>
            </section>

            <section class="content-section">
                <h2>Où va le paiement</h2>
                <p>
                    Si tu veux, tu peux contribuer librement au fonctionnement du site. Le paiement va à <strong>Raths IT</strong>, l’entreprise individuelle de Patrick Raths. Il est traité par <?php echo h(support_provider_name()); ?>, par exemple avec TWINT ou par carte. Tu choisis le montant toi-même sur la page du service de paiement.
                </p>
                <p>
                    En cliquant, tu quittes wichtlä.ch. wichtlä.ch ne reçoit aucune donnée de paiement et ne transmet aucune donnée des groupes Secret Santa.
                </p>
                <p class="support-page-action"><?php echo support_external_anchor('page', t('support.button'), 'button support-button'); ?></p>
                <p class="support-note"><?php echo h(t('support.fineprint')); ?></p>
                <p>
                    Tu trouves plus d’informations sur le traitement des données de paiement dans la <a href="<?php echo h(i18n_href('datenschutz')); ?>#freiwillige-unterstuetzung">déclaration de protection des données</a>.
                </p>
            </section>
        </article>
    </div>
