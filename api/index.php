<?php
/**
 * API Index / Info Endpoint
 * 
 * GET /api/ - API Information und verfügbare Endpoints
 */

require_once __DIR__ . '/helpers.php';

// CORS Headers setzen
set_cors_headers();

// Für Index-Seite keine Auth erforderlich (nur Info)
if (isset($_GET['docs']) || $_SERVER['REQUEST_METHOD'] === 'GET') {
    
    $endpoints = [
        'groups' => [
            'url' => '/api/groups.php',
            'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'description' => 'Gruppen verwalten',
            'examples' => [
                'GET /api/groups.php' => 'Alle Gruppen abrufen',
                'GET /api/groups.php?id=1' => 'Einzelne Gruppe',
                'POST /api/groups.php' => 'Neue Gruppe erstellen',
                'PUT /api/groups.php?id=1' => 'Gruppe aktualisieren',
                'DELETE /api/groups.php?id=1' => 'Gruppe löschen'
            ]
        ],
        'participants' => [
            'url' => '/api/participants.php',
            'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'description' => 'Teilnehmer verwalten',
            'examples' => [
                'GET /api/participants.php?group_id=1' => 'Teilnehmer einer Gruppe',
                'GET /api/participants.php?token=xxx' => 'Teilnehmer per Token',
                'POST /api/participants.php' => 'Neuen Teilnehmer erstellen',
                'PUT /api/participants.php?id=1' => 'Teilnehmer aktualisieren',
                'DELETE /api/participants.php?id=1' => 'Teilnehmer löschen'
            ]
        ],
        'draw' => [
            'url' => '/api/draw.php',
            'methods' => ['POST'],
            'description' => 'Auslosung durchführen oder zurücksetzen',
            'examples' => [
                'POST /api/draw.php' => 'Auslosung durchführen',
                'POST /api/draw.php?action=reset' => 'Auslosung zurücksetzen'
            ]
        ],
        'exclusions' => [
            'url' => '/api/exclusions.php',
            'methods' => ['GET', 'POST', 'DELETE'],
            'description' => 'Ausschlüsse verwalten',
            'examples' => [
                'GET /api/exclusions.php?group_id=1' => 'Ausschlüsse einer Gruppe',
                'POST /api/exclusions.php' => 'Neuen Ausschluss erstellen',
                'DELETE /api/exclusions.php?id=1' => 'Ausschluss löschen'
            ]
        ]
    ];
    
    $info = [
        'name' => 'Wichtlä.ch API',
        'version' => API_VERSION,
        'status' => API_ENABLED ? 'online' : 'offline',
        'base_url' => get_display_url('/api/'),
        'documentation' => get_display_url('/api/README.md'),
        'authentication' => [
            'type' => 'Bearer Token',
            'header' => 'Authorization: Bearer YOUR_TOKEN',
            'alternative' => 'X-API-Token: YOUR_TOKEN'
        ],
        'rate_limit' => [
            'requests_per_minute' => API_RATE_LIMIT,
            'policy' => 'Per IP address'
        ],
        'response_format' => API_DEFAULT_FORMAT,
        'endpoints' => $endpoints,
        'support' => [
            'documentation' => get_display_url('/api/README.md'),
            'github' => 'https://github.com/piroh1990/wichteln',
            'email' => 'support@wichtlä.ch'
        ]
    ];
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Wenn nicht GET, dann Authentifizierung erforderlich
authenticate_api();
api_response(200, true, 'API ist aktiv', [
    'version' => API_VERSION,
    'status' => 'online'
]);
