<?php
// /admin/index.php

require_once __DIR__ . '/../../includes/functions.php';

// Überprüfen, ob das master_token korrekt ist
$master_token = $_GET['master_token'] ?? '';

if ($master_token !== MASTER_ADMIN_TOKEN) {
    die('Zugriff verweigert. Ungültiges Master-Token.');
}

$pdo = db_connect();

// Aktionen: Reset oder Löschen einer Gruppe über POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['group_id'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF-Token ungültig.');
    }

    $action = $_POST['action'];
    $group_id = intval($_POST['group_id']);

    if ($action === 'reset') {
        // Gruppe zurücksetzen: is_drawn auf 0 setzen und assigned_to in Teilnehmern leeren
        $pdo->beginTransaction();
        try {
            // Setze is_drawn auf 0
            $stmt = $pdo->prepare("UPDATE `groups` SET `is_drawn` = 0 WHERE `id` = ?");
            $stmt->execute([$group_id]);

            // Setze assigned_to auf NULL für alle Teilnehmer der Gruppe
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
        // Gruppe löschen: Setze assigned_to auf NULL für alle Teilnehmer der Gruppe
        $pdo->beginTransaction();
        try {
            // Setze assigned_to auf NULL für Teilnehmer der Gruppe
            $stmt = $pdo->prepare("UPDATE `participants` SET `assigned_to` = NULL WHERE `group_id` = ?");
            $stmt->execute([$group_id]);

            // Lösche Teilnehmer
            $stmt = $pdo->prepare("DELETE FROM `participants` WHERE `group_id` = ?");
            $stmt->execute([$group_id]);

            // Lösche Gruppe
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

// Alle Gruppen abrufen mit Teilnehmer- und Ausschluss-Statistiken
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
");
$stmt->execute();
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Archivierte Statistiken abrufen
$stmt = $pdo->prepare("SELECT * FROM `group_statistics` ORDER BY `archived_at` DESC");
$stmt->execute();
$archived_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Gesamtstatistiken berechnen
$total_groups = count($groups);
$total_participants = 0;
$total_drawn = 0;
$total_not_drawn = 0;

foreach ($groups as $group) {
    $total_participants += $group['participant_count'];
    if ($group['is_drawn']) {
        $total_drawn++;
    } else {
        $total_not_drawn++;
    }
}

// Archiv-Statistiken
$total_archived_groups = count($archived_stats);
$total_archived_participants = array_sum(array_column($archived_stats, 'participant_count'));
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
            <div class="notification success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="notification error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon primary">🎁</div>
                    <span class="stat-card-label">Gesamt Gruppen</span>
                </div>
                <div class="stat-card-value"><?php echo $total_groups + $total_archived_groups; ?></div>
                <div class="stat-card-footer">
                    <?php echo $total_groups; ?> Aktiv / <?php echo $total_archived_groups; ?> Archiviert
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon success">👥</div>
                    <span class="stat-card-label">Teilnehmer</span>
                </div>
                <div class="stat-card-value"><?php echo $total_participants + $total_archived_participants; ?></div>
                <div class="stat-card-footer">
                    <?php echo $total_participants; ?> Aktiv / <?php echo $total_archived_participants; ?> Archiviert
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon warning">✓</div>
                    <span class="stat-card-label">Ausgelost</span>
                </div>
                <div class="stat-card-value"><?php echo $total_drawn; ?></div>
                <div class="stat-card-footer">
                    <?php 
                    $percentage = $total_groups > 0 ? round(($total_drawn / $total_groups) * 100) : 0;
                    echo $percentage . '% aller Gruppen';
                    ?>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon info">⏳</div>
                    <span class="stat-card-label">Ausstehend</span>
                </div>
                <div class="stat-card-value"><?php echo $total_not_drawn; ?></div>
                <div class="stat-card-footer">
                    Warten auf Auslosung
                </div>
            </div>
        </div>

        <!-- Groups Section -->
        <div class="groups-section">
            <div class="section-header">
                <h2 class="section-title">Alle Gruppen</h2>
            </div>

            <?php if ($groups): ?>
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
                                    <span class="group-stat-icon">👥</span>
                                    <div class="group-stat-content">
                                        <span class="group-stat-value"><?php echo $group['participant_count']; ?></span>
                                        <span class="group-stat-label">Teilnehmer</span>
                                    </div>
                                </div>
                                <div class="group-stat">
                                    <span class="group-stat-icon">✉️</span>
                                    <div class="group-stat-content">
                                        <span class="group-stat-value"><?php echo $group['participants_with_email']; ?></span>
                                        <span class="group-stat-label">Mit E-Mail</span>
                                    </div>
                                </div>
                                <div class="group-stat">
                                    <span class="group-stat-icon">🚫</span>
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
                                    <img src="https://xn--wichtl-gua.ch/images/icon-admin.svg" alt="Verwalten" width="16" height="16">
                                    Verwalten
                                </a>

                                <form action="index.php?master_token=<?php echo urlencode($master_token); ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="reset">
                                    <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group['id']); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Möchtest du die Gruppe \"<?php echo htmlspecialchars($group['name']); ?>\" wirklich zurücksetzen?');">
                                        <img src="https://xn--wichtl-gua.ch/images/icon-reset.svg" alt="Reset" width="16" height="16">
                                        Reset
                                    </button>
                                </form>

                                <form action="index.php?master_token=<?php echo urlencode($master_token); ?>" method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group['id']); ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo get_csrf_token(); ?>">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('⚠️ WARNUNG: Möchtest du die Gruppe \"<?php echo htmlspecialchars($group['name']); ?>\" wirklich PERMANENT löschen?\n\nDiese Aktion kann NICHT rückgängig gemacht werden!');">
                                        <img src="https://xn--wichtl-gua.ch/images/icon-delete.svg" alt="Delete" width="16" height="16">
                                        Löschen
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🎁</div>
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
                <div style="overflow-x: auto; background: white; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                        <thead>
                            <tr style="background: #f8f9fa; text-align: left; border-bottom: 2px solid #e9ecef;">
                                <th style="padding: 12px 20px; color: #495057; font-weight: 600;">Event Datum</th>
                                <th style="padding: 12px 20px; color: #495057; font-weight: 600;">Teilnehmer</th>
                                <th style="padding: 12px 20px; color: #495057; font-weight: 600;">Budget</th>
                                <th style="padding: 12px 20px; color: #495057; font-weight: 600;">Status</th>
                                <th style="padding: 12px 20px; color: #495057; font-weight: 600;">Archiviert am</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archived_stats as $stat): ?>
                                <tr style="border-bottom: 1px solid #e9ecef;">
                                    <td style="padding: 12px 20px;">
                                        <?php echo $stat['gift_exchange_date'] ? date('d.m.Y', strtotime($stat['gift_exchange_date'])) : '<span style="color: #adb5bd;">Unbekannt</span>'; ?>
                                    </td>
                                    <td style="padding: 12px 20px;">
                                        <?php echo $stat['participant_count']; ?>
                                        <span style="color: #adb5bd; font-size: 0.85em;">(<?php echo $stat['participant_with_email_count']; ?> mit Mail)</span>
                                    </td>
                                    <td style="padding: 12px 20px;">
                                        <?php echo $stat['budget'] ? number_format($stat['budget'], 2) . ' CHF' : '-'; ?>
                                    </td>
                                    <td style="padding: 12px 20px;">
                                        <?php if ($stat['is_drawn']): ?>
                                            <span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 500;">Ausgelost</span>
                                        <?php else: ?>
                                            <span style="background: #e2e3e5; color: #383d41; padding: 4px 8px; border-radius: 4px; font-size: 0.85em; font-weight: 500;">Nicht beendet</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 12px 20px; color: #6c757d; font-size: 0.9em;">
                                        <?php echo date('d.m.Y H:i', strtotime($stat['archived_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state" style="padding: 2rem; background: #f8f9fa; border-radius: 8px; text-align: center;">
                    <p style="color: #6c757d; margin: 0;">Keine archivierten Daten vorhanden.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
