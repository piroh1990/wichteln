<?php
// /admin/index.php

require_once __DIR__ . '/../../includes/functions.php';

// Überprüfen, ob das master_token korrekt ist
$master_token = $_GET['master_token'] ?? '';

if ($master_token !== MASTER_ADMIN_TOKEN) {
    die('Zugriff verweigert. Ungültiges Master-Token.');
}

$pdo = db_connect();

// Helper: URL mit master_token und Parametern bauen
function admin_url($master_token, $params = []) {
    $params['master_token'] = $master_token;
    return 'index.php?' . http_build_query($params);
}

// Helper: Pagination rendern
function render_pagination($current_page, $total_pages, $param_name, $master_token, $other_params = []) {
    if ($total_pages <= 1) return;

    $max_visible = 7;
    echo '<nav class="pagination">';

    // Previous
    $prev_class = $current_page <= 1 ? ' disabled' : '';
    $prev_params = array_merge($other_params, [$param_name => $current_page - 1]);
    echo '<a href="' . ($current_page > 1 ? htmlspecialchars(admin_url($master_token, $prev_params)) : '#') . '" class="pagination-link' . $prev_class . '">&larr; Vorherige</a>';

    // Page numbers with ellipsis
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);

    // Always show first page
    if ($start > 1) {
        $p = array_merge($other_params, [$param_name => 1]);
        echo '<a href="' . htmlspecialchars(admin_url($master_token, $p)) . '" class="pagination-link">1</a>';
        if ($start > 2) echo '<span class="pagination-ellipsis">&hellip;</span>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $current_page ? ' active' : '';
        $p = array_merge($other_params, [$param_name => $i]);
        echo '<a href="' . htmlspecialchars(admin_url($master_token, $p)) . '" class="pagination-link' . $active . '">' . $i . '</a>';
    }

    // Always show last page
    if ($end < $total_pages) {
        if ($end < $total_pages - 1) echo '<span class="pagination-ellipsis">&hellip;</span>';
        $p = array_merge($other_params, [$param_name => $total_pages]);
        echo '<a href="' . htmlspecialchars(admin_url($master_token, $p)) . '" class="pagination-link">' . $total_pages . '</a>';
    }

    // Next
    $next_class = $current_page >= $total_pages ? ' disabled' : '';
    $next_params = array_merge($other_params, [$param_name => $current_page + 1]);
    echo '<a href="' . ($current_page < $total_pages ? htmlspecialchars(admin_url($master_token, $next_params)) : '#') . '" class="pagination-link' . $next_class . '">N&auml;chste &rarr;</a>';

    echo '</nav>';
}

// Pagination Parameter
$page = max(1, intval($_GET['page'] ?? 1));
$archive_page = max(1, intval($_GET['archive_page'] ?? 1));
$per_page = 12;
$archive_per_page = 15;

// Aktionen: Reset oder Löschen einer Gruppe über GET-Parameter
if (isset($_GET['action']) && isset($_GET['group_id'])) {
    $action = $_GET['action'];
    $group_id = intval($_GET['group_id']);

    if ($action === 'reset') {
        // Gruppe zurücksetzen: is_drawn auf 0 setzen und assigned_to in Teilnehmern leeren
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE `groups` SET `is_drawn` = 0 WHERE `id` = ?");
            $stmt->execute([$group_id]);

            $stmt = $pdo->prepare("UPDATE `participants` SET `assigned_to` = NULL WHERE `group_id` = ?");
            $stmt->execute([$group_id]);

            $pdo->commit();
            $message = "Gruppe erfolgreich zurückgesetzt.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Fehler beim Zurücksetzen der Gruppe: " . $e->getMessage();
        }
    }

    if ($action === 'delete') {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("UPDATE `participants` SET `assigned_to` = NULL WHERE `group_id` = ?");
            $stmt->execute([$group_id]);

            $stmt = $pdo->prepare("DELETE FROM `participants` WHERE `group_id` = ?");
            $stmt->execute([$group_id]);

            $stmt = $pdo->prepare("DELETE FROM `groups` WHERE `id` = ?");
            $stmt->execute([$group_id]);

            $pdo->commit();
            $message = "Gruppe erfolgreich gelöscht.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Fehler beim Löschen der Gruppe: " . $e->getMessage();
        }
    }
}

// ============================================================
// Enhanced Statistics (aggregate queries, not per-page)
// ============================================================

