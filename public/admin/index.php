<?php
// Master-Dashboard. Anmeldung über das Master-Token, danach Session.

require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/master_admin.php';

if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params(array(
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ));
    session_start();
}

if (!headers_sent()) {
    header('X-Robots-Tag: noindex, nofollow');
}

$provided_token = '';
if (isset($_POST['master_token']) && is_string($_POST['master_token'])) {
    $provided_token = $_POST['master_token'];
} elseif (isset($_GET['master_token']) && is_string($_GET['master_token'])) {
    $provided_token = $_GET['master_token'];
}

if (master_admin_token_matches($provided_token)) {
    if (empty($_SESSION['master_admin_authenticated'])) {
        session_regenerate_id(true);
    }
    $_SESSION['master_admin_authenticated'] = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['master_token'])) {
    $params = $_GET;
    unset($params['master_token'], $params['action'], $params['group_id']);
    $target = 'index.php';
    if (!empty($params)) {
        $target .= '?' . http_build_query($params);
    }
    header('Location: ' . $target);
    exit;
}

if (empty($_SESSION['master_admin_authenticated'])) {
    http_response_code(403);
    die('Zugriff verweigert. Ungültiges Master-Token.');
}

function admin_url($params = array()) {
    if (empty($params)) {
        return 'index.php';
    }
    return 'index.php?' . http_build_query($params);
}

function master_dashboard_redirect($params) {
    header('Location: ' . admin_url($params));
    exit;
}

function master_dashboard_date_label($known, $value, $with_time) {
    if (!$known) {
        return '–';
    }
    $formatted = master_admin_format_date($value, $with_time);
    return $formatted !== '' ? $formatted : '–';
}

function master_dashboard_sent_label($known, $value) {
    if (!$known) {
        return '–';
    }
    if ($value === null || $value === '') {
        return 'nicht gesendet';
    }
    $formatted = master_admin_format_date($value, true);
    return $formatted !== '' ? $formatted : '–';
}

function master_dashboard_description($value) {
    $text = trim((string) $value);
    if ($text === '') {
        return 'Keine Beschreibung';
    }
    return master_admin_truncate($text, 50);
}

$pdo = db_connect();
$schema = master_admin_schema_report($pdo);
$flags = $schema['flags'];
$filters = master_admin_filters_from_request($_GET);
$query_params = master_admin_query_params($filters);
$now = time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_ok = verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '');

    if (isset($_POST['logout'])) {
        if (!$csrf_ok) {
            $_SESSION['master_admin_flash'] = array('type' => 'error', 'text' => csrf_failure_message());
            master_dashboard_redirect($query_params);
        }
        $_SESSION = array();
        if (ini_get('session.use_cookies')) {
            $cookie_params = session_get_cookie_params();
            setcookie(session_name(), '', array(
                'expires' => time() - 42000,
                'path' => $cookie_params['path'],
                'domain' => $cookie_params['domain'],
                'secure' => $cookie_params['secure'],
                'httponly' => $cookie_params['httponly'],
                'samesite' => !empty($cookie_params['samesite']) ? $cookie_params['samesite'] : 'Lax',
            ));
        }
        session_destroy();
        http_response_code(200);
        echo '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><meta name="robots" content="noindex, nofollow"><title>Abgemeldet</title></head><body><p>Du wurdest abgemeldet. Für einen erneuten Zugriff den Master-Admin-Link verwenden.</p></body></html>';
        exit;
    }

    $bulk_result = master_admin_handle_bulk_request($pdo, $_POST, $_SESSION, $csrf_ok, $now);
    if (is_array($bulk_result)) {
        if ($bulk_result['status'] !== 'confirm') {
            $flash_type = ($bulk_result['status'] === 'done') ? 'success' : (($bulk_result['status'] === 'cancelled') ? 'success' : 'error');
            $_SESSION['master_admin_flash'] = array(
                'type' => $flash_type,
                'text' => $bulk_result['message'],
            );
        }
        master_dashboard_redirect($query_params);
    }

    if (isset($_POST['action'], $_POST['group_id'])) {
        if (!$csrf_ok) {
            $_SESSION['master_admin_flash'] = array('type' => 'error', 'text' => csrf_failure_message());
            master_dashboard_redirect($query_params);
        }
        $action = (string) $_POST['action'];
        $group_id = (int) $_POST['group_id'];
        $message = '';
        $error_text = '';
        if ($action === 'reset' && $group_id > 0) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('UPDATE `groups` SET `is_drawn` = 0 WHERE `id` = ?');
                $stmt->execute(array($group_id));
                $stmt = $pdo->prepare('UPDATE `participants` SET `assigned_to` = NULL WHERE `group_id` = ?');
                $stmt->execute(array($group_id));
                $pdo->commit();
                $message = 'Gruppe erfolgreich zurückgesetzt.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Master-Admin Reset: ' . $e->getMessage());
                $error_text = 'Fehler beim Zurücksetzen der Gruppe.';
            }
        } elseif ($action === 'delete' && $group_id > 0) {
            $pdo->beginTransaction();
            try {
                delete_group_with_members($pdo, $group_id);
                $pdo->commit();
                $message = 'Gruppe erfolgreich gelöscht.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Master-Admin Löschen: ' . $e->getMessage());
                $error_text = 'Fehler beim Löschen der Gruppe.';
            }
        }
        if ($message !== '' || $error_text !== '') {
            $_SESSION['master_admin_flash'] = array(
                'type' => $message !== '' ? 'success' : 'error',
                'text' => $message !== '' ? $message : $error_text,
            );
        }
        master_dashboard_redirect($query_params);
    }
}

