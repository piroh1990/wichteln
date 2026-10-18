<div class="content-page">
        <div class="breadcrumb">
            <a href="<?php echo h(i18n_href('index')); ?>">Home</a> / About us
        </div>

        <header class="page-header">
            <h1 class="page-title">About us</h1>
            <p class="page-subtitle">Who is behind Wichtlä.ch</p>
        </header>

        <article>
            <section class="content-section">
                <h2>Our mission</h2>
                <p>
                    <strong>Wichtlä.ch</strong> has a simple goal: to make organising a Secret Santa as straightforward as possible. No scraps of paper, no fiddly apps, no hidden costs. You create a group, invite the participants, and the names are drawn automatically and fairly. The name “Wichtlä” comes from “Wichtel”, the German and Swiss-German word for Secret Santa.
                </p>
                <p>
                    For family, friends or a company Secret Santa, the tool is for anyone who wants to add this tradition to Christmas. The whole platform is <strong>free</strong> and requires <strong>no account</strong>.
                </p>
            </section>

            <section class="content-section">
                <h2>Who is behind it?</h2>
                <p>
                    Wichtlä.ch is Patrick Raths’s hobby. He built the site himself and runs it through <a href="https://raths-it.ch"><strong>Raths IT</strong></a> from <strong>Switzerland</strong>. The idea came from an everyday problem: every year, in the run-up to Christmas, the same routine. Write names, put them in a hat, hope nobody draws themselves, then somehow get the slips to everyone. As soon as people are not in the same place, it quickly gets complicated.
                </p>
                <p>
                    Wichtlä.ch grew out of that need: a lean, fast online tool that solves exactly this problem. No unnecessary extras, no data hoarding, no forced account. Just a Secret Santa, the way it should be.
                </p>
            </section>

            <section class="content-section">
                <h2>Why Wichtlä.ch?</h2>
                <p>There are plenty of reasons why Wichtlä.ch is the right choice for your Secret Santa:</p>

                <ul>
                    <li><strong>100% free:</strong> no hidden costs, no premium features behind a paywall</li>
                    <li><strong>No account needed:</strong> start right away, without an account or handing over personal data</li>
                    <li><strong>Privacy under the Swiss Federal Act on Data Protection (FADP):</strong> your data is handled confidentially and protected under the strict Swiss Federal Act on Data Protection (FADP)</li>
                    <li><strong>Easy to use:</strong> your Secret Santa group is created and the invites are sent in under 2 minutes</li>
                    <li><strong>A fair draw:</strong> our algorithm matches people at random and fairly, and you can define exclusions</li>
                    <li><strong>Automatic email notification:</strong> every participant receives their match conveniently by email</li>
                    <li><strong>On every device:</strong> it works on phone, tablet and desktop, with no app to download</li>
                </ul>
            </section>

            <?php
            require_once dirname(__DIR__, 2) . '/includes/support.php';
            echo support_about_html();
            ?>

            <section class="content-section">
                <h2>Contact</h2>
                <p>
                    Got a question, a suggestion or feedback? We would love to hear from you. Just send us an email at:
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
                    You will find more legal information in our <a href="<?php echo h(i18n_href('impressum')); ?>">legal notice</a> and our <a href="<?php echo h(i18n_href('datenschutz')); ?>">privacy policy</a>.
                </p>
            </section>

            <div class="cta-section">
                <h2>Ready for your Secret Santa?</h2>
                <p>Organise your Secret Santa in under 2 minutes — free, simple, and with no account needed!</p>
                <a href="<?php echo h(i18n_href('create_group')); ?>" class="cta-button-white">Create a Secret Santa group →</a>
            </div>
        </article>
    </div>
