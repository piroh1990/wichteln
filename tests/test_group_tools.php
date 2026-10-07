<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/functions.php';

run_test('wishlist: only a real change counts', function() {
    assert_true(!wishlist_has_real_change("Schokolade\r\nWein", "Schokolade\nWein"), 'Line endings are the same wishlist');
    assert_true(!wishlist_has_real_change('  Tee  ', 'Tee'), 'Surrounding space is not a change');
    assert_true(wishlist_has_real_change('Tee', 'Tee und Kekse'), 'Added text is a change');
    assert_true(wishlist_has_real_change('', 'Buch'), 'Empty to text is a change');
    assert_true(!wishlist_has_real_change(null, '   '), 'Null and blank are both empty');
});

run_test('wishlist notice: sent, unchanged, rate limit', function() {
    assert_true(strpos(wishlist_update_notice('sent'), 'informiert') !== false, 'Sent notice names the mail');
    assert_true(strpos(wishlist_update_notice('unchanged'), 'unverändert') !== false, 'Unchanged notice does not claim a mail');
    assert_true(strpos(wishlist_update_notice('rate_limited'), 'Pause') !== false, 'Rate limit is explained');
    assert_true(strpos(wishlist_update_notice('not_drawn'), 'gespeichert') !== false, 'Before the draw the list is still saved');
});

run_test('consume_rate_limit: blocks the next call inside the window', function() {
    $identity = 'tools-' . bin2hex(random_bytes(8));
    assert_true(consume_rate_limit('test-bucket', $identity, 1, 600, false), 'First call is allowed');
    assert_true(!consume_rate_limit('test-bucket', $identity, 1, 600, false), 'Second call is blocked');
    $file = dirname(__DIR__) . '/logs/rate-limit/' . hash('sha256', 'test-bucket|' . $identity) . '.json';
    if (is_file($file)) {
        unlink($file);
    }
});

run_test('admin checklist: ready, done and reveal', function() {
    $people = array(
        array('email' => 'a@example.ch', 'wishlist' => 'Buch'),
        array('email' => '', 'wishlist' => '  '),
        array('email' => 'nicht-gültig', 'wishlist' => "Tee\r\n"),
    );
    $ready = admin_group_checklist($people, array('is_drawn' => 0));
    assert_equals(3, $ready['participants'], 'Participant count');
    assert_equals(1, $ready['with_email'], 'Only a valid email counts');
    assert_equals(2, $ready['with_wishlist'], 'Blank wishlist does not count');
    assert_equals('ready', $ready['draw'], 'Two or more people are ready to draw');
    assert_equals('open', $ready['reveal'], 'Reveal starts open');
    assert_equals('Auslosung bereit', admin_checklist_draw_label($ready['draw']), 'Ready label');

    $waiting = admin_group_checklist(array(array('email' => 'a@example.ch')), array());
    assert_equals('not_ready', $waiting['draw'], 'One person is not enough');
    assert_equals('Auslosung noch nicht bereit', admin_checklist_draw_label('not_ready'), 'Not-ready label');

    $done = admin_group_checklist($people, array('is_drawn' => 1, 'reveal_sent_at' => '2026-10-06 12:00:00'));
    assert_equals('done', $done['draw'], 'Drawn groups are done');
    assert_equals('sent', $done['reveal'], 'Reveal timestamp marks the mail as sent');
    assert_equals('Auflösung gesendet', admin_checklist_reveal_label('sent'), 'Reveal label');
    assert_equals('Auflösung offen', admin_checklist_reveal_label('open'), 'Open reveal label');
});

