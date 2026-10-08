<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/master_admin.php';

run_test('canonical URL ignores the host header', function () {
    $_SERVER['HTTP_HOST'] = 'evil.example';
    $_SERVER['HTTPS'] = 'on';
    assert_equals(canonical_base_url(), get_base_url());
    assert_true(strpos(get_base_url(), 'evil.example') === false);
    assert_true(strpos(get_display_url('/admin.php'), 'https://') === 0);
    assert_true(strpos(get_display_url('/admin.php'), 'evil.example') === false);
});

run_test('session helper sets httponly samesite and secure flag logic', function () {
    $source = file_get_contents(dirname(__DIR__) . '/includes/session.php');
    assert_true(strpos($source, "'httponly' => true") !== false);
    assert_true(strpos($source, "'samesite' => 'Lax'") !== false);
    assert_true(strpos($source, "'secure' => \$https") !== false);
    foreach (array('admin.php', 'participant.php', 'register.php', 'create_group.php', 'admin-link.php', 'captcha.php') as $file) {
        $page = file_get_contents(dirname(__DIR__) . '/public/' . $file);
        assert_true(strpos($page, 'start_secure_session()') !== false, $file);
    }
});

run_test('csv cells with formula prefixes are quoted', function () {
    assert_equals("'=Formel", master_admin_csv_cell('=Formel'));
    assert_equals("'+Formel", master_admin_csv_cell('+Formel'));
    assert_equals("'-12", master_admin_csv_cell('-12'));
    assert_equals("'@cmd", master_admin_csv_cell('@cmd'));
    assert_equals("'\tcmd", master_admin_csv_cell("\tcmd"));
    $cr = master_admin_csv_cell("\r=Formel");
    assert_true(strpos($cr, "'") === 0, 'CR prefix is quoted');
    assert_equals('Anna', master_admin_csv_cell('Anna'));
});

run_test('rate limit fails closed and blocks the next attempt', function () {
    $ref = new ReflectionFunction('consume_rate_limit');
    $default = $ref->getParameters()[4]->getDefaultValue();
    assert_equals(false, $default, 'fail open is not the default');

    $id = 'unit-' . bin2hex(random_bytes(8));
    assert_true(consume_rate_limit('unit', $id, 2, 60, false));
    assert_true(consume_rate_limit('unit', $id, 2, 60, false));
    assert_true(!consume_rate_limit('unit', $id, 2, 60, false));

    $status = rate_limit_storage_status();
    assert_true(!empty($status['writable']) || !empty($status['exists']));
});

run_test('captcha field maxlength matches the generated code', function () {
    $code = generate_captcha_code();
    $length = strlen($code);
    assert_equals(captcha_length(), $length);
    $field = captcha_answer_field();
    if (preg_match('/maxlength="(\d+)"/', $field, $match) !== 1) {
        throw new Exception('Captcha field has no maxlength');
    }
    assert_equals((string) $length, $match[1]);
    assert_true(strpos($field, 'minlength="' . $length . '"') !== false);
    assert_true(strpos($field, 'inputmode="numeric"') === false);
    assert_true(strpos($field, 'inputmode="text"') !== false);
    assert_true(strpos($field, 'pattern="[0-9]') === false);
    assert_true(strpos(captcha_hint_text(), (string) $length . ' Zeichen') !== false);

    foreach (array('create_group.php', 'admin-link.php') as $file) {
        $page = file_get_contents(dirname(__DIR__) . '/public/' . $file);
        assert_true(strpos($page, 'captcha_answer_field()') !== false, $file);
        assert_true(strpos($page, 'maxlength="5"') === false, $file);
        assert_true(strpos($page, '5 Zahlen') === false, $file);
        assert_true(strpos($page, '5-stellig') === false, $file);
        assert_true(strpos($page, 'inputmode="numeric"') === false, $file);
    }
    $register = file_get_contents(dirname(__DIR__) . '/public/register.php');
    assert_true(strpos($register, 'captcha_answer') === false, 'register has no separate captcha field');
});

run_test('captcha is single use and not only digits', function () {
    $script = tempnam(sys_get_temp_dir(), 'captcha');
    $body = <<<'PHP'
<?php
require SESSION_FILE;
require LIB_FILE;
start_secure_session();
$_SESSION['captcha_code'] = 'AB23CD';
if (consume_captcha_answer('wrong') || !empty($_SESSION['captcha_code'])) {
    fwrite(STDERR, "failure did not consume the code\n");
    exit(1);
}
$_SESSION['captcha_code'] = 'AB23CD';
if (!consume_captcha_answer('  ab23cd  ') || consume_captcha_answer('AB23CD')) {
    fwrite(STDERR, "code was reused\n");
    exit(1);
}
$code = generate_captcha_code();
if (preg_match('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/', $code) !== 1) {
    fwrite(STDERR, "unexpected alphabet\n");
    exit(1);
}
echo "ok\n";
PHP;
    $body = str_replace('SESSION_FILE', var_export(dirname(__DIR__) . '/includes/session.php', true), $body);
    $body = str_replace('LIB_FILE', var_export(dirname(__DIR__) . '/includes/captcha_lib.php', true), $body);
    file_put_contents($script, $body);
    $output = array();
    $exit = 0;
    exec('php ' . escapeshellarg($script), $output, $exit);
    @unlink($script);
    assert_equals(0, $exit, implode("\n", $output));
    assert_equals('ok', isset($output[0]) ? $output[0] : '');
});

