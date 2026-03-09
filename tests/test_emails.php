<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/functions.php';

// Test create_html_email
run_test("create_html_email: basic rendering", function() {
    $name = "Max";
    $assigned_name = "Erika";
    $wishlist = "Schokolade, Wein";
    $budget = "20 EUR";
    $description = "Wichteln im Büro";
    $gift_date = "2023-12-24";

    $html = create_html_email($name, $assigned_name, $wishlist, $budget, $description, $gift_date);

    assert_true(strpos($html, $name) !== false, "HTML should contain recipient name");
    assert_true(strpos($html, $assigned_name) !== false, "HTML should contain assigned partner name");
    assert_true(strpos($html, $wishlist) !== false, "HTML should contain wishlist");
    assert_true(strpos($html, $budget) !== false, "HTML should contain budget");
    assert_true(strpos($html, $description) !== false, "HTML should contain description");
    assert_true(strpos($html, $gift_date) !== false, "HTML should contain gift date");
});

run_test("create_html_email: empty wishlist", function() {
    $name = "Max";
    $assigned_name = "Erika";
    $wishlist = "";
    $budget = "20 EUR";
    $description = "Wichteln im Büro";
    $gift_date = "2023-12-24";

    $html = create_html_email($name, $assigned_name, $wishlist, $budget, $description, $gift_date);

    assert_true(strpos($html, "Wunschliste von") === false, "HTML should NOT contain wishlist section if empty");
});

run_test("create_html_email: HTML escaping", function() {
    $name = "<b>Max</b>";
    $assigned_name = "<script>alert('Erika')</script>";
    $wishlist = "<i>Schokolade</i>";
    $budget = "<u>20 EUR</u>";
    $description = "<div>Wichteln</div>";
    $gift_date = "<span>2023-12-24</span>";

    $html = create_html_email($name, $assigned_name, $wishlist, $budget, $description, $gift_date);

    assert_true(strpos($html, htmlspecialchars($name)) !== false, "Name should be escaped");
    assert_true(strpos($html, htmlspecialchars($assigned_name)) !== false, "Assigned name should be escaped");
    assert_true(strpos($html, htmlspecialchars($wishlist)) !== false, "Wishlist should be escaped");
    assert_true(strpos($html, htmlspecialchars($budget)) !== false, "Budget should be escaped");
    assert_true(strpos($html, htmlspecialchars($description)) !== false, "Description should be escaped");
    assert_true(strpos($html, htmlspecialchars($gift_date)) !== false, "Gift date should be escaped");

    assert_true(strpos($html, $name) === false, "Unescaped name should not be present");
    assert_true(strpos($html, $assigned_name) === false, "Unescaped assigned name should not be present");
});

// Test create_registration_email
run_test("create_registration_email: basic rendering", function() {
    $name = "Max";
    $group_name = "Büro Wichteln";
    $participant_link = "http://example.com/p/token";
    $budget = "20 EUR";
    $description = "Wichteln im Büro";
    $gift_date = "2023-12-24";

    $html = create_registration_email($name, $group_name, $participant_link, $budget, $description, $gift_date);

    assert_true(strpos($html, $name) !== false, "HTML should contain recipient name");
    assert_true(strpos($html, $group_name) !== false, "HTML should contain group name");
    assert_true(strpos($html, $participant_link) !== false, "HTML should contain participant link");
    assert_true(strpos($html, $budget) !== false, "HTML should contain budget");
    assert_true(strpos($html, $description) !== false, "HTML should contain description");
    assert_true(strpos($html, $gift_date) !== false, "HTML should contain gift date");
});

// Test create_admin_email
run_test("create_admin_email: basic rendering", function() {
    $group_name = "Büro Wichteln";
    $admin_link = "http://example.com/a/token";
    $invite_link = "http://example.com/join/token";
    $budget = "20 EUR";
    $description = "Wichteln im Büro";
    $gift_date = "2023-12-24";

    $html = create_admin_email($group_name, $admin_link, $invite_link, $budget, $description, $gift_date);

    assert_true(strpos($html, $group_name) !== false, "HTML should contain group name");
    assert_true(strpos($html, $admin_link) !== false, "HTML should contain admin link");
    assert_true(strpos($html, $invite_link) !== false, "HTML should contain invite link");
    assert_true(strpos($html, $budget) !== false, "HTML should contain budget");
    assert_true(strpos($html, $description) !== false, "HTML should contain description");
    assert_true(strpos($html, $gift_date) !== false, "HTML should contain gift date");
});