run_test('exclusion message: one way and both ways', function() {
    $one = exclusion_save_message('added', null);
    assert_equals('success', $one['type'], 'One direction succeeds');
    assert_true(strpos($one['text'], 'hinzugefügt') !== false, 'One-way text');

    $dup = exclusion_save_message('duplicate', null);
    assert_equals('error', $dup['type'], 'Duplicate is an error');

    $both = exclusion_save_message('added', 'added');
    assert_true(strpos($both['text'], 'beide Richtungen') !== false, 'Both directions are named');

    $half = exclusion_save_message('duplicate', 'added');
    assert_equals('success', $half['type'], 'The missing direction is still a success');
    assert_true(strpos($half['text'], 'Gegenrichtung') !== false, 'Counter-direction is mentioned');

    $none = exclusion_save_message('duplicate', 'duplicate');
    assert_equals('error', $none['type'], 'Both existing is an error');
    assert_equals('error', exclusion_save_message('invalid', null)['type'], 'Invalid pair is an error');
});

run_test('bulk import: names, emails and validation', function() {
    $raw = "Anna\nPeter, peter@example.ch\nMaria; maria@example.ch\nAnna, Peter\nonly@example.ch\n\nTim\ttim@example.ch\n";
    $parsed = parse_bulk_participants($raw);
    assert_equals(1, count($parsed['errors']), 'A bare email is rejected');
    assert_true(strpos($parsed['errors'][0], 'Zeile 5') !== false, 'The error names the line');
    assert_equals(5, count($parsed['rows']), 'Five valid rows remain');
    assert_equals('Anna', $parsed['rows'][0]['name'], 'Name only');
    assert_equals(null, $parsed['rows'][0]['email'], 'No email');
    assert_equals('peter@example.ch', $parsed['rows'][1]['email'], 'Comma email');
    assert_equals('maria@example.ch', $parsed['rows'][2]['email'], 'Semicolon email');
    assert_equals('Anna, Peter', $parsed['rows'][3]['name'], 'Comma inside a name stays');
    assert_equals('tim@example.ch', $parsed['rows'][4]['email'], 'Tab email');

    $angle = parse_bulk_participant_line('Sara <sara@example.ch>');
    assert_equals('Sara', $angle['name'], 'Angle-bracket name');
    assert_equals('sara@example.ch', $angle['email'], 'Angle-bracket email');

    $dupes = parse_bulk_participants("A, a@example.ch\nB, A@example.ch");
    assert_equals(1, count($dupes['errors']), 'Duplicate emails are rejected');
    assert_equals(1, count($dupes['rows']), 'The first email is kept');

    $too_many = parse_bulk_participants("A\nB\nC", 2);
    assert_true(count($too_many['errors']) > 0, 'The row cap is reported');
});

run_test('gift reminder sentence and due window', function() {
    assert_equals(4, gift_days_until('2026-12-24', '2026-12-20'), 'Four days until the date');
    assert_equals(0, gift_days_until('2026-12-24', '2026-12-24'), 'Same day is zero');
    assert_equals(-2, gift_days_until('2026-12-22', '2026-12-24'), 'Past dates are negative');
    assert_equals(null, gift_days_until('2026-02-31', '2026-02-01'), 'Impossible dates are rejected');
    assert_equals('In 4 Tagen ist die Geschenkübergabe.', gift_reminder_sentence(4), 'Plural sentence');
    assert_equals('Morgen ist die Geschenkübergabe.', gift_reminder_sentence(1), 'Tomorrow');
    assert_equals('Heute ist die Geschenkübergabe.', gift_reminder_sentence(0), 'Today');
    assert_true(gift_reminder_due('2026-12-24', null, '2026-12-20', 7), 'Inside the window');
    assert_true(!gift_reminder_due('2026-12-24', null, '2026-12-01', 7), 'Too early for the cron window');
    assert_true(!gift_reminder_due('2026-12-24', '2026-12-20 08:00:00', '2026-12-20', 7), 'Already sent is not due');
});

