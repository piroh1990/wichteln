<?php
// tests/test_cleanup_fk_constraint.php

require_once __DIR__ . '/../includes/cleanup.php';

run_test("Test Cleanup with FK Constraints (No CASCADE)", function() {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Enable foreign keys
    $pdo->exec("PRAGMA foreign_keys = ON;");

    // Create tables WITHOUT ON DELETE CASCADE
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
      FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) -- NO CASCADE HERE
    )");

    $pdo->exec("CREATE TABLE `exclusions` (
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `group_id` INTEGER NOT NULL,
      `participant_id` INTEGER NOT NULL,
      `excluded_participant_id` INTEGER NOT NULL,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`), -- NO CASCADE HERE
      FOREIGN KEY (`participant_id`) REFERENCES `participants`(`id`), -- NO CASCADE HERE
      FOREIGN KEY (`excluded_participant_id`) REFERENCES `participants`(`id`) -- NO CASCADE HERE
    )");

    $pdo->exec("CREATE TABLE `group_statistics` (
      `id` INTEGER PRIMARY KEY AUTOINCREMENT,
      `original_group_id` INTEGER,
      `participant_count` INTEGER DEFAULT 0,
      `participant_with_email_count` INTEGER DEFAULT 0,
      `exclusion_count` INTEGER DEFAULT 0,
      `budget` REAL,
      `gift_exchange_date` DATE,
      `is_drawn` INTEGER DEFAULT 0,
      `created_at` DATETIME,
      `archived_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert Old Group (should be archived)
    $pdo->exec("INSERT INTO `groups` (name, gift_exchange_date, created_at, budget, is_drawn) VALUES
        ('Old Group', date('now', '-4 months'), date('now', '-4 months'), 50.00, 1)");
    $old_group_id = $pdo->lastInsertId();

    $pdo->exec("INSERT INTO `participants` (group_id, name, email) VALUES ($old_group_id, 'User 1', 'user1@test.com')");
    $p1_id = $pdo->lastInsertId();
    $pdo->exec("INSERT INTO `participants` (group_id, name, email) VALUES ($old_group_id, 'User 2', 'user2@test.com')");
    $p2_id = $pdo->lastInsertId();

    // Add exclusion
    $pdo->exec("INSERT INTO `exclusions` (group_id, participant_id, excluded_participant_id) VALUES ($old_group_id, $p1_id, $p2_id)");

    // Run Cleanup
    $result = cleanup_old_groups($pdo);

    // Check results
    if ($result['errors'] > 0) {
        throw new Exception("Cleanup failed with errors: " . print_r($result['logs'], true));
    }

    assert_equals(1, $result['archived'], "Should have archived 1 group");

    // Verify group is gone
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE id = ?");
    $stmt->execute([$old_group_id]);
    assert_equals(0, (int)$stmt->fetchColumn(), "Group should be deleted");
});
