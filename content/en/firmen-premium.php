<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / <?php echo h(t('footer.premium')); ?>
        </div>

        <header class="page-header">
            <h1 class="page-title">Company Premium</h1>
            <p class="page-subtitle">Several groups, one licence</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Why I added this</h2>
                <p>
                    A normal group stays free. Premium is for clubs and companies that run several groups in one season: departments, sites, one draw for the apprentices and one for the team.
                </p>
                <p>
                    One licence covers the current season and as many groups as the plan allows. There is no account. You connect the licence to the group you are creating.
                </p>
            </section>

            <section class="content-section">
                <h2>What is included</h2>
                <ul>
<?php foreach (premium_feature_keys() as $feature_key): ?>
                    <li><?php echo h(t($feature_key)); ?></li>
<?php endforeach; ?>
                </ul>
                <?php echo premium_pricing_cards_html(); ?>
                <p><?php echo h(t('premium.not_included')); ?></p>
            </section>

            <section class="content-section">
                <h2>How a group becomes Premium</h2>
                <p>
                    When you create the group, or later in the admin area: enter the licence email. I send a confirmation link and a six-digit code to that address. The group is linked only after you confirm. Or you enter the licence code.
                </p>
                <p>
                    The logo and colours live once on the licence and apply to every new group. I send the overview link together with the code.
                </p>
                <p>
                    No licence yet? Further down you can pay by card or TWINT, or I can send an invoice.
                </p>
                <p>
                    How this data is handled is in the <a href="<?php echo h(i18n_href('datenschutz')); ?>#firmen-premium">privacy policy</a>.
                </p>
            </section>
            <?php echo premium_purchase_section_html(); ?>
        </article>
    </div>