// Active data aggregate
$stmt = $pdo->query("
    SELECT
        COUNT(DISTINCT g.id) as active_groups,
        COALESCE(SUM(p_counts.cnt), 0) as active_participants,
        COALESCE(SUM(p_counts.email_cnt), 0) as active_email_participants,
        SUM(CASE WHEN g.is_drawn = 1 THEN 1 ELSE 0 END) as active_drawn,
        AVG(CASE WHEN g.budget IS NOT NULL THEN g.budget END) as active_avg_budget,
        SUM(CASE WHEN g.gift_exchange_date >= CURDATE() THEN 1 ELSE 0 END) as upcoming_events,
        SUM(CASE WHEN g.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01') THEN 1 ELSE 0 END) as groups_this_month
    FROM `groups` g
    LEFT JOIN (
        SELECT group_id, COUNT(*) as cnt,
            SUM(CASE WHEN email IS NOT NULL AND email != '' THEN 1 ELSE 0 END) as email_cnt
        FROM participants GROUP BY group_id
    ) p_counts ON g.id = p_counts.group_id
");
$active_stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Archived data aggregate
$stmt = $pdo->query("
    SELECT
        COUNT(*) as archived_groups,
        COALESCE(SUM(participant_count), 0) as archived_participants,
        COALESCE(SUM(participant_with_email_count), 0) as archived_email_participants,
        SUM(CASE WHEN is_drawn = 1 THEN 1 ELSE 0 END) as archived_drawn,
        AVG(CASE WHEN budget IS NOT NULL THEN budget END) as archived_avg_budget
    FROM `group_statistics`
");
$archived_agg = $stmt->fetch(PDO::FETCH_ASSOC);

// Compute combined statistics
$total_groups = intval($active_stats['active_groups']) + intval($archived_agg['archived_groups']);
$total_participants = intval($active_stats['active_participants']) + intval($archived_agg['archived_participants']);
$total_email = intval($active_stats['active_email_participants']) + intval($archived_agg['archived_email_participants']);
$total_drawn = intval($active_stats['active_drawn']) + intval($archived_agg['archived_drawn']);
$avg_group_size = $total_groups > 0 ? round($total_participants / $total_groups, 1) : 0;
$completion_rate = $total_groups > 0 ? round($total_drawn / $total_groups * 100) : 0;
$email_rate = $total_participants > 0 ? round($total_email / $total_participants * 100) : 0;

// Average budget (weighted)
$budget_values = [];
if ($active_stats['active_avg_budget'] !== null) $budget_values[] = floatval($active_stats['active_avg_budget']);
if ($archived_agg['archived_avg_budget'] !== null) $budget_values[] = floatval($archived_agg['archived_avg_budget']);
$avg_budget = count($budget_values) > 0 ? array_sum($budget_values) / count($budget_values) : null;

$upcoming_events = intval($active_stats['upcoming_events']);
$groups_this_month = intval($active_stats['groups_this_month']);

// ============================================================
// Monthly Trend (last 6 months)
// ============================================================

$trend_data = [];
// Active groups by month
$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM `groups`
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $trend_data[$row['month']] = ($trend_data[$row['month']] ?? 0) + intval($row['count']);
}

// Archived groups by month (original creation date)
$stmt = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
    FROM `group_statistics`
    WHERE created_at IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $trend_data[$row['month']] = ($trend_data[$row['month']] ?? 0) + intval($row['count']);
}

// Fill in missing months
$trend_months = [];
for ($i = 5; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $trend_months[$m] = $trend_data[$m] ?? 0;
}
$trend_max = max(1, max($trend_months));

// ============================================================
// Paginated Active Groups
// ============================================================

$stmt = $pdo->query("SELECT COUNT(*) FROM `groups`");
$total_group_count = intval($stmt->fetchColumn());
$total_pages = max(1, ceil($total_group_count / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT
        g.*,
        COUNT(DISTINCT p.id) as participant_count,
        COUNT(DISTINCT CASE WHEN p.email IS NOT NULL AND p.email != '' THEN p.id END) as participants_with_email,
        COUNT(DISTINCT e.id) as exclusion_count
    FROM `groups` g
    LEFT JOIN `participants` p ON g.id = p.group_id
    LEFT JOIN `exclusions` e ON g.id = e.group_id
    GROUP BY g.id
    ORDER BY g.name ASC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// Paginated Archived Groups
// ============================================================

$stmt = $pdo->query("SELECT COUNT(*) FROM `group_statistics`");
$total_archive_count = intval($stmt->fetchColumn());
$total_archive_pages = max(1, ceil($total_archive_count / $archive_per_page));
$archive_page = min($archive_page, $total_archive_pages);
$archive_offset = ($archive_page - 1) * $archive_per_page;

$stmt = $pdo->prepare("SELECT * FROM `group_statistics` ORDER BY `archived_at` DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $archive_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $archive_offset, PDO::PARAM_INT);
$stmt->execute();
$archived_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// German month names
$month_names = [
    '01' => 'Jan', '02' => 'Feb', '03' => 'Mär', '04' => 'Apr',
    '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
    '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Dez'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel - Wichtel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="apple-touch-icon" sizes="57x57" href="https://xn--wichtl-gua.ch/images/favicon/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="https://xn--wichtl-gua.ch/images/favicon/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="https://xn--wichtl-gua.ch/images/favicon/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="https://xn--wichtl-gua.ch/images/favicon/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="https://xn--wichtl-gua.ch/images/favicon/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="https://xn--wichtl-gua.ch/images/favicon/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="https://xn--wichtl-gua.ch/images/favicon/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="https://xn--wichtl-gua.ch/images/favicon/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="https://xn--wichtl-gua.ch/images/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="192x192" href="https://xn--wichtl-gua.ch/images/favicon/android-icon-192x192.png">
    <link rel="icon" type="image/png" sizes="32x32" href="https://xn--wichtl-gua.ch/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="https://xn--wichtl-gua.ch/images/favicon/favicon-96x96.png">
    <link rel="icon" type="image/png" sizes="16x16" href="https://xn--wichtl-gua.ch/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="https://xn--wichtl-gua.ch/images/favicon/manifest.json">
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="msapplication-TileImage" content="https://xn--wichtl-gua.ch/images/favicon/ms-icon-144x144.png">
    <meta name="theme-color" content="#ffffff">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Admin CSS -->
    <link rel="stylesheet" href="admin-styles.css">
</head>
<body>
    <div class="admin-header">
        <img src="https://xn--wichtl-gua.ch/images/logo.png" alt="Wichtel Logo">
        <h1>Admin Control Panel</h1>
        <p>Gesamtübersicht aller Wichtel-Gruppen</p>
    </div>

    <div class="admin-container">
        <?php if (isset($message)): ?>
            <div class="notification success" role="status" aria-live="polite">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="notification error" role="alert" aria-live="assertive">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon primary" aria-hidden="true">🎁</div>
                    <span class="stat-card-label">Gesamt Gruppen</span>
                </div>
                <div class="stat-card-value"><?php echo $total_groups; ?></div>
                <div class="stat-card-footer">
                    <?php echo intval($active_stats['active_groups']); ?> Aktiv / <?php echo intval($archived_agg['archived_groups']); ?> Archiviert
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon success" aria-hidden="true">👥</div>
                    <span class="stat-card-label">Teilnehmer</span>
                </div>
                <div class="stat-card-value"><?php echo $total_participants; ?></div>
                <div class="stat-card-footer">
                    <?php echo intval($active_stats['active_participants']); ?> Aktiv / <?php echo intval($archived_agg['archived_participants']); ?> Archiviert
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon warning" aria-hidden="true">📊</div>
                    <span class="stat-card-label">Durchschn. Gruppengrösse</span>
                </div>
                <div class="stat-card-value"><?php echo $avg_group_size; ?></div>
                <div class="stat-card-footer">
                    Teilnehmer pro Gruppe
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon info" aria-hidden="true">✓</div>
                    <span class="stat-card-label">Abschlussrate</span>
                </div>
                <div class="stat-card-value"><?php echo $completion_rate; ?>%</div>
                <div class="stat-card-footer">
                    <?php echo $total_drawn; ?> von <?php echo $total_groups; ?> Gruppen ausgelost
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon primary" aria-hidden="true">💰</div>
                    <span class="stat-card-label">Durchschn. Budget</span>
                </div>
                <div class="stat-card-value"><?php echo $avg_budget !== null ? number_format($avg_budget, 0) : '–'; ?></div>
                <div class="stat-card-footer">
                    <?php echo $avg_budget !== null ? 'CHF pro Gruppe' : 'Kein Budget gesetzt'; ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon success" aria-hidden="true">✉️</div>
                    <span class="stat-card-label">E-Mail-Rate</span>
                </div>
                <div class="stat-card-value"><?php echo $email_rate; ?>%</div>
                <div class="stat-card-footer">
                    <?php echo $total_email; ?> von <?php echo $total_participants; ?> mit E-Mail
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon warning" aria-hidden="true">📅</div>
                    <span class="stat-card-label">Bevorstehende Events</span>
                </div>
                <div class="stat-card-value"><?php echo $upcoming_events; ?></div>
                <div class="stat-card-footer">
                    Gruppen mit kommendem Übergabedatum
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon info" aria-hidden="true">🆕</div>
                    <span class="stat-card-label">Gruppen diesen Monat</span>
                </div>
                <div class="stat-card-value"><?php echo $groups_this_month; ?></div>
                <div class="stat-card-footer">
                    Neu erstellt in <?php echo $month_names[date('m')] . ' ' . date('Y'); ?>
                </div>
            </div>
        </div>

        <!-- Monthly Trend -->
        <?php if (array_sum($trend_months) > 0): ?>
        <div class="trend-section">
            <div class="section-header">
                <h2 class="section-title">Gruppen pro Monat</h2>
            </div>
            <div class="trend-chart">
                <?php foreach ($trend_months as $month => $count):
                    $parts = explode('-', $month);
                    $label = $month_names[$parts[1]] . ' ' . $parts[0];
                    $width = round(($count / $trend_max) * 100);
                ?>
                    <div class="trend-bar-row">
                        <span class="trend-bar-label"><?php echo $label; ?></span>
                        <div class="trend-bar-container">
                            <div class="trend-bar" style="width: <?php echo $width; ?>%;"></div>
                            <span class="trend-bar-value"><?php echo $count; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Groups Section -->
        <div class="groups-section">
            <div class="section-header">
                <h2 class="section-title">Alle Gruppen</h2>
            </div>

            <?php if ($groups): ?>
                <div class="pagination-info">
                    Zeige <?php echo $offset + 1; ?>–<?php echo min($offset + $per_page, $total_group_count); ?> von <?php echo $total_group_count; ?> Gruppen
                </div>
                <div class="groups-grid">
                    <?php foreach ($groups as $group): ?>
                        <div class="group-card">
                            <div class="group-card-header">
                                <div>
                                    <h3 class="group-name"><?php echo htmlspecialchars($group['name'] ?? ''); ?></h3>
                                    <span class="group-status-badge <?php echo $group['is_drawn'] ? 'drawn' : 'not-drawn'; ?>">
                                        <?php echo $group['is_drawn'] ? 'Ausgelost' : 'Nicht ausgelost'; ?>
                                    </span>
                                </div>
                            </div>

                            <div class="group-stats">
                                <div class="group-stat">
                                    <span class="group-stat-icon" aria-hidden="true">👥</span>
                                    <div class="group-stat-content">
                                        <span class="group-stat-value"><?php echo $group['participant_count']; ?></span>
                                        <span class="group-stat-label">Teilnehmer</span>
                                    </div>
                                </div>
                                <div class="group-stat">
                                    <span class="group-stat-icon" aria-hidden="true">✉️</span>
                                    <div class="group-stat-content">
                                        <span class="group-stat-value"><?php echo $group['participants_with_email']; ?></span>
                                        <span class="group-stat-label">Mit E-Mail</span>
                                    </div>
                                </div>
                                <div class="group-stat">
                                    <span class="group-stat-icon" aria-hidden="true">🚫</span>
                                    <div class="group-stat-content">
                                        <span class="group-stat-value"><?php echo $group['exclusion_count']; ?></span>
                                        <span class="group-stat-label">Ausschlüsse</span>
                                    </div>
                                </div>
                            </div>

                            <div class="group-details">
                                <div class="group-detail-item">
                                    <span class="group-detail-label">💰 Budget</span>
                                    <span class="group-detail-value">
                                        <?php echo $group['budget'] !== null ? number_format($group['budget'], 2) . " CHF" : "Nicht festgelegt"; ?>
                                    </span>
                                </div>
                                <div class="group-detail-item">
                                    <span class="group-detail-label">📅 Geschenkübergabe</span>
                                    <span class="group-detail-value">
                                        <?php echo $group['gift_exchange_date'] ? date('d.m.Y', strtotime($group['gift_exchange_date'])) : "Nicht festgelegt"; ?>
                                    </span>
                                </div>
                                <div class="group-detail-item">
                                    <span class="group-detail-label">📝 Beschreibung</span>
                                    <span class="group-detail-value">
                                        <?php
                                        $desc = $group['description'] ?? 'Keine Beschreibung';
                                        echo strlen($desc) > 50 ? substr(htmlspecialchars($desc), 0, 50) . '...' : htmlspecialchars($desc);
                                        ?>
                                    </span>
                                </div>
                            </div>

                            <div class="group-actions">
                                <a href="https://xn--wichtl-gua.ch/admin.php?token=<?php echo urlencode($group['admin_token']); ?>"
                                   class="btn btn-primary">
                                    <img src="https://xn--wichtl-gua.ch/images/icon-admin.svg" alt="" aria-hidden="true" width="16" height="16">
                                    Verwalten
                                </a>

                                <a href="<?php echo htmlspecialchars(admin_url($master_token, ['action' => 'reset', 'group_id' => $group['id'], 'page' => $page, 'archive_page' => $archive_page])); ?>"
                                   class="btn btn-secondary"
                                   onclick="return confirm('Möchtest du die Gruppe &quot;<?php echo htmlspecialchars($group['name']); ?>&quot; wirklich zurücksetzen?');">
                                    <img src="https://xn--wichtl-gua.ch/images/icon-reset.svg" alt="" aria-hidden="true" width="16" height="16">
                                    Reset
                                </a>

                                <a href="<?php echo htmlspecialchars(admin_url($master_token, ['action' => 'delete', 'group_id' => $group['id'], 'page' => $page, 'archive_page' => $archive_page])); ?>"
                                   class="btn btn-danger"
                                   onclick="return confirm('⚠️ WARNUNG: Möchtest du die Gruppe &quot;<?php echo htmlspecialchars($group['name']); ?>&quot; wirklich PERMANENT löschen?\n\nDiese Aktion kann NICHT rückgängig gemacht werden!');">
                                    <img src="https://xn--wichtl-gua.ch/images/icon-delete.svg" alt="" aria-hidden="true" width="16" height="16">
                                    Löschen
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php render_pagination($page, $total_pages, 'page', $master_token, ['archive_page' => $archive_page]); ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon" aria-hidden="true">🎁</div>
                    <h3 class="empty-state-title">Keine Gruppen vorhanden</h3>
                    <p class="empty-state-text">Es wurden noch keine Wichtel-Gruppen erstellt.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Archived Groups Section -->
        <div class="groups-section" style="margin-top: 3rem;">
            <div class="section-header">
                <h2 class="section-title">Archivierte Gruppen (Statistik)</h2>
            </div>

            <?php if ($archived_stats): ?>
                <div class="pagination-info">
                    Zeige <?php echo $archive_offset + 1; ?>–<?php echo min($archive_offset + $archive_per_page, $total_archive_count); ?> von <?php echo $total_archive_count; ?> archivierten Gruppen
                </div>
                <div style="overflow-x: auto; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <table class="archive-table">
                        <thead>
                            <tr>
                                <th>Gruppenname</th>
                                <th>Event Datum</th>
                                <th>Teilnehmer</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Archiviert am</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archived_stats as $stat): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($stat['group_name'])): ?>
                                            <?php echo htmlspecialchars($stat['group_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Unbekannt</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $stat['gift_exchange_date'] ? date('d.m.Y', strtotime($stat['gift_exchange_date'])) : '<span class="text-muted">Unbekannt</span>'; ?>
                                    </td>
                                    <td>
                                        <?php echo $stat['participant_count']; ?>
                                        <span class="text-muted">(<?php echo $stat['participant_with_email_count']; ?> mit Mail)</span>
                                    </td>
                                    <td>
                                        <?php echo $stat['budget'] ? number_format($stat['budget'], 2) . ' CHF' : '–'; ?>
                                    </td>
                                    <td>
                                        <?php if ($stat['is_drawn']): ?>
                                            <span class="archive-status-badge drawn">Ausgelost</span>
                                        <?php else: ?>
                                            <span class="archive-status-badge not-drawn">Nicht beendet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted">
                                        <?php echo date('d.m.Y H:i', strtotime($stat['archived_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php render_pagination($archive_page, $total_archive_pages, 'archive_page', $master_token, ['page' => $page]); ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon" aria-hidden="true">📊</div>
                    <h3 class="empty-state-title">Keine archivierten Gruppen</h3>
                    <p class="empty-state-text">Keine archivierten Daten vorhanden.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