run_test('draw refuses a second save inside a transaction', function () {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE `groups` (id INTEGER PRIMARY KEY, is_drawn INTEGER)');
    $pdo->exec('CREATE TABLE `participants` (id INTEGER PRIMARY KEY, group_id INTEGER, assigned_to INTEGER)');
    $pdo->exec('INSERT INTO `groups` (id, is_drawn) VALUES (1, 0)');
    $pdo->exec('INSERT INTO `participants` (id, group_id, assigned_to) VALUES (1, 1, NULL), (2, 1, NULL)');

    assert_true(save_draw_assignment($pdo, 1, array(1, 2), array(2, 1)));
    assert_equals(1, (int) $pdo->query('SELECT is_drawn FROM `groups` WHERE id = 1')->fetchColumn());
    assert_true(!save_draw_assignment($pdo, 1, array(1, 2), array(2, 1)));
    assert_equals(2, (int) $pdo->query('SELECT assigned_to FROM `participants` WHERE id = 1')->fetchColumn());
});

run_test('fisher yates permutation stays a permutation', function () {
    $values = array(1, 2, 3, 4, 5, 6);
    $shuffled = secure_shuffle_values($values);
    sort($values);
    $copy = $shuffled;
    sort($copy);
    assert_equals($values, $copy);
    assert_equals(6, count($shuffled));
});

run_test('length limits and masked emails', function () {
    assert_equals('', limit_text_error('Anna', 255, 'Name'));
    assert_true(limit_text_error(str_repeat('a', 256), 255, 'Name') !== '');
    assert_equals('a***@example.ch', mask_email('anna@example.ch'));
    assert_equals('***', mask_email('keine-adresse'));
});

run_test('signed calendar link grants no access token', function () {
    $url = gift_ics_url(array('id' => 4, 'gift_exchange_date' => '2026-12-24'));
    assert_true(strpos($url, 'token=') === false);
    assert_true(gift_ics_signature_valid(4, '2026-12-24', substr(strrchr($url, '='), 1)));
    assert_true(!gift_ics_signature_valid(4, '2026-12-25', substr(strrchr($url, '='), 1)));
    assert_true(!gift_ics_signature_valid(4, '2026-12-24', str_repeat('ab', 32)));
});

run_test('confirm helpers and token pages do not load matomo', function () {
    $attr = html_onsubmit_confirm('Gruppe «A"B» löschen?');
    assert_true(strpos($attr, 'return confirm(') !== false);
    assert_true(strpos($attr, '<') === false);
    foreach (array('admin.php', 'participant.php', 'register.php') as $file) {
        $page = file_get_contents(dirname(__DIR__) . '/public/' . $file);
        assert_true(strpos($page, 'matomo_tracking.php') === false, $file);
        assert_true(strpos($page, 'no-referrer') !== false, $file);
    }
    $matomo = file_get_contents(dirname(__DIR__) . '/includes/templates/matomo_tracking.php');
    assert_true(strpos($matomo, 'setCustomUrl') !== false);
    assert_true(strpos($matomo, 'setReferrerUrl') !== false);
    $api = file_get_contents(dirname(__DIR__) . '/public/api/helpers.php');
    assert_true(strpos($api, "\$_REQUEST['api_token']") === false);
    $draw = file_get_contents(dirname(__DIR__) . '/public/api/draw.php');
    assert_true(strpos($draw, "'assignments'") === false);
});

run_test('master login form does not accept the token from the query string', function () {
    $page = file_get_contents(dirname(__DIR__) . '/public/admin/index.php');
    assert_true(strpos($page, 'master_admin_login_document') !== false);
    assert_true(strpos($page, "isset(\$_GET['master_token'])") !== false);
    assert_true(strpos($page, 'name="master_token"') === false || strpos(file_get_contents(dirname(__DIR__) . '/includes/master_admin.php'), 'name="master_token"') !== false);
    $login = file_get_contents(dirname(__DIR__) . '/includes/master_admin.php');
    assert_true(strpos($login, 'name="master_token"') !== false);
    assert_true(strpos($login, 'type="password"') !== false);
});
