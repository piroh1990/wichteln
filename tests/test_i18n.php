<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/functions.php';

function i18n_mail_needles() {
    return array(
        'Wunschliste',
        'Geschenk',
        'Hallo',
        'Spass',
        'Auflösung',
        'Teilnehmer',
        'Wichtel',
        'Gruppendetails',
        'Beschreibung',
        'Kalender',
        'Erinnerung',
        'Bitte',
        'Einladung',
        'Ausschluss',
        'festgelegt',
        'Willkommen',
        'Dein',
        'Deine',
        'Wichteln',
        'E-Mail',
    );
}

function assert_no_german_leftovers($text, $label) {
    foreach (i18n_mail_needles() as $needle) {
        if (strpos($text, $needle) !== false) {
            throw new Exception($label . ' still contains «' . $needle . '»');
        }
    }
}

function assert_placeholders_filled($text, $label) {
    $tokens = array('{name}', '{group}', '{count}', '{url}', '{date}', '{pairs}', '{sentence}', '{year}', '{tagline}', '{when}');
    foreach ($tokens as $token) {
        if (strpos($text, $token) !== false) {
            throw new Exception($label . ' still contains ' . $token);
        }
    }
}

function assert_localized_mail($lang, $subject, $html, $plain, $expected_subject, $must_contain) {
    assert_equals($expected_subject, $subject, $lang . ' subject');
    assert_true($subject !== '', $lang . ' subject is empty');
    assert_placeholders_filled($subject . "\n" . $html . "\n" . $plain, $lang . ' mail');
    assert_true(strpos($html, 'lang="' . $lang . '"') !== false, $lang . ' html lang');
    if ($lang === 'de') {
        assert_true(strpos($html, '/fr/') === false && strpos($html, '/en/') === false, 'German html has no language prefix');
        assert_true(strpos($plain, '/fr/') === false && strpos($plain, '/en/') === false, 'German plain has no language prefix');
    } else {
        assert_true(strpos($html, '/' . $lang . '/') !== false, $lang . ' public link prefix');
        assert_true(strpos($plain, '/' . $lang . '/') !== false, $lang . ' plain public link prefix');
    }
    foreach ($must_contain as $piece) {
        $in_html = strpos($html, $piece) !== false;
        $in_plain = strpos($plain, $piece) !== false;
        assert_true($in_html || $in_plain, $lang . ' mail is missing ' . $piece);
    }
    if ($lang !== 'de') {
        assert_no_german_leftovers($subject, $lang . ' subject');
        assert_no_german_leftovers($html, $lang . ' html');
        assert_no_german_leftovers($plain, $lang . ' plain');
    }
}

run_test('catalogs share keys, order and non-empty values', function () {
    $de = i18n_catalog('de');
    $fr = i18n_catalog('fr');
    $en = i18n_catalog('en');
    assert_equals(array_keys($de), array_keys($fr), 'fr keys match de');
    assert_equals(array_keys($de), array_keys($en), 'en keys match de');
    assert_true(count($de) > 400, 'catalog has the UI strings');
    foreach (array('de' => $de, 'fr' => $fr, 'en' => $en) as $lang => $catalog) {
        foreach ($catalog as $key => $value) {
            if (!is_string($value) || $value === '') {
                throw new Exception($lang . ' key ' . $key . ' is empty');
            }
        }
    }
    $german = file_get_contents(dirname(__DIR__) . '/lang/de.php');
    assert_true(strpos($german, 'ß') === false, 'German catalog uses ss');
});

