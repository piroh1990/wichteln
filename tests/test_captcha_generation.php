<?php
// Mock session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// buffer output
ob_start();
// Use relative path to include captcha.php from tests directory
include __DIR__ . '/../public/captcha.php';
$output = ob_get_clean();

// Check session
if (!isset($_SESSION['captcha_code'])) {
    echo "FAIL: Session captcha_code not set\n";
    exit(1);
}

if (!preg_match('/^\d{5}$/', $_SESSION['captcha_code'])) {
    echo "FAIL: captcha_code is not 5 digits: " . $_SESSION['captcha_code'] . "\n";
    exit(1);
}

// Check output
if (strpos($output, "\x89PNG") !== 0) {
    echo "FAIL: Output does not start with PNG magic bytes\n";
    exit(1);
}

echo "SUCCESS: Captcha generated correctly with code " . $_SESSION['captcha_code'] . "\n";
?>
