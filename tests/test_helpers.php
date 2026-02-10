<?php

require_once __DIR__ . '/../api/helpers.php';

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
