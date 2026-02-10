<?php
// /admin/index.php

require_once __DIR__ . '/../functions.php';

// Überprüfen, ob das master_token korrekt ist
$master_token = $_GET['master_token'] ?? '';

if ($master_token !== MASTER_ADMIN_TOKEN) {
    die('Zugriff verweigert. Ungültiges Master-Token.');
}

$pdo = db_connect();

// Aktionen: Reset oder Löschen einer Gruppe über GET-Parameter
if (isset($_GET['action']) && isset($_GET['group_id'])) {
    $action = $_GET['action'];
    $group_id = intval($_GET['group_id']);

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
                <div class="stat-card-value"><?php echo $total_groups; ?></div>
                <div class="stat-card-footer">
                    Aktive Wichtel-Gruppen
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-card-icon success">👥</div>
                    <span class="stat-card-label">Teilnehmer</span>
                </div>
                <div class="stat-card-value"><?php echo $total_participants; ?></div>
                <div class="stat-card-footer">
                    Registrierte Personen
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

                                <a href="index.php?master_token=<?php echo urlencode($master_token); ?>&action=reset&group_id=<?php echo urlencode($group['id']); ?>" 
                                   class="btn btn-secondary" 
                                   onclick="return confirm('Möchtest du die Gruppe \"<?php echo htmlspecialchars($group['name']); ?>\" wirklich zurücksetzen?');">
                                    <img src="https://xn--wichtl-gua.ch/images/icon-reset.svg" alt="Reset" width="16" height="16">
                                    Reset
                                </a>

                                <a href="index.php?master_token=<?php echo urlencode($master_token); ?>&action=delete&group_id=<?php echo urlencode($group['id']); ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('⚠️ WARNUNG: Möchtest du die Gruppe \"<?php echo htmlspecialchars($group['name']); ?>\" wirklich PERMANENT löschen?\n\nDiese Aktion kann NICHT rückgängig gemacht werden!');">
                                    <img src="https://xn--wichtl-gua.ch/images/icon-delete.svg" alt="Delete" width="16" height="16">
                                    Löschen
                                </a>
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
    </div>
</body>
</html>
