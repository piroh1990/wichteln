<?php

$tests_passed = 0;
$tests_failed = 0;

function run_test($name, $callback) {
    global $tests_passed, $tests_failed;
    echo "Running test: $name... ";
    try {
        $callback();
        echo "\033[32mPASSED\033[0m\n";
        $tests_passed++;
    } catch (Exception $e) {
        echo "\033[31mFAILED\033[0m\n";
        echo "  Error: " . $e->getMessage() . "\n";
        $tests_failed++;
    }
}

function assert_equals($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        $msg = $message ? $message : "Expected " . var_export($expected, true) . " but got " . var_export($actual, true);
        throw new Exception($msg);
    }
}

function assert_true($condition, $message = '') {
    if (!$condition) {
        $msg = $message ? $message : "Expected true but got false";
        throw new Exception($msg);
    }
}

function print_summary() {
    global $tests_passed, $tests_failed;
    echo "\nSummary:\n";
    echo "  Passed: \033[32m$tests_passed\033[0m\n";
    echo "  Failed: \033[31m$tests_failed\033[0m\n";

    if ($tests_failed > 0) {
        exit(1);
    }
    exit(0);
}
