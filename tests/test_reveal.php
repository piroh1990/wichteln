<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/functions.php';

function reveal_sample_participants() {
    return array(
        array(
            'id' => '11',
            'name' => 'Bea',
            'email' => 'bea@example.com',
            'assigned_to' => '10',
        ),
        array(
            'id' => '10',
            'name' => 'Anna',
            'email' => 'anna@example.com',
            'assigned_to' => '12',
        ),
        array(
            'id' => '12',
            'name' => "O'Brien <script>",
            'email' => 'obrien@example.com',
            'assigned_to' => '11',
        ),
    );
}

run_test("build_reveal_pairs: Geber nach Name, Beschenkter aus assigned_to", function() {
    $pairs = build_reveal_pairs(reveal_sample_participants());

    assert_equals(3, count($pairs), "Three complete pairs");
    assert_equals('Anna', $pairs[0]['giver_name']);
    assert_equals("O'Brien <script>", $pairs[0]['receiver_name']);
    assert_equals('Bea', $pairs[1]['giver_name']);
    assert_equals('Anna', $pairs[1]['receiver_name']);
    assert_equals("O'Brien <script>", $pairs[2]['giver_name']);
    assert_equals('Bea', $pairs[2]['receiver_name']);
});

run_test("build_reveal_pairs: unvollständige Zuordnung fehlt", function() {
    $participants = reveal_sample_participants();
    $participants[1]['assigned_to'] = null;
    $pairs = build_reveal_pairs($participants);

    assert_equals(2, count($pairs), "Missing assignment is not a pair");
});

run_test("build_reveal_pairs: unbekannter Beschenkter fehlt", function() {
    $participants = reveal_sample_participants();
    $participants[0]['assigned_to'] = 99;
    $pairs = build_reveal_pairs($participants);

    assert_equals(2, count($pairs));
});

run_test("build_reveal_pairs: Zeilenumbruch im Namen wird entfernt", function() {
    $participants = array(
        array('id' => 1, 'name' => "Ann\na", 'email' => 'a@example.com', 'assigned_to' => 2),
        array('id' => 2, 'name' => "Bea", 'email' => 'b@example.com', 'assigned_to' => 1),
    );
    $pairs = build_reveal_pairs($participants);
    assert_equals('Ann a', $pairs[0]['giver_name']);
});

run_test("create_reveal_email: Liste und Escaping", function() {
    $pairs = build_reveal_pairs(reveal_sample_participants());
    $html = create_reveal_email(array(
        'name' => '<b>Max</b>',
        'group_name' => 'Büro "Nord" & Co',
        'pairs' => $pairs,
    ));

    assert_true(strpos($html, htmlspecialchars('<b>Max</b>', ENT_QUOTES, 'UTF-8')) !== false, "Recipient name is escaped");
    assert_true(strpos($html, '<b>Max</b>') === false, "Raw recipient markup is absent");
    assert_true(strpos($html, htmlspecialchars('Büro "Nord" & Co', ENT_QUOTES, 'UTF-8')) !== false, "Group name is escaped");
    assert_true(strpos($html, htmlspecialchars("O'Brien <script>", ENT_QUOTES, 'UTF-8')) !== false, "Pair names are escaped");
    assert_true(strpos($html, '<script>') === false, "Script tag is not raw HTML");
    assert_true(strpos($html, 'Geber') !== false, "Label Geber");
    assert_true(strpos($html, 'Beschenkter') !== false, "Label Beschenkter");
    assert_true(strpos($html, '3 Paare') !== false, "Pair count");
    assert_true(strpos($html, 'Auflösung') !== false, "Title");
});

