<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "Ist das ein Zahlengenerator?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Nein. Es ist ein Wichtel-Generator für Namen, kein allgemeiner Zahlengenerator und kein öffentliches Losrad."
            }
        },
        {
            "@type": "Question",
            "name": "Brauche ich ein Konto?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Nein. Du brauchst kein Konto. Als Admin hinterlegst du eine E-Mail, damit der Admin-Link ankommt."
            }
        },
        {
            "@type": "Question",
            "name": "Wie fair ist die Ziehung?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Jede Person beschenkt genau eine andere, niemand zieht sich selbst, und gesetzte Ausschlüsse werden eingehalten."
            }
        }
    ]
}
</script>
<div class="content-page">
    <div class="breadcrumb">
        <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / Wichtel-Generator
    </div>

    <header class="page-header">
        <h1 class="page-title">Wichtel-Generator: Namen online auslosen</h1>
        <p class="page-subtitle">Namen eintragen, Ausschlüsse setzen, Lose per Link, Mail oder WhatsApp verschicken. Gratis und ohne Anmeldung.</p>
    </header>

    <article>
        <section class="content-section">
            <p>Du trägst die Namen ein, legst fest, wer wen nicht ziehen soll, und lost danach aus. Die Ziehung ist zufällig: niemand zieht sich selbst, und die Ausschlüsse gelten. Das läuft im Browser, ohne App und ohne Konto.</p>
            <ol>
                <li><strong>Namen eingeben.</strong> Du erstellst eine Gruppe und sammelst die Teilnehmer über den Einladungslink.</li>
                <li><strong>Ausschlüsse festlegen.</strong> Paare oder andere Personen, die sich nicht beschenken sollen, kannst du ausschliessen.</li>
                <li><strong>Links verschicken.</strong> Wer eine E-Mail hinterlegt, bekommt das Los per Mail. Den Einladungslink kannst du auch per WhatsApp teilen.</li>
            </ol>
        </section>

        <section class="content-section">
            <h2>Ist das ein Zahlengenerator?</h2>
            <p>Nein. Es ist ein Wichtel-Generator für Namen, kein allgemeiner Zahlengenerator und kein öffentliches Losrad.</p>
            <h2>Brauche ich ein Konto?</h2>
            <p>Nein. Du brauchst kein Konto. Als Admin hinterlegst du eine E-Mail, damit der Admin-Link ankommt.</p>
            <h2>Wie fair ist die Ziehung?</h2>
            <p>Jede Person beschenkt genau eine andere, niemand zieht sich selbst, und gesetzte Ausschlüsse werden eingehalten.</p>
        </section>

        <div class="cta-section">
            <h2>Namen jetzt auslosen</h2>
            <p>Die Gruppe ist in ein paar Minuten bereit. Danach teilst du den Link, wie es für die Runde passt.</p>
            <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button-white">Jetzt Gruppe erstellen →</a>
        </div>
    </article>
</div>
