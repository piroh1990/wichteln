<?php
/**
 * Draw API Endpoint
 * 
 * POST   /api/draw.php                    - Auslosung durchführen
 * POST   /api/draw.php?action=reset       - Auslosung zurücksetzen
 */

require_once __DIR__ . '/helpers.php';

// CORS Headers setzen
set_cors_headers();

// Authentifizierung
authenticate_api();

// Datenbank-Verbindung
$pdo = db_connect();

// Request Method
$method = $_SERVER['REQUEST_METHOD'];

// Request Body
$input = get_request_body();

// Query Parameters
$action = isset($_GET['action']) ? $_GET['action'] : 'draw';

if ($method !== 'POST') {
    api_response(405, false, 'Nur POST-Methode erlaubt', null);
}

// Gruppe-ID erforderlich
$group_id = $input['group_id'] ?? null;
if (!$group_id) {
    api_response(400, false, 'group_id ist erforderlich', null);
}

// Gruppe laden
$stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `id` = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    api_response(404, false, 'Gruppe nicht gefunden', null);
}

if ($action === 'reset') {
    // AUSLOSUNG ZURÜCKSETZEN
    
    if (!$group['is_drawn']) {
        api_response(400, false, 'Gruppe wurde noch nicht ausgelost', null);
    }
    
    $pdo->beginTransaction();
    try {
        // is_drawn zurücksetzen
        $stmt = $pdo->prepare("UPDATE `groups` SET `is_drawn` = 0 WHERE `id` = ?");
        $stmt->execute([$group_id]);
        
        // assigned_to zurücksetzen
        $stmt = $pdo->prepare("UPDATE `participants` SET `assigned_to` = NULL WHERE `group_id` = ?");
        $stmt->execute([$group_id]);
        
        $pdo->commit();
        
        log_api_request('SUCCESS', '/api/draw.php', "Reset draw for group: $group_id");
        api_response(200, true, 'Auslosung erfolgreich zurückgesetzt', [
            'group_id' => $group_id,
            'is_drawn' => false
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        api_response(500, false, 'Fehler beim Zurücksetzen: ' . $e->getMessage(), null);
    }
    
} else {
    // AUSLOSUNG DURCHFÜHREN
    
    if ($group['is_drawn']) {
        api_response(400, false, 'Gruppe wurde bereits ausgelost', null);
    }
    
    // Teilnehmer laden
    $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `group_id` = ?");
    $stmt->execute([$group_id]);
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($participants) < 2) {
        api_response(400, false, 'Mindestens 2 Teilnehmer erforderlich', null);
    }
    
    // Ausschlüsse laden
    $stmt = $pdo->prepare("SELECT participant_id, excluded_participant_id FROM `exclusions` WHERE `group_id` = ?");
    $stmt->execute([$group_id]);
    $exclusion_rules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Exclusions-Map erstellen
    $exclusions_map = [];
    foreach ($exclusion_rules as $rule) {
        if (!isset($exclusions_map[$rule['participant_id']])) {
            $exclusions_map[$rule['participant_id']] = [];
        }
        $exclusions_map[$rule['participant_id']][] = $rule['excluded_participant_id'];
    }
    
    $participant_ids = array_column($participants, 'id');
    $assigned_ids = $participant_ids;
    
    // Gültige Zuteilung finden
    $max_attempts = 1000;
    $attempt = 0;
    $valid_assignment = false;
    
    while (!$valid_assignment && $attempt < $max_attempts) {
        shuffle($assigned_ids);
        $valid_assignment = true;
        
        for ($i = 0; $i < count($participant_ids); $i++) {
            $giver = $participant_ids[$i];
            $receiver = $assigned_ids[$i];
            
            // Prüfen ob Person sich selbst zieht
            if ($giver == $receiver) {
                $valid_assignment = false;
                break;
            }
            
            // Prüfen ob Zuteilung ausgeschlossen
            if (isset($exclusions_map[$giver]) && in_array($receiver, $exclusions_map[$giver])) {
                $valid_assignment = false;
                break;
            }
        }
        
        $attempt++;
    }
    
    if (!$valid_assignment) {
        api_response(400, false, 'Keine gültige Auslosung möglich. Bitte Ausschlüsse überprüfen.', [
            'attempts' => $attempt,
            'participants_count' => count($participants),
            'exclusions_count' => count($exclusion_rules)
        ]);
    }
    
    $pdo->beginTransaction();
    try {
        // Zuordnungen speichern
        $assignments = [];
        for ($i = 0; $i < count($participant_ids); $i++) {
            $stmt = $pdo->prepare("UPDATE `participants` SET `assigned_to` = ? WHERE `id` = ?");
            $stmt->execute([$assigned_ids[$i], $participant_ids[$i]]);
            
            $assignments[] = [
                'giver_id' => $participant_ids[$i],
                'receiver_id' => $assigned_ids[$i]
            ];
        }
        
        // Gruppe als ausgelost markieren
        $stmt = $pdo->prepare("UPDATE `groups` SET `is_drawn` = 1 WHERE `id` = ?");
        $stmt->execute([$group_id]);
        
        $pdo->commit();
        
        // E-Mails versenden (optional, falls send_emails=true)
        $send_emails = isset($input['send_emails']) && $input['send_emails'] === true;
        $emails_sent = 0;
        
        if ($send_emails) {
            foreach ($participants as $participant) {
                if (!empty($participant['email'])) {
                    // Zugewiesenen Partner finden
                    $assigned_to = null;
                    foreach ($assignments as $assignment) {
                        if ($assignment['giver_id'] == $participant['id']) {
                            $assigned_to = $assignment['receiver_id'];
                            break;
                        }
                    }
                    
                    if ($assigned_to) {
                        $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `id` = ?");
                        $stmt->execute([$assigned_to]);
                        $assigned = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($assigned) {
                            $group_budget = $group['budget'] !== null ? number_format($group['budget'], 2) . " CHF" : "Nicht festgelegt";
                            $group_description = $group['description'] ?: "Keine Beschreibung.";
                            $gift_date = $group['gift_exchange_date'] ? date('d.m.Y', strtotime($group['gift_exchange_date'])) : "Nicht festgelegt";
                            
                            $subject = 'Dein Wichtelpartner 🎁';
                            $html_message = create_html_email(
                                $participant['name'],
                                $assigned['name'],
                                $assigned['wishlist'] ?? '',
                                $group_budget,
                                $group_description,
                                $gift_date
                            );
                            
                            if (send_email($participant['email'], $subject, $html_message, true)) {
                                $emails_sent++;
                            }
                        }
                    }
                }
            }
        }
        
        log_api_request('SUCCESS', '/api/draw.php', "Draw completed for group: $group_id");
        api_response(200, true, 'Auslosung erfolgreich durchgeführt', [
            'group_id' => $group_id,
            'is_drawn' => true,
            'participants_count' => count($participants),
            'attempts_needed' => $attempt,
            'emails_sent' => $emails_sent,
            'assignments' => $assignments
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        api_response(500, false, 'Fehler bei der Auslosung: ' . $e->getMessage(), null);
    }
}
