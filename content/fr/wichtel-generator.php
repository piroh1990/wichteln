<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "Est-ce un générateur de nombres ?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Non. C’est un générateur de tirage au sort pour les noms du Secret Santa, pas un générateur de nombres et pas une roue publique."
            }
        },
        {
            "@type": "Question",
            "name": "Faut-il un compte ?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Non. Tu n’as pas besoin de compte. Comme admin, tu indiques une adresse e-mail pour recevoir le lien admin."
            }
        },
        {
            "@type": "Question",
            "name": "Le tirage est-il équitable ?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Chaque personne offre un cadeau à une seule autre, personne ne tire son propre nom, et les exclusions sont respectées."
            }
        }
    ]
}
</script>
<div class="content-page">
    <div class="breadcrumb">
        <a href="<?php echo h(i18n_href('index')); ?>">Accueil</a> / Générateur de tirage
    </div>

    <header class="page-header">
        <h1 class="page-title">Générateur de tirage au sort : Secret Santa en ligne</h1>
        <p class="page-subtitle">Tu entres les noms, tu poses les exclusions, puis tu envoies les liens par e-mail ou WhatsApp. Gratuit et sans compte.</p>
    </header>

    <article>
        <section class="content-section">
            <p>Tu inscris les noms, tu indiques qui ne doit pas tirer qui, puis tu lances le tirage. Il est aléatoire : personne ne se tire soi-même, et les exclusions comptent. Tout se passe dans le navigateur, sans application et sans compte.</p>
            <ol>
                <li><strong>Entrer les noms.</strong> Tu crées un groupe et tu rassembles les participants avec le lien d’invitation.</li>
                <li><strong>Poser les exclusions.</strong> Tu peux écarter les couples, ou d’autres personnes qui ne doivent pas s’offrir de cadeau.</li>
                <li><strong>Envoyer les liens.</strong> Qui laisse une adresse e-mail reçoit son tirage par e-mail. Tu peux aussi partager le lien d’invitation par WhatsApp.</li>
            </ol>
        </section>

        <section class="content-section">
            <h2>Est-ce un générateur de nombres ?</h2>
            <p>Non. C’est un générateur de tirage au sort pour les noms du Secret Santa, pas un générateur de nombres et pas une roue publique.</p>
            <h2>Faut-il un compte ?</h2>
            <p>Non. Tu n’as pas besoin de compte. Comme admin, tu indiques une adresse e-mail pour recevoir le lien admin.</p>
            <h2>Le tirage est-il équitable ?</h2>
            <p>Chaque personne offre un cadeau à une seule autre, personne ne tire son propre nom, et les exclusions sont respectées.</p>
        </section>

        <div class="cta-section">
            <h2>Tirer les noms</h2>
            <p>Le groupe est prêt en quelques minutes. Ensuite, tu partages le lien comme ça arrange la ronde.</p>
            <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button-white">Créer un groupe →</a>
        </div>
    </article>
</div>