run_test("create_reveal_plain_text: Geber -> Beschenkter ohne HTML", function() {
    $pairs = build_reveal_pairs(reveal_sample_participants());
    $plain = create_reveal_plain_text(array(
        'name' => 'Max',
        'group_name' => 'Büro',
        'pairs' => $pairs,
    ));

    assert_true(strpos($plain, 'Hallo Max,') !== false);
    assert_true(strpos($plain, 'Anna -> O\'Brien <script>') !== false, "Arrow list keeps the plain name");
    assert_true(strpos($plain, 'Bea -> Anna') !== false);
    assert_true(strpos($plain, "O'Brien <script> -> Bea") !== false);
    assert_true(strpos($plain, '<table') === false, "Plain text has no HTML table");
    assert_true(strpos($plain, '<p') === false, "Plain text has no HTML paragraphs");
    assert_true(strpos($plain, 'Geber -> Beschenkter') !== false);
});

run_test("create_reveal_email: Anrede ist persönlich, die Liste gleich", function() {
    $pairs = build_reveal_pairs(reveal_sample_participants());
    $for_anna = create_reveal_email(array('name' => 'Anna', 'group_name' => 'Büro', 'pairs' => $pairs));
    $for_bea = create_reveal_email(array('name' => 'Bea', 'group_name' => 'Büro', 'pairs' => $pairs));

    assert_true(strpos($for_anna, '>Anna<') !== false);
    assert_true(strpos($for_bea, '>Bea<') !== false);
    assert_true($for_anna !== $for_bea);
    assert_true(substr_count($for_anna, 'Beschenkter') === substr_count($for_bea, 'Beschenkter'));
});

run_test("deliver_group_reveal: zählt Versand, Fehler und fehlende Mail", function() {
    $participants = reveal_sample_participants();
    $participants[] = array(
        'id' => '13',
        'name' => 'Ohne',
        'email' => '',
        'assigned_to' => null,
    );
    // Make the draw complete: Ohne gives to Anna, and reroute the cycle.
    // Current: Anna -> O'Brien -> Bea -> Anna. Insert Ohne by changing Bea -> Ohne and Ohne -> Anna.
    $participants[0]['assigned_to'] = '13';
    $participants[3]['assigned_to'] = '10';
    $participants[2]['email'] = 'nicht-gültig';

    $calls = array();
    $result = deliver_group_reveal($participants, array('name' => 'Büro'), function ($to, $subject, $html, $plain) use (&$calls) {
        $calls[] = array($to, $subject, $html, $plain);
        return $to !== 'bea@example.com';
    });

    assert_equals('ok', $result['status']);
    assert_equals(1, $result['sent'], "Only Anna succeeds");
    assert_equals(1, $result['failed'], "Bea mail fails");
    assert_equals(2, $result['skipped'], "Empty and invalid addresses are skipped");
    assert_equals(2, count($calls));
    assert_equals(reveal_email_subject(), $calls[0][1]);
    assert_true(strpos($calls[0][2], 'Geber') !== false);
    assert_true(strpos($calls[0][3], ' -> ') !== false);
});

run_test("deliver_group_reveal: unvollständig sendet nichts", function() {
    $participants = reveal_sample_participants();
    $participants[0]['assigned_to'] = null;
    $called = false;
    $result = deliver_group_reveal($participants, array('name' => 'Büro'), function () use (&$called) {
        $called = true;
        return true;
    });

    assert_equals('incomplete', $result['status']);
    assert_equals(0, $result['sent']);
    assert_true($called === false, "Sender must not be called");
});

run_test("deliver_group_reveal: Ausnahme zählt als Fehler", function() {
    $participants = array(
        array('id' => 1, 'name' => 'Anna', 'email' => 'anna@example.com', 'assigned_to' => 2),
        array('id' => 2, 'name' => 'Bea', 'email' => 'bea@example.com', 'assigned_to' => 1),
    );
    $result = deliver_group_reveal($participants, array('name' => 'Büro'), function () {
        throw new Exception('smtp down');
    });

    assert_equals('failed', $result['status']);
    assert_equals(0, $result['sent']);
    assert_equals(2, $result['failed']);
});

