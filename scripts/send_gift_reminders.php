#!/usr/bin/env php
<?php
/**
 * Erinnerung an die Geschenkübergabe.
 *
 * Idempotent: Gruppen mit gesetztem reminder_sent_at werden übersprungen.
 * Standardfenster: Geschenkdatum heute bis in 7 Tagen.
 *
 * Nutzung:
 *   php scripts/send_gift_reminders.php
 *   php scripts/send_gift_reminders.php --days=7
 *   php scripts/send_gift_reminders.php --dry-run
 *
 * Cron, zum Beispiel täglich um 08:15:
 *   15 8 * * * /usr/bin/php /pfad/zu/wichteln/scripts/send_gift_reminders.php >> /pfad/zu/wichteln/logs/reminder.log 2>&1
 *
 * Voraussetzung: groups.reminder_sent_at
 * (database/migrations/20261007_add_reminder_sent_at.sql).
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Nur auf der Kommandozeile ausführen.\n");
    exit(1);
}

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/includes/functions.php';

$within_days = 7;
$dry_run = false;
foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run') {
        $dry_run = true;
        continue;
    }
    if (strpos($arg, '--days=') === 0) {
        $within_days = (int) substr($arg, 7);
    }
}

if ($within_days < 0 || $within_days > 60) {
    fwrite(STDERR, "Tage müssen zwischen 0 und 60 liegen.\n");
    exit(1);
}

$pdo = db_connect();
if (!mysql_column_exists($pdo, 'groups', 'reminder_sent_at')) {
    fwrite(STDERR, "Spalte groups.reminder_sent_at fehlt. Migration database/migrations/20261007_add_reminder_sent_at.sql ausführen.\n");
    exit(1);
}

$today = date('Y-m-d');
$stmt = $pdo->query('SELECT * FROM `groups` WHERE `gift_exchange_date` IS NOT NULL AND `reminder_sent_at` IS NULL');
$groups = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : array();

$considered = 0;
$reminded = 0;
$errors = 0;

foreach ($groups as $group) {
    if (!gift_reminder_due($group['gift_exchange_date'], $group['reminder_sent_at'], $today, $within_days)) {
        continue;
    }
    $considered++;

    $participants_stmt = $pdo->prepare('SELECT * FROM `participants` WHERE `group_id` = ?');
    $participants_stmt->execute(array($group['id']));
    $participants = $participants_stmt->fetchAll(PDO::FETCH_ASSOC);

    $days = gift_days_until($group['gift_exchange_date'], $today);
    $label = isset($group['name']) ? $group['name'] : ('#' . $group['id']);

    if ($dry_run) {
        echo 'Trockenlauf: ' . $label . ' (' . gift_reminder_sentence($days) . ")\n";
        continue;
    }

    $result = deliver_gift_reminders($participants, $group, null, $today);
    if ($result['status'] === 'ok') {
        $update = $pdo->prepare('UPDATE `groups` SET `reminder_sent_at` = NOW() WHERE `id` = ? AND `reminder_sent_at` IS NULL');
        $update->execute(array($group['id']));
        $reminded++;
        echo $label . ': ' . gift_reminder_result_message($result)['text'] . "\n";
    } elseif ($result['status'] === 'no_recipients') {
        echo $label . ': keine Empfänger, nichts gespeichert.' . "\n";
    } else {
        $errors++;
        echo $label . ': ' . gift_reminder_result_message($result)['text'] . "\n";
    }
}

echo 'Geprüft im Fenster von ' . $within_days . ' Tagen: ' . $considered . ', erinnert: ' . $reminded . ".\n";
exit($errors > 0 ? 1 : 0);
