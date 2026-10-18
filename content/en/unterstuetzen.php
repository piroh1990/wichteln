<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / <?php echo h(t('footer.support')); ?>
        </div>

        <header class="page-header">
            <h1 class="page-title">Chip in (voluntary)</h1>
            <p class="page-subtitle">Free, with no ads and no account</p>
        </header>

        <article>
            <section class="content-section">
                <h2>The site stays free</h2>
                <p>
                    <strong>wichtlä.ch</strong> is free, with no ads and no account. We want it to stay that way.
                </p>
                <p>
                    A contribution is voluntary. It is not required in order to use any feature, and there is nothing in return. Whether you contribute or not changes nothing about the Secret Santa.
                </p>
            </section>

            <section class="content-section">
                <h2>Where the payment goes</h2>
                <p>
                    If you like, you can voluntarily contribute to running the site. The payment goes to <strong>Raths IT</strong>, Patrick Raths’s sole proprietorship. It is handled by <?php echo h(support_provider_name()); ?>, for example with TWINT or by card. You choose the amount yourself on the payment service’s page.
                </p>
                <p>
                    When you click, you leave wichtlä.ch. wichtlä.ch does not receive any payment data and does not pass on any Secret Santa group data.
                </p>
                <p class="support-page-action"><?php echo support_external_anchor('page', t('support.button'), 'button support-button'); ?></p>
                <p class="support-note"><?php echo h(t('support.fineprint')); ?></p>
                <p>
                    More about how payment data is handled is in the <a href="<?php echo h(i18n_href('datenschutz')); ?>#freiwillige-unterstuetzung">privacy policy</a>.
                </p>
            </section>
        </article>
    </div>
