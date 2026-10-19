<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / <?php echo h(t('footer.support')); ?>
        </div>

        <header class="page-header">
            <h1 class="page-title">Chip in (voluntary)</h1>
            <p class="page-subtitle">A hobby from Switzerland</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Why I built this site</h2>
                <p>
                    wichtlä.ch is my hobby. I built the site myself, because none of the existing Secret Santa sites really suited me: too many ads, too complicated, often with an account to create. I wanted something simple and clean.
                </p>
                <p>
                    <?php echo (function_exists('premium_feature_enabled') && premium_feature_enabled()) ? h(t('premium.extra.support')) : 'The server and running the site cost some money and a lot of free time. If you like, a voluntary contribution helps keep the site free and ad-free.'; ?>
                </p>
            </section>

            <section class="content-section">
                <h2>Where the payment goes</h2>
                <p>
                    The payment goes to <strong>Raths IT</strong>, my sole proprietorship. It is handled by <?php echo h(support_provider_name()); ?>, for example with TWINT or by card. You choose the amount yourself on the payment service’s page.
                </p>
                <p>
                    A contribution is voluntary and is not required in order to use any feature.
                </p>
                <p>
                    When you click, you leave wichtlä.ch. I do not receive any payment data, and no Secret Santa group data is passed on to the payment service.
                </p>
                <p class="support-page-action"><?php echo support_external_anchor('page', t('support.button'), 'button support-button'); ?></p>
                <p class="support-note"><?php echo h(t('support.fineprint')); ?></p>
                <p>
                    More about how payment data is handled is in the <a href="<?php echo h(i18n_href('datenschutz')); ?>#freiwillige-unterstuetzung">privacy policy</a>.
                </p>
            </section>
        </article>
    </div>
