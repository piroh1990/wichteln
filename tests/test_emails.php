<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/functions.php';

// Test create_html_email
run_test("create_html_email: basic rendering", function() {
    $data = [
        'name' => "Max",
        'assigned_name' => "Erika",
        'wishlist' => "Schokolade, Wein",
        'budget' => "20 EUR",
        'description' => "Wichteln im Büro",
        'gift_date' => "2023-12-24"
    ];

    $html = create_html_email($data);

    assert_true(strpos($html, $data['name']) !== false, "HTML should contain recipient name");
    assert_true(strpos($html, $data['assigned_name']) !== false, "HTML should contain assigned partner name");
    assert_true(strpos($html, $data['wishlist']) !== false, "HTML should contain wishlist");
    assert_true(strpos($html, $data['budget']) !== false, "HTML should contain budget");
    assert_true(strpos($html, $data['description']) !== false, "HTML should contain description");
    assert_true(strpos($html, $data['gift_date']) !== false, "HTML should contain gift date");
    assert_true(strpos($html, 'Zum Teilnehmerbereich') === false, "HTML omits the participant link when none is passed");
});

run_test("create_html_email: personal participant link", function() {
    $link = 'https://wichtlä.ch/participant.php?token=abc';
    $data = array(
        'name' => 'Max',
        'assigned_name' => 'Erika',
        'wishlist' => 'Schokolade',
        'budget' => '20 CHF',
        'description' => 'Büro',
        'gift_date' => '24.12.2026',
        'participant_link' => $link,
        'language' => 'de',
    );
    $html = create_html_email($data);
    $plain = create_partner_plain_text($data);
    assert_true(strpos($html, $link) !== false, 'HTML shows the participant URL');
    assert_true(strpos($html, 'Zum Teilnehmerbereich') !== false, 'HTML has the participant button');
    assert_true(strpos($plain, 'Teilnehmerbereich: ' . $link) !== false, 'Plain text shows the participant URL');
    assert_equals('', mail_participant_link('  '));
    assert_true(strpos(mail_participant_link('abc def'), '/participant.php?token=abc%20def') !== false, 'Token is encoded in the site URL');

    $evil = create_html_email(array(
        'name' => 'Max',
        'assigned_name' => 'Erika',
        'wishlist' => '',
        'budget' => '20 CHF',
        'description' => 'Büro',
        'gift_date' => '24.12.2026',
        'participant_link' => 'https://wichtlä.ch/participant.php?token=abc" onclick="alert(1)',
    ));
    assert_true(strpos($evil, 'token=abc"') === false, 'A crafted quote stays inside the href');
    assert_true(strpos($evil, 'token=abc&quot;') !== false, 'The quote is escaped');

    $reveal = array(
        'name' => 'Max',
        'group_name' => 'Büro',
        'pairs' => array(array('giver_name' => 'Max', 'receiver_name' => 'Erika')),
        'participant_link' => $link,
        'language' => 'de',
    );
    assert_true(strpos(create_reveal_email($reveal), $link) !== false, 'Reveal HTML shows the participant URL');
    assert_true(strpos(create_reveal_plain_text($reveal), 'Teilnehmerbereich: ' . $link) !== false, 'Reveal plain text shows the participant URL');
    $draw = file_get_contents(dirname(__DIR__) . '/public/api/draw.php');
    assert_true(strpos($draw, 'send_partner_mail(') !== false, 'API draw uses the shared partner mail');
    assert_true(strpos($draw, 'participant_token') !== false, 'API draw passes the participant token');
    $admin = file_get_contents(dirname(__DIR__) . '/public/admin.php');
    assert_true(substr_count($admin, "isset(\$participant['participant_token'])") >= 2, 'Admin draw and resend pass the token');
});

run_test("create_html_email: empty wishlist", function() {
    $data = [
        'name' => "Max",
        'assigned_name' => "Erika",
        'wishlist' => "",
        'budget' => "20 EUR",
        'description' => "Wichteln im Büro",
        'gift_date' => "2023-12-24"
    ];

    $html = create_html_email($data);

    assert_true(strpos($html, "Wunschliste von") === false, "HTML should NOT contain wishlist section if empty");
});

