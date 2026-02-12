<?php

/**
 * Cleans up old groups by archiving their statistics and deleting the original data.
 *
 * @param PDO $pdo The database connection
 * @return array Result stats ['archived' => int, 'errors' => int, 'logs' => array]
 */
function cleanup_old_groups($pdo) {
    $logs = [];
    $logs[] = "[" . date('Y-m-d H:i:s') . "] Starting cleanup process...";

    try {
        // Find groups to archive
        // Criteria:
        // 1. gift_exchange_date is set and is older than 3 months
        // 2. OR gift_exchange_date is NULL and created_at is older than 3 months (abandoned groups)
        // Use SQLite compatible syntax for date arithmetic if needed, but for now stick to MySQL
        // For testing with SQLite, we might need to adjust the query or use a different approach.
        // Let's try to make it standard SQL or handle driver differences.

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
             $sql = "SELECT * FROM `groups`
                WHERE (gift_exchange_date IS NOT NULL AND gift_exchange_date < date('now', '-3 months'))
                OR (gift_exchange_date IS NULL AND created_at < date('now', '-3 months'))";
        } else {
             $sql = "SELECT * FROM `groups`
                WHERE (gift_exchange_date IS NOT NULL AND gift_exchange_date < DATE_SUB(NOW(), INTERVAL 3 MONTH))
                OR (gift_exchange_date IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 3 MONTH))";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $logs[] = "[" . date('Y-m-d H:i:s') . "] Found " . count($groups) . " groups to archive.";

        if (count($groups) === 0) {
            $logs[] = "[" . date('Y-m-d H:i:s') . "] Nothing to do.";
            return ['archived' => 0, 'errors' => 0, 'logs' => $logs];
        }

        $archived_count = 0;
        $error_count = 0;

        foreach ($groups as $group) {
            try {
                $pdo->beginTransaction();

                $group_id = $group['id'];
                $group_name = $group['name'];

                // 1. Gather Statistics

                // Count participants
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `participants` WHERE `group_id` = ?");
                $stmt->execute([$group_id]);
                $participant_count = $stmt->fetchColumn();

                // Count participants with email
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `participants` WHERE `group_id` = ? AND `email` IS NOT NULL AND `email` != ''");
                $stmt->execute([$group_id]);
                $participant_with_email_count = $stmt->fetchColumn();

                // Count exclusions
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM `exclusions` WHERE `group_id` = ?");
                $stmt->execute([$group_id]);
                $exclusion_count = $stmt->fetchColumn();

                // 2. Archive Statistics
                $insert_sql = "INSERT INTO `group_statistics`
                               (original_group_id, participant_count, participant_with_email_count, exclusion_count,
                                budget, gift_exchange_date, is_drawn, created_at, archived_at)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, " . ($driver === 'sqlite' ? "datetime('now')" : "NOW()") . ")";

                $stmt = $pdo->prepare($insert_sql);
                $stmt->execute([
                    $group_id,
                    $participant_count,
                    $participant_with_email_count,
                    $exclusion_count,
                    $group['budget'],
                    $group['gift_exchange_date'],
                    $group['is_drawn'],
                    $group['created_at']
                ]);

                // 3. Delete Group (Cascades to participants and exclusions)
                $stmt = $pdo->prepare("UPDATE `participants` SET `assigned_to` = NULL WHERE `group_id` = ?");
                $stmt->execute([$group_id]);

                $stmt = $pdo->prepare("DELETE FROM `groups` WHERE `id` = ?");
                $stmt->execute([$group_id]);

                $pdo->commit();

                $logs[] = "[" . date('Y-m-d H:i:s') . "] Archived and deleted group: '{$group_name}' (ID: {$group_id})";
                $archived_count++;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $logs[] = "[" . date('Y-m-d H:i:s') . "] ERROR archiving group ID {$group['id']}: " . $e->getMessage();
                $error_count++;
            }
        }

        $logs[] = "[" . date('Y-m-d H:i:s') . "] Cleanup complete. Archived: {$archived_count}, Errors: {$error_count}.";

        return ['archived' => $archived_count, 'errors' => $error_count, 'logs' => $logs];

    } catch (Exception $e) {
        $logs[] = "[" . date('Y-m-d H:i:s') . "] CRITICAL ERROR: " . $e->getMessage();
        return ['archived' => 0, 'errors' => 1, 'logs' => $logs];
    }
}
