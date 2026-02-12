#!/usr/bin/env php
<?php
/**
 * Wichtlä.ch - Group Cleanup Script
 *
 * This script identifies groups where the event took place more than 3 months ago,
 * archives their statistics, and then deletes the personal data.
 *
 * Usage: php scripts/cleanup_groups.php
 * Setup: Add to cron to run daily, e.g.:
 * 0 3 * * * /usr/bin/php /path/to/wichtel-app/scripts/cleanup_groups.php >> /path/to/wichtel-app/logs/cleanup.log 2>&1
 */

// Define root path
define('ROOT_PATH', dirname(__DIR__));

// Include functions for DB connection
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/cleanup.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pdo = db_connect();
$result = cleanup_old_groups($pdo);

// Output logs
foreach ($result['logs'] as $log) {
    echo $log . "\n";
}

if ($result['errors'] > 0) {
    exit(1);
}

exit(0);
