<?php

require_once __DIR__ . '/cleanup.php';
require_once __DIR__ . '/urls.php';

/**
 * Master-Dashboard: Filter, Sortierung, Kennzahlen, CSV und Systemstatus.
 * Keine Abhängigkeit von config.php, damit die Funktionen mit SQLite testbar sind.
 */

function master_admin_status_options() {
    return array(
        'alle' => 'Alle',
        'ausgelost' => 'Ausgelost',
        'offen' => 'Offen',
        'leer' => 'Leer (0 Teilnehmer)',
        'datum_vorbei' => 'Datum vorbei',
        'problematisch' => 'Problematisch',
        'aufraeumen' => 'Leer / verwaist',
    );
}

function master_admin_sort_options() {
    return array(
        'created_at' => 'Erstelldatum',
        'gift_exchange_date' => 'Geschenkdatum',
        'participant_count' => 'Teilnehmerzahl',
        'name' => 'Name',
    );
}

function master_admin_required_columns() {
    return array(
        array('table' => 'groups', 'column' => 'name', 'sql' => 'ALTER TABLE `groups` ADD COLUMN `name` VARCHAR(255) NULL DEFAULT NULL;'),
        array('table' => 'groups', 'column' => 'admin_token', 'sql' => 'ALTER TABLE `groups` ADD COLUMN `admin_token` VARCHAR(64) NULL DEFAULT NULL;'),
        array('table' => 'groups', 'column' => 'invite_token', 'sql' => 'ALTER TABLE `groups` ADD COLUMN `invite_token` VARCHAR(64) NULL DEFAULT NULL;'),
        array('table' => 'groups', 'column' => 'admin_email', 'sql' => 'ALTER TABLE `groups` ADD COLUMN `admin_email` VARCHAR(255) NULL DEFAULT NULL;'),
        array('table' => 'groups', 'column' => 'budget', 'sql' => 'ALTER TABLE `groups` ADD COLUMN `budget` DECIMAL(10,2) NULL DEFAULT NULL;'),
        array('table' => 'groups', 'column' => 'description', 'sql' => 'ALTER TABLE `groups` ADD COLUMN `description` TEXT NULL;'),
        array('table' => 'groups', 'column' => 'gift_exchange_date', 'sql' => 'ALTER TABLE `groups` ADD COLUMN `gift_exchange_date` DATE NULL DEFAULT NULL;'),
        array('table' => 'groups', 'column' => 'is_drawn', 'sql' => 'ALTER TABLE `groups` ADD COLUMN `is_drawn` TINYINT(1) NULL DEFAULT 0;'),
        array('table' => 'groups', 'column' => 'reveal_sent_at', 'sql' => 'ALTER TABLE `groups` ADD COLUMN `reveal_sent_at` DATETIME NULL DEFAULT NULL;'),
        array('table' => 'groups', 'column' => 'reminder_sent_at', 'sql' => 'ALTER TABLE `groups` ADD COLUMN `reminder_sent_at` DATETIME NULL DEFAULT NULL;'),
        array('table' => 'groups', 'column' => 'created_at', 'sql' => 'ALTER TABLE `groups` ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;'),
        array('table' => 'participants', 'column' => 'group_id', 'sql' => 'ALTER TABLE `participants` ADD COLUMN `group_id` INT NULL DEFAULT NULL;'),
        array('table' => 'participants', 'column' => 'name', 'sql' => 'ALTER TABLE `participants` ADD COLUMN `name` VARCHAR(255) NULL DEFAULT NULL;'),
        array('table' => 'participants', 'column' => 'email', 'sql' => 'ALTER TABLE `participants` ADD COLUMN `email` VARCHAR(255) NULL DEFAULT NULL;'),
        array('table' => 'participants', 'column' => 'participant_token', 'sql' => 'ALTER TABLE `participants` ADD COLUMN `participant_token` VARCHAR(64) NULL DEFAULT NULL;'),
        array('table' => 'participants', 'column' => 'assigned_to', 'sql' => 'ALTER TABLE `participants` ADD COLUMN `assigned_to` INT NULL DEFAULT NULL;'),
        array('table' => 'participants', 'column' => 'wishlist', 'sql' => 'ALTER TABLE `participants` ADD COLUMN `wishlist` TEXT NULL;'),
        array('table' => 'participants', 'column' => 'created_at', 'sql' => 'ALTER TABLE `participants` ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;'),
        array('table' => 'exclusions', 'column' => 'group_id', 'sql' => 'ALTER TABLE `exclusions` ADD COLUMN `group_id` INT NULL DEFAULT NULL;'),
        array('table' => 'exclusions', 'column' => 'participant_id', 'sql' => 'ALTER TABLE `exclusions` ADD COLUMN `participant_id` INT NULL DEFAULT NULL;'),
        array('table' => 'exclusions', 'column' => 'excluded_participant_id', 'sql' => 'ALTER TABLE `exclusions` ADD COLUMN `excluded_participant_id` INT NULL DEFAULT NULL;'),
        array('table' => 'group_statistics', 'column' => 'original_group_id', 'sql' => 'ALTER TABLE `group_statistics` ADD COLUMN `original_group_id` INT NULL DEFAULT NULL;'),
        array('table' => 'group_statistics', 'column' => 'group_name', 'sql' => 'ALTER TABLE `group_statistics` ADD COLUMN `group_name` VARCHAR(255) NULL DEFAULT NULL;'),
        array('table' => 'group_statistics', 'column' => 'participant_count', 'sql' => 'ALTER TABLE `group_statistics` ADD COLUMN `participant_count` INT NULL DEFAULT 0;'),
        array('table' => 'group_statistics', 'column' => 'participant_with_email_count', 'sql' => 'ALTER TABLE `group_statistics` ADD COLUMN `participant_with_email_count` INT NULL DEFAULT 0;'),
        array('table' => 'group_statistics', 'column' => 'exclusion_count', 'sql' => 'ALTER TABLE `group_statistics` ADD COLUMN `exclusion_count` INT NULL DEFAULT 0;'),
        array('table' => 'group_statistics', 'column' => 'budget', 'sql' => 'ALTER TABLE `group_statistics` ADD COLUMN `budget` DECIMAL(10,2) NULL DEFAULT NULL;'),
        array('table' => 'group_statistics', 'column' => 'gift_exchange_date', 'sql' => 'ALTER TABLE `group_statistics` ADD COLUMN `gift_exchange_date` DATE NULL DEFAULT NULL;'),
        array('table' => 'group_statistics', 'column' => 'is_drawn', 'sql' => 'ALTER TABLE `group_statistics` ADD COLUMN `is_drawn` TINYINT(1) NULL DEFAULT 0;'),
        array('table' => 'group_statistics', 'column' => 'created_at', 'sql' => 'ALTER TABLE `group_statistics` ADD COLUMN `created_at` TIMESTAMP NULL DEFAULT NULL;'),
        array('table' => 'group_statistics', 'column' => 'archived_at', 'sql' => 'ALTER TABLE `group_statistics` ADD COLUMN `archived_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP;'),
    );
}