run_test("create_html_email: HTML escaping", function() {
    $data = [
        'name' => "<b>Max</b>",
        'assigned_name' => "<script>alert('Erika')</script>",
        'wishlist' => "<i>Schokolade</i>",
        'budget' => "<u>20 EUR</u>",
        'description' => "<div>Wichteln</div>",
        'gift_date' => "<span>2023-12-24</span>"
    ];

    $html = create_html_email($data);

    assert_true(strpos($html, htmlspecialchars($data['name'])) !== false, "Name should be escaped");
    assert_true(strpos($html, htmlspecialchars($data['assigned_name'])) !== false, "Assigned name should be escaped");
    assert_true(strpos($html, htmlspecialchars($data['wishlist'])) !== false, "Wishlist should be escaped");
    assert_true(strpos($html, htmlspecialchars($data['budget'])) !== false, "Budget should be escaped");
    assert_true(strpos($html, htmlspecialchars($data['description'])) !== false, "Description should be escaped");
    assert_true(strpos($html, htmlspecialchars($data['gift_date'])) !== false, "Gift date should be escaped");

    assert_true(strpos($html, $data['name']) === false, "Unescaped name should not be present");
    assert_true(strpos($html, $data['assigned_name']) === false, "Unescaped assigned name should not be present");
});

// Test create_registration_email
run_test("create_registration_email: basic rendering", function() {
    $data = [
        'name' => "Max",
        'group_name' => "Büro Wichteln",
        'participant_link' => "http://example.com/p/token",
        'budget' => "20 EUR",
        'description' => "Wichteln im Büro",
        'gift_date' => "2023-12-24"
    ];

    $html = create_registration_email($data);

    assert_true(strpos($html, $data['name']) !== false, "HTML should contain recipient name");
    assert_true(strpos($html, $data['group_name']) !== false, "HTML should contain group name");
    assert_true(strpos($html, $data['participant_link']) !== false, "HTML should contain participant link");
    assert_true(strpos($html, $data['budget']) !== false, "HTML should contain budget");
    assert_true(strpos($html, $data['description']) !== false, "HTML should contain description");
    assert_true(strpos($html, $data['gift_date']) !== false, "HTML should contain gift date");
});

// Test create_admin_email
run_test("create_admin_email: basic rendering", function() {
    $data = [
        'group_name' => "Büro Wichteln",
        'admin_link' => "http://example.com/a/token",
        'invite_link' => "http://example.com/join/token",
        'budget' => "20 EUR",
        'description' => "Wichteln im Büro",
        'gift_date' => "2023-12-24"
    ];

    $html = create_admin_email($data);

    assert_true(strpos($html, $data['group_name']) !== false, "HTML should contain group name");
    assert_true(strpos($html, $data['admin_link']) !== false, "HTML should contain admin link");
    assert_true(strpos($html, $data['invite_link']) !== false, "HTML should contain invite link");
    assert_true(strpos($html, $data['budget']) !== false, "HTML should contain budget");
    assert_true(strpos($html, $data['description']) !== false, "HTML should contain description");
    assert_true(strpos($html, $data['gift_date']) !== false, "HTML should contain gift date");
});

// Test create_wishlist_update_email
run_test("create_wishlist_update_email: basic rendering", function() {
    $giver_name = "Max";
    $updater_name = "Erika";
    $wishlist = "Schokolade, Wein";

    $html = create_wishlist_update_email($giver_name, $updater_name, $wishlist);

    assert_true(strpos($html, htmlspecialchars($giver_name)) !== false, "HTML should contain giver name");
    assert_true(strpos($html, htmlspecialchars($updater_name)) !== false, "HTML should contain updater name");
    assert_true(strpos($html, htmlspecialchars($wishlist)) !== false, "HTML should contain wishlist");
    assert_true(strpos($html, "aktualisiert") !== false, "HTML should mention update");
});

run_test("create_wishlist_update_email: empty wishlist", function() {
    $giver_name = "Max";
    $updater_name = "Erika";
    $wishlist = "";

    $html = create_wishlist_update_email($giver_name, $updater_name, $wishlist);

    assert_true(strpos($html, htmlspecialchars($giver_name)) !== false, "HTML should contain giver name");
    assert_true(strpos($html, htmlspecialchars($updater_name)) !== false, "HTML should contain updater name");
    assert_true(strpos($html, "derzeit leer") !== false, "HTML should mention empty wishlist");
});

run_test("create_wishlist_update_email: HTML escaping", function() {
    $giver_name = "<script>alert('Max')</script>";
    $updater_name = "<b>Erika</b>";
    $wishlist = "<i>Schokolade</i>";

    $html = create_wishlist_update_email($giver_name, $updater_name, $wishlist);

    assert_true(strpos($html, htmlspecialchars($giver_name)) !== false, "Giver name should be escaped");
    assert_true(strpos($html, htmlspecialchars($updater_name)) !== false, "Updater name should be escaped");
    assert_true(strpos($html, htmlspecialchars($wishlist)) !== false, "Wishlist should be escaped");
    assert_true(strpos($html, $giver_name) === false, "Unescaped giver name should not be present");
});