run_test('partner registration admin wishlist reveal recovery and reminder mails are localized', function () {
    $group = array(
        'id' => 9,
        'name' => 'Pine',
        'budget' => '25',
        'description' => 'Meet at noon',
        'gift_exchange_date' => '2026-12-24',
        'language' => 'de',
    );
    $expected = array(
        'fr' => array(
            'partner' => 'Ton tirage au Secret Santa est fait 🎁',
            'registration' => 'Ton inscription au Secret Santa est confirmée ! 🎁',
            'admin' => 'Ton groupe Secret Santa «Pine» est créé ! 🎁',
            'wishlist' => 'Liste de souhaits mise à jour',
            'reveal' => 'Révélation : qui était le Père Noël secret de qui ?',
            'recovery' => 'Ton lien admin',
        ),
        'en' => array(
            'partner' => 'Your Secret Santa match is in 🎁',
            'registration' => 'You’re signed up for Secret Santa! 🎁',
            'admin' => 'Your Secret Santa group “Pine” is ready! 🎁',
            'wishlist' => 'Wishlist updated',
            'reveal' => 'Reveal: who was whose Secret Santa?',
            'recovery' => 'Your admin link',
        ),
        'de' => array(
            'partner' => 'Dein Wichtelpartner 🎁',
            'registration' => 'Willkommen beim Wichteln! 🎁',
            'admin' => 'Deine Wichtelgruppe "Pine" wurde erstellt! 🎁',
            'wishlist' => 'Wunschliste aktualisiert',
            'reveal' => 'Auflösung: Wer hat wem gewichtelt?',
            'recovery' => 'Dein Admin-Link',
        ),
    );

    foreach (array('de', 'fr', 'en') as $lang) {
        $fields = mail_present_group($group, $lang);
        $partner = array(
            'name' => 'Anna',
            'assigned_name' => 'Noah',
            'wishlist' => 'a red scarf',
            'budget' => $fields['budget'],
            'description' => $fields['description'],
            'gift_date' => $fields['gift_date'],
            'ics_url' => $fields['ics_url'],
            'participant_link' => 'https://wichtlä.ch/participant.php?token=abc',
            'language' => $lang,
        );
        $partner_html = create_html_email($partner);
        $partner_plain = create_partner_plain_text($partner);
        assert_localized_mail(
            $lang,
            partner_email_subject($lang),
            $partner_html,
            $partner_plain,
            $expected[$lang]['partner'],
            array('Anna', 'Noah', 'a red scarf', $fields['budget'], 'participant.php?token=abc')
        );
        assert_true(strpos($partner_html, 'participant.php?token=abc') !== false, $lang . ' partner html link');
        assert_true(strpos($partner_plain, 'participant.php?token=abc') !== false, $lang . ' partner plain link');
        assert_true(strpos($fields['ics_url'], 'lang=' . $lang . '&s=') !== false, $lang . ' ics url');

        $registration = array(
            'name' => 'Anna',
            'group_name' => 'Pine',
            'participant_link' => 'https://wichtlä.ch/participant.php?token=abc',
            'budget' => $fields['budget'],
            'description' => $fields['description'],
            'gift_date' => $fields['gift_date'],
            'ics_url' => $fields['ics_url'],
            'language' => $lang,
        );
        assert_localized_mail(
            $lang,
            registration_email_subject($lang),
            create_registration_email($registration),
            create_registration_plain_text($registration),
            $expected[$lang]['registration'],
            array('Anna', 'Pine', 'participant.php?token=abc')
        );

        $admin = array(
            'group_name' => 'Pine',
            'admin_link' => 'https://wichtlä.ch/admin.php?token=adm',
            'invite_link' => 'https://wichtlä.ch/register.php?token=inv',
            'budget' => $fields['budget'],
            'description' => $fields['description'],
            'gift_date' => $fields['gift_date'],
            'ics_url' => $fields['ics_url'],
            'language' => $lang,
        );
        assert_localized_mail(
            $lang,
            admin_created_email_subject('Pine', $lang),
            create_admin_email($admin),
            create_admin_plain_text($admin),
            $expected[$lang]['admin'],
            array('Pine', 'admin.php?token=adm', 'register.php?token=inv')
        );

        $wishlist_html = create_wishlist_update_email('Anna', 'Noah', 'a red scarf', $lang);
        $wishlist_plain = create_wishlist_plain_text('Anna', 'Noah', 'a red scarf', $lang);
        assert_localized_mail(
            $lang,
            wishlist_email_subject($lang),
            $wishlist_html,
            $wishlist_plain,
            $expected[$lang]['wishlist'],
            array('Anna', 'Noah', 'a red scarf')
        );

        $reveal = array(
            'name' => 'Anna',
            'group_name' => 'Pine',
            'pairs' => array(
                array('giver_name' => 'Anna', 'receiver_name' => 'Noah'),
                array('giver_name' => 'Noah', 'receiver_name' => 'Anna'),
            ),
            'participant_link' => 'https://wichtlä.ch/participant.php?token=abc',
            'language' => $lang,
        );
        $reveal_html = create_reveal_email($reveal);
        $reveal_plain = create_reveal_plain_text($reveal);
        assert_localized_mail(
            $lang,
            reveal_email_subject($lang),
            $reveal_html,
            $reveal_plain,
            $expected[$lang]['reveal'],
            array('Anna', 'Noah', 'Pine', 'participant.php?token=abc')
        );
        assert_true(strpos($reveal_html, 'participant.php?token=abc') !== false, $lang . ' reveal html link');
        assert_true(strpos($reveal_plain, 'participant.php?token=abc') !== false, $lang . ' reveal plain link');

        $recovery_groups = array(
            array('name' => 'Pine', 'admin_link' => 'https://wichtlä.ch/admin.php?token=adm'),
        );
        assert_localized_mail(
            $lang,
            admin_recovery_email_subject($lang),
            create_admin_recovery_email($recovery_groups, $lang),
            create_admin_recovery_plain_text($recovery_groups, $lang),
            $expected[$lang]['recovery'],
            array('Pine', 'admin.php?token=adm')
        );

        $sentence = gift_reminder_sentence(4, $lang);
        $reminder = array(
            'name' => 'Anna',
            'sentence' => $sentence,
            'group_name' => 'Pine',
            'gift_date' => $fields['gift_date'],
            'participant_link' => 'https://wichtlä.ch/participant.php?token=abc',
            'ics_url' => $fields['ics_url'],
            'language' => $lang,
        );
        $reminder_subject = reminder_email_subject($sentence, 'Pine', $lang);
        assert_localized_mail(
            $lang,
            $reminder_subject,
            create_gift_reminder_email($reminder),
            create_gift_reminder_plain_text($reminder),
            $reminder_subject,
            array('Anna', 'Pine', 'participant.php?token=abc')
        );
        if ($lang === 'fr') {
            assert_true(strpos($sentence, 'dans 4 jours') !== false, 'French reminder sentence');
            assert_true(strpos($reminder_subject, 'In 4 Tagen') === false, 'French reminder subject');
        }
        if ($lang === 'en') {
            assert_true(strpos($sentence, 'in 4 days') !== false, 'English reminder sentence');
        }
        if ($lang === 'de') {
            assert_equals('In 4 Tagen ist die Geschenkübergabe.', $sentence, 'German reminder sentence');
            assert_true(strpos(create_gift_reminder_email($reminder), '/fr/') === false, 'German mail has no fr prefix');
            assert_true(strpos(create_gift_reminder_email($reminder), '/en/') === false, 'German mail has no en prefix');
        }

        $ics = build_gift_ics($group, '20261007T120000Z', $lang);
        assert_true(strpos($ics, 'DTSTART;VALUE=DATE:20261224') !== false, $lang . ' ics date');
        assert_placeholders_filled($ics, $lang . ' ics');
        if ($lang === 'de') {
            assert_true(strpos($ics, 'SUMMARY:Geschenkübergabe: Pine') !== false, 'German ics summary');
            assert_equals('geschenkuebergabe.ics', t('calendar.filename'));
        } else {
            assert_no_german_leftovers($ics, $lang . ' ics');
            assert_true(strpos($ics, 'Geschenkübergabe') === false, $lang . ' ics summary');
        }
    }

    i18n_activate('fr');
    assert_equals('remise-cadeaux.ics', t('calendar.filename'));
    i18n_activate('en');
    assert_equals('gift-exchange.ics', t('calendar.filename'));
    i18n_activate('de');
});