function master_admin_identifier($name) {
    return is_string($name) && preg_match('/^[A-Za-z0-9_]+$/', $name) === 1;
}

function master_admin_list_columns(PDO $pdo, $table) {
    if (!master_admin_identifier($table)) {
        return array('exists' => false, 'columns' => array());
    }
    try {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $check = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ?");
            $check->execute(array($table));
            $exists = (bool) $check->fetchColumn();
            if (!$exists) {
                return array('exists' => false, 'columns' => array());
            }
            $stmt = $pdo->query('PRAGMA table_info(' . $pdo->quote($table) . ')');
            $columns = array();
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($row['name'])) {
                        $columns[] = $row['name'];
                    }
                }
            }
            return array('exists' => true, 'columns' => $columns);
        }

        $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');
        $columns = array();
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if (isset($row['Field'])) {
                    $columns[] = $row['Field'];
                }
            }
        }
        return array('exists' => true, 'columns' => $columns);
    } catch (Exception $e) {
        error_log('Spaltenprüfung ' . $table . ': ' . $e->getMessage());
        return array('exists' => false, 'columns' => array());
    }
}

function master_admin_repair_sql($table, $column, array $flags) {
    if ($table === 'participants' && $column === 'participant_token' && !empty($flags['participants.token'])) {
        return 'ALTER TABLE `participants` CHANGE COLUMN `token` `participant_token` VARCHAR(64) NULL;';
    }
    foreach (master_admin_required_columns() as $item) {
        if ($item['table'] === $table && $item['column'] === $column) {
            return $item['sql'];
        }
    }
    return '';
}

function master_admin_schema_report(PDO $pdo) {
    $tables = array();
    foreach (master_admin_required_columns() as $column) {
        $tables[$column['table']] = true;
    }
    foreach (array_keys($tables) as $table) {
        $tables[$table] = master_admin_list_columns($pdo, $table);
    }

    $participant_columns = isset($tables['participants']['columns']) ? $tables['participants']['columns'] : array();
    $flags = array(
        'participants.token' => in_array('token', $participant_columns, true),
    );

    $items = array();
    $missing = 0;
    foreach (master_admin_required_columns() as $column) {
        $info = $tables[$column['table']];
        $ok = !empty($info['exists']) && in_array($column['column'], $info['columns'], true);
        $flags[$column['table'] . '.' . $column['column']] = $ok;
        $table_missing = empty($info['exists']);
        $sql = '';
        $note = '';
        if (!$ok) {
            $missing++;
            if ($table_missing) {
                $note = 'Tabelle `' . $column['table'] . '` fehlt.';
            } else {
                $sql = master_admin_repair_sql($column['table'], $column['column'], $flags);
            }
        }
        $items[] = array(
            'table' => $column['table'],
            'column' => $column['column'],
            'ok' => $ok,
            'table_missing' => $table_missing,
            'sql' => $sql,
            'note' => $note,
        );
    }

    return array(
        'flags' => $flags,
        'items' => $items,
        'missing_count' => $missing,
    );
}

function master_admin_filters_from_request(array $query) {
    $statuses = master_admin_status_options();
    $sorts = master_admin_sort_options();
    $status = isset($query['status']) ? (string) $query['status'] : 'alle';
    if (!isset($statuses[$status])) {
        $status = 'alle';
    }
    $sort = isset($query['sort']) ? (string) $query['sort'] : 'created_at';
    if (!isset($sorts[$sort])) {
        $sort = 'created_at';
    }
    $dir = (isset($query['dir']) && strtolower((string) $query['dir']) === 'asc') ? 'asc' : 'desc';
    $view = (isset($query['view']) && $query['view'] === 'cards') ? 'cards' : 'table';
    $q = isset($query['q']) ? trim((string) $query['q']) : '';
    if (function_exists('mb_substr')) {
        $q = mb_substr($q, 0, 100, 'UTF-8');
    } else {
        $q = substr($q, 0, 100);
    }
    $page = isset($query['page']) ? (int) $query['page'] : 1;
    if ($page < 1) {
        $page = 1;
    }
    $archive_page = isset($query['archive_page']) ? (int) $query['archive_page'] : 1;
    if ($archive_page < 1) {
        $archive_page = 1;
    }
    return array(
        'q' => $q,
        'status' => $status,
        'sort' => $sort,
        'dir' => $dir,
        'view' => $view,
        'page' => $page,
        'archive_page' => $archive_page,
    );
}

function master_admin_query_params(array $filters, array $override = array()) {
    $filters = array_merge($filters, $override);
    $params = array();
    if (isset($filters['q']) && $filters['q'] !== '') {
        $params['q'] = $filters['q'];
    }
    if (isset($filters['status']) && $filters['status'] !== 'alle') {
        $params['status'] = $filters['status'];
    }
    if (isset($filters['sort']) && $filters['sort'] !== 'created_at') {
        $params['sort'] = $filters['sort'];
    }
    if (isset($filters['dir']) && $filters['dir'] !== 'desc') {
        $params['dir'] = $filters['dir'];
    }
    if (isset($filters['view']) && $filters['view'] !== 'table') {
        $params['view'] = $filters['view'];
    }
    if (isset($filters['page']) && (int) $filters['page'] > 1) {
        $params['page'] = (int) $filters['page'];
    }
    if (isset($filters['archive_page']) && (int) $filters['archive_page'] > 1) {
        $params['archive_page'] = (int) $filters['archive_page'];
    }
    return $params;
}

function master_admin_order_sql($sort, $dir, array $flags = array()) {
    $has_created = !array_key_exists('groups.created_at', $flags) || !empty($flags['groups.created_at']);
    $has_gift = !array_key_exists('groups.gift_exchange_date', $flags) || !empty($flags['groups.gift_exchange_date']);
    $map = array(
        'name' => 'g.`name`',
        'participant_count' => '`participant_count`',
    );
    if ($has_created) {
        $map['created_at'] = 'g.`created_at`';
    }
    if ($has_gift) {
        $map['gift_exchange_date'] = 'g.`gift_exchange_date`';
    }
    $sort = (string) $sort;
    if (!isset($map[$sort])) {
        if (isset($map['created_at'])) {
            $column = $map['created_at'];
        } else {
            $column = 'g.`name`';
        }
    } else {
        $column = $map[$sort];
    }
    $direction = (strtolower((string) $dir) === 'asc') ? 'ASC' : 'DESC';
    return $column . ' ' . $direction . ', g.`id` DESC';
}

function master_admin_like_pattern($term) {
    // Ausrufezeichen als ESCAPE, damit MySQL und SQLite dasselbe Zeichen sehen.
    $term = str_replace(array('!', '%', '_'), array('!!', '!%', '!_'), (string) $term);
    return '%' . $term . '%';
}

