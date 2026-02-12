<?php
/**
 * Groups API Endpoint
 * 
 * GET    /api/groups.php           - Liste aller Gruppen
 * GET    /api/groups.php?id=1      - Einzelne Gruppe
 * POST   /api/groups.php           - Neue Gruppe erstellen
 * PUT    /api/groups.php?id=1      - Gruppe aktualisieren
 * DELETE /api/groups.php?id=1      - Gruppe löschen
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
$group_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$admin_token = isset($_GET['admin_token']) ? $_GET['admin_token'] : null;
$invite_token = isset($_GET['invite_token']) ? $_GET['invite_token'] : null;

switch ($method) {
    case 'GET':
        if ($group_id) {
            // Einzelne Gruppe abrufen
            $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `id` = ?");
            $stmt->execute([$group_id]);
            $group = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$group) {
                api_response(404, false, 'Gruppe nicht gefunden', null);
            }
            
            // Teilnehmer laden
            $stmt = $pdo->prepare("SELECT `id`, `name`, `email`, `wishlist`, `created_at` FROM `participants` WHERE `group_id` = ?");
            $stmt->execute([$group_id]);
            $group['participants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ausschlüsse laden
            $stmt = $pdo->prepare("
                SELECT e.id, p1.name as participant_name, p2.name as excluded_name 
                FROM `exclusions` e
                JOIN `participants` p1 ON e.participant_id = p1.id
                JOIN `participants` p2 ON e.excluded_participant_id = p2.id
                WHERE e.group_id = ?
            ");
            $stmt->execute([$group_id]);
            $group['exclusions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            log_api_request('SUCCESS', '/api/groups.php', "Group ID: $group_id");
            api_response(200, true, 'Gruppe erfolgreich abgerufen', sanitize_for_api($group));
            
        } elseif ($admin_token) {
            // Gruppe per Admin-Token abrufen
            $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE `admin_token` = ?");
            $stmt->execute([$admin_token]);
            $group = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$group) {
                api_response(404, false, 'Gruppe nicht gefunden', null);
            }
            
            // Teilnehmer laden
            $stmt = $pdo->prepare("SELECT * FROM `participants` WHERE `group_id` = ?");
            $stmt->execute([$group['id']]);
            $group['participants'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            log_api_request('SUCCESS', '/api/groups.php', "Admin Token");
            api_response(200, true, 'Gruppe erfolgreich abgerufen', sanitize_for_api($group));
            
        } elseif ($invite_token) {
            // Gruppe per Invite-Token abrufen (öffentliche Infos)
            $stmt = $pdo->prepare("SELECT `id`, `name`, `budget`, `description`, `gift_exchange_date`, `is_drawn` FROM `groups` WHERE `invite_token` = ?");
            $stmt->execute([$invite_token]);
            $group = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$group) {
                api_response(404, false, 'Gruppe nicht gefunden', null);
            }
            
            log_api_request('SUCCESS', '/api/groups.php', "Invite Token");
            api_response(200, true, 'Gruppe erfolgreich abgerufen', sanitize_for_api($group));
            
        } else {
            // Alle Gruppen abrufen (mit Pagination)
            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
            $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
            
            $query = "SELECT `id`, `name`, `budget`, `description`, `gift_exchange_date`, `is_drawn`, `created_at` FROM `groups` ORDER BY `created_at` DESC";
            $result = paginate($query, $pdo, $page, $per_page);
            
            log_api_request('SUCCESS', '/api/groups.php', "List - Page $page");
            api_response(200, true, 'Gruppen erfolgreich abgerufen', sanitize_for_api($result['items']), $result['pagination']);
        }
        break;
        
    case 'POST':
        // Neue Gruppe erstellen
        $name = $input['name'] ?? '';
        $admin_email = $input['admin_email'] ?? '';
        $budget = $input['budget'] ?? null;
        $description = $input['description'] ?? null;
        $gift_exchange_date = $input['gift_exchange_date'] ?? null;
        
        // Validierung
        if ($error = validate_input('name', $name, ['required'])) {
            api_response(400, false, $error, null);
        }
        if ($admin_email && ($error = validate_input('admin_email', $admin_email, ['email']))) {
            api_response(400, false, $error, null);
        }
        if ($budget && ($error = validate_input('budget', $budget, ['numeric']))) {
            api_response(400, false, $error, null);
        }
        if ($gift_exchange_date && ($error = validate_input('gift_exchange_date', $gift_exchange_date, ['date']))) {
            api_response(400, false, $error, null);
        }
        
        // Tokens generieren
        $admin_token = generate_token();
        $invite_token = generate_token();
        
        // Gruppe erstellen
        $stmt = $pdo->prepare("INSERT INTO `groups` (`name`, `admin_token`, `invite_token`, `admin_email`, `budget`, `description`, `gift_exchange_date`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $admin_token, $invite_token, $admin_email, $budget, $description, $gift_exchange_date]);
        
        $group_id = $pdo->lastInsertId();
        
        // E-Mail senden falls angegeben
        if ($admin_email) {
            $admin_link = get_display_url('/admin.php?token=' . urlencode($admin_token));
            $invite_link = get_display_url('/register.php?token=' . urlencode($invite_token));
            
            $group_budget = $budget !== null ? number_format($budget, 2) . " CHF" : "Nicht festgelegt";
            $group_description = $description ?: "Keine Beschreibung";
            $gift_date = $gift_exchange_date ? date('d.m.Y', strtotime($gift_exchange_date)) : "Nicht festgelegt";
            
            $subject = 'Deine Wichtelgruppe "' . $name . '" wurde erstellt! 🎁';
            $html_message = create_admin_email($name, $admin_link, $invite_link, $group_budget, $group_description, $gift_date);
            send_email($admin_email, $subject, $html_message, true);
        }
        
        log_api_request('SUCCESS', '/api/groups.php', "Created group: $name");
        api_response(201, true, 'Gruppe erfolgreich erstellt', [
            'id' => intval($group_id),
            'name' => $name,
            'admin_token' => $admin_token,
            'invite_token' => $invite_token,
            'admin_link' => get_display_url('/admin.php?token=' . urlencode($admin_token)),
            'invite_link' => get_display_url('/register.php?token=' . urlencode($invite_token))
        ]);
        break;
        
    case 'PUT':
        // Gruppe aktualisieren
        if (!$group_id) {
            api_response(400, false, 'Gruppen-ID erforderlich', null);
        }
        
        $budget = $input['budget'] ?? null;
        $description = $input['description'] ?? null;
        $gift_exchange_date = $input['gift_exchange_date'] ?? null;
        
        // Validierung
        if ($budget && ($error = validate_input('budget', $budget, ['numeric']))) {
            api_response(400, false, $error, null);
        }
        if ($gift_exchange_date && ($error = validate_input('gift_exchange_date', $gift_exchange_date, ['date']))) {
            api_response(400, false, $error, null);
        }
        
        $stmt = $pdo->prepare("UPDATE `groups` SET `budget` = ?, `description` = ?, `gift_exchange_date` = ? WHERE `id` = ?");
        $stmt->execute([$budget, $description, $gift_exchange_date, $group_id]);
        
        log_api_request('SUCCESS', '/api/groups.php', "Updated group: $group_id");
        api_response(200, true, 'Gruppe erfolgreich aktualisiert', ['id' => $group_id]);
        break;
        
    case 'DELETE':
        // Gruppe löschen
        if (!$group_id) {
            api_response(400, false, 'Gruppen-ID erforderlich', null);
        }
        
        $pdo->beginTransaction();
        try {
            // Setze assigned_to auf NULL
            $stmt = $pdo->prepare("UPDATE `participants` SET `assigned_to` = NULL WHERE `group_id` = ?");
            $stmt->execute([$group_id]);
            
            // Lösche Ausschlüsse
            $stmt = $pdo->prepare("DELETE FROM `exclusions` WHERE `group_id` = ?");
            $stmt->execute([$group_id]);
            
            // Lösche Teilnehmer
            $stmt = $pdo->prepare("DELETE FROM `participants` WHERE `group_id` = ?");
            $stmt->execute([$group_id]);
            
            // Lösche Gruppe
            $stmt = $pdo->prepare("DELETE FROM `groups` WHERE `id` = ?");
            $stmt->execute([$group_id]);
            
            $pdo->commit();
            
            log_api_request('SUCCESS', '/api/groups.php', "Deleted group: $group_id");
            api_response(200, true, 'Gruppe erfolgreich gelöscht', ['id' => $group_id]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            api_response(500, false, 'Fehler beim Löschen der Gruppe: ' . $e->getMessage(), null);
        }
        break;
        
    default:
        api_response(405, false, 'Methode nicht erlaubt', null);
}
