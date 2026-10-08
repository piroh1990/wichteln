<?php

require_once __DIR__ . '/../includes/master_admin.php';

function mad_pdo() {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('CREATE TABLE `groups` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `name` TEXT,
        `admin_token` TEXT,
        `invite_token` TEXT,
        `admin_email` TEXT,
        `budget` REAL,
        `description` TEXT,
        `gift_exchange_date` TEXT,
        `is_drawn` INTEGER DEFAULT 0,
        `reveal_sent_at` TEXT,
        `reminder_sent_at` TEXT,
        `created_at` TEXT
    )');
    $pdo->exec('CREATE TABLE `participants` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `group_id` INTEGER,
        `name` TEXT,
        `email` TEXT,
        `participant_token` TEXT,
        `assigned_to` INTEGER,
        `wishlist` TEXT,
        `created_at` TEXT,
        FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`),
        FOREIGN KEY (`assigned_to`) REFERENCES `participants`(`id`)
    )');
    $pdo->exec('CREATE TABLE `exclusions` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `group_id` INTEGER,
        `participant_id` INTEGER,
        `excluded_participant_id` INTEGER,
        FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`),
        FOREIGN KEY (`participant_id`) REFERENCES `participants`(`id`),
        FOREIGN KEY (`excluded_participant_id`) REFERENCES `participants`(`id`)
    )');
    $pdo->exec('CREATE TABLE `group_statistics` (
        `id` INTEGER PRIMARY KEY AUTOINCREMENT,
        `original_group_id` INTEGER,
        `group_name` TEXT,
        `participant_count` INTEGER,
        `participant_with_email_count` INTEGER,
        `exclusion_count` INTEGER,
        `budget` REAL,
        `gift_exchange_date` TEXT,
        `is_drawn` INTEGER,
        `created_at` TEXT,
        `archived_at` TEXT
    )');
    return $pdo;
}

function mad_flags(PDO $pdo) {
    $report = master_admin_schema_report($pdo);
    return $report['flags'];
}

