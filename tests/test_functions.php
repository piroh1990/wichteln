<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/functions.php';

// Test default length (32 bytes = 64 hex characters)
run_test("generate_token: default length", function() {
    $token = generate_token();
    assert_equals(64, strlen($token), "Default token should be 64 characters long");
    assert_true(ctype_xdigit($token), "Token should only contain hex characters");
});

// Test custom length
run_test("generate_token: custom length", function() {
    $length = 16;
    $token = generate_token($length);
    assert_equals(32, strlen($token), "Token should be 32 characters long for length 16");
    assert_true(ctype_xdigit($token), "Token should only contain hex characters");
});

// Test uniqueness
run_test("generate_token: uniqueness", function() {
    $token1 = generate_token();
    $token2 = generate_token();
    assert_true($token1 !== $token2, "Two generated tokens should be different");
});

// Test different lengths
run_test("generate_token: multiple lengths", function() {
    foreach ([8, 16, 32, 64] as $length) {
        $token = generate_token($length);
        assert_equals($length * 2, strlen($token), "Token length should be double the input bytes");
    }
});