run_test('reveal and reminder use the recipient language', function () {
    $sent = array();
    $sender = function ($to, $subject, $html, $plain) use (&$sent) {
        $sent[] = array($to, $subject, $html, $plain);
        return true;
    };
    $people = array(
        array(
            'id' => 1,
            'name' => 'Anna',
            'email' => 'anna@example.ch',
            'assigned_to' => 2,
            'language' => 'fr',
            'participant_token' => str_repeat('a', 32),
        ),
        array(
            'id' => 2,
            'name' => 'Noah',
            'email' => 'noah@example.ch',
            'assigned_to' => 1,
            'language' => 'en',
            'participant_token' => str_repeat('b', 32),
        ),
    );
    $group = array(
        'id' => 4,
        'name' => 'Pine',
        'gift_exchange_date' => '2026-12-24',
        'language' => 'de',
    );
    $reveal = deliver_group_reveal($people, $group, $sender);
    assert_equals(2, $reveal['sent'], 'Both languages are sent');
    assert_equals('Révélation : qui était le Père Noël secret de qui ?', $sent[0][1]);
    assert_equals('Reveal: who was whose Secret Santa?', $sent[1][1]);
    assert_true(strpos($sent[0][2], 'participant.php?token=' . str_repeat('a', 32)) !== false, 'French reveal html link');
    assert_true(strpos($sent[0][3], 'participant.php?token=' . str_repeat('a', 32)) !== false, 'French reveal plain link');
    assert_true(strpos($sent[1][2], 'participant.php?token=' . str_repeat('b', 32)) !== false, 'English reveal html link');
    assert_true(strpos($sent[1][3], 'participant.php?token=' . str_repeat('b', 32)) !== false, 'English reveal plain link');
    assert_true(strpos($sent[0][2], 'lang=fr&s=') === false || strpos($sent[0][2], '/fr/') !== false, 'French reveal links home in French');
    assert_no_german_leftovers($sent[0][2], 'fr reveal html');
    assert_no_german_leftovers($sent[1][3], 'en reveal plain');

    $sent = array();
    $reminder = deliver_gift_reminders($people, $group, $sender, '2026-12-20');
    assert_equals(2, $reminder['sent'], 'Reminder follows each participant');
    assert_true(strpos($sent[0][1], 'dans 4 jours') !== false, 'French reminder subject');
    assert_true(strpos($sent[0][3], 'lang=fr&s=') !== false, 'French reminder calendar link');
    assert_true(strpos($sent[1][1], 'in 4 days') !== false, 'English reminder subject');
    assert_true(strpos($sent[1][3], 'lang=en&s=') !== false, 'English reminder calendar link');
    assert_no_german_leftovers($sent[0][2] . $sent[0][3], 'fr reminder');
    assert_no_german_leftovers($sent[1][2] . $sent[1][3], 'en reminder');
});

run_test('plural, date and money follow the language', function () {
    i18n_activate('fr');
    assert_equals('0 participant', tn('admin.participants', 0), 'French zero is singular');
    assert_equals('1 participant', tn('admin.participants', 1), 'French one');
    assert_equals('2 participants', tn('admin.participants', 2), 'French other');
    assert_equals('18.10.2026', fmt_date('2026-10-18'), 'French date');
    assert_equals('25.00 CHF', fmt_money(25), 'French money');
    i18n_activate('en');
    assert_equals('0 participants', tn('admin.participants', 0), 'English zero is plural');
    assert_equals('1 participant', tn('admin.participants', 1), 'English one');
    assert_equals('18 Oct 2026', fmt_date('2026-10-18'), 'English date');
    assert_equals('CHF 25.00', fmt_money(25), 'English money');
    i18n_activate('de');
    assert_equals('0 Teilnehmer', tn('admin.participants', 0), 'German zero');
    assert_equals('1 Teilnehmer', tn('admin.participants', 1), 'German one');
    assert_equals('24.12.2026', fmt_date('2026-12-24'), 'German date');
    assert_equals('25.00 CHF', fmt_money('25'), 'German money');
});