function master_admin_reference_dates($now) {
    $now = (int) $now;
    return array(
        'today' => date('Y-m-d', $now),
        'orphan_before' => date('Y-m-d H:i:s', $now - (14 * 86400)),
    );
}

function master_admin_flag_known(array $flags, $key) {
    if (!array_key_exists($key, $flags)) {
        return true;
    }
    return !empty($flags[$key]);
}

function master_admin_group_query(array $filters, array $flags, $now) {
    $dates = master_admin_reference_dates($now);
    $has_created = master_admin_flag_known($flags, 'groups.created_at');
    $has_gift = master_admin_flag_known($flags, 'groups.gift_exchange_date');
    $has_admin_email = !empty($flags['groups.admin_email']);
    $has_reveal = !empty($flags['groups.reveal_sent_at']);
    $has_reminder = !empty($flags['groups.reminder_sent_at']);

    $select_created = $has_created ? 'g.`created_at`' : 'NULL';
    $select_gift = $has_gift ? 'g.`gift_exchange_date`' : 'NULL';
    $select_email = $has_admin_email ? 'g.`admin_email`' : 'NULL';
    $select_reveal = $has_reveal ? 'g.`reveal_sent_at`' : 'NULL';
    $select_reminder = $has_reminder ? 'g.`reminder_sent_at`' : 'NULL';

    $select = 'SELECT g.`id`, g.`name`, g.`description`, g.`budget`, ' . $select_gift . ' AS gift_exchange_date, g.`is_drawn`, '
        . $select_created . ' AS created_at, g.`admin_token`, ' . $select_email . ' AS admin_email, '
        . $select_reveal . ' AS reveal_sent_at, ' . $select_reminder . ' AS reminder_sent_at, '
        . 'COUNT(DISTINCT p.`id`) AS participant_count, '
        . "COUNT(DISTINCT CASE WHEN p.`email` IS NOT NULL AND p.`email` != '' THEN p.`id` END) AS participants_with_email, "
        . 'COUNT(DISTINCT e.`id`) AS exclusion_count '
        . 'FROM `groups` g '
        . 'LEFT JOIN `participants` p ON g.`id` = p.`group_id` '
        . 'LEFT JOIN `exclusions` e ON g.`id` = e.`group_id`';

    $where = array();
    $having = array();
    $params = array();
    $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
    if ($q !== '') {
        $where[] = 'g.`name` LIKE :q ESCAPE \'!\'';
        $params[':q'] = master_admin_like_pattern($q);
    }

    $status = isset($filters['status']) ? (string) $filters['status'] : 'alle';
    if (!isset(master_admin_status_options()[$status])) {
        $status = 'alle';
    }

    if ($status === 'ausgelost') {
        $where[] = 'g.`is_drawn` = 1';
    } elseif ($status === 'offen') {
        $where[] = 'g.`is_drawn` = 0';
    } elseif ($status === 'leer') {
        $having[] = 'participant_count = 0';
    } elseif ($status === 'datum_vorbei') {
        if ($has_gift) {
            $where[] = 'g.`gift_exchange_date` IS NOT NULL AND g.`gift_exchange_date` < :today';
            $params[':today'] = $dates['today'];
        } else {
            $where[] = '1 = 0';
        }
    } elseif ($status === 'problematisch') {
        $or = array();
        if ($has_created) {
            $or[] = '(participant_count = 0 AND g.`created_at` IS NOT NULL AND g.`created_at` < :orphan_before)';
            $params[':orphan_before'] = $dates['orphan_before'];
        }
        if ($has_gift) {
            $or[] = '(g.`gift_exchange_date` IS NOT NULL AND g.`gift_exchange_date` < :today AND g.`is_drawn` = 0)';
            $params[':today'] = $dates['today'];
        }
        $or[] = '(g.`is_drawn` = 1 AND participants_with_email = 0 AND participant_count > 0)';
        $or[] = '(participant_count > 0 AND participant_count < 3)';
        if (count($or) > 0) {
            $having[] = '(' . implode(' OR ', $or) . ')';
        }
    } elseif ($status === 'aufraeumen') {
        $or = array('participant_count = 0');
        if ($has_gift) {
            $or[] = '(g.`gift_exchange_date` IS NOT NULL AND g.`gift_exchange_date` < :today AND g.`is_drawn` = 0)';
            $params[':today'] = $dates['today'];
        }
        $having[] = '(' . implode(' OR ', $or) . ')';
    }

    $sql_where = count($where) > 0 ? (' WHERE ' . implode(' AND ', $where)) : '';
    $group_cols = array('g.`id`', 'g.`name`', 'g.`description`', 'g.`budget`', 'g.`is_drawn`', 'g.`admin_token`');
    if ($has_gift) {
        $group_cols[] = 'g.`gift_exchange_date`';
    }
    if ($has_created) {
        $group_cols[] = 'g.`created_at`';
    }
    if ($has_admin_email) {
        $group_cols[] = 'g.`admin_email`';
    }
    if ($has_reveal) {
        $group_cols[] = 'g.`reveal_sent_at`';
    }
    if ($has_reminder) {
        $group_cols[] = 'g.`reminder_sent_at`';
    }
    $sql_group = ' GROUP BY ' . implode(', ', $group_cols);
    $sql_having = count($having) > 0 ? (' HAVING ' . implode(' AND ', $having)) : '';
    $order = master_admin_order_sql(
        isset($filters['sort']) ? $filters['sort'] : 'created_at',
        isset($filters['dir']) ? $filters['dir'] : 'desc',
        $flags
    );
    $body = $select . $sql_where . $sql_group . $sql_having;

    return array(
        'select_sql' => $body . ' ORDER BY ' . $order,
        'count_sql' => 'SELECT COUNT(*) FROM (' . $body . ') master_admin_counted',
        'params' => $params,
    );
}

function master_admin_bind(PDOStatement $stmt, array $params) {
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
}

function master_admin_fetch_groups(PDO $pdo, array $filters, array $flags, $page, $per_page, $now) {
    $page = max(1, (int) $page);
    $per_page = (int) $per_page;
    if ($per_page < 1) {
        $per_page = 20;
    }
    if ($per_page > 500) {
        $per_page = 500;
    }
    $query = master_admin_group_query($filters, $flags, $now);
    $count_stmt = $pdo->prepare($query['count_sql']);
    master_admin_bind($count_stmt, $query['params']);
    $count_stmt->execute();
    $total = (int) $count_stmt->fetchColumn();
    $pages = max(1, (int) ceil($total / $per_page));
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $per_page;
    $sql = $query['select_sql'] . ' LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    master_admin_bind($stmt, $query['params']);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return array(
        'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'per_page' => $per_page,
        'offset' => $offset,
    );
}

function master_admin_fetch_all_groups(PDO $pdo, array $filters, array $flags, $now) {
    $all = array();
    $page = 1;
    $total = 0;
    do {
        $batch = master_admin_fetch_groups($pdo, $filters, $flags, $page, 200, $now);
        $total = $batch['total'];
        foreach ($batch['rows'] as $row) {
            $all[] = $row;
        }
        $page++;
    } while (count($all) < $total && count($batch['rows']) > 0 && $page < 50);

    return $all;
}

