<?php

require_once __DIR__ . '/framework.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

// CSRF Token Tests
run_test("get_csrf_token: generates and stores token", function() {
    // Clear session for clean test
    $_SESSION = [];

    $token = get_csrf_token();

    assert_equals(64, strlen($token), "CSRF token should be 64 characters long");
    assert_true(isset($_SESSION['csrf_token']), "CSRF token should be stored in session");
    assert_equals($token, $_SESSION['csrf_token'], "Returned token should match session token");
});

run_test("get_csrf_token: returns existing token", function() {
    $_SESSION['csrf_token'] = "existing_token";

    $token = get_csrf_token();

    assert_equals("existing_token", $token, "Should return the existing session token");
});

run_test("verify_csrf_token: valid token", function() {
    $token = "test_token";
    $_SESSION['csrf_token'] = $token;

    assert_true(verify_csrf_token($token), "Should return true for matching token");
});

run_test("verify_csrf_token: invalid token", function() {
    $_SESSION['csrf_token'] = "valid_token";

    assert_true(!verify_csrf_token("invalid_token"), "Should return false for non-matching token");
});

run_test("verify_csrf_token: empty input", function() {
    $_SESSION['csrf_token'] = "valid_token";

    assert_true(!verify_csrf_token(""), "Should return false for empty token");
    assert_true(!verify_csrf_token(null), "Should return false for null token");
});

run_test("verify_csrf_token: missing session token", function() {
    $_SESSION = [];

    assert_true(!verify_csrf_token("any_token"), "Should return false if no token is in session");
});