run_test("deliver_group_reveal: niemand mit E-Mail", function() {
    $participants = array(
        array('id' => 1, 'name' => 'Anna', 'email' => '', 'assigned_to' => 2),
        array('id' => 2, 'name' => 'Bea', 'email' => '   ', 'assigned_to' => 1),
    );
    $result = deliver_group_reveal($participants, array('name' => 'Büro'), function () {
        return true;
    });

    assert_equals('no_recipients', $result['status']);
    assert_equals(0, $result['sent']);
    assert_equals(2, $result['skipped']);
});

run_test("reveal_result_message: Erfolg, Teilfehler, Totalausfall", function() {
    $ok = reveal_result_message(array('status' => 'ok', 'sent' => 4, 'failed' => 0, 'skipped' => 1));
    assert_equals('success', $ok['type']);
    assert_equals('Die Auflösung wurde an 4 Teilnehmer gesendet. 1 Teilnehmer ohne E-Mail wurde übersprungen.', $ok['text']);

    $partial = reveal_result_message(array('status' => 'ok', 'sent' => 1, 'failed' => 2, 'skipped' => 0));
    assert_equals('warning', $partial['type']);
    assert_true(strpos($partial['text'], 'an 1 Teilnehmer gesendet') !== false);
    assert_true(strpos($partial['text'], '2 E-Mails sind fehlgeschlagen') !== false);

    $failed = reveal_result_message(array('status' => 'failed', 'sent' => 0, 'failed' => 1, 'skipped' => 0));
    assert_equals('error', $failed['type']);
    assert_equals('Die Auflösung wurde an niemanden gesendet. 1 E-Mail ist fehlgeschlagen.', $failed['text']);

    $none = reveal_result_message(array('status' => 'no_recipients', 'sent' => 0, 'failed' => 0, 'skipped' => 3));
    assert_equals('error', $none['type']);
    assert_true(strpos($none['text'], 'an niemanden gesendet') !== false);
});

run_test("build_outgoing_mail: Klartext und HTML als multipart", function() {
    $mail = build_outgoing_mail('<p>Hallo</p>', true, "Hallo\nAnna -> Bea");

    assert_true(strpos($mail['headers'], 'multipart/alternative') !== false);
    assert_true(is_string($mail['boundary']) && $mail['boundary'] !== '');
    $plain_pos = strpos($mail['body'], "Hallo\nAnna -> Bea");
    $html_pos = strpos($mail['body'], '<p>Hallo</p>');
    assert_true($plain_pos !== false && $html_pos !== false && $plain_pos < $html_pos, "Plain part comes before HTML");
    assert_true(strpos($mail['body'], 'Content-Type: text/plain; charset=UTF-8') !== false);
    assert_true(strpos($mail['body'], 'Content-Type: text/html; charset=UTF-8') !== false);
});

run_test("build_outgoing_mail: HTML ohne Alternative bleibt text/html", function() {
    $mail = build_outgoing_mail('<p>Hallo</p>', true, null);
    assert_true(strpos($mail['headers'], 'Content-Type: text/html; charset=UTF-8') !== false);
    assert_true(strpos($mail['headers'], 'multipart/alternative') === false);
    assert_equals('<p>Hallo</p>', $mail['body']);
});

run_test("encode_mail_subject: ASCII bleibt, Umlaute werden kodiert", function() {
    assert_equals('Dein Wichtelpartner', encode_mail_subject('Dein Wichtelpartner'));
    $encoded = encode_mail_subject('Auflösung: Wer hat wem gewichtelt?');
    assert_true(strpos($encoded, '=?UTF-8?B?') === 0, "UTF-8 encoded-word");
    assert_true(strpos($encoded, "\n") === false, "No line break in subject");
    assert_equals('ohneZeile', encode_mail_subject("ohne\r\nZeile"));
});

run_test("format_reveal_sent_at: deutsches Datum", function() {
    assert_equals('06.10.2026, 18:30 Uhr', format_reveal_sent_at('2026-10-06 18:30:00'));
    assert_equals('', format_reveal_sent_at(''));
    assert_equals('', format_reveal_sent_at(null));
});