run_test('gift reminder delivery is idempotent at the message layer', function() {
    $sent = array();
    $sender = function ($to, $subject, $html, $plain) use (&$sent) {
        $sent[] = array($to, $subject, $html, $plain);
        return true;
    };
    $group = array(
        'id' => 7,
        'name' => 'Familie',
        'gift_exchange_date' => '2026-12-24',
    );
    $people = array(
        array('name' => 'Anna', 'email' => 'anna@example.ch', 'participant_token' => str_repeat('a', 32)),
        array('name' => 'Ohne', 'email' => '', 'participant_token' => str_repeat('b', 32)),
    );
    $result = deliver_gift_reminders($people, $group, $sender, '2026-12-20');
    assert_equals('ok', $result['status'], 'One recipient is enough');
    assert_equals(1, $result['sent'], 'Only the address with email');
    assert_equals(1, $result['skipped'], 'Missing email is skipped');
    assert_equals(1, count($sent), 'Sender called once');
    assert_true(strpos($sent[0][1], 'In 4 Tagen') !== false, 'Subject names the remaining days');
    assert_true(strpos($sent[0][2], 'In Kalender speichern') !== false, 'HTML offers the calendar file');
    assert_true(strpos($sent[0][3], 'anna@example.ch') === false, 'Plain text does not leak other addresses');

    $none = deliver_gift_reminders($people, array('name' => 'Ohne Datum'), $sender, '2026-12-20');
    assert_equals('no_date', $none['status'], 'No date means no mail');
    assert_equals(1, count($sent), 'Sender was not called again');

    $message = gift_reminder_result_message($result);
    assert_equals('success', $message['type'], 'Partial skip is still a success');
    assert_true(strpos($message['text'], '1 Teilnehmer') !== false, 'Count is in the flash text');
});

run_test('ics download contains the gift date and escapes commas', function() {
    $ics = build_gift_ics(array(
        'id' => 3,
        'name' => 'Anna, Peter',
        'gift_exchange_date' => '2026-12-24',
    ), '20261007T120000Z');
    assert_true(strpos($ics, 'DTSTART;VALUE=DATE:20261224') !== false, 'Start date');
    assert_true(strpos($ics, 'DTEND;VALUE=DATE:20261225') !== false, 'End date is the next day');
    assert_true(strpos($ics, 'SUMMARY:Geschenkübergabe: Anna\, Peter') !== false, 'Comma is escaped');
    assert_true(strpos($ics, "\r\n") !== false, 'ICS uses CRLF');
    assert_equals('', build_gift_ics(array('name' => 'Leer', 'gift_exchange_date' => '')), 'No date yields no file');

    $url = gift_ics_url('teilnehmer', str_repeat('ab', 16));
    assert_true(strpos($url, 'kalender.php?rolle=teilnehmer&token=') !== false, 'Calendar URL carries the role');
    assert_equals('', gift_ics_url('gast', str_repeat('ab', 16)), 'Unknown role has no URL');
});

run_test('clean urls, sitemap and robots', function() {
    $htaccess = file_get_contents(dirname(__DIR__) . '/public/.htaccess');
    foreach (public_clean_slugs() as $slug) {
        assert_true(strpos($htaccess, $slug) !== false, 'Rewrite lists ' . $slug);
        assert_true(is_file(dirname(__DIR__) . '/public/' . $slug . '.php'), $slug . '.php exists');
    }
    assert_true(strpos($htaccess, '!-f') !== false, 'Existing files are left alone so .php still works');

    $sitemap = file_get_contents(dirname(__DIR__) . '/public/sitemap.xml');
    assert_true(strpos($sitemap, 'participant.php') === false, 'Tokenless participant page is not in the sitemap');
    assert_true(strpos($sitemap, 'https://xn--wichtl-gua.ch/faq') !== false, 'FAQ uses the clean URL');
    assert_true(strpos($sitemap, 'faq.php') === false, 'Sitemap FAQ has no .php');
    assert_true(strpos($sitemap, '2026-10-07') !== false, 'lastmod is updated');
    assert_true(strpos($sitemap, '/impressum') !== false, 'Impressum stays listed');
    assert_true(strpos($sitemap, '/datenschutz') !== false, 'Privacy page stays listed');

    $robots = file_get_contents(dirname(__DIR__) . '/public/robots.txt');
    assert_true(strpos($robots, 'Disallow: /participant.php') !== false, 'Participant URLs are disallowed');
    assert_true(strpos($robots, 'Disallow: /admin.php') !== false, 'Admin URLs are disallowed');
    assert_true(strpos($robots, 'Disallow: /kalender.php') !== false, 'Calendar token URLs are disallowed');
    assert_true(strpos($robots, 'token=') !== false, 'Query tokens are mentioned');
});

