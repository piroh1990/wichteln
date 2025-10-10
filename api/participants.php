<?php
/**
 * Participants API Endpoint
 * 
 * GET    /api/participants.php                    - Liste aller Teilnehmer
 * GET    /api/participants.php?id=1               - Einzelner Teilnehmer
 * GET    /api/participants.php?token=xxx          - Teilnehmer per Token
 * GET    /api/participants.php?group_id=1         - Teilnehmer einer Gruppe
 * POST   /api/participants.php                    - Neuen Teilnehmer erstellen
 * PUT    /api/participants.php?id=1               - Teilnehmer aktualisieren
 * DELETE /api/participants.php?id=1               - Teilnehmer löschen
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
$participant_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : null;
$token = isset($_GET['token']) ? $_GET['token'] : null;

switch ($method) {
    case 'GET':
        if ($participant_id) {
            // Einzelnen Teilnehmer abrufen
            $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `id` = ?");
            $stmt->execute([$participant_id]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$participant) {
                api_response(404, false, 'Teilnehmer nicht gefunden', null);
            }
            
            // Zugewiesenen Partner laden (falls ausgelost)
            if ($participant['assigned_to']) {
                $stmt = $pdo->prepare("SELECT `id`, `name`, `wishlist` FROM `participants` WHERE `id` = ?");
                $stmt->execute([$participant['assigned_to']]);
                $participant['assigned_partner'] = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Gruppeninfo laden
            $stmt = $pdo->prepare("SELECT `name`, `budget`, `description`, `gift_exchange_date`, `is_drawn` FROM `groups` WHERE `id` = ?");
            $stmt->execute([$participant['group_id']]);
            $participant['group'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            log_api_request('SUCCESS', '/api/participants.php', "Participant ID: $participant_id");
            api_response(200, true, 'Teilnehmer erfolgreich abgerufen', sanitize_for_api($participant));
            
        } elseif ($token) {
            // Teilnehmer per Token abrufen
            $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `token` = ?");
            $stmt->execute([$token]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$participant) {
                api_response(404, false, 'Teilnehmer nicht gefunden', null);
            }
            
            // Zugewiesenen Partner laden (falls ausgelost)
            if ($participant['assigned_to']) {
                $stmt = $pdo->prepare("SELECT `id`, `name`, `wishlist` FROM `participants` WHERE `id` = ?");
                $stmt->execute([$participant['assigned_to']]);
                $participant['assigned_partner'] = $stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            // Gruppeninfo laden
            $stmt = $pdo->prepare("SELECT `name`, `budget`, `description`, `gift_exchange_date`, `is_drawn` FROM `groups` WHERE `id` = ?");
            $stmt->execute([$participant['group_id']]);
            $participant['group'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            log_api_request('SUCCESS', '/api/participants.php', "Participant Token");
            api_response(200, true, 'Teilnehmer erfolgreich abgerufen', sanitize_for_api($participant));
            
        } elseif ($group_id) {
            // Alle Teilnehmer einer Gruppe
            $stmt = $pdo->prepare("SELECT `id`, `name`, `email`, `wishlist`, `created_at` FROM `participants` WHERE `group_id` = ?");
            $stmt->execute([$group_id]);
            $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            log_api_request('SUCCESS', '/api/participants.php', "Group ID: $group_id");
            api_response(200, true, 'Teilnehmer erfolgreich abgerufen', sanitize_for_api($participants));
            
        } else {
            // Alle Teilnehmer (mit Pagination)
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
            
            $query = "SELECT `id`, `group_id`, `name`, `email`, `wishlist`, `created_at` FROM `participants` ORDER BY `created_at` DESC";
            $result = paginate($query, $pdo, $page, $per_page);
            
            log_api_request('SUCCESS', '/api/participants.php', "List - Page $page");
            api_response(200, true, 'Teilnehmer erfolgreich abgerufen', sanitize_for_api($result['items']), $result['pagination']);
        }
        break;
        
    case 'POST':
        // Neuen Teilnehmer erstellen
        $group_id = $input['group_id'] ?? null;
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? null;
        $wishlist = $input['wishlist'] ?? null;
        
        // Validierung
        if (!$group_id) {
            api_response(400, false, 'group_id ist erforderlich', null);
        }
        if ($error = validate_input('name', $name, ['required'])) {
            api_response(400, false, $error, null);
        }
        if ($email && ($error = validate_input('email', $email, ['email']))) {
            api_response(400, false, $error, null);
        }
        
        // Prüfen ob Gruppe existiert
        $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `id` = ?");
        $stmt->execute([$group_id]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$group) {
            api_response(404, false, 'Gruppe nicht gefunden', null);
        }
        
        // Prüfen ob bereits ausgelost
        if ($group['is_drawn']) {
            api_response(400, false, 'Gruppe wurde bereits ausgelost. Keine neuen Teilnehmer möglich.', null);
        }
        
        // Token generieren
        $participant_token = generate_token();
        
        // Teilnehmer erstellen
        $stmt = $pdo->prepare("INSERT INTO `participants` (`group_id`, `name`, `email`, `token`, `wishlist`) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$group_id, $name, $email, $participant_token, $wishlist]);
        
        $participant_id = $pdo->lastInsertId();
        
        // E-Mail senden falls angegeben
        if ($email) {
            $participant_link = get_display_url('/participant.php?token=' . urlencode($participant_token));
            
            $group_budget = $group['budget'] !== null ? number_format($group['budget'], 2) . " CHF" : "Nicht festgelegt";
            $group_description = $group['description'] ?: "Keine Beschreibung";
            $gift_date = $group['gift_exchange_date'] ? date('d.m.Y', strtotime($group['gift_exchange_date'])) : "Nicht festgelegt";
            
            $subject = 'Willkommen beim Wichteln! 🎁';
            $html_message = create_registration_email($name, $group['name'], $participant_link, $group_budget, $group_description, $gift_date);
            send_email($email, $subject, $html_message, true);
        }
        
        log_api_request('SUCCESS', '/api/participants.php', "Created participant: $name");
        api_response(201, true, 'Teilnehmer erfolgreich erstellt', [
            'id' => intval($participant_id),
            'name' => $name,
            'token' => $participant_token,
            'participant_link' => get_display_url('/participant.php?token=' . urlencode($participant_token))
        ]);
        break;
        
    case 'PUT':
        // Teilnehmer aktualisieren
        if (!$participant_id && !$token) {
            api_response(400, false, 'Teilnehmer-ID oder Token erforderlich', null);
        }
        
        // Teilnehmer laden
        if ($participant_id) {
            $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `id` = ?");
            $stmt->execute([$participant_id]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `token` = ?");
            $stmt->execute([$token]);
        }
        
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$participant) {
            api_response(404, false, 'Teilnehmer nicht gefunden', null);
        }
        
        // Gruppe laden
        $stmt = $pdo->prepare("SELECT `is_drawn` FROM `groups` WHERE `id` = ?");
        $stmt->execute([$participant['group_id']]);
        $group = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Nur Wunschliste vor Auslosung änderbar
        $wishlist = $input['wishlist'] ?? $participant['wishlist'];
        
        if ($group['is_drawn']) {
            // Nach Auslosung nur Wunschliste aktualisieren
            $stmt = $pdo->prepare("UPDATE `participants` SET `wishlist` = ? WHERE `id` = ?");
            $stmt->execute([$wishlist, $participant['id']]);
        } else {
            // Vor Auslosung alle Felder aktualisieren
            $name = $input['name'] ?? $participant['name'];
            $email = $input['email'] ?? $participant['email'];
            
            if ($email && ($error = validate_input('email', $email, ['email']))) {
                api_response(400, false, $error, null);
            }
            
            $stmt = $pdo->prepare("UPDATE `participants` SET `name` = ?, `email` = ?, `wishlist` = ? WHERE `id` = ?");
            $stmt->execute([$name, $email, $wishlist, $participant['id']]);
        }
        
        log_api_request('SUCCESS', '/api/participants.php', "Updated participant: {$participant['id']}");
        api_response(200, true, 'Teilnehmer erfolgreich aktualisiert', ['id' => $participant['id']]);
        break;
        
    case 'DELETE':
        // Teilnehmer löschen
        if (!$participant_id) {
            api_response(400, false, 'Teilnehmer-ID erforderlich', null);
        }
        
        // Gruppe prüfen
        $stmt = $pdo->prepare("
            SELECT g.is_drawn 
            FROM participants p 
            JOIN groups g ON p.group_id = g.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$participant_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            api_response(404, false, 'Teilnehmer nicht gefunden', null);
        }
        
        if ($result['is_drawn']) {
            api_response(400, false, 'Teilnehmer kann nach Auslosung nicht gelöscht werden', null);
        }
        
        $stmt = $pdo->prepare("DELETE FROM `participants` WHERE `id` = ?");
        $stmt->execute([$participant_id]);
        
        log_api_request('SUCCESS', '/api/participants.php', "Deleted participant: $participant_id");
        api_response(200, true, 'Teilnehmer erfolgreich gelöscht', ['id' => $participant_id]);
        break;
        
    default:
        api_response(405, false, 'Methode nicht erlaubt', null);
}
