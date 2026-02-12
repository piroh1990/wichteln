<?php
/**
 * Exclusions API Endpoint
 * 
 * GET    /api/exclusions.php?group_id=1   - Alle Ausschlüsse einer Gruppe
 * POST   /api/exclusions.php              - Neuen Ausschluss erstellen
 * DELETE /api/exclusions.php?id=1         - Ausschluss löschen
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
$exclusion_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;

switch ($method) {
    case 'GET':
        if (!$group_id) {
            api_response(400, false, 'group_id ist erforderlich', null);
        }
        
        // Alle Ausschlüsse einer Gruppe
        $stmt = $pdo->prepare("
            SELECT 
                e.id,
                e.participant_id,
                e.excluded_participant_id,
                p1.name as participant_name,
                p2.name as excluded_name,
                e.created_at
            FROM `exclusions` e
            JOIN `participants` p1 ON e.participant_id = p1.id
            JOIN `participants` p2 ON e.excluded_participant_id = p2.id
            WHERE e.group_id = ?
            ORDER BY p1.name, p2.name
        ");
        $stmt->execute([$group_id]);
        $exclusions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        log_api_request('SUCCESS', '/api/exclusions.php', "Group ID: $group_id");
        api_response(200, true, 'Ausschlüsse erfolgreich abgerufen', sanitize_for_api($exclusions));
        break;
        
    case 'POST':
        // Neuen Ausschluss erstellen
        $group_id = $input['group_id'] ?? null;
        $participant_id = $input['participant_id'] ?? null;
        $excluded_participant_id = $input['excluded_participant_id'] ?? null;
        
        // Validierung
        if (!$group_id) {
            api_response(400, false, 'group_id ist erforderlich', null);
        }
        if (!$participant_id) {
            api_response(400, false, 'participant_id ist erforderlich', null);
        }
        if (!$excluded_participant_id) {
            api_response(400, false, 'excluded_participant_id ist erforderlich', null);
        }
        if ($participant_id == $excluded_participant_id) {
            api_response(400, false, 'Teilnehmer kann sich nicht selbst ausschließen', null);
        }
        
        // Gruppe prüfen
        $stmt = $pdo->prepare("SELECT `is_drawn` FROM `groups` WHERE `id` = ?");
        $stmt->execute([$group_id]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$group) {
            api_response(404, false, 'Gruppe nicht gefunden', null);
        }
        if ($group['is_drawn']) {
            api_response(400, false, 'Ausschlüsse können nach der Auslosung nicht mehr geändert werden', null);
        }
        
        // Teilnehmer prüfen
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `participants` WHERE `id` IN (?, ?) AND `group_id` = ?");
        $stmt->execute([$participant_id, $excluded_participant_id, $group_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] != 2) {
            api_response(400, false, 'Beide Teilnehmer müssen zur Gruppe gehören', null);
        }
        
        // Ausschluss erstellen
        try {
            $stmt = $pdo->prepare("INSERT INTO `exclusions` (`group_id`, `participant_id`, `excluded_participant_id`) VALUES (?, ?, ?)");
            $stmt->execute([$group_id, $participant_id, $excluded_participant_id]);
            
            $exclusion_id = $pdo->lastInsertId();
            
            log_api_request('SUCCESS', '/api/exclusions.php', "Created exclusion: $exclusion_id");
            api_response(201, true, 'Ausschluss erfolgreich erstellt', [
                'id' => intval($exclusion_id),
                'group_id' => $group_id,
                'participant_id' => $participant_id,
                'excluded_participant_id' => $excluded_participant_id
            ]);
            
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Duplicate entry
                api_response(400, false, 'Dieser Ausschluss existiert bereits', null);
            } else {
                api_response(500, false, 'Fehler beim Erstellen des Ausschlusses', null);
            }
        }
        break;
        
    case 'DELETE':
        if (!$exclusion_id) {
            api_response(400, false, 'Ausschluss-ID erforderlich', null);
        }
        
        // Gruppe prüfen
        $stmt = $pdo->prepare("
            SELECT g.is_drawn 
            FROM exclusions e 
            JOIN groups g ON e.group_id = g.id 
            WHERE e.id = ?
        ");
        $stmt->execute([$exclusion_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            api_response(404, false, 'Ausschluss nicht gefunden', null);
        }
        if ($result['is_drawn']) {
            api_response(400, false, 'Ausschlüsse können nach der Auslosung nicht mehr gelöscht werden', null);
        }
        
        $stmt = $pdo->prepare("DELETE FROM `exclusions` WHERE `id` = ?");
        $stmt->execute([$exclusion_id]);
        
        log_api_request('SUCCESS', '/api/exclusions.php', "Deleted exclusion: $exclusion_id");
        api_response(200, true, 'Ausschluss erfolgreich gelöscht', ['id' => $exclusion_id]);
        break;
        
    default:
        api_response(405, false, 'Methode nicht erlaubt', null);
}