function master_admin_group_warnings(array $group, $now, array $flags = array()) {
    $now = (int) $now;
    $dates = master_admin_reference_dates($now);
    $created_known = master_admin_flag_known($flags, 'groups.created_at');
    $gift_known = master_admin_flag_known($flags, 'groups.gift_exchange_date');
    $email_known = !empty($flags['groups.admin_email']);
    $count = isset($group['participant_count']) ? (int) $group['participant_count'] : 0;
    $emails = isset($group['participants_with_email']) ? (int) $group['participants_with_email'] : 0;
    $drawn = isset($group['is_drawn']) && (int) $group['is_drawn'] === 1;
    $warnings = array();

    $created_old = false;
    if ($created_known && !empty($group['created_at'])) {
        $created_ts = strtotime((string) $group['created_at']);
        if ($created_ts !== false && $created_ts < ($now - (14 * 86400))) {
            $created_old = true;
        }
    }
    if ($count === 0 && $created_old) {
        $warnings[] = '0 Teilnehmer seit >14 Tagen';
    }
    if ($gift_known && !empty($group['gift_exchange_date']) && !$drawn) {
        $gift_day = substr((string) $group['gift_exchange_date'], 0, 10);
        if ($gift_day < $dates['today']) {
            $warnings[] = 'Datum vorbei, nie ausgelost';
        }
    }
    if ($drawn && $count > 0 && $emails === 0) {
        $warnings[] = 'Ausgelost, aber keine E-Mails';
    }
    if ($count > 0 && $count < 3) {
        $warnings[] = 'Weniger als 3 Teilnehmer';
    }
    if ($email_known && $count === 0 && $created_old) {
        $admin_email = isset($group['admin_email']) ? trim((string) $group['admin_email']) : '';
        if ($admin_email === '') {
            $warnings[] = 'Verwaist (leer, ohne Admin-E-Mail)';
        }
    }
    return $warnings;
}

function master_admin_truncate($text, $length = 50) {
    $text = (string) $text;
    $length = (int) $length;
    if ($length < 1) {
        $length = 50;
    }
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') <= $length) {
            return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        }
        return htmlspecialchars(mb_substr($text, 0, $length, 'UTF-8'), ENT_QUOTES, 'UTF-8') . '…';
    }
    if (strlen($text) <= $length) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    return htmlspecialchars(substr($text, 0, $length), ENT_QUOTES, 'UTF-8') . '…';
}

function master_admin_format_date($value, $with_time = false) {
    if ($value === null || $value === '') {
        return '';
    }
    $timestamp = strtotime((string) $value);
    if ($timestamp === false) {
        return '';
    }
    return $with_time ? date('d.m.Y H:i', $timestamp) : date('d.m.Y', $timestamp);
}

function master_admin_manage_url($admin_token) {
    return '/admin.php?token=' . rawurlencode((string) $admin_token);
}

function master_admin_normalize_ids($ids) {
    if (!is_array($ids)) {
        return array();
    }
    $out = array();
    foreach ($ids as $id) {
        if (is_int($id)) {
            $number = $id;
        } elseif (is_string($id) && preg_match('/^[1-9][0-9]*$/', $id)) {
            $number = (int) $id;
        } else {
            continue;
        }
        if ($number > 0 && $number <= 2147483647) {
            $out[$number] = $number;
        }
    }
    return array_values($out);
}

function master_admin_bulk_confirmation_valid($pending, $token, $now) {
    if (!is_array($pending)) {
        return null;
    }
    if (!isset($pending['action'], $pending['ids'], $pending['token'], $pending['created_at'])) {
        return null;
    }
    if ($pending['action'] !== 'archive' && $pending['action'] !== 'delete') {
        return null;
    }
    if (!is_string($pending['token']) || $pending['token'] === '' || !is_string($token)) {
        return null;
    }
    if (!hash_equals($pending['token'], $token)) {
        return null;
    }
    $created = (int) $pending['created_at'];
    $now = (int) $now;
    if ($created <= 0 || $now < $created || ($now - $created) > 900) {
        return null;
    }
    $ids = master_admin_normalize_ids($pending['ids']);
    if (count($ids) === 0 || count($ids) > 200) {
        return null;
    }
    return array('action' => $pending['action'], 'ids' => $ids);
}

