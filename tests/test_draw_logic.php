<?php

require_once __DIR__ . '/framework.php';
require_once __DIR__ . '/../includes/functions.php';

run_test("perform_draw: basic drawing", function() {
    $participant_ids = [1, 2, 3, 4, 5];
    $assigned_ids = perform_draw($participant_ids);

    assert_true(is_array($assigned_ids), "Result should be an array");
    assert_equals(count($participant_ids), count($assigned_ids), "Result should have the same number of elements");

    for ($i = 0; $i < count($participant_ids); $i++) {
        assert_true($participant_ids[$i] !== $assigned_ids[$i], "Participant should not be assigned to themselves");
        assert_true(in_array($assigned_ids[$i], $participant_ids), "Assigned ID must be a valid participant ID");
    }

    // Check that every ID is assigned exactly once
    $counts = array_count_values($assigned_ids);
    foreach ($participant_ids as $id) {
        assert_equals(1, $counts[$id], "ID $id should be assigned exactly once");
    }
});

run_test("perform_draw: respect exclusions", function() {
    $participant_ids = [1, 2, 3];
    // With 3 participants:
    // 1 can only draw 2 or 3
    // If 1 draws 2, 2 can only draw 3, and 3 must draw 1. (1->2, 2->3, 3->1)
    // If 1 draws 3, 3 can only draw 2, and 2 must draw 1. (1->3, 3->2, 2->1)

    // Exclude 1 -> 2 and 2 -> 3
    // This should force 1->3, 3->2, 2->1
    $exclusions = [
        1 => [2],
        2 => [3]
    ];

    $assigned_ids = perform_draw($participant_ids, $exclusions);

    assert_true(is_array($assigned_ids), "Result should be an array");
    // $participant_ids = [1, 2, 3]
    // $assigned_ids[0] is for participant 1
    // $assigned_ids[1] is for participant 2
    // $assigned_ids[2] is for participant 3
    assert_equals(3, $assigned_ids[0], "Participant 1 should be assigned 3");
    assert_equals(1, $assigned_ids[1], "Participant 2 should be assigned 1");
    assert_equals(2, $assigned_ids[2], "Participant 3 should be assigned 2");
});

run_test("perform_draw: return false on impossible draw", function() {
    $participant_ids = [1, 2];
    // With 2 participants, only valid draw is 1->2 and 2->1.
    // If we exclude 1->2, no valid draw is possible.
    $exclusions = [
        1 => [2]
    ];

    $assigned_ids = perform_draw($participant_ids, $exclusions, 100);
    assert_true($assigned_ids === false, "Should return false for impossible draw");
});

run_test("perform_draw: too few participants", function() {
    assert_true(perform_draw([1]) === false, "Should return false for 1 participant");
    assert_true(perform_draw([]) === false, "Should return false for 0 participants");
});