$message = '';
$error = '';
if (!empty($_SESSION['master_admin_flash']) && is_array($_SESSION['master_admin_flash'])) {
    $flash = $_SESSION['master_admin_flash'];
    unset($_SESSION['master_admin_flash']);
    if (($flash['type'] ?? '') === 'success') {
        $message = isset($flash['text']) ? (string) $flash['text'] : '';
    } else {
        $error = isset($flash['text']) ? (string) $flash['text'] : '';
    }
}

if (isset($_GET['action'], $_GET['group_id']) && $message === '' && $error === '') {
    $error = 'Diese Aktion ist nur noch über die Schaltflächen auf dieser Seite möglich. Bitte lade die Seite neu.';
}

$pending_bulk = null;
if (!empty($_SESSION['master_admin_pending_bulk']) && is_array($_SESSION['master_admin_pending_bulk'])) {
    $pending_token = isset($_SESSION['master_admin_pending_bulk']['token']) ? $_SESSION['master_admin_pending_bulk']['token'] : '';
    $pending_valid = master_admin_bulk_confirmation_valid($_SESSION['master_admin_pending_bulk'], is_string($pending_token) ? $pending_token : '', $now);
    if ($pending_valid === null) {
        unset($_SESSION['master_admin_pending_bulk']);
        if ($error === '') {
            $error = 'Die Bestätigung ist abgelaufen. Es wurde nichts geändert.';
        }
    } else {
        $pending_bulk = $_SESSION['master_admin_pending_bulk'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['export'])) {
    $export = (string) $_GET['export'];
    if ($export === 'gruppen' || $export === 'statistik') {
        try {
            if ($export === 'gruppen') {
                $csv_rows = master_admin_fetch_all_groups($pdo, $filters, $flags, $now);
                $csv = master_admin_groups_csv($csv_rows, $flags, $now);
                $filename = 'wichtel-gruppen-' . date('Y-m-d') . '.csv';
            } else {
                $csv_kpis = master_admin_kpis($pdo, $now, $flags);
                $csv_season = master_admin_season_bounds($now);
                $csv_current = master_admin_window_stats($pdo, $csv_season['current_start'], $csv_season['current_end']);
                $csv_previous = master_admin_window_stats($pdo, $csv_season['previous_start'], $csv_season['previous_end']);
                $csv_daily = master_admin_daily_series($pdo, $csv_season['nov_start'], $csv_season['nov_end']);
                $csv = master_admin_statistics_csv($csv_kpis, $csv_season, $csv_current, $csv_previous, $csv_daily);
                $filename = 'wichtel-statistik-' . date('Y-m-d') . '.csv';
            }
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store');
            echo $csv;
            exit;
        } catch (Exception $e) {
            error_log('Master-Admin CSV: ' . $e->getMessage());
            $error = 'Der Export ist fehlgeschlagen.';
        }
    }
}

$per_page = ($filters['view'] === 'cards') ? 9 : 20;
$group_page = array('rows' => array(), 'total' => 0, 'page' => 1, 'pages' => 1, 'offset' => 0, 'per_page' => $per_page);
$list_error = false;
try {
    $group_page = master_admin_fetch_groups($pdo, $filters, $flags, $filters['page'], $per_page, $now);
} catch (Exception $e) {
    $list_error = true;
    error_log('Master-Admin Gruppenliste: ' . $e->getMessage());
    if ($error === '') {
        $error = 'Die Gruppenliste konnte nicht geladen werden. Bitte den Systemstatus prüfen.';
    }
}

$cleanup_total = 0;
if (!$list_error) {
    try {
        $cleanup_filters = $filters;
        $cleanup_filters['status'] = 'aufraeumen';
        $cleanup_probe = master_admin_fetch_groups($pdo, $cleanup_filters, $flags, 1, 1, $now);
        $cleanup_total = $cleanup_probe['total'];
    } catch (Exception $e) {
        error_log('Master-Admin Aufräumen: ' . $e->getMessage());
    }
}

$kpis = array(
    'total_groups' => 0,
    'active_groups' => 0,
    'archived_groups' => 0,
    'total_participants' => 0,
    'active_participants' => 0,
    'archived_participants' => 0,
    'avg_group_size' => 0,
    'completion_rate' => 0,
    'total_drawn' => 0,
    'avg_budget' => null,
    'email_rate' => 0,
    'total_email' => 0,
    'upcoming_events' => 0,
    'groups_this_month' => 0,
);
try {
    $kpis = master_admin_kpis($pdo, $now, $flags);
} catch (Exception $e) {
    error_log('Master-Admin Kennzahlen: ' . $e->getMessage());
}

$season = master_admin_season_bounds($now);
$season_current = master_admin_window_stats($pdo, $season['current_start'], $season['current_end']);
$season_previous = master_admin_window_stats($pdo, $season['previous_start'], $season['previous_end']);
$daily = master_admin_daily_series($pdo, $season['nov_start'], $season['nov_end']);
$trend = master_admin_monthly_trend($pdo, $now, 6);
$trend_max = max(1, max($trend));
$archive = master_admin_fetch_archive($pdo, $filters['archive_page'], 15);
$month_labels = master_admin_month_labels();

$created_known = !empty($flags['groups.created_at']);
$reveal_known = !empty($flags['groups.reveal_sent_at']);
$reminder_known = !empty($flags['groups.reminder_sent_at']);

$php_status = master_admin_php_status();
$mail_status = master_admin_mail_status(
    defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : '',
    defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : '',
    (string) ini_get('sendmail_path'),
    function_exists('mail')
);
$ads_status = master_admin_ads_status(
    defined('GOOGLE_CMP_ENABLED') ? (bool) GOOGLE_CMP_ENABLED : null,
    defined('GOOGLE_ADS_TESTING') ? (bool) GOOGLE_ADS_TESTING : null
);
$apache_modules = null;
if (function_exists('apache_get_modules')) {
    $loaded_modules = @apache_get_modules();
    if (is_array($loaded_modules)) {
        $apache_modules = $loaded_modules;
    }
}
$rewrite_probe = null;
if ($apache_modules === null && php_sapi_name() !== 'cli-server') {
    $rewrite_probe = master_admin_probe_clean_url(master_admin_clean_url_probe_target($_SERVER));
}
$rewrite_status = master_admin_rewrite_status($apache_modules, $rewrite_probe);

$pending_names = array();
if (is_array($pending_bulk)) {
    try {
        $pending_names = master_admin_group_names($pdo, $pending_bulk['ids']);
    } catch (Exception $e) {
        error_log('Master-Admin Bestätigung: ' . $e->getMessage());
    }
}

$export_params = $query_params;
unset($export_params['page'], $export_params['archive_page']);
$list_return = $query_params;
$list_return['page'] = $group_page['page'];
if ((int) $list_return['page'] <= 1) {
    unset($list_return['page']);
}

function master_dashboard_page_url($filters_params, $page_key, $page_number) {
    $params = $filters_params;
    if ($page_number <= 1) {
        unset($params[$page_key]);
    } else {
        $params[$page_key] = $page_number;
    }
    return admin_url($params);
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Master-Dashboard – Wichtlä.ch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon/favicon-16x16.png">
    <meta name="theme-color" content="#ffffff">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/styles.css">
    <link rel="stylesheet" href="/admin/admin-styles.css">
</head>
<body>
    <?php include __DIR__ . '/../../includes/templates/navigation.php'; ?>
    <div class="container app-page master-dashboard">
        <header class="app-hero">
            <p class="app-kicker">Verwaltung</p>
            <h1>Master-Dashboard</h1>
            <p class="app-hero-lead">Alle Gruppen, die Saisonstatistik und der Systemstatus.</p>
            <nav class="app-toc" aria-label="Bereiche dieser Seite">
                <a href="#gruppen">Gruppen</a>
                <a href="#kennzahlen">Kennzahlen</a>
                <a href="#saison">Saison</a>
                <a href="#systemstatus">Systemstatus</a>
                <a href="#archiv">Archiv</a>
            </nav>
            <form method="POST" action="<?php echo htmlspecialchars(admin_url($query_params), ENT_QUOTES, 'UTF-8'); ?>" class="master-logout">
                <?php echo csrf_input(); ?>
                <button type="submit" name="logout" value="1">Abmelden</button>
            </form>
        </header>

        <?php if ($message !== ''): ?>
            <div class="notification success" role="status" aria-live="polite"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
            <div class="notification error" role="alert" aria-live="assertive"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if (is_array($pending_bulk)): ?>
            <?php
            $pending_action = $pending_bulk['action'] === 'archive' ? 'archive' : 'delete';
            $pending_title = $pending_action === 'archive' ? 'Archivieren bestätigen' : 'Löschen bestätigen';
            $pending_text = $pending_action === 'archive'
                ? 'Die Statistik bleibt in group_statistics erhalten. Teilnehmer und Ausschlüsse werden gelöscht.'
                : 'Die Gruppen werden samt Teilnehmern und Ausschlüssen gelöscht. Es wird keine Statistik gespeichert.';
            $pending_button = $pending_action === 'archive' ? 'Ja, archivieren' : 'Ja, löschen';
            $pending_confirm = $pending_action === 'archive'
                ? 'Ausgewählte Gruppen jetzt archivieren? Das kann nicht rückgängig gemacht werden.'
                : 'Ausgewählte Gruppen jetzt dauerhaft löschen? Das kann nicht rückgängig gemacht werden.';
            ?>
            <section class="section-card master-confirm" aria-labelledby="confirm-heading">
                <div class="section-card-header">
                    <span class="section-icon" aria-hidden="true">⚠️</span>
                    <h2 id="confirm-heading"><?php echo htmlspecialchars($pending_title, ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>
                <p class="section-description"><?php echo htmlspecialchars(count($pending_bulk['ids']) . ' Gruppen sind ausgewählt. ' . $pending_text, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if (count($pending_names) > 0): ?>
                    <ul>
                        <?php foreach ($pending_names as $pending_group): ?>
                            <li><?php echo htmlspecialchars((string) $pending_group['name'], ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if (count($pending_names) < count($pending_bulk['ids'])): ?>
                    <p>Einige ausgewählte Gruppen gibt es nicht mehr. Die Aktion wird dann vollständig zurückgerollt.</p>
                <?php endif; ?>
                <div class="confirm-actions">
                    <form method="POST" action="<?php echo htmlspecialchars(admin_url($query_params), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="confirm_bulk" value="1">
                        <input type="hidden" name="confirm_token" value="<?php echo htmlspecialchars((string) $pending_bulk['token'], ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit" class="<?php echo $pending_action === 'archive' ? 'button secondary' : 'button error'; ?>" onclick="<?php echo html_onsubmit_confirm($pending_confirm); ?>"><?php echo htmlspecialchars($pending_button, ENT_QUOTES, 'UTF-8'); ?></button>
                    </form>
                    <form method="POST" action="<?php echo htmlspecialchars(admin_url($query_params), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo csrf_input(); ?>
                        <button type="submit" name="cancel_bulk" value="1" class="button secondary">Abbrechen</button>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="section-card" id="gruppen" aria-labelledby="groups-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">🎁</span>
                <h2 id="groups-heading">Gruppen</h2>
            </div>
            <p class="section-description">Suche, Filter und Sortierung laufen auf dem Server und gelten auch für die Seiten und den CSV-Export. Die Tabelle ist die Standardansicht.</p>

            <form method="GET" action="index.php" class="master-filters">
                <?php if ($filters['view'] !== 'table'): ?>
                    <input type="hidden" name="view" value="<?php echo htmlspecialchars($filters['view'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                <?php if ($filters['archive_page'] > 1): ?>
                    <input type="hidden" name="archive_page" value="<?php echo (int) $filters['archive_page']; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="group-search">Gruppenname</label>
                    <input id="group-search" type="search" name="q" value="<?php echo htmlspecialchars($filters['q'], ENT_QUOTES, 'UTF-8'); ?>" maxlength="100">
                </div>
                <div class="form-group">
                    <label for="group-status">Status</label>
                    <select id="group-status" name="status">
                        <?php foreach (master_admin_status_options() as $status_key => $status_label): ?>
                            <option value="<?php echo htmlspecialchars($status_key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filters['status'] === $status_key ? ' selected' : ''; ?>><?php echo htmlspecialchars($status_label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="group-sort">Sortierung</label>
                    <select id="group-sort" name="sort">
                        <?php foreach (master_admin_sort_options() as $sort_key => $sort_label): ?>
                            <option value="<?php echo htmlspecialchars($sort_key, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filters['sort'] === $sort_key ? ' selected' : ''; ?>><?php echo htmlspecialchars($sort_label, ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="group-dir">Richtung</label>
                    <select id="group-dir" name="dir">
                        <option value="desc"<?php echo $filters['dir'] === 'desc' ? ' selected' : ''; ?>>Absteigend</option>
                        <option value="asc"<?php echo $filters['dir'] === 'asc' ? ' selected' : ''; ?>>Aufsteigend</option>
                    </select>
                </div>
                <button type="submit" class="button primary">Anwenden</button>
            </form>

            <div class="cleanup-callout">
                <p>
                    <strong><?php echo (int) $cleanup_total; ?> leere oder verwaiste Gruppen.</strong>
                    Leer heisst 0 Teilnehmer. Verwaist heisst zusätzlich: Geschenkdatum vorbei und nie ausgelost.
                </p>
                <?php if ($filters['status'] !== 'aufraeumen'): ?>
                    <a class="button secondary" href="<?php echo htmlspecialchars(admin_url(master_admin_query_params($filters, array('status' => 'aufraeumen', 'page' => 1))), ENT_QUOTES, 'UTF-8'); ?>">Nur diese anzeigen</a>
                <?php else: ?>
                    <a class="button secondary" href="<?php echo htmlspecialchars(admin_url(master_admin_query_params($filters, array('status' => 'alle', 'page' => 1))), ENT_QUOTES, 'UTF-8'); ?>">Alle Gruppen</a>
                <?php endif; ?>
            </div>

            <?php if (!$reveal_known || !$reminder_known || !$created_known): ?>
                <p class="season-note">Fehlende Spalten werden als «–» angezeigt und stehen im Systemstatus mit dem passenden SQL.</p>
            <?php endif; ?>

            <div class="master-toolbar">
                <form id="master-bulk" method="POST" action="<?php echo htmlspecialchars(admin_url($query_params), ENT_QUOTES, 'UTF-8'); ?>" class="master-toolbar-actions">
                    <?php echo csrf_input(); ?>
                    <label class="master-check"><input type="checkbox" id="select-all-groups"> Alle auf dieser Seite</label>
                    <button type="submit" name="bulk_action" value="archive" class="button secondary" onclick="return masterBulkConfirm('Ausgewählte Gruppen archivieren? Die Statistik bleibt erhalten, Teilnehmer und Ausschlüsse werden gelöscht.');">Archivieren</button>
                    <button type="submit" name="bulk_action" value="delete" class="button error" onclick="return masterBulkConfirm('Ausgewählte Gruppen dauerhaft löschen? Es wird keine Statistik gespeichert.');">Löschen</button>
                </form>
                <div class="view-toggle" role="group" aria-label="Ansicht">
                    <a href="<?php echo htmlspecialchars(admin_url(master_admin_query_params($filters, array('view' => 'table', 'page' => 1))), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filters['view'] === 'table' ? ' class="is-active" aria-current="true"' : ''; ?>>Tabelle</a>
                    <a href="<?php echo htmlspecialchars(admin_url(master_admin_query_params($filters, array('view' => 'cards', 'page' => 1))), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $filters['view'] === 'cards' ? ' class="is-active" aria-current="true"' : ''; ?>>Karten</a>
                </div>
                <div class="export-links">
                    <a href="<?php echo htmlspecialchars(admin_url(array_merge($export_params, array('export' => 'gruppen'))), ENT_QUOTES, 'UTF-8'); ?>">CSV Gruppen</a>
                    <a href="<?php echo htmlspecialchars(admin_url(array_merge($export_params, array('export' => 'statistik'))), ENT_QUOTES, 'UTF-8'); ?>">CSV Statistik</a>
                </div>
            </div>

            <?php if ($group_page['total'] > 0): ?>
                <p class="pagination-info">Zeige <?php echo (int) ($group_page['offset'] + 1); ?>–<?php echo (int) min($group_page['offset'] + $group_page['per_page'], $group_page['total']); ?> von <?php echo (int) $group_page['total']; ?> Gruppen</p>
                <?php if ($filters['view'] === 'cards'): ?>
                    <div class="master-cards">
                        <?php foreach ($group_page['rows'] as $group): ?>
                            <?php $warnings = master_admin_group_warnings($group, $now, $flags); ?>
                            <article class="master-card">
                                <div class="master-card-head">
                                    <h3><?php echo htmlspecialchars((string) $group['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <label class="master-check"><input class="group-select" type="checkbox" name="group_ids[]" value="<?php echo (int) $group['id']; ?>" form="master-bulk"> <span class="cell-muted">wählen</span></label>
                                </div>
                                <span class="status-badge <?php echo ((int) $group['is_drawn'] === 1) ? 'drawn' : 'open'; ?>"><?php echo ((int) $group['is_drawn'] === 1) ? 'Ausgelost' : 'Offen'; ?></span>
                                <div class="master-meta">
                                    <p><span>Erstellt</span><?php echo htmlspecialchars(master_dashboard_date_label($created_known, isset($group['created_at']) ? $group['created_at'] : null, true), ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p><span>Geschenkdatum</span><?php echo htmlspecialchars(master_admin_format_date(isset($group['gift_exchange_date']) ? $group['gift_exchange_date'] : '', false) ?: '–', ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p><span>Teilnehmer</span><?php echo (int) $group['participant_count']; ?></p>
                                    <p><span>Mit E-Mail</span><?php echo (int) $group['participants_with_email']; ?></p>
                                    <p><span>Ausschlüsse</span><?php echo (int) $group['exclusion_count']; ?></p>
                                    <p><span>Budget</span><?php echo (isset($group['budget']) && $group['budget'] !== null && $group['budget'] !== '') ? htmlspecialchars(number_format((float) $group['budget'], 2) . ' CHF', ENT_QUOTES, 'UTF-8') : '–'; ?></p>
                                    <p><span>Auflösung</span><?php echo htmlspecialchars(master_dashboard_sent_label($reveal_known, isset($group['reveal_sent_at']) ? $group['reveal_sent_at'] : null), ENT_QUOTES, 'UTF-8'); ?></p>
                                    <p><span>Erinnerung</span><?php echo htmlspecialchars(master_dashboard_sent_label($reminder_known, isset($group['reminder_sent_at']) ? $group['reminder_sent_at'] : null), ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                                <p><?php echo master_dashboard_description(isset($group['description']) ? $group['description'] : ''); ?></p>
                                <?php if (count($warnings) > 0): ?>
                                    <p><?php foreach ($warnings as $warning): ?><span class="warning-badge"><?php echo htmlspecialchars($warning, ENT_QUOTES, 'UTF-8'); ?></span><?php endforeach; ?></p>
                                <?php endif; ?>
                                <div class="card-actions">
                                    <a class="button primary" href="<?php echo htmlspecialchars(master_admin_manage_url($group['admin_token']), ENT_QUOTES, 'UTF-8'); ?>"><img src="/images/icon-admin.svg" alt="" width="16" height="16"> Verwalten</a>
                                    <form method="POST" class="inline-action-form" action="<?php echo htmlspecialchars(admin_url($query_params), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="<?php echo html_onsubmit_confirm('Gruppe «' . (string) $group['name'] . '» zurücksetzen?'); ?>">
                                        <?php echo csrf_input(); ?>
                                        <input type="hidden" name="action" value="reset">
                                        <input type="hidden" name="group_id" value="<?php echo (int) $group['id']; ?>">
                                        <button type="submit" class="button secondary"><img src="/images/icon-reset.svg" alt="" width="16" height="16"> Zurücksetzen</button>
                                    </form>
                                    <form method="POST" class="inline-action-form" action="<?php echo htmlspecialchars(admin_url($query_params), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="<?php echo html_onsubmit_confirm('Gruppe «' . (string) $group['name'] . '» dauerhaft löschen? Das kann nicht rückgängig gemacht werden.'); ?>">
                                        <?php echo csrf_input(); ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="group_id" value="<?php echo (int) $group['id']; ?>">
                                        <button type="submit" class="button error"><img src="/images/icon-delete.svg" alt="" width="16" height="16"> Löschen</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="table-scroll">
                        <table class="master-table">
                            <thead>
                                <tr>
                                    <th>Wahl</th>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th>Erstellt</th>
                                    <th>Geschenkdatum</th>
                                    <th>Teilnehmer</th>
                                    <th>E-Mails</th>
                                    <th>Ausschlüsse</th>
                                    <th>Auflösung</th>
                                    <th>Erinnerung</th>
                                    <th>Hinweise</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($group_page['rows'] as $group): ?>
                                    <?php $warnings = master_admin_group_warnings($group, $now, $flags); ?>
                                    <tr>
                                        <td><input class="group-select" type="checkbox" name="group_ids[]" value="<?php echo (int) $group['id']; ?>" form="master-bulk" aria-label="<?php echo htmlspecialchars('Gruppe ' . (string) $group['name'] . ' auswählen', ENT_QUOTES, 'UTF-8'); ?>"></td>
                                        <td class="group-name-cell"><?php echo htmlspecialchars((string) $group['name'], ENT_QUOTES, 'UTF-8'); ?><div class="cell-muted"><?php echo master_dashboard_description(isset($group['description']) ? $group['description'] : ''); ?></div></td>
                                        <td><span class="status-badge <?php echo ((int) $group['is_drawn'] === 1) ? 'drawn' : 'open'; ?>"><?php echo ((int) $group['is_drawn'] === 1) ? 'Ausgelost' : 'Offen'; ?></span></td>
                                        <td><?php echo htmlspecialchars(master_dashboard_date_label($created_known, isset($group['created_at']) ? $group['created_at'] : null, true), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(master_admin_format_date(isset($group['gift_exchange_date']) ? $group['gift_exchange_date'] : '', false) ?: '–', ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo (int) $group['participant_count']; ?></td>
                                        <td><?php echo (int) $group['participants_with_email']; ?></td>
                                        <td><?php echo (int) $group['exclusion_count']; ?></td>
                                        <td><?php echo htmlspecialchars(master_dashboard_sent_label($reveal_known, isset($group['reveal_sent_at']) ? $group['reveal_sent_at'] : null), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars(master_dashboard_sent_label($reminder_known, isset($group['reminder_sent_at']) ? $group['reminder_sent_at'] : null), ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php if (count($warnings) === 0): ?><span class="cell-muted">–</span><?php else: foreach ($warnings as $warning): ?><span class="warning-badge"><?php echo htmlspecialchars($warning, ENT_QUOTES, 'UTF-8'); ?></span><?php endforeach; endif; ?></td>
                                        <td>
                                            <div class="row-actions">
                                                <a class="button primary" href="<?php echo htmlspecialchars(master_admin_manage_url($group['admin_token']), ENT_QUOTES, 'UTF-8'); ?>"><img src="/images/icon-admin.svg" alt="" width="16" height="16"> Verwalten</a>
                                                <form method="POST" class="inline-action-form" action="<?php echo htmlspecialchars(admin_url($query_params), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="<?php echo html_onsubmit_confirm('Gruppe «' . (string) $group['name'] . '» zurücksetzen?'); ?>">
                                                    <?php echo csrf_input(); ?>
                                                    <input type="hidden" name="action" value="reset">
                                                    <input type="hidden" name="group_id" value="<?php echo (int) $group['id']; ?>">
                                                    <button type="submit" class="button secondary"><img src="/images/icon-reset.svg" alt="" width="16" height="16"> Zurücksetzen</button>
                                                </form>
                                                <form method="POST" class="inline-action-form" action="<?php echo htmlspecialchars(admin_url($query_params), ENT_QUOTES, 'UTF-8'); ?>" onsubmit="<?php echo html_onsubmit_confirm('Gruppe «' . (string) $group['name'] . '» dauerhaft löschen? Das kann nicht rückgängig gemacht werden.'); ?>">
                                                    <?php echo csrf_input(); ?>
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="group_id" value="<?php echo (int) $group['id']; ?>">
                                                    <button type="submit" class="button error"><img src="/images/icon-delete.svg" alt="" width="16" height="16"> Löschen</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <?php if ($group_page['pages'] > 1): ?>
                    <nav class="pagination" aria-label="Seiten der Gruppen">
                        <?php if ($group_page['page'] > 1): ?>
                            <a href="<?php echo htmlspecialchars(master_dashboard_page_url($list_return, 'page', $group_page['page'] - 1), ENT_QUOTES, 'UTF-8'); ?>">Zurück</a>
                        <?php else: ?>
                            <span class="is-disabled">Zurück</span>
                        <?php endif; ?>
                        <?php for ($i = max(1, $group_page['page'] - 2); $i <= min($group_page['pages'], $group_page['page'] + 2); $i++): ?>
                            <a href="<?php echo htmlspecialchars(master_dashboard_page_url($list_return, 'page', $i), ENT_QUOTES, 'UTF-8'); ?>"<?php echo $i === (int) $group_page['page'] ? ' class="is-active" aria-current="page"' : ''; ?>><?php echo $i; ?></a>
                        <?php endfor; ?>
                        <?php if ($group_page['page'] < $group_page['pages']): ?>
                            <a href="<?php echo htmlspecialchars(master_dashboard_page_url($list_return, 'page', $group_page['page'] + 1), ENT_QUOTES, 'UTF-8'); ?>">Weiter</a>
                        <?php else: ?>
                            <span class="is-disabled">Weiter</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Keine Gruppen für diese Auswahl</h3>
                    <p class="section-description">Passe Suche oder Filter an.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="section-card" id="kennzahlen" aria-labelledby="kpi-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">📊</span>
                <h2 id="kpi-heading">Kennzahlen</h2>
            </div>
            <div class="status-grid master-kpis">
                <div class="status-tile"><span class="status-tile-label">Gesamt Gruppen</span><span class="status-tile-value"><?php echo (int) $kpis['total_groups']; ?></span><span class="status-tile-hint"><?php echo (int) $kpis['active_groups']; ?> aktiv / <?php echo (int) $kpis['archived_groups']; ?> archiviert</span></div>
                <div class="status-tile"><span class="status-tile-label">Teilnehmer</span><span class="status-tile-value"><?php echo (int) $kpis['total_participants']; ?></span><span class="status-tile-hint"><?php echo (int) $kpis['active_participants']; ?> aktiv / <?php echo (int) $kpis['archived_participants']; ?> archiviert</span></div>
                <div class="status-tile"><span class="status-tile-label">Gruppengrösse</span><span class="status-tile-value"><?php echo htmlspecialchars((string) $kpis['avg_group_size'], ENT_QUOTES, 'UTF-8'); ?></span><span class="status-tile-hint">Teilnehmer pro Gruppe</span></div>
                <div class="status-tile"><span class="status-tile-label">Abschlussrate</span><span class="status-tile-value"><?php echo (int) $kpis['completion_rate']; ?>%</span><span class="status-tile-hint"><?php echo (int) $kpis['total_drawn']; ?> von <?php echo (int) $kpis['total_groups']; ?> ausgelost</span></div>
                <div class="status-tile"><span class="status-tile-label">Budget</span><span class="status-tile-value"><?php echo $kpis['avg_budget'] !== null ? htmlspecialchars(number_format((float) $kpis['avg_budget'], 0), ENT_QUOTES, 'UTF-8') : '–'; ?></span><span class="status-tile-hint"><?php echo $kpis['avg_budget'] !== null ? 'CHF, Durchschnitt' : 'Kein Budget gesetzt'; ?></span></div>
                <div class="status-tile"><span class="status-tile-label">E-Mail-Rate</span><span class="status-tile-value"><?php echo (int) $kpis['email_rate']; ?>%</span><span class="status-tile-hint"><?php echo (int) $kpis['total_email']; ?> von <?php echo (int) $kpis['total_participants']; ?> mit E-Mail</span></div>
                <div class="status-tile"><span class="status-tile-label">Bevorstehende Events</span><span class="status-tile-value"><?php echo (int) $kpis['upcoming_events']; ?></span><span class="status-tile-hint">Geschenkdatum ab heute</span></div>
                <div class="status-tile"><span class="status-tile-label">Diesen Monat</span><span class="status-tile-value"><?php echo (int) $kpis['groups_this_month']; ?></span><span class="status-tile-hint"><?php echo htmlspecialchars($month_labels[date('m', $now)] . ' ' . date('Y', $now), ENT_QUOTES, 'UTF-8'); ?></span></div>
            </div>
            <?php if (array_sum($trend) > 0): ?>
                <h3>Gruppen pro Monat</h3>
                <div class="trend-chart">
                    <?php foreach ($trend as $month => $count): ?>
                        <?php
                        $parts = explode('-', $month);
                        $label = (isset($month_labels[$parts[1]]) ? $month_labels[$parts[1]] : $parts[1]) . ' ' . $parts[0];
                        $width = (int) round(((int) $count / $trend_max) * 100);
                        ?>
                        <div class="trend-row">
                            <span class="trend-label"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                            <div class="trend-track"><div class="trend-bar" style="width: <?php echo $width; ?>%;"></div></div>
                            <span class="trend-value"><?php echo (int) $count; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="section-card" id="saison" aria-labelledby="season-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">📅</span>
                <h2 id="season-heading">Saisonvergleich</h2>
            </div>
            <p class="section-description"><?php echo htmlspecialchars($season['label'], ENT_QUOTES, 'UTF-8'); ?>. Gezählt wird nach Erstelldatum. Teilnehmer im Archiv haben kein eigenes Tagesdatum und stehen separat.</p>
            <?php if (!$season['started']): ?>
                <p class="season-note">Die neue Saison beginnt am 1. September.</p>
            <?php endif; ?>
            <div class="table-scroll">
                <table class="master-table season-table">
                    <thead>
                        <tr>
                            <th>Kennzahl</th>
                            <th><?php echo htmlspecialchars($season['short_label'], ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars($season['previous_short_label'], ENT_QUOTES, 'UTF-8'); ?></th>
                            <th>Differenz</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $season_rows = array(
                            array('Gruppen', $season_current['groups'], $season_previous['groups']),
                            array('davon ausgelost', $season_current['drawn'], $season_previous['drawn']),
                            array('Teilnehmer, noch aktiv', $season_current['participants'], $season_previous['participants']),
                            array('Teilnehmer archivierter Gruppen', $season_current['archived_participants'], $season_previous['archived_participants']),
                        );
                        foreach ($season_rows as $season_row):
                            $diff = (int) $season_row[1] - (int) $season_row[2];
                            $diff_label = ($diff > 0 ? '+' : '') . $diff;
                            $diff_class = $diff > 0 ? 'diff-up' : ($diff < 0 ? 'diff-down' : '');
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($season_row[0], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int) $season_row[1]; ?></td>
                                <td><?php echo (int) $season_row[2]; ?></td>
                                <td class="<?php echo $diff_class; ?>"><?php echo htmlspecialchars($diff_label, ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <h3>November und Dezember <?php echo htmlspecialchars($season['short_label'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <figure class="master-chart">
                <?php echo master_admin_chart_svg($daily); ?>
                <figcaption>Gruppen (rot) und noch vorhandene Teilnehmer (petrol) pro Tag. Archivierte Gruppen zählen bei den Gruppen mit, sobald ihr Erstelldatum in diesen Zeitraum fällt.</figcaption>
            </figure>
            <details class="chart-data">
                <summary>Tageswerte</summary>
                <div class="table-scroll">
                    <table class="master-table season-table">
                        <thead><tr><th>Datum</th><th>Gruppen</th><th>Teilnehmer</th></tr></thead>
                        <tbody>
                            <?php foreach ($daily as $point): ?>
                                <?php if ((int) $point['groups'] === 0 && (int) $point['participants'] === 0) { continue; } ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(master_admin_format_date($point['date'], false), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int) $point['groups']; ?></td>
                                    <td><?php echo (int) $point['participants']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </details>
        </section>

        <section class="section-card" id="systemstatus" aria-labelledby="status-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">🛠️</span>
                <h2 id="status-heading">Systemstatus</h2>
            </div>
            <div class="status-line">
                <span class="system-pill<?php echo $php_status['ok'] ? '' : ' is-warn'; ?>">PHP <?php echo htmlspecialchars($php_status['version'], ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="system-pill<?php echo $mail_status['ok'] ? '' : ' is-warn'; ?>">E-Mail <?php echo $mail_status['ok'] ? 'konfiguriert' : 'nicht konfiguriert'; ?></span>
                <span class="system-pill<?php echo $rewrite_status === 'aktiv' ? '' : ($rewrite_status === 'inaktiv' ? ' is-warn' : ' is-unknown'); ?>">Clean-URLs <?php echo htmlspecialchars($rewrite_status, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="system-pill<?php echo ((int) $schema['missing_count'] === 0) ? '' : ' is-warn'; ?>">Spalten <?php echo ((int) $schema['missing_count'] === 0) ? 'vollständig' : ((int) $schema['missing_count'] . ' fehlen'); ?></span>
                <span class="system-pill<?php echo $ads_status['ok'] ? '' : ' is-warn'; ?>">Anzeigen-Flags <?php echo $ads_status['ok'] ? 'unauffällig' : 'prüfen'; ?></span>
            </div>
            <div class="status-list">
                <p>Versand über PHP <code>mail()</code><?php if ($mail_status['from'] !== ''): ?> von <?php echo htmlspecialchars($mail_status['from'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>. Sendmail-Pfad <?php echo $mail_status['sendmail'] ? 'gesetzt' : 'leer'; ?>.</p>
                <?php foreach ($mail_status['warnings'] as $mail_warning): ?>
                    <p class="notification warning" role="status"><?php echo htmlspecialchars($mail_warning, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endforeach; ?>
                <p>GOOGLE_CMP_ENABLED: <?php echo $ads_status['cmp_enabled'] === null ? 'nicht gesetzt' : ($ads_status['cmp_enabled'] ? 'true' : 'false'); ?>. GOOGLE_ADS_TESTING: <?php echo $ads_status['ads_testing'] === null ? 'nicht gesetzt' : ($ads_status['ads_testing'] ? 'true' : 'false'); ?>.</p>
                <?php foreach ($ads_status['warnings'] as $ads_warning): ?>
                    <p class="notification warning" role="status"><?php echo htmlspecialchars($ads_warning, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endforeach; ?>
                <?php if (!$php_status['ok']): ?>
                    <p class="notification warning" role="status">PHP 7.4 oder neuer wird erwartet.</p>
                <?php endif; ?>
            </div>
            <?php if ((int) $schema['missing_count'] > 0): ?>
                <h3>Fehlende Spalten</h3>
                <p class="section-description">Einfaches SQL für phpMyAdmin. Zuerst die richtige Datenbank wählen, dann die Zeile ausführen.</p>
                <?php foreach ($schema['items'] as $item): ?>
                    <?php if (!empty($item['ok'])) { continue; } ?>
                    <div class="column-check is-missing">
                        <strong><?php echo htmlspecialchars($item['table'] . '.' . $item['column'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span>fehlt</span>
                    </div>
                    <?php if ($item['note'] !== ''): ?>
                        <p><?php echo htmlspecialchars($item['note'], ENT_QUOTES, 'UTF-8'); ?> Siehe <code>database/setup.sql</code>.</p>
                    <?php endif; ?>
                    <?php if ($item['sql'] !== ''): ?>
                        <pre class="sql-snippet"><?php echo htmlspecialchars($item['sql'], ENT_QUOTES, 'UTF-8'); ?></pre>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            <details class="column-details">
                <summary>Alle geprüften Spalten</summary>
                <?php foreach ($schema['items'] as $item): ?>
                    <div class="column-check<?php echo empty($item['ok']) ? ' is-missing' : ''; ?>">
                        <span><?php echo htmlspecialchars($item['table'] . '.' . $item['column'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <strong><?php echo empty($item['ok']) ? 'fehlt' : 'vorhanden'; ?></strong>
                    </div>
                <?php endforeach; ?>
            </details>
        </section>

        <section class="section-card" id="archiv" aria-labelledby="archive-heading">
            <div class="section-card-header">
                <span class="section-icon" aria-hidden="true">🗄️</span>
                <h2 id="archive-heading">Archivierte Gruppen</h2>
            </div>
            <?php if (!empty($archive['error'])): ?>
                <p>Das Archiv konnte nicht gelesen werden.</p>
            <?php elseif ($archive['total'] > 0): ?>
                <p class="pagination-info">Zeige <?php echo (int) ($archive['offset'] + 1); ?>–<?php echo (int) min($archive['offset'] + 15, $archive['total']); ?> von <?php echo (int) $archive['total']; ?> archivierten Gruppen</p>
                <div class="table-scroll">
                    <table class="master-table archive-table">
                        <thead>
                            <tr>
                                <th>Gruppenname</th>
                                <th>Eventdatum</th>
                                <th>Teilnehmer</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Archiviert am</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archive['rows'] as $stat): ?>
                                <tr>
                                    <td><?php echo !empty($stat['group_name']) ? htmlspecialchars((string) $stat['group_name'], ENT_QUOTES, 'UTF-8') : 'Unbekannt'; ?></td>
                                    <td><?php echo htmlspecialchars(master_admin_format_date(isset($stat['gift_exchange_date']) ? $stat['gift_exchange_date'] : '', false) ?: '–', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int) $stat['participant_count']; ?> <span class="cell-muted">(<?php echo (int) $stat['participant_with_email_count']; ?> mit E-Mail)</span></td>
                                    <td><?php echo (isset($stat['budget']) && $stat['budget'] !== null && $stat['budget'] !== '') ? htmlspecialchars(number_format((float) $stat['budget'], 2) . ' CHF', ENT_QUOTES, 'UTF-8') : '–'; ?></td>
                                    <td><span class="status-badge <?php echo ((int) $stat['is_drawn'] === 1) ? 'drawn' : 'open'; ?>"><?php echo ((int) $stat['is_drawn'] === 1) ? 'Ausgelost' : 'Nicht beendet'; ?></span></td>
                                    <td><?php echo htmlspecialchars(master_admin_format_date(isset($stat['archived_at']) ? $stat['archived_at'] : '', true) ?: '–', ENT_QUOTES, 'UTF-8'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if ($archive['pages'] > 1): ?>
                    <nav class="pagination" aria-label="Seiten des Archivs">
                        <?php if ($archive['page'] > 1): ?>
                            <a href="<?php echo htmlspecialchars(master_dashboard_page_url($query_params, 'archive_page', $archive['page'] - 1), ENT_QUOTES, 'UTF-8'); ?>">Zurück</a>
                        <?php endif; ?>
                        <span><?php echo (int) $archive['page']; ?> / <?php echo (int) $archive['pages']; ?></span>
                        <?php if ($archive['page'] < $archive['pages']): ?>
                            <a href="<?php echo htmlspecialchars(master_dashboard_page_url($query_params, 'archive_page', $archive['page'] + 1), ENT_QUOTES, 'UTF-8'); ?>">Weiter</a>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Keine archivierten Gruppen</h3>
                    <p class="section-description">Archivierte Statistik erscheint hier, sobald Gruppen über das Aufräumen oder das Cleanup-Skript archiviert wurden.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <?php include __DIR__ . '/../../includes/templates/footer.php'; ?>
    <script>
    function masterBulkConfirm(message) {
        var boxes = document.querySelectorAll('input.group-select:checked');
        if (!boxes.length) {
            window.alert('Bitte mindestens eine Gruppe auswählen.');
            return false;
        }
        return window.confirm(message);
    }
    document.addEventListener('DOMContentLoaded', function () {
        var all = document.getElementById('select-all-groups');
        if (!all) {
            return;
        }
        all.addEventListener('change', function () {
            var boxes = document.querySelectorAll('input.group-select');
            for (var i = 0; i < boxes.length; i++) {
                boxes[i].checked = all.checked;
            }
        });
    });
    </script>
</body>
</html>