function master_admin_apply_bulk(PDO $pdo, $action, array $ids) {
    if ($action !== 'archive' && $action !== 'delete') {
        throw new InvalidArgumentException('Unbekannte Aktion.');
    }
    $ids = master_admin_normalize_ids($ids);
    if (count($ids) === 0) {
        throw new InvalidArgumentException('Keine Gruppe ausgewählt.');
    }
    if (count($ids) > 200) {
        throw new InvalidArgumentException('Höchstens 200 Gruppen auf einmal.');
    }

    $started = !$pdo->inTransaction();
    if ($started) {
        $pdo->beginTransaction();
    }
    try {
        foreach ($ids as $id) {
            $stmt = $pdo->prepare('SELECT * FROM `groups` WHERE `id` = ?');
            $stmt->execute(array($id));
            $group = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$group) {
                throw new RuntimeException('Gruppe ' . $id . ' wurde nicht gefunden.');
            }
            if ($action === 'archive') {
                archive_group_into_statistics($pdo, $group);
            } else {
                delete_group_with_members($pdo, $id);
            }
        }
        if ($started) {
            $pdo->commit();
        }
        return array('ok' => true, 'count' => count($ids), 'action' => $action);
    } catch (Exception $e) {
        if ($started && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function master_admin_handle_bulk_request(PDO $pdo, array $post, array &$session, $csrf_ok, $now = null) {
    $now = ($now === null) ? time() : (int) $now;
    $is_bulk = isset($post['bulk_action']) || isset($post['confirm_bulk']) || isset($post['cancel_bulk']);
    if (!$is_bulk) {
        return null;
    }
    if (!$csrf_ok) {
        return array(
            'status' => 'error',
            'message' => 'Ungültiger CSRF-Token. Bitte lade die Seite neu und versuche es erneut.',
        );
    }
    if (isset($post['cancel_bulk'])) {
        unset($session['master_admin_pending_bulk']);
        return array('status' => 'cancelled', 'message' => 'Aktion abgebrochen. Es wurde nichts geändert.');
    }
    if (isset($post['confirm_bulk'])) {
        $pending = isset($session['master_admin_pending_bulk']) ? $session['master_admin_pending_bulk'] : null;
        $token = isset($post['confirm_token']) && is_string($post['confirm_token']) ? $post['confirm_token'] : '';
        $valid = master_admin_bulk_confirmation_valid($pending, $token, $now);
        if ($valid === null) {
            unset($session['master_admin_pending_bulk']);
            return array(
                'status' => 'error',
                'message' => 'Die Bestätigung ist ungültig oder abgelaufen. Es wurde nichts geändert.',
            );
        }
        unset($session['master_admin_pending_bulk']);
        try {
            $result = master_admin_apply_bulk($pdo, $valid['action'], $valid['ids']);
        } catch (Exception $e) {
            error_log('Master-Admin Bulk: ' . $e->getMessage());
            return array(
                'status' => 'error',
                'message' => 'Die Aktion wurde zurückgerollt. Es wurde nichts geändert.',
            );
        }
        $verb = ($valid['action'] === 'archive') ? 'archiviert' : 'gelöscht';
        return array(
            'status' => 'done',
            'action' => $valid['action'],
            'count' => $result['count'],
            'message' => $result['count'] . ' Gruppen wurden ' . $verb . '.',
        );
    }

    $action = (string) $post['bulk_action'];
    if ($action !== 'archive' && $action !== 'delete') {
        return array('status' => 'error', 'message' => 'Unbekannte Aktion.');
    }
    $ids = master_admin_normalize_ids(isset($post['group_ids']) ? $post['group_ids'] : array());
    if (count($ids) === 0) {
        return array('status' => 'error', 'message' => 'Keine Gruppe ausgewählt.');
    }
    if (count($ids) > 200) {
        return array('status' => 'error', 'message' => 'Höchstens 200 Gruppen auf einmal.');
    }
    $token = bin2hex(random_bytes(16));
    $session['master_admin_pending_bulk'] = array(
        'action' => $action,
        'ids' => $ids,
        'token' => $token,
        'created_at' => $now,
    );
    return array(
        'status' => 'confirm',
        'action' => $action,
        'ids' => $ids,
        'token' => $token,
    );
}

function master_admin_group_names(PDO $pdo, array $ids) {
    $ids = master_admin_normalize_ids($ids);
    if (count($ids) === 0) {
        return array();
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare('SELECT `id`, `name` FROM `groups` WHERE `id` IN (' . $placeholders . ') ORDER BY `name` ASC');
    $stmt->execute($ids);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function master_admin_csv_cell($value) {
    $value = (string) $value;
    $first = ($value === '') ? '' : substr($value, 0, 1);
    $dangerous = ($first !== '' && strpos("=+-@\t\r", $first) !== false);
    $value = str_replace(array("\r", "\n"), ' ', $value);
    if ($dangerous) {
        $value = "'" . $value;
    }
    return $value;
}

function master_admin_csv_document(array $rows) {
    $handle = fopen('php://temp', 'w+');
    foreach ($rows as $row) {
        $clean = array();
        foreach ($row as $cell) {
            $clean[] = master_admin_csv_cell($cell);
        }
        fputcsv($handle, $clean, ';', '"', '\\');
    }
    rewind($handle);
    $csv = stream_get_contents($handle);
    fclose($handle);
    if ($csv === false) {
        $csv = '';
    }
    $csv = str_replace("\n", "\r\n", $csv);
    return "\xEF\xBB\xBF" . $csv;
}

function master_admin_groups_csv(array $rows, array $flags, $now) {
    $reveal_known = !empty($flags['groups.reveal_sent_at']);
    $reminder_known = !empty($flags['groups.reminder_sent_at']);
    $header = array(
        'ID',
        'Name',
        'Erstellt',
        'Geschenkdatum',
        'Status',
        'Budget CHF',
        'Teilnehmer',
        'Mit E-Mail',
        'Ausschlüsse',
        'Auflösung gesendet',
        'Erinnerung gesendet',
        'Hinweise',
    );
    $lines = array($header);
    foreach ($rows as $row) {
        $budget = '';
        if (isset($row['budget']) && $row['budget'] !== null && $row['budget'] !== '') {
            $budget = number_format((float) $row['budget'], 2, ',', '');
        }
        $reveal = '–';
        if ($reveal_known) {
            $reveal = !empty($row['reveal_sent_at']) ? master_admin_format_date($row['reveal_sent_at'], true) : 'nicht gesendet';
        }
        $reminder = '–';
        if ($reminder_known) {
            $reminder = !empty($row['reminder_sent_at']) ? master_admin_format_date($row['reminder_sent_at'], true) : 'nicht gesendet';
        }
        $warnings = master_admin_group_warnings($row, $now, $flags);
        $lines[] = array(
            isset($row['id']) ? (int) $row['id'] : '',
            isset($row['name']) ? $row['name'] : '',
            master_admin_format_date(isset($row['created_at']) ? $row['created_at'] : '', false),
            master_admin_format_date(isset($row['gift_exchange_date']) ? $row['gift_exchange_date'] : '', false),
            (isset($row['is_drawn']) && (int) $row['is_drawn'] === 1) ? 'Ausgelost' : 'Offen',
            $budget,
            isset($row['participant_count']) ? (int) $row['participant_count'] : 0,
            isset($row['participants_with_email']) ? (int) $row['participants_with_email'] : 0,
            isset($row['exclusion_count']) ? (int) $row['exclusion_count'] : 0,
            $reveal,
            $reminder,
            implode(' | ', $warnings),
        );
    }
    return master_admin_csv_document($lines);
}

function master_admin_kpis(PDO $pdo, $now, array $flags = array()) {
    $now = (int) $now;
    $has_created = master_admin_flag_known($flags, 'groups.created_at');
    $has_gift = master_admin_flag_known($flags, 'groups.gift_exchange_date');
    $today = date('Y-m-d', $now);
    $month_start = date('Y-m-01 00:00:00', $now);
    $month_end = date('Y-m-01 00:00:00', strtotime('+1 month', strtotime(date('Y-m-01', $now))));

    $upcoming_sql = $has_gift
        ? 'SUM(CASE WHEN gift_exchange_date IS NOT NULL AND gift_exchange_date >= ? THEN 1 ELSE 0 END)'
        : '0';
    $month_sql = $has_created
        ? 'SUM(CASE WHEN created_at IS NOT NULL AND created_at >= ? AND created_at < ? THEN 1 ELSE 0 END)'
        : '0';
    $sql = 'SELECT COUNT(*) AS active_groups, '
        . 'SUM(CASE WHEN is_drawn = 1 THEN 1 ELSE 0 END) AS active_drawn, '
        . 'AVG(CASE WHEN budget IS NOT NULL THEN budget END) AS active_avg_budget, '
        . $upcoming_sql . ' AS upcoming_events, '
        . $month_sql . ' AS groups_this_month FROM `groups`';
    $params = array();
    if ($has_gift) {
        $params[] = $today;
    }
    if ($has_created) {
        $params[] = $month_start;
        $params[] = $month_end;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $active = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$active) {
        $active = array();
    }

    $people = array('cnt' => 0, 'email_cnt' => 0);
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS cnt, SUM(CASE WHEN email IS NOT NULL AND email != '' THEN 1 ELSE 0 END) AS email_cnt FROM `participants`");
        $fetched = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if ($fetched) {
            $people = $fetched;
        }
    } catch (Exception $e) {
        error_log('Teilnehmer-Kennzahlen: ' . $e->getMessage());
    }

    $archived = array(
        'archived_groups' => 0,
        'archived_participants' => 0,
        'archived_email_participants' => 0,
        'archived_drawn' => 0,
        'archived_avg_budget' => null,
    );
    try {
        $stmt = $pdo->query('SELECT COUNT(*) AS archived_groups, COALESCE(SUM(participant_count), 0) AS archived_participants, COALESCE(SUM(participant_with_email_count), 0) AS archived_email_participants, SUM(CASE WHEN is_drawn = 1 THEN 1 ELSE 0 END) AS archived_drawn, AVG(CASE WHEN budget IS NOT NULL THEN budget END) AS archived_avg_budget FROM `group_statistics`');
        $fetched = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
        if ($fetched) {
            $archived = $fetched;
        }
    } catch (Exception $e) {
        error_log('Archiv-Kennzahlen: ' . $e->getMessage());
    }

    $active_groups = isset($active['active_groups']) ? (int) $active['active_groups'] : 0;
    $archived_groups = isset($archived['archived_groups']) ? (int) $archived['archived_groups'] : 0;
    $active_participants = isset($people['cnt']) ? (int) $people['cnt'] : 0;
    $archived_participants = isset($archived['archived_participants']) ? (int) $archived['archived_participants'] : 0;
    $active_email = isset($people['email_cnt']) ? (int) $people['email_cnt'] : 0;
    $archived_email = isset($archived['archived_email_participants']) ? (int) $archived['archived_email_participants'] : 0;
    $active_drawn = isset($active['active_drawn']) ? (int) $active['active_drawn'] : 0;
    $archived_drawn = isset($archived['archived_drawn']) ? (int) $archived['archived_drawn'] : 0;

    $total_groups = $active_groups + $archived_groups;
    $total_participants = $active_participants + $archived_participants;
    $total_email = $active_email + $archived_email;
    $total_drawn = $active_drawn + $archived_drawn;

    $budget_values = array();
    if (isset($active['active_avg_budget']) && $active['active_avg_budget'] !== null) {
        $budget_values[] = (float) $active['active_avg_budget'];
    }
    if (isset($archived['archived_avg_budget']) && $archived['archived_avg_budget'] !== null) {
        $budget_values[] = (float) $archived['archived_avg_budget'];
    }
    $avg_budget = count($budget_values) > 0 ? array_sum($budget_values) / count($budget_values) : null;

    return array(
        'total_groups' => $total_groups,
        'active_groups' => $active_groups,
        'archived_groups' => $archived_groups,
        'total_participants' => $total_participants,
        'active_participants' => $active_participants,
        'archived_participants' => $archived_participants,
        'avg_group_size' => $total_groups > 0 ? round($total_participants / $total_groups, 1) : 0,
        'completion_rate' => $total_groups > 0 ? (int) round($total_drawn / $total_groups * 100) : 0,
        'total_drawn' => $total_drawn,
        'avg_budget' => $avg_budget,
        'email_rate' => $total_participants > 0 ? (int) round($total_email / $total_participants * 100) : 0,
        'total_email' => $total_email,
        'upcoming_events' => isset($active['upcoming_events']) ? (int) $active['upcoming_events'] : 0,
        'groups_this_month' => isset($active['groups_this_month']) ? (int) $active['groups_this_month'] : 0,
    );
}

function master_admin_monthly_trend(PDO $pdo, $now, $months = 6) {
    $now = (int) $now;
    $months = (int) $months;
    if ($months < 1) {
        $months = 6;
    }
    $origin = strtotime(date('Y-m-01', $now));
    $keys = array();
    for ($i = $months - 1; $i >= 0; $i--) {
        $keys[] = date('Y-m', strtotime('-' . $i . ' months', $origin));
    }
    $counts = array();
    foreach ($keys as $key) {
        $counts[$key] = 0;
    }
    $range_start = $keys[0] . '-01 00:00:00';
    $range_end = date('Y-m-01 00:00:00', strtotime('+1 month', strtotime($keys[$months - 1] . '-01')));
    foreach (array('groups', 'group_statistics') as $table) {
        try {
            $stmt = $pdo->prepare('SELECT `created_at` FROM `' . $table . '` WHERE `created_at` IS NOT NULL AND `created_at` >= ? AND `created_at` < ?');
            $stmt->execute(array($range_start, $range_end));
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $month = substr((string) $row['created_at'], 0, 7);
                if (isset($counts[$month])) {
                    $counts[$month]++;
                }
            }
        } catch (Exception $e) {
            error_log('Monatstrend ' . $table . ': ' . $e->getMessage());
        }
    }
    return $counts;
}

function master_admin_season_bounds($now) {
    $now = (int) $now;
    $year = (int) date('Y', $now);
    $month = (int) date('n', $now);
    if ($month >= 9) {
        $start_year = $year;
    } elseif ($month <= 1) {
        $start_year = $year - 1;
    } else {
        $start_year = $year;
    }
    $current_start = sprintf('%04d-09-01 00:00:00', $start_year);
    $current_end = sprintf('%04d-02-01 00:00:00', $start_year + 1);
    $previous_start = sprintf('%04d-09-01 00:00:00', $start_year - 1);
    $previous_end = sprintf('%04d-02-01 00:00:00', $start_year);
    $short = $start_year . '/' . substr((string) ($start_year + 1), -2);
    $previous_short = ($start_year - 1) . '/' . substr((string) $start_year, -2);
    return array(
        'current_start' => $current_start,
        'current_end' => $current_end,
        'previous_start' => $previous_start,
        'previous_end' => $previous_end,
        'nov_start' => sprintf('%04d-11-01', $start_year),
        'nov_end' => sprintf('%04d-01-01', $start_year + 1),
        'label' => 'Wichtel-Saison ' . $short . ' (1. September ' . $start_year . ' – 31. Januar ' . ($start_year + 1) . ')',
        'previous_label' => 'Wichtel-Saison ' . $previous_short,
        'short_label' => $short,
        'previous_short_label' => $previous_short,
        'started' => $now >= strtotime($current_start),
        'definition' => '1. September bis 31. Januar',
    );
}

function master_admin_window_stats(PDO $pdo, $start, $end) {
    $stats = array(
        'groups' => 0,
        'active_groups' => 0,
        'archived_groups' => 0,
        'participants' => 0,
        'archived_participants' => 0,
        'drawn' => 0,
    );
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS groups_count, SUM(CASE WHEN is_drawn = 1 THEN 1 ELSE 0 END) AS drawn_count FROM `groups` WHERE created_at >= ? AND created_at < ?');
        $stmt->execute(array($start, $end));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats['active_groups'] = (int) $row['groups_count'];
            $stats['drawn'] += (int) $row['drawn_count'];
        }
    } catch (Exception $e) {
        error_log('Saison Gruppen: ' . $e->getMessage());
    }
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM `participants` WHERE created_at >= ? AND created_at < ?');
        $stmt->execute(array($start, $end));
        $stats['participants'] = (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        error_log('Saison Teilnehmer: ' . $e->getMessage());
    }
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) AS groups_count, COALESCE(SUM(participant_count), 0) AS participants_sum, SUM(CASE WHEN is_drawn = 1 THEN 1 ELSE 0 END) AS drawn_count FROM `group_statistics` WHERE created_at >= ? AND created_at < ?');
        $stmt->execute(array($start, $end));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $stats['archived_groups'] = (int) $row['groups_count'];
            $stats['archived_participants'] = (int) $row['participants_sum'];
            $stats['drawn'] += (int) $row['drawn_count'];
        }
    } catch (Exception $e) {
        error_log('Saison Archiv: ' . $e->getMessage());
    }
    $stats['groups'] = $stats['active_groups'] + $stats['archived_groups'];
    return $stats;
}

