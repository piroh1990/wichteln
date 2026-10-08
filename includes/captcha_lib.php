<?php

/**
 * Captcha ohne Zeichen, die leicht verwechselt werden.
 * Länge, Alphabet und Formularfelder kommen von hier, damit sie nicht auseinanderlaufen.
 */
function captcha_alphabet() {
    return 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
}

function captcha_length() {
    return 6;
}

function captcha_uses_letters() {
    return preg_match('/[A-Za-z]/', captcha_alphabet()) === 1;
}

function captcha_pattern() {
    $chars = captcha_alphabet();
    if (captcha_uses_letters()) {
        $chars .= strtolower(preg_replace('/[^A-Z]/', '', captcha_alphabet()));
    }
    return '[' . $chars . ']{' . captcha_length() . '}';
}

function generate_captcha_code() {
    $alphabet = captcha_alphabet();
    $max = strlen($alphabet) - 1;
    $code = '';
    $length = captcha_length();
    for ($i = 0; $i < $length; $i++) {
        $code .= $alphabet[random_int(0, $max)];
    }
    return $code;
}

function captcha_hint_text($suffix = '') {
    $text = 'Bitte gib die ' . captcha_length() . ' Zeichen aus dem Bild ein.';
    if ($suffix !== '') {
        $text .= ' ' . $suffix;
    }
    return $text;
}

function captcha_answer_field() {
    $length = captcha_length();
    $inputmode = captcha_uses_letters() ? 'text' : 'numeric';
    return '<input type="text" id="captcha_answer" name="captcha_answer" required'
        . ' minlength="' . $length . '"'
        . ' maxlength="' . $length . '"'
        . ' pattern="' . htmlspecialchars(captcha_pattern(), ENT_QUOTES, 'UTF-8') . '"'
        . ' inputmode="' . $inputmode . '"'
        . ' autocomplete="off"'
        . ' autocapitalize="characters"'
        . ' spellcheck="false"'
        . ' placeholder="' . htmlspecialchars('Die ' . $length . ' Zeichen aus dem Bild', ENT_QUOTES, 'UTF-8') . '"'
        . ' aria-describedby="captcha_hint">';
}

/**
 * Prüft die Antwort und verwirft den Code immer, auch bei Fehlern.
 * Gross- und Kleinschreibung sind gleich. Leerzeichen am Rand zählen nicht.
 */
function consume_captcha_answer($answer) {
    $expected = '';
    if (isset($_SESSION['captcha_code']) && is_string($_SESSION['captcha_code'])) {
        $expected = $_SESSION['captcha_code'];
    }
    unset($_SESSION['captcha_code']);

    $answer = strtoupper(trim((string) $answer));
    $answer = preg_replace('/\s+/', '', $answer);
    if (!is_string($answer) || $expected === '' || $answer === '') {
        return false;
    }
    return hash_equals($expected, $answer);
}
