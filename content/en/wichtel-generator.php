<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "FAQPage",
    "mainEntity": [
        {
            "@type": "Question",
            "name": "Is this a number generator?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "No. It is a Secret Santa generator for names, not a general number generator and not a public wheel."
            }
        },
        {
            "@type": "Question",
            "name": "Do I need an account?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "No. You do not need an account. As the admin, you leave an email address so the admin link can arrive."
            }
        },
        {
            "@type": "Question",
            "name": "Is the draw fair?",
            "acceptedAnswer": {
                "@type": "Answer",
                "text": "Each person gives a gift to exactly one other person, nobody draws themselves, and the exclusions you set are kept."
            }
        }
    ]
}
</script>
<div class="content-page">
    <div class="breadcrumb">
        <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / Secret Santa generator
    </div>

    <header class="page-header">
        <h1 class="page-title">Secret Santa generator: draw names online</h1>
        <p class="page-subtitle">Enter the names, set exclusions, then share the links by email or WhatsApp. Free, and no account.</p>
    </header>

    <article>
        <section class="content-section">
            <p>You enter the names, decide who should not draw whom, and then run the draw. It is random: nobody draws themselves, and the exclusions hold. It runs in the browser, with no app and no account.</p>
            <ol>
                <li><strong>Enter the names.</strong> You create a group and collect everyone through the invite link.</li>
                <li><strong>Set exclusions.</strong> You can keep couples, or anyone else who should not buy for each other, out of the draw.</li>
                <li><strong>Send the links.</strong> Anyone who leaves an email address gets their match by email. You can also share the invite link on WhatsApp.</li>
            </ol>
        </section>

        <section class="content-section">
            <h2>Is this a number generator?</h2>
            <p>No. It is a Secret Santa generator for names, not a general number generator and not a public wheel.</p>
            <h2>Do I need an account?</h2>
            <p>No. You do not need an account. As the admin, you leave an email address so the admin link can arrive.</p>
            <h2>Is the draw fair?</h2>
            <p>Each person gives a gift to exactly one other person, nobody draws themselves, and the exclusions you set are kept.</p>
        </section>

        <div class="cta-section">
            <h2>Draw the names</h2>
            <p>The group is ready in a few minutes. After that, you share the link however the round prefers.</p>
            <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button-white">Create a group →</a>
        </div>
    </article>
</div>