function master_admin_accumulate_days(PDO $pdo, $table, $field, array &$days, $start, $end) {
    if ($table !== 'groups' && $table !== 'group_statistics' && $table !== 'participants') {
        return;
    }
    if ($field !== 'groups' && $field !== 'participants') {
        return;
    }
    try {
        $stmt = $pdo->prepare('SELECT `created_at` FROM `' . $table . '` WHERE `created_at` IS NOT NULL AND `created_at` >= ? AND `created_at` < ?');
        $stmt->execute(array($start, $end));
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $day = substr((string) $row['created_at'], 0, 10);
            if (isset($days[$day])) {
                $days[$day][$field]++;
            }
        }
    } catch (Exception $e) {
        error_log('Tagesstatistik ' . $table . ': ' . $e->getMessage());
    }
}

function master_admin_daily_series(PDO $pdo, $start_date, $end_exclusive) {
    $cursor = strtotime($start_date . ' 00:00:00');
    $end = strtotime($end_exclusive . ' 00:00:00');
    $days = array();
    if ($cursor === false || $end === false || $cursor >= $end) {
        return array();
    }
    for ($t = $cursor; $t < $end; $t += 86400) {
        $key = date('Y-m-d', $t);
        $days[$key] = array('date' => $key, 'groups' => 0, 'participants' => 0);
    }
    $range_start = $start_date . ' 00:00:00';
    $range_end = $end_exclusive . ' 00:00:00';
    master_admin_accumulate_days($pdo, 'groups', 'groups', $days, $range_start, $range_end);
    master_admin_accumulate_days($pdo, 'group_statistics', 'groups', $days, $range_start, $range_end);
    master_admin_accumulate_days($pdo, 'participants', 'participants', $days, $range_start, $range_end);
    return array_values($days);
}