function mad_group(PDO $pdo, array $data) {
    $stmt = $pdo->prepare('INSERT INTO `groups`
        (name, admin_token, invite_token, admin_email, budget, description, gift_exchange_date, is_drawn, reveal_sent_at, reminder_sent_at, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute(array(
        $data['name'],
        isset($data['admin_token']) ? $data['admin_token'] : 'admin-' . $data['name'],
        isset($data['invite_token']) ? $data['invite_token'] : 'invite-' . $data['name'],
        array_key_exists('admin_email', $data) ? $data['admin_email'] : 'admin@example.com',
        isset($data['budget']) ? $data['budget'] : 20,
        isset($data['description']) ? $data['description'] : 'Beschreibung',
        array_key_exists('gift', $data) ? $data['gift'] : '2026-12-24',
        !empty($data['drawn']) ? 1 : 0,
        isset($data['reveal']) ? $data['reveal'] : null,
        isset($data['reminder']) ? $data['reminder'] : null,
        $data['created_at'],
    ));
    return (int) $pdo->lastInsertId();
}

function mad_people(PDO $pdo, $group_id, $count, $with_email, $created_at) {
    $ids = array();
    for ($i = 1; $i <= $count; $i++) {
        $stmt = $pdo->prepare('INSERT INTO `participants` (group_id, name, email, participant_token, created_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute(array(
            $group_id,
            'Person ' . $group_id . '-' . $i,
            $with_email ? ('person' . $group_id . '-' . $i . '@example.com') : null,
            'ptoken-' . $group_id . '-' . $i,
            $created_at,
        ));
        $ids[] = (int) $pdo->lastInsertId();
    }
    return $ids;
}

function mad_names(array $rows) {
    $names = array();
    foreach ($rows as $row) {
        $names[] = $row['name'];
    }
    sort($names);
    return $names;
}

function mad_fixture(PDO $pdo) {
    $alpha = mad_group($pdo, array(
        'name' => 'Alpha',
        'created_at' => '2026-10-01 09:00:00',
        'drawn' => 1,
        'reveal' => '2026-10-02 10:00:00',
        'reminder' => '2026-10-03 10:00:00',
        'admin_token' => 'SECRETADMINTOKEN123',
        'invite_token' => 'SECRETINVITE',
        'description' => 'A & B und noch viel mehr Text mit Umlauten äöü für die Kürzung der Beschreibung',
    ));
    $people = mad_people($pdo, $alpha, 4, true, '2026-10-01 09:30:00');
    $pdo->prepare('UPDATE `participants` SET name = ?, email = ?, participant_token = ? WHERE id = ?')
        ->execute(array('Geheime Person', 'secret.person@example.com', 'SECRETPARTICIPANT', $people[0]));

    $beta = mad_group($pdo, array('name' => 'Beta', 'created_at' => '2026-10-02 09:00:00'));
    mad_people($pdo, $beta, 2, true, '2026-10-02 09:30:00');

    mad_group($pdo, array(
        'name' => 'Gamma',
        'created_at' => '2026-09-01 12:00:00',
        'admin_email' => null,
        'gift' => null,
    ));

    $delta = mad_group($pdo, array(
        'name' => 'Delta',
        'created_at' => '2026-09-15 09:00:00',
        'gift' => '2026-10-01',
    ));
    mad_people($pdo, $delta, 5, true, '2026-09-15 10:00:00');

    $epsilon = mad_group($pdo, array(
        'name' => 'Epsilon',
        'created_at' => '2026-10-03 09:00:00',
        'drawn' => 1,
    ));
    $epsilon_people = mad_people($pdo, $epsilon, 3, false, '2026-10-03 09:30:00');
    $pdo->prepare('UPDATE `participants` SET assigned_to = ? WHERE id = ?')->execute(array($epsilon_people[1], $epsilon_people[0]));
    $pdo->prepare('INSERT INTO `exclusions` (group_id, participant_id, excluded_participant_id) VALUES (?, ?, ?)')
        ->execute(array($epsilon, $epsilon_people[0], $epsilon_people[1]));

    mad_group($pdo, array('name' => 'Zeta', 'created_at' => '2026-10-08 11:00:00', 'gift' => null));
    mad_group($pdo, array('name' => 'Eta', 'created_at' => '2026-09-24 12:00:00', 'gift' => null));
    $theta = mad_group($pdo, array('name' => '100% Spass', 'created_at' => '2026-10-05 09:00:00'));
    mad_people($pdo, $theta, 4, true, '2026-10-05 09:30:00');
    $iota = mad_group($pdo, array('name' => '=Formel', 'created_at' => '2026-10-04 09:00:00'));
    mad_people($pdo, $iota, 4, true, '2026-10-04 09:30:00');
    $kappa = mad_group($pdo, array('name' => 'A_B', 'created_at' => '2026-10-06 09:00:00'));
    mad_people($pdo, $kappa, 4, true, '2026-10-06 09:30:00');

    return array('alpha' => $alpha, 'epsilon' => $epsilon);
}

run_test('Sortierung und Status sind whitelisted', function () {
    assert_equals(
        'g.`created_at` ASC, g.`id` DESC',
        master_admin_order_sql('admin_token; DROP TABLE groups', 'asc')
    );
    assert_equals('g.`name` DESC, g.`id` DESC', master_admin_order_sql('name', 'desc'));
    assert_equals('`participant_count` ASC, g.`id` DESC', master_admin_order_sql('participant_count', 'asc'));
    assert_true(strpos(master_admin_order_sql('gift_exchange_date;delete', 'asc'), 'delete') === false);

    $filters = master_admin_filters_from_request(array(
        'status' => 'ausgelost;drop',
        'sort' => 'name;drop',
        'dir' => 'ASC',
        'view' => 'cards',
        'q' => '  Alpha  ',
    ));
    assert_equals('alle', $filters['status']);
    assert_equals('created_at', $filters['sort']);
    assert_equals('asc', $filters['dir']);
    assert_equals('cards', $filters['view']);
    assert_equals('Alpha', $filters['q']);

    $valid = master_admin_filters_from_request(array('status' => 'problematisch', 'sort' => 'name', 'dir' => 'asc'));
    assert_equals('problematisch', $valid['status']);
    assert_equals('name', $valid['sort']);

    $query = master_admin_group_query(array(
        'q' => '',
        'status' => 'offen',
        'sort' => 'created_at; DROP TABLE groups',
        'dir' => 'asc',
    ), array(), time());
    assert_true(strpos($query['select_sql'], 'DROP') === false);
    assert_true(strpos($query['select_sql'], 'g.`is_drawn` = 0') !== false);
    assert_true(strpos($query['count_sql'], 'master_admin_counted') !== false);
});

run_test('Beschreibung wird vor dem Escapen gekürzt', function () {
    $cut = master_admin_truncate('xxxx&yyyyyyyyyyyy', 6);
    assert_equals('xxxx&amp;y…', $cut);
    assert_true(strpos($cut, '&amp;') !== false);
    assert_equals('äöü', master_admin_truncate('äöü', 3));
    assert_equals('äöü…', master_admin_truncate('äöüä', 3));
    assert_equals('A &amp; B und …', master_admin_truncate('A & B und noch mehr Text', 10));
    assert_equals('&lt;&gt;&amp;', master_admin_truncate('<>&', 50));
});

run_test('Verwalten-Link ist relativ und ohne Host', function () {
    $url = master_admin_manage_url('abc def');
    assert_equals('/admin.php?token=abc%20def', $url);
    assert_true(strpos($url, 'https://') === false);
    assert_true(strpos($url, 'xn--') === false);
});

run_test('Filter, Sortierung und Warnungen passen zusammen', function () {
    $pdo = mad_pdo();
    $ids = mad_fixture($pdo);
    $flags = mad_flags($pdo);
    $now = strtotime('2026-10-08 12:00:00');
    $base = array('q' => '', 'sort' => 'name', 'dir' => 'asc', 'view' => 'table', 'page' => 1, 'archive_page' => 1);

    $all = master_admin_fetch_groups($pdo, array_merge($base, array('status' => 'alle')), $flags, 1, 50, $now);
    assert_equals(10, $all['total']);

    $by_status = array(
        'ausgelost' => array('Alpha', 'Epsilon'),
        'leer' => array('Eta', 'Gamma', 'Zeta'),
        'datum_vorbei' => array('Delta'),
        'problematisch' => array('Beta', 'Delta', 'Epsilon', 'Gamma'),
        'aufraeumen' => array('Delta', 'Eta', 'Gamma', 'Zeta'),
    );
    foreach ($by_status as $status => $expected) {
        $page = master_admin_fetch_groups($pdo, array_merge($base, array('status' => $status)), $flags, 1, 50, $now);
        assert_equals($expected, mad_names($page['rows']), $status);
    }

    $offen = master_admin_fetch_groups($pdo, array_merge($base, array('status' => 'offen')), $flags, 1, 50, $now);
    assert_true(!in_array('Alpha', mad_names($offen['rows']), true));
    assert_true(!in_array('Epsilon', mad_names($offen['rows']), true));
    assert_equals(8, $offen['total']);

    $percent = master_admin_fetch_groups($pdo, array_merge($base, array('status' => 'alle', 'q' => '%')), $flags, 1, 50, $now);
    assert_equals(array('100% Spass'), mad_names($percent['rows']));
    $underscore = master_admin_fetch_groups($pdo, array_merge($base, array('status' => 'alle', 'q' => '_')), $flags, 1, 50, $now);
    assert_equals(array('A_B'), mad_names($underscore['rows']));
    $alpha = master_admin_fetch_groups($pdo, array_merge($base, array('status' => 'alle', 'q' => 'Alpha')), $flags, 1, 50, $now);
    assert_equals(array('Alpha'), mad_names($alpha['rows']));

    $sorted = master_admin_fetch_groups($pdo, array_merge($base, array('status' => 'alle', 'sort' => 'name', 'dir' => 'asc')), $flags, 1, 50, $now);
    assert_equals('100% Spass', $sorted['rows'][0]['name']);
    assert_equals('Zeta', $sorted['rows'][count($sorted['rows']) - 1]['name']);

    $by_size = master_admin_fetch_groups($pdo, array_merge($base, array('status' => 'alle', 'sort' => 'participant_count', 'dir' => 'desc')), $flags, 1, 50, $now);
    assert_equals('Delta', $by_size['rows'][0]['name']);

    $page2 = master_admin_fetch_groups($pdo, array_merge($base, array('status' => 'alle', 'sort' => 'name', 'dir' => 'asc')), $flags, 2, 3, $now);
    assert_equals(10, $page2['total']);
    assert_equals(2, $page2['page']);
    assert_equals(array('Alpha', 'Beta', 'Delta'), mad_names($page2['rows']));

    $evil = master_admin_fetch_groups($pdo, array_merge($base, array('status' => 'alle', 'sort' => 'admin_token; DROP TABLE groups')), $flags, 1, 50, $now);
    assert_equals(10, $evil['total']);

    $problematic_ids = array();
    $problematic = master_admin_fetch_groups($pdo, array_merge($base, array('status' => 'problematisch')), $flags, 1, 50, $now);
    foreach ($problematic['rows'] as $row) {
        $problematic_ids[(int) $row['id']] = true;
    }
    foreach ($all['rows'] as $row) {
        $warnings = master_admin_group_warnings($row, $now, $flags);
        $flagged = isset($problematic_ids[(int) $row['id']]);
        assert_equals(count($warnings) > 0, $flagged, $row['name']);
        if ($row['name'] === 'Gamma') {
            assert_true(in_array('0 Teilnehmer seit >14 Tagen', $warnings, true));
            assert_true(in_array('Verwaist (leer, ohne Admin-E-Mail)', $warnings, true));
        }
        if ($row['name'] === 'Eta') {
            assert_equals(0, count($warnings));
        }
        if ($row['name'] === 'Delta') {
            assert_true(in_array('Datum vorbei, nie ausgelost', $warnings, true));
        }
        if ($row['name'] === 'Epsilon') {
            assert_true(in_array('Ausgelost, aber keine E-Mails', $warnings, true));
        }
        if ($row['name'] === 'Beta') {
            assert_true(in_array('Weniger als 3 Teilnehmer', $warnings, true));
        }
    }

    assert_true($ids['alpha'] > 0);
});

run_test('Sammelaktionen bestätigen, archivieren, löschen und rollen zurück', function () {
    $pdo = mad_pdo();
    $ids = mad_fixture($pdo);
    $now = strtotime('2026-10-08 12:00:00');
    $session = array();

    $gamma = (int) $pdo->query("SELECT id FROM `groups` WHERE name = 'Gamma'")->fetchColumn();
    $zeta = (int) $pdo->query("SELECT id FROM `groups` WHERE name = 'Zeta'")->fetchColumn();

    $first = master_admin_handle_bulk_request($pdo, array(
        'bulk_action' => 'archive',
        'group_ids' => array((string) $gamma, (string) $zeta, '0', 'abc', '-3'),
    ), $session, true, $now);
    assert_equals('confirm', $first['status']);
    assert_equals(1, (int) $pdo->query('SELECT COUNT(*) FROM `groups` WHERE id = ' . $gamma)->fetchColumn());
    assert_equals(0, (int) $pdo->query('SELECT COUNT(*) FROM `group_statistics`')->fetchColumn());

    $blocked = master_admin_handle_bulk_request($pdo, array(
        'confirm_bulk' => '1',
        'confirm_token' => $first['token'],
    ), $session, false, $now);
    assert_equals('error', $blocked['status']);
    assert_equals(1, (int) $pdo->query('SELECT COUNT(*) FROM `groups` WHERE id = ' . $gamma)->fetchColumn());

    $wrong = master_admin_handle_bulk_request($pdo, array(
        'confirm_bulk' => '1',
        'confirm_token' => 'falsch',
    ), $session, true, $now);
    assert_equals('error', $wrong['status']);
    assert_equals(1, (int) $pdo->query('SELECT COUNT(*) FROM `groups` WHERE id = ' . $gamma)->fetchColumn());

    $session['master_admin_pending_bulk'] = array(
        'action' => 'archive',
        'ids' => array($gamma, $zeta),
        'token' => 'ok-token',
        'created_at' => $now,
    );
    $expired = master_admin_handle_bulk_request($pdo, array(
        'confirm_bulk' => '1',
        'confirm_token' => 'ok-token',
    ), $session, true, $now + 901);
    assert_equals('error', $expired['status']);
    assert_true(empty($session['master_admin_pending_bulk']));

    $session = array();
    $again = master_admin_handle_bulk_request($pdo, array(
        'bulk_action' => 'archive',
        'group_ids' => array($gamma, $zeta),
    ), $session, true, $now);
    $done = master_admin_handle_bulk_request($pdo, array(
        'confirm_bulk' => '1',
        'confirm_token' => $again['token'],
    ), $session, true, $now + 10);
    assert_equals('done', $done['status']);
    assert_equals(2, $done['count']);
    assert_equals(0, (int) $pdo->query('SELECT COUNT(*) FROM `groups` WHERE id IN (' . $gamma . ',' . $zeta . ')')->fetchColumn());
    $stats = $pdo->query("SELECT * FROM `group_statistics` WHERE group_name = 'Gamma'")->fetch(PDO::FETCH_ASSOC);
    assert_true(is_array($stats));
    assert_equals(0, (int) $stats['participant_count']);
    assert_equals(1, (int) $pdo->query("SELECT COUNT(*) FROM `groups` WHERE name = 'Alpha'")->fetchColumn());

    $before_stats = (int) $pdo->query('SELECT COUNT(*) FROM `group_statistics`')->fetchColumn();
    $session = array();
    $delete_request = master_admin_handle_bulk_request($pdo, array(
        'bulk_action' => 'delete',
        'group_ids' => array($ids['epsilon']),
    ), $session, true, $now);
    $deleted = master_admin_handle_bulk_request($pdo, array(
        'confirm_bulk' => '1',
        'confirm_token' => $delete_request['token'],
    ), $session, true, $now);
    assert_equals('done', $deleted['status']);
    assert_equals(0, (int) $pdo->query('SELECT COUNT(*) FROM `groups` WHERE id = ' . (int) $ids['epsilon'])->fetchColumn());
    assert_equals(0, (int) $pdo->query('SELECT COUNT(*) FROM `participants` WHERE group_id = ' . (int) $ids['epsilon'])->fetchColumn());
    assert_equals(0, (int) $pdo->query('SELECT COUNT(*) FROM `exclusions` WHERE group_id = ' . (int) $ids['epsilon'])->fetchColumn());
    assert_equals($before_stats, (int) $pdo->query('SELECT COUNT(*) FROM `group_statistics`')->fetchColumn());

    $alpha_id = (int) $ids['alpha'];
    $failed = false;
    try {
        master_admin_apply_bulk($pdo, 'archive', array($alpha_id, 99999));
    } catch (Exception $e) {
        $failed = true;
    }
    assert_true($failed);
    assert_equals(1, (int) $pdo->query('SELECT COUNT(*) FROM `groups` WHERE id = ' . $alpha_id)->fetchColumn());
    assert_equals(0, (int) $pdo->query('SELECT COUNT(*) FROM `group_statistics` WHERE original_group_id = ' . $alpha_id)->fetchColumn());
    assert_true((int) $pdo->query('SELECT COUNT(*) FROM `participants` WHERE group_id = ' . $alpha_id)->fetchColumn() > 0);

    $empty_session = array();
    $empty = master_admin_handle_bulk_request($pdo, array('bulk_action' => 'delete', 'group_ids' => array()), $empty_session, true, $now);
    assert_equals('error', $empty['status']);
    $unknown_session = array();
    $unknown = master_admin_handle_bulk_request($pdo, array('bulk_action' => 'drop', 'group_ids' => array(1)), $unknown_session, true, $now);
    assert_equals('error', $unknown['status']);
});

run_test('CSV enthält keine Tokens und respektiert den Filter', function () {
    $pdo = mad_pdo();
    mad_fixture($pdo);
    $flags = mad_flags($pdo);
    $now = strtotime('2026-10-08 12:00:00');
    $rows = master_admin_fetch_all_groups($pdo, array(
        'q' => '',
        'status' => 'alle',
        'sort' => 'name',
        'dir' => 'asc',
    ), $flags, $now);
    $csv = master_admin_groups_csv($rows, $flags, $now);
    assert_equals("\xEF\xBB\xBF", substr($csv, 0, 3));
    assert_true(strpos($csv, ';') !== false);
    assert_true(strpos($csv, 'Alpha') !== false);
    assert_true(strpos($csv, 'SECRETADMINTOKEN123') === false);
    assert_true(strpos($csv, 'SECRETINVITE') === false);
    assert_true(strpos($csv, 'secret.person@example.com') === false);
    assert_true(strpos($csv, 'Geheime Person') === false);
    assert_true(strpos($csv, 'SECRETPARTICIPANT') === false);
    assert_true(strpos($csv, 'admin_token') === false);
    assert_true(strpos($csv, "'=Formel") !== false);

    $leer = master_admin_fetch_all_groups($pdo, array(
        'q' => '',
        'status' => 'leer',
        'sort' => 'name',
        'dir' => 'asc',
    ), $flags, $now);
    $leer_csv = master_admin_groups_csv($leer, $flags, $now);
    assert_true(strpos($leer_csv, 'Gamma') !== false);
    assert_true(strpos($leer_csv, 'Alpha') === false);

    $kpis = master_admin_kpis($pdo, $now, $flags);
    $season = master_admin_season_bounds($now);
    $current = master_admin_window_stats($pdo, $season['current_start'], $season['current_end']);
    $previous = master_admin_window_stats($pdo, $season['previous_start'], $season['previous_end']);
    $daily = master_admin_daily_series($pdo, $season['nov_start'], $season['nov_end']);
    $stats_csv = master_admin_statistics_csv($kpis, $season, $current, $previous, $daily);
    assert_equals("\xEF\xBB\xBF", substr($stats_csv, 0, 3));
    assert_true(strpos($stats_csv, 'Saisondefinition') !== false);
    assert_true(strpos($stats_csv, '2026-11-01') !== false);
    assert_true(strpos($stats_csv, 'SECRET') === false);
    assert_true(strpos($stats_csv, '@') === false);
});

run_test('Systemstatus, Saison und Tageschart', function () {
    $full = mad_pdo();
    $full_report = master_admin_schema_report($full);
    assert_equals(0, $full_report['missing_count']);

    $partial = new PDO('sqlite::memory:');
    $partial->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $partial->exec('CREATE TABLE `groups` (id INTEGER PRIMARY KEY, name TEXT, created_at TEXT)');
    $partial->exec('CREATE TABLE `participants` (id INTEGER PRIMARY KEY, token TEXT, name TEXT, email TEXT)');
    $partial->exec('CREATE TABLE `exclusions` (id INTEGER PRIMARY KEY, group_id INTEGER)');
    $partial->exec('CREATE TABLE `group_statistics` (id INTEGER PRIMARY KEY, group_name TEXT)');
    $report = master_admin_schema_report($partial);
    $seen = array();
    foreach ($report['items'] as $item) {
        $seen[$item['table'] . '.' . $item['column']] = $item;
    }
    assert_true(empty($seen['groups.reminder_sent_at']['ok']));
    assert_equals(
        'ALTER TABLE `groups` ADD COLUMN `reminder_sent_at` DATETIME NULL DEFAULT NULL;',
        $seen['groups.reminder_sent_at']['sql']
    );
    assert_equals(
        'ALTER TABLE `groups` ADD COLUMN `reveal_sent_at` DATETIME NULL DEFAULT NULL;',
        $seen['groups.reveal_sent_at']['sql']
    );
    assert_equals(
        'ALTER TABLE `participants` CHANGE COLUMN `token` `participant_token` VARCHAR(64) NULL;',
        $seen['participants.participant_token']['sql']
    );

    $missing_table = new PDO('sqlite::memory:');
    $missing_table->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $missing_report = master_admin_schema_report($missing_table);
    foreach ($missing_report['items'] as $item) {
        if ($item['table'] === 'groups' && $item['column'] === 'reminder_sent_at') {
            assert_true($item['table_missing']);
            assert_equals('', $item['sql']);
        }
    }

    assert_equals('aktiv', master_admin_rewrite_status(array('mod_rewrite', 'core'), null));
    assert_equals('inaktiv', master_admin_rewrite_status(array('core'), null));
    assert_equals('unbekannt', master_admin_rewrite_status(null, null));
    assert_equals('aktiv', master_admin_rewrite_status(null, true));
    assert_equals('inaktiv', master_admin_rewrite_status(null, false));
    assert_equals('http://localhost/faq', master_admin_clean_url_probe_target(array('HTTP_HOST' => 'localhost')));
    assert_equals('', master_admin_clean_url_probe_target(array('HTTP_HOST' => 'bad host')));

    $mail_ok = master_admin_mail_status('noreply@example.com', 'Wichtel', '/usr/sbin/sendmail -t -i', true);
    assert_true($mail_ok['ok']);
    $mail_bad = master_admin_mail_status('keine-mail', '', '', false);
    assert_true(!$mail_bad['ok']);
    assert_true(count($mail_bad['warnings']) >= 2);

    assert_true(master_admin_ads_status(false, false)['ok']);
    $cmp = master_admin_ads_status(true, false);
    assert_true(!$cmp['ok']);
    assert_true(strpos(implode(' ', $cmp['warnings']), 'GOOGLE_CMP_ENABLED') !== false);
    $testing = master_admin_ads_status(false, true);
    assert_true(strpos(implode(' ', $testing['warnings']), 'GOOGLE_ADS_TESTING') !== false);

    $php_status = master_admin_php_status();
    assert_equals(PHP_VERSION, $php_status['version']);
    assert_true($php_status['ok']);

    $october = master_admin_season_bounds(strtotime('2026-10-08 12:00:00'));
    assert_equals('2026-09-01 00:00:00', $october['current_start']);
    assert_equals('2027-02-01 00:00:00', $october['current_end']);
    assert_equals('2025-09-01 00:00:00', $october['previous_start']);
    assert_equals('2026-11-01', $october['nov_start']);
    assert_equals('2027-01-01', $october['nov_end']);
    assert_true(strpos($october['label'], 'September') !== false);
    assert_true(strpos($october['label'], 'Januar') !== false);
    assert_true($october['started']);

    $january = master_admin_season_bounds(strtotime('2026-01-15'));
    assert_equals('2025-09-01 00:00:00', $january['current_start']);
    assert_equals('2025-11-01', $january['nov_start']);

    $march = master_admin_season_bounds(strtotime('2026-03-01'));
    assert_equals('2026-09-01 00:00:00', $march['current_start']);
    assert_true(!$march['started']);

    $pdo = mad_pdo();
    mad_group($pdo, array('name' => 'November', 'created_at' => '2026-11-03 15:00:00', 'drawn' => 1));
    mad_people($pdo, (int) $pdo->query("SELECT id FROM `groups` WHERE name = 'November'")->fetchColumn(), 2, true, '2026-11-03 16:00:00');
    $pdo->prepare('INSERT INTO `group_statistics` (original_group_id, group_name, participant_count, participant_with_email_count, exclusion_count, is_drawn, created_at) VALUES (1, ?, 4, 4, 0, 1, ?)')
        ->execute(array('Archivtag', '2026-12-01 08:00:00'));
    $series = master_admin_daily_series($pdo, '2026-11-01', '2027-01-01');
    assert_equals(61, count($series));
    $indexed = array();
    foreach ($series as $point) {
        $indexed[$point['date']] = $point;
    }
    assert_equals(1, $indexed['2026-11-03']['groups']);
    assert_equals(2, $indexed['2026-11-03']['participants']);
    assert_equals(1, $indexed['2026-12-01']['groups']);
    assert_equals(0, $indexed['2026-12-01']['participants']);
    assert_equals(0, $indexed['2026-11-02']['groups']);

    $svg = master_admin_chart_svg($series);
    assert_true(strpos($svg, '<svg') !== false, 'SVG fehlt');
    assert_true(stripos($svg, '<script') === false, 'Chart enthält ein Script');
    assert_true(strpos($svg, 'polyline') !== false, 'Linie fehlt');
    $svg_without_namespace = str_replace('http://www.w3.org/2000/svg', '', $svg);
    assert_true(strpos($svg_without_namespace, 'http://') === false, 'Chart lädt eine externe URL');
    assert_true(strpos($svg_without_namespace, 'https://') === false, 'Chart lädt eine externe URL');

    $page = file_get_contents(__DIR__ . '/../public/admin/index.php');
    assert_true(strpos($page, 'xn--wichtl-gua.ch') === false);
    assert_true(strpos($page, 'verify_csrf_token') !== false);
    assert_true(strpos($page, 'noindex') !== false);
    assert_true(strpos($page, 'master_admin_truncate') !== false || strpos($page, 'master_dashboard_description') !== false);
    assert_true(strpos($page, 'substr(htmlspecialchars') === false);
    assert_true(strpos($page, 'matomo') === false);
    assert_true(strpos($page, 'adsbygoogle') === false);
    assert_true(strpos($page, '/css/styles.css') !== false);
});
