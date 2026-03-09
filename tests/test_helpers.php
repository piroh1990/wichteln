<?php

require_once __DIR__ . '/../public/api/helpers.php';

// Test simple string
run_test("sanitize_for_api: simple string", function() {
    $input = "Hello World";
    $expected = "Hello World";
    $actual = sanitize_for_api($input);
    assert_equals($expected, $actual, "Should return simple string as is");
});

// Test HTML entities
run_test("sanitize_for_api: HTML entities", function() {
    $input = "<script>alert('XSS')</script>";
    $expected = "&lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;";
    $actual = sanitize_for_api($input);
    assert_equals($expected, $actual, "Should escape HTML characters");
});

// Test nested array
run_test("sanitize_for_api: nested array", function() {
    $input = [
        'name' => 'John <Doe>',
        'details' => [
            'bio' => '<b>Developer</b>',
            'hobbies' => ['coding', '<i>reading</i>']
        ]
    ];

    $expected = [
        'name' => 'John &lt;Doe&gt;',
        'details' => [
            'bio' => '&lt;b&gt;Developer&lt;/b&gt;',
            'hobbies' => ['coding', '&lt;i&gt;reading&lt;/i&gt;']
        ]
    ];

    $actual = sanitize_for_api($input);
    assert_equals($expected, $actual, "Should recursively sanitize arrays");
});

// Test mixed types (int/float/bool should be untouched or handled safely)
run_test("sanitize_for_api: mixed types", function() {
    $input = [
        'id' => 123,
        'active' => true,
        'score' => 99.9,
        'null' => null
    ];

    // The function returns $data if not array or string.
    // So integers/booleans/null remain as is.
    $expected = $input;

    $actual = sanitize_for_api($input);
    assert_equals($expected, $actual, "Should preserve non-string types");
});

// Test empty inputs
run_test("sanitize_for_api: empty inputs", function() {
    assert_equals("", sanitize_for_api(""), "Empty string should remain empty");
    assert_equals([], sanitize_for_api([]), "Empty array should remain empty");
});

// Test validate_input email validation
run_test("validate_input: email validation", function() {
    $field = "Email";

    // Valid emails
    assert_equals(null, validate_input($field, "test@example.com", ['email']), "Should accept standard email");
    assert_equals(null, validate_input($field, "user.name+tag@sub.example.com", ['email']), "Should accept complex email");
    assert_equals(null, validate_input($field, "a@b.cd", ['email']), "Should accept short domain");

    // Invalid emails
    assert_equals("Email muss eine gültige E-Mail sein", validate_input($field, "plainaddress", ['email']), "Should reject plain string");
    assert_equals("Email muss eine gültige E-Mail sein", validate_input($field, "#@%^%#$@#$@#.com", ['email']), "Should reject garbage");
    assert_equals("Email muss eine gültige E-Mail sein", validate_input($field, "@example.com", ['email']), "Should reject missing user part");
    assert_equals("Email muss eine gültige E-Mail sein", validate_input($field, "Joe Smith <email@example.com>", ['email']), "Should reject name with email");
    assert_equals("Email muss eine gültige E-Mail sein", validate_input($field, "email.example.com", ['email']), "Should reject missing @");
    assert_equals("Email muss eine gültige E-Mail sein", validate_input($field, "email@example@example.com", ['email']), "Should reject double @");
    assert_equals("Email muss eine gültige E-Mail sein", validate_input($field, "email@example", ['email']), "Should reject missing TLD dot");
    assert_equals("Email muss eine gültige E-Mail sein", validate_input($field, "email@.com", ['email']), "Should reject domain starting with dot");

    // Edge case: empty string
    assert_equals("Email muss eine gültige E-Mail sein", validate_input($field, "", ['email']), "Empty string with 'email' rule should be invalid");

    // Mixed rules: required + email
    assert_equals("Email ist erforderlich", validate_input($field, "", ['required', 'email']), "Empty string with 'required' + 'email' should hit 'required' first");
});