function master_admin_chart_svg(array $series) {
    $count = count($series);
    if ($count === 0) {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 80" role="img" aria-label="Keine Tageswerte"><text x="16" y="44" fill="#5f6368" font-size="14" font-family="Roboto, sans-serif">Keine Tageswerte</text></svg>';
    }
    $max = 1;
    foreach ($series as $point) {
        $max = max($max, (int) $point['groups'], (int) $point['participants']);
    }
    $width = 640;
    $height = 220;
    $left = 36;
    $right = 12;
    $top = 28;
    $bottom = 32;
    $inner_w = $width - $left - $right;
    $inner_h = $height - $top - $bottom;
    $group_points = array();
    $people_points = array();
    $x_of = array();
    for ($i = 0; $i < $count; $i++) {
        $x = $left + ($count === 1 ? ($inner_w / 2) : ($inner_w * $i / ($count - 1)));
        $x_of[$i] = $x;
        $group_y = $top + $inner_h - (((int) $series[$i]['groups']) / $max) * $inner_h;
        $people_y = $top + $inner_h - (((int) $series[$i]['participants']) / $max) * $inner_h;
        $group_points[] = round($x, 1) . ',' . round($group_y, 1);
        $people_points[] = round($x, 1) . ',' . round($people_y, 1);
    }
    $ticks = array();
    for ($i = 0; $i < $count; $i += 15) {
        $ticks[] = $i;
    }
    if ($ticks[count($ticks) - 1] !== $count - 1) {
        $ticks[] = $count - 1;
    }

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="Gruppen und Teilnehmer pro Tag im November und Dezember">';
    for ($step = 0; $step <= 2; $step++) {
        $y = round($top + ($inner_h * $step / 2), 1);
        $svg .= '<line x1="' . $left . '" y1="' . $y . '" x2="' . ($width - $right) . '" y2="' . $y . '" stroke="#e1e4e8" stroke-width="1"/>';
    }
    $svg .= '<text x="2" y="' . ($top + 4) . '" fill="#5f6368" font-size="11" font-family="Roboto, sans-serif">' . (int) $max . '</text>';
    $svg .= '<text x="' . $left . '" y="16" fill="#e63946" font-size="12" font-family="Roboto, sans-serif">Gruppen</text>';
    $svg .= '<text x="' . ($left + 72) . '" y="16" fill="#2a9d8f" font-size="12" font-family="Roboto, sans-serif">Teilnehmer</text>';
    $svg .= '<polyline fill="none" stroke="#2a9d8f" stroke-width="2" points="' . implode(' ', $people_points) . '"/>';
    $svg .= '<polyline fill="none" stroke="#e63946" stroke-width="2" points="' . implode(' ', $group_points) . '"/>';
    foreach ($ticks as $index) {
        $label = '';
        if (!empty($series[$index]['date'])) {
            $ts = strtotime($series[$index]['date']);
            if ($ts !== false) {
                $label = date('d.m.', $ts);
            }
        }
        $svg .= '<text x="' . round($x_of[$index], 1) . '" y="' . ($height - 8) . '" text-anchor="middle" fill="#5f6368" font-size="11" font-family="Roboto, sans-serif">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</text>';
    }
    $svg .= '</svg>';
    return $svg;
}

