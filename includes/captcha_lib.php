<?php

/**
 * Captcha ohne Ziffern, die mit Buchstaben verwechselt werden.
 */
function captcha_alphabet() {
    return 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
}

function generate_captcha_code() {
    $alphabet = captcha_alphabet();
    $max = strlen($alphabet) - 1;
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $alphabet[random_int(0, $max)];
    }
    return $code;
}

/**
 * Prüft die Antwort und verwirft den Code immer, auch bei Fehlern.
 */
function consume_captcha_answer($answer) {
    $expected = '';
    if (isset($_SESSION['captcha_code']) && is_string($_SESSION['captcha_code'])) {
        $expected = $_SESSION['captcha_code'];
    }
    unset($_SESSION['captcha_code']);

    $answer = strtoupper(trim((string) $answer));
    if ($expected === '' || $answer === '') {
        return false;
    }
    return hash_equals($expected, $answer);
}
