<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Accueil</a> / <?php echo h(t('footer.premium')); ?>
        </div>

        <header class="page-header">
            <h1 class="page-title">Premium pour entreprises</h1>
            <p class="page-subtitle">Plusieurs groupes, une licence</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Pourquoi je l’ai ajouté</h2>
                <p>
                    Le groupe habituel reste gratuit. Premium est prévu pour les associations et les entreprises qui mènent plusieurs groupes dans une saison : des services, des sites, un moment pour les apprentis et un pour l’équipe.
                </p>
                <p>
                    Une licence vaut pour la saison en cours et couvre autant de groupes que l’offre le permet. Pas de compte. Tu relies la licence au groupe que tu es en train de créer.
                </p>
            </section>

            <section class="content-section">
                <h2>Ce qui est inclus</h2>
                <ul>
<?php foreach (premium_feature_keys() as $feature_key): ?>
                    <li><?php echo h(t($feature_key)); ?></li>
<?php endforeach; ?>
                </ul>
                <?php echo premium_pricing_cards_html(); ?>
                <p><?php echo h(t('premium.not_included')); ?></p>
            </section>

            <section class="content-section">
                <h2>Comment un groupe devient Premium</h2>
                <p>
                    À la création du groupe, ou plus tard dans l’admin : indique l’adresse de la licence. J’envoie un lien de confirmation et un code à six chiffres à cette adresse. Le groupe n’est relié qu’après confirmation. Ou tu saisis le code de licence.
                </p>
                <p>
                    Le logo et les couleurs sont enregistrés une fois sur la licence et valent pour chaque nouveau groupe. J’envoie le lien de la vue d’ensemble avec le code.
                </p>
                <p>
                    Pas encore de licence ? Plus bas, tu peux payer par carte ou TWINT, ou je t’envoie une facture.
                </p>
                <p>
                    Le traitement des données est décrit dans la <a href="<?php echo h(i18n_href('datenschutz')); ?>#firmen-premium">politique de confidentialité</a>.
                </p>
            </section>
        </article>
    </div>
