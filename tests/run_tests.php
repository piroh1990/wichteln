<?php
// Set up test environment
require_once __DIR__ . '/framework.php';

// Find and execute all test files
$test_files = glob(__DIR__ . '/test_*.php');

echo "Found " . count($test_files) . " test files.\n\n";

foreach ($test_files as $file) {
    echo "Running tests in " . basename($file) . "...\n";
    require $file;
}

print_summary();
