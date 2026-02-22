<?php
// Performance Benchmark for N+1 Query in draw.php

function benchmark_n_plus_one($participant_count) {
    // Setup SQLite in-memory
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create schema
    $pdo->exec("CREATE TABLE participants (id INTEGER PRIMARY KEY, group_id INTEGER, name TEXT, email TEXT, wishlist TEXT, assigned_to INTEGER)");
    $pdo->exec("CREATE TABLE groups (id INTEGER PRIMARY KEY, budget REAL, description TEXT, gift_exchange_date TEXT, is_drawn INTEGER)");

    // Seed data
    $pdo->exec("INSERT INTO groups (id, budget, description, gift_exchange_date, is_drawn) VALUES (1, 20.0, 'Test Group', '2023-12-24', 0)");

    $stmt = $pdo->prepare("INSERT INTO participants (group_id, name, email, wishlist) VALUES (1, ?, ?, ?)");
    for ($i = 1; $i <= $participant_count; $i++) {
        $stmt->execute(["Participant $i", "participant$i@example.com", "Wish $i"]);
    }

    // Load participants (as draw.php does at line 86-88)
    $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `group_id` = ?");
    $stmt->execute([1]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mock assignments (as draw.php does at line 156)
    $assignments = [];
    foreach ($participants as $p) {
        $receiver_id = ($p['id'] % $participant_count) + 1;
        $assignments[] = [
            'giver_id' => $p['id'],
            'receiver_id' => $receiver_id
        ];
    }

    $group = ['budget' => 20.0, 'description' => 'Test Group', 'gift_exchange_date' => '2023-12-24'];

    // --- BASELINE (N+1 Query simulation) ---
    $start_time = microtime(true);
    $emails_sent = 0;
    foreach ($participants as $participant) {
        if (!empty($participant['email'])) {
            $assigned_to = null;
            // Original O(N) lookup
            foreach ($assignments as $assignment) {
                if ($assignment['giver_id'] == $participant['id']) {
                    $assigned_to = $assignment['receiver_id'];
                    break;
                }
            }
            if ($assigned_to) {
                // Original N+1 Query
                $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `id` = ?");
                $stmt->execute([$assigned_to]);
                $assigned = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($assigned) {
                    $emails_sent++;
                }
            }
        }
    }
    $baseline_duration = microtime(true) - $start_time;

    // --- OPTIMIZED (In-memory lookup) ---
    $start_time = microtime(true);
    $emails_sent_opt = 0;

    // Optimized indexing (matches implementation)
    $participants_by_id = array_column($participants, null, 'id');
    $assignments_lookup = array_column($assignments, 'receiver_id', 'giver_id');

    foreach ($participants as $participant) {
        if (!empty($participant['email'])) {
            $assigned_to = $assignments_lookup[$participant['id']] ?? null;

            if ($assigned_to) {
                $assigned = $participants_by_id[$assigned_to] ?? null;
                if ($assigned) {
                    $emails_sent_opt++;
                }
            }
        }
    }
    $optimized_duration = microtime(true) - $start_time;

    echo "Results for $participant_count participants:\n";
    echo "Baseline duration:  " . number_format($baseline_duration, 6) . "s\n";
    echo "Optimized duration: " . number_format($optimized_duration, 6) . "s\n";
    if ($baseline_duration > 0) {
        $improvement = ($baseline_duration - $optimized_duration) / $baseline_duration * 100;
        echo "Improvement:        " . number_format($improvement, 2) . "%\n";
    }
    echo "----------------------------------------\n";
}

benchmark_n_plus_one(10);
benchmark_n_plus_one(100);
benchmark_n_plus_one(500);
