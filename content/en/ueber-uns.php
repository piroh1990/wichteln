<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / About us
        </div>

        <header class="page-header">
            <h1 class="page-title">About us</h1>
            <p class="page-subtitle">Why I built wichtlä.ch</p>
        </header>

        <article>
            <section class="content-section">
                <h2>What matters to me</h2>
                <p>
                    I want Secret Santa to be simple. No scraps of paper, no app and no hidden costs: you create a group, invite everyone, and the names are drawn fairly.
                </p>
                <p>
                    It works for family, friends or the team at work. A normal Secret Santa is free, with no ads and no account.<?php if (function_exists('premium_feature_enabled') && premium_feature_enabled()): ?> <?php echo str_replace('extra features', '<a href="' . h(i18n_href('firmen-premium')) . '">extra features</a>', t('premium.extra.about')); ?><?php endif; ?>
                </p>
            </section>

            <section class="content-section">
                <h2>Who is behind it</h2>
                <p>
                    I am Patrick Raths, and wichtlä.ch is my hobby. I built the site myself and run it through my company <a href="https://raths-it.ch"><strong>Raths IT</strong></a> in <strong>Switzerland</strong>. The idea came from a problem that many people know: every year you write names on slips, throw them into a hat and hope that nobody draws themselves. When not everyone is in the same place, it quickly gets complicated.
                </p>
                <p>
                    That is why I built wichtlä.ch. It is a lean tool with no frills and no account, and I only collect the data the Secret Santa needs.
                </p>
            </section>

            <section class="content-section">
                <h2>Why Wichtlä.ch?</h2>
                <p>Here is what I paid attention to while building it:</p>

                <ul>
                    <li><?php if (function_exists('premium_feature_enabled') && premium_feature_enabled()): ?><strong>Free:</strong> a normal Secret Santa stays without ads and without an account. <?php echo str_replace('extra features', '<a href="' . h(i18n_href('firmen-premium')) . '">extra features</a>', t('premium.extra.bullet')); ?><?php else: ?><strong>Free:</strong> a normal Secret Santa stays without ads and without an account.<?php endif; ?></li>
                    <li><strong>No account needed:</strong> you can start right away.</li>
                    <li><strong>Privacy under the Swiss Federal Act on Data Protection (FADP):</strong> I handle your data confidentially.</li>
                    <li><strong>Simple:</strong> in less than two minutes your group is created and the invitations are sent.</li>
                    <li><strong>A fair draw:</strong> the assignment is random, and you can set exclusions.</li>
                    <li><strong>Result by email:</strong> everyone receives their match conveniently by email.</li>
                    <li><strong>For every device:</strong> it runs on phone, tablet and computer, with no app.</li>
                </ul>
            </section>

            <?php
            require_once dirname(__DIR__, 2) . '/includes/support.php';
            echo support_about_html();
            ?>

            <section class="content-section">
                <h2>Contact</h2>
                <p>
                    Questions, ideas or feedback? I am glad to get every message. Just write to me at:
                </p>
                <p>
                    <strong>Email:</strong> <span id="email-address"></span>
                </p>

                <script>
                    // Protect the email address from spam bots
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
                    You will find more legal information in the <a href="<?php echo h(i18n_href('impressum')); ?>">legal notice</a> and in the <a href="<?php echo h(i18n_href('datenschutz')); ?>">privacy policy</a>.
                </p>
            </section>

            <div class="cta-section">
                <h2>Ready for your Secret Santa?</h2>
                <p><?php echo (function_exists('premium_feature_enabled') && premium_feature_enabled()) ? h(t('premium.extra.cta')) : 'Organise your Secret Santa in under 2 minutes — free, simple, and with no account needed!'; ?></p>
                <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button-white">Create a Secret Santa group →</a>
            </div>
        </article>
    </div>