function master_admin_statistics_csv(array $kpis, array $season, array $current, array $previous, array $daily) {
    $rows = array(
        array('Kennzahl', 'Wert'),
        array('Gruppen gesamt', $kpis['total_groups']),
        array('Gruppen aktiv', $kpis['active_groups']),
        array('Gruppen archiviert', $kpis['archived_groups']),
        array('Teilnehmer gesamt', $kpis['total_participants']),
        array('Teilnehmer aktiv', $kpis['active_participants']),
        array('Teilnehmer archiviert', $kpis['archived_participants']),
        array('Durchschnittliche Gruppengrösse', $kpis['avg_group_size']),
        array('Abschlussrate Prozent', $kpis['completion_rate']),
        array('Gruppen ausgelost', $kpis['total_drawn']),
        array('E-Mail-Rate Prozent', $kpis['email_rate']),
        array('Teilnehmer mit E-Mail', $kpis['total_email']),
        array('Bevorstehende Events', $kpis['upcoming_events']),
        array('Gruppen diesen Monat', $kpis['groups_this_month']),
        array('Saisondefinition', $season['definition']),
        array('Aktuelle Saison', $season['label']),
        array('Vorherige Saison', $season['previous_label']),
        array('Gruppen aktuelle Saison', $current['groups']),
        array('Gruppen vorherige Saison', $previous['groups']),
        array('Ausgelost aktuelle Saison', $current['drawn']),
        array('Ausgelost vorherige Saison', $previous['drawn']),
        array('Teilnehmer aktiv aktuelle Saison', $current['participants']),
        array('Teilnehmer aktiv vorherige Saison', $previous['participants']),
        array('Teilnehmer archiviert aktuelle Saison', $current['archived_participants']),
        array('Teilnehmer archiviert vorherige Saison', $previous['archived_participants']),
        array(),
        array('Datum', 'Gruppen', 'Teilnehmer'),
    );
    foreach ($daily as $point) {
        $rows[] = array($point['date'], (int) $point['groups'], (int) $point['participants']);
    }
    return master_admin_csv_document($rows);
}

function master_admin_fetch_archive(PDO $pdo, $page, $per_page) {
    $page = max(1, (int) $page);
    $per_page = (int) $per_page;
    if ($per_page < 1) {
        $per_page = 15;
    }
    if ($per_page > 100) {
        $per_page = 100;
    }
    try {
        $total = (int) $pdo->query('SELECT COUNT(*) FROM `group_statistics`')->fetchColumn();
        $pages = max(1, (int) ceil($total / $per_page));
        if ($page > $pages) {
            $page = $pages;
        }
        $offset = ($page - 1) * $per_page;
        $stmt = $pdo->prepare(
            'SELECT `id`, `group_name`, `participant_count`, `participant_with_email_count`, `budget`, `gift_exchange_date`, `is_drawn`, `archived_at` '
            . 'FROM `group_statistics` ORDER BY `archived_at` DESC LIMIT ' . (int) $per_page . ' OFFSET ' . (int) $offset
        );
        $stmt->execute();
        return array(
            'rows' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'offset' => $offset,
            'error' => false,
        );
    } catch (Exception $e) {
        error_log('Archivliste: ' . $e->getMessage());
        return array(
            'rows' => array(),
            'total' => 0,
            'page' => 1,
            'pages' => 1,
            'offset' => 0,
            'error' => true,
        );
    }
}

function master_admin_month_labels() {
    return array(
        '01' => 'Jan', '02' => 'Feb', '03' => 'Mär', '04' => 'Apr',
        '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
        '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Dez',
    );
}

function master_admin_mail_status($from_email, $from_name, $sendmail_path, $mail_exists) {
    $from_email = is_string($from_email) ? trim($from_email) : '';
    $from_name = is_string($from_name) ? trim($from_name) : '';
    $sendmail_path = is_string($sendmail_path) ? trim($sendmail_path) : '';
    $valid_email = $from_email !== '' && (bool) filter_var($from_email, FILTER_VALIDATE_EMAIL);
    $warnings = array();
    if (!$mail_exists) {
        $warnings[] = 'PHP-Funktion mail() ist nicht verfügbar.';
    }
    if (!$valid_email || $from_name === '') {
        $warnings[] = 'Absender SMTP_FROM_EMAIL oder SMTP_FROM_NAME fehlt.';
    }
    if ($sendmail_path === '') {
        $warnings[] = 'sendmail_path ist leer.';
    }
    return array(
        'ok' => $valid_email && $from_name !== '' && $mail_exists,
        'from' => $valid_email ? $from_email : '',
        'sendmail' => $sendmail_path !== '',
        'warnings' => $warnings,
    );
}

function master_admin_ads_status($cmp_enabled, $ads_testing) {
    $warnings = array();
    if ($cmp_enabled === null) {
        $warnings[] = 'GOOGLE_CMP_ENABLED ist nicht gesetzt.';
    } elseif ($cmp_enabled) {
        $warnings[] = 'GOOGLE_CMP_ENABLED ist aktiv. Live sollte der Wert false sein.';
    }
    if ($ads_testing === null) {
        $warnings[] = 'GOOGLE_ADS_TESTING ist nicht gesetzt.';
    } elseif ($ads_testing) {
        $warnings[] = 'GOOGLE_ADS_TESTING ist aktiv. Live sollte der Wert false sein.';
    }
    return array(
        'cmp_enabled' => $cmp_enabled,
        'ads_testing' => $ads_testing,
        'warnings' => $warnings,
        'ok' => count($warnings) === 0,
    );
}

function master_admin_rewrite_status($apache_modules, $probe) {
    if (is_array($apache_modules)) {
        return in_array('mod_rewrite', $apache_modules, true) ? 'aktiv' : 'inaktiv';
    }
    if ($probe === true) {
        return 'aktiv';
    }
    if ($probe === false) {
        return 'inaktiv';
    }
    return 'unbekannt';
}

function master_admin_clean_url_probe_target(array $server = array()) {
    unset($server);
    return canonical_base_url() . '/faq';
}

function master_admin_probe_clean_url($url) {
    $allowed = master_admin_clean_url_probe_target();
    if (!is_string($url) || $url !== $allowed) {
        return null;
    }
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'GET',
            'timeout' => 2,
            'ignore_errors' => true,
            'header' => "User-Agent: wichtel-status\r\n",
        ),
    ));
    $body = @file_get_contents($url, false, $context);
    if ($body === false || !isset($http_response_header) || !is_array($http_response_header) || !isset($http_response_header[0])) {
        return null;
    }
    if (preg_match('#\s(\d{3})(\s|$)#', $http_response_header[0], $match) !== 1) {
        return null;
    }
    $status = (int) $match[1];
    if ($status >= 200 && $status < 400) {
        return true;
    }
    if ($status === 404) {
        return false;
    }
    return null;
}

function master_admin_php_status() {
    return array(
        'version' => PHP_VERSION,
        'ok' => version_compare(PHP_VERSION, '7.4.0', '>='),
    );
}

function master_admin_login_document($csrf_html, $error) {
    $error_html = '';
    if (is_string($error) && $error !== '') {
        $error_html = '<p class="notification error" role="alert">' . htmlspecialchars($error, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    return '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<meta name="robots" content="noindex, nofollow">'
        . '<title>Master-Admin</title>'
        . '<link rel="stylesheet" href="/css/styles.css">'
        . '</head><body><div class="container"><h1>Master-Admin</h1>'
        . '<p>Melde dich mit dem Master-Token an. Der Token gehört ins Formular, nicht in die Adresszeile.</p>'
        . $error_html
        . '<form method="POST" action="index.php">'
        . $csrf_html
        . '<div class="form-group"><label for="master_token">Master-Token</label>'
        . '<input type="password" id="master_token" name="master_token" required autocomplete="current-password"></div>'
        . '<button type="submit" class="button primary">Anmelden</button>'
        . '</form></div></body></html>';
}
