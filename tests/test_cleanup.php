<?php

require_once __DIR__ . '/../includes/cleanup.php';

run_test("Test Cleanup Logic", function() {
    // Setup in-memory SQLite database
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create tables
    $pdo->exec("CREATE TABLE `groups` (
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `name` TEXT NOT NULL,
      `admin_token` TEXT,
      `invite_token` TEXT,
      `admin_email` TEXT,
      `budget` REAL,
      `description` TEXT,
      `gift_exchange_date` DATE,
      `is_drawn` INTEGER DEFAULT 0,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $pdo->exec("CREATE TABLE `participants` (
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `group_id` INTEGER NOT NULL,
      `name` TEXT NOT NULL,
      `email` TEXT,
      `token` TEXT,
      `assigned_to` INTEGER,
      `wishlist` TEXT,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE `exclusions` (
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `group_id` INTEGER NOT NULL,
      `participant_id` INTEGER NOT NULL,
      `excluded_participant_id` INTEGER NOT NULL,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE
    )");

    $pdo->exec("CREATE TABLE `group_statistics` (
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `original_group_id` INTEGER,
      `group_name` TEXT,
      `participant_count` INTEGER DEFAULT 0,
      `participant_with_email_count` INTEGER DEFAULT 0,
      `exclusion_count` INTEGER DEFAULT 0,
      `budget` REAL,
      `gift_exchange_date` DATE,
      `is_drawn` INTEGER DEFAULT 0,
      `created_at` DATETIME,
      `archived_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert Test Data

    // 1. Old Group (should be archived)
    $pdo->exec("INSERT INTO `groups` (name, gift_exchange_date, created_at, budget, is_drawn) VALUES
        ('Old Group', date('now', '-4 months'), date('now', '-4 months'), 50.00, 1)");
    $old_group_id = $pdo->lastInsertId();

    $pdo->exec("INSERT INTO `participants` (group_id, name, email) VALUES ($old_group_id, 'User 1', 'user1@test.com')");
    $pdo->exec("INSERT INTO `participants` (group_id, name, email) VALUES ($old_group_id, 'User 2', 'user2@test.com')");
    $pdo->exec("INSERT INTO `exclusions` (group_id, participant_id, excluded_participant_id) VALUES ($old_group_id, 1, 2)");

    // 2. New Group (should NOT be archived)
    $pdo->exec("INSERT INTO `groups` (name, gift_exchange_date, created_at, budget, is_drawn) VALUES
        ('New Group', date('now', '-1 month'), date('now', '-1 month'), 20.00, 0)");
    $new_group_id = $pdo->lastInsertId();

    // 3. Abandoned Old Group (should be archived)
    $pdo->exec("INSERT INTO `groups` (name, gift_exchange_date, created_at) VALUES
        ('Abandoned Group', NULL, date('now', '-4 months'))");
    $abandoned_group_id = $pdo->lastInsertId();

    // Run Cleanup
    $result = cleanup_old_groups($pdo);

    assert_equals(2, $result['archived'], "Should have archived 2 groups");
    assert_equals(0, $result['errors'], "Should have 0 errors");

    // Check if Old Group is gone
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE id = ?");
    $stmt->execute([$old_group_id]);
    assert_equals(0, (int)$stmt->fetchColumn(), "Old Group should be deleted");

    // Check if New Group is present
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE id = ?");
    $stmt->execute([$new_group_id]);
    assert_equals(1, (int)$stmt->fetchColumn(), "New Group should remain");

    // Check if Abandoned Group is gone
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE id = ?");
    $stmt->execute([$abandoned_group_id]);
    assert_equals(0, (int)$stmt->fetchColumn(), "Abandoned Group should be deleted");

    // Check Statistics Table
    $stmt = $pdo->prepare("SELECT * FROM `group_statistics` WHERE original_group_id = ?");
    $stmt->execute([$old_group_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    assert_true($stats !== false, "Statistics for Old Group should exist");
    assert_equals(2, (int)$stats['participant_count'], "Participant count should be 2");
    assert_equals(1, (int)$stats['exclusion_count'], "Exclusion count should be 1");
});