run_test('copy matches reveal, recovery and the homepage typo fix', function() {
    $home = file_get_contents(dirname(__DIR__) . '/public/index.php');
    assert_true(strpos($home, 'Auschlüsse') === false, 'Typo is gone');
    assert_true(strpos($home, 'Ausschlüsse möglich') !== false, 'Correct spelling is on the homepage');
    assert_true(strpos($home, 'Niemand außer dir kennt alle Zuordnungen') === false, 'Old secrecy claim is gone');
    assert_true(strpos($home, 'Auflösungs-Mail') !== false, 'Homepage mentions the reveal mail');

    $faq = file_get_contents(dirname(__DIR__) . '/public/faq.php');
    assert_true(strpos($faq, 'Los abrufen') !== false, 'The old button is named so we can say it does not exist');
    assert_true(strpos($faq, 'keinen Knopf') !== false, 'Homepage lot lookup is retracted');
    assert_true(strpos($faq, 'keine Wiederherstellungsmöglichkeit') === false, 'Recovery is no longer described as impossible');
    assert_true(strpos($faq, 'auch nicht der Admin') === false, 'Admin secrecy is no longer absolute');
    assert_true(strpos($faq, 'Auflösungs-Mail') !== false, 'Reveal mail is documented');
    assert_true(strpos($faq, '/admin-link') !== false, 'FAQ links to recovery');
    assert_true(strpos($faq, 'google_ads.php') === false, 'FAQ has no ads');

    $recovery = file_get_contents(dirname(__DIR__) . '/public/admin-link.php');
    assert_true(strpos($recovery, 'csrf_input()') !== false, 'Recovery form has CSRF');
    assert_true(strpos($recovery, 'captcha.php') !== false, 'Recovery form has the captcha');
    assert_true(strpos($recovery, 'admin_link_recovery_allowed') !== false, 'Recovery is rate limited');
    assert_true(strpos($recovery, 'google_ads.php') === false, 'Recovery page has no ads');

    $admin = file_get_contents(dirname(__DIR__) . '/public/admin.php');
    assert_true(strpos($admin, 'id="admin-checklist"') !== false, 'Checklist is rendered');
    assert_true(strpos($admin, 'name="bidirectional"') !== false, 'Two-way exclusion checkbox exists');
    assert_true(strpos($admin, 'name="bulk_participants"') !== false, 'Bulk import field exists');
    assert_true(strpos($admin, 'name="send_reminder"') !== false, 'Reminder button exists');
    assert_true(strpos($admin, 'confirm_reminder_resend') !== false, 'Resend needs confirmation');
    assert_true(strpos($admin, 'In Kalender speichern') !== false, 'Admin calendar link exists');
});

run_test('reminder column is in setup and the migration', function() {
    $setup = file_get_contents(dirname(__DIR__) . '/database/setup.sql');
    $migration = file_get_contents(dirname(__DIR__) . '/database/migrations/20261007_add_reminder_sent_at.sql');
    assert_true(strpos($setup, 'reminder_sent_at') !== false, 'New installs create the column');
    assert_true(strpos($migration, 'phpMyAdmin') !== false, 'Migration explains phpMyAdmin');
    assert_true(strpos($migration, 'ADD COLUMN `reminder_sent_at`') !== false, 'Migration adds the column');
    assert_true(strpos($migration, 'USE ') === false, 'Migration does not switch databases');
});
