# Wichtlä.ch API Documentation

REST API für die Wichtlä.ch Android-App und andere Clients.

## 🔐 Authentifizierung

Die API verwendet Token-basierte Authentifizierung. Jeder Request muss einen gültigen API-Token enthalten.

### Token-Übergabe

**Option 1: Authorization Header (empfohlen)**
```http
Authorization: Bearer YOUR_API_TOKEN
```

**Option 2: X-API-Token Header**
```http
X-API-Token: YOUR_API_TOKEN
```

**Option 3: Query/Body Parameter (nur für Entwicklung)**
```http
?api_token=YOUR_API_TOKEN
```

### Token konfigurieren

1. Generiere einen sicheren Token:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

2. Setze in `config.php`:
```php
define('API_TOKEN', 'dein_generierter_token');
```

## 📋 Base URL

```
https://wichtlä.ch/api/
```

## 🎯 Endpoints

### Groups (Gruppen)

#### Liste aller Gruppen
```http
GET /api/groups.php
```

**Query Parameters:**
- `page` (optional): Seitennummer (default: 1)
- `per_page` (optional): Einträge pro Seite (default: 20, max: 100)

**Response:**
```json
{
  "success": true,
  "message": "Gruppen erfolgreich abgerufen",
  "data": [
    {
      "id": 1,
      "name": "Wichtelgruppe 2025",
      "budget": "25.00",
      "description": "Firmen-Wichteln",
      "gift_exchange_date": "2025-12-24",
      "is_drawn": 0,
      "created_at": "2025-10-10 12:00:00"
    }
  ],
  "meta": {
    "timestamp": 1696939200,
    "version": "v1",
    "current_page": 1,
    "per_page": 20,
    "total": 5,
    "total_pages": 1,
    "has_more": false
  }
}
```

#### Einzelne Gruppe abrufen
```http
GET /api/groups.php?id=1
GET /api/groups.php?admin_token=xxx
GET /api/groups.php?invite_token=xxx
```

**Response:**
```json
{
  "success": true,
  "message": "Gruppe erfolgreich abgerufen",
  "data": {
    "id": 1,
    "name": "Wichtelgruppe 2025",
    "admin_token": "...",
    "invite_token": "...",
    "admin_email": "admin@example.com",
    "budget": "25.00",
    "description": "Firmen-Wichteln",
    "gift_exchange_date": "2025-12-24",
    "is_drawn": 0,
    "created_at": "2025-10-10 12:00:00",
    "participants": [...],
    "exclusions": [...]
  }
}
```

#### Neue Gruppe erstellen
```http
POST /api/groups.php
Content-Type: application/json

{
  "name": "Wichtelgruppe 2025",
  "admin_email": "admin@example.com",
  "budget": 25.00,
  "description": "Firmen-Wichteln",
  "gift_exchange_date": "2025-12-24"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Gruppe erfolgreich erstellt",
  "data": {
    "id": 1,
    "name": "Wichtelgruppe 2025",
    "admin_token": "...",
    "invite_token": "...",
    "admin_link": "https://wichtlä.ch/admin.php?token=...",
    "invite_link": "https://wichtlä.ch/register.php?token=..."
  }
}
```

#### Gruppe aktualisieren
```http
PUT /api/groups.php?id=1
Content-Type: application/json

{
  "budget": 30.00,
  "description": "Neuer Text",
  "gift_exchange_date": "2025-12-25"
}
```

**Erlaubte Felder:**
- `budget` (optional): Neues Budget (numerisch)
- `description` (optional): Neue Beschreibung
- `gift_exchange_date` (optional): Neues Datum (Format: YYYY-MM-DD)

**Response:**
```json
{
  "success": true,
  "message": "Gruppe erfolgreich aktualisiert",
  "data": {
    "id": 1,
    "name": "Wichtelgruppe 2025",
    "budget": "30.00",
    "description": "Neuer Text",
    "gift_exchange_date": "2025-12-25",
    "is_drawn": 0,
    "created_at": "2025-10-10 12:00:00"
  }
}
```

**Error Responses:**
```json
{
  "success": false,
  "message": "Gruppe nicht gefunden",
  "data": null
}
```

#### Gruppe löschen
```http
DELETE /api/groups.php?id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Gruppe und alle zugehörigen Daten wurden gelöscht",
  "data": {
    "deleted_group_id": 1,
    "deleted_participants": 5,
    "deleted_exclusions": 2
  }
}
```

**Hinweis:** Löscht auch alle Teilnehmer und Ausschlüsse der Gruppe (CASCADE DELETE).

---

### Participants (Teilnehmer)

#### Liste aller Teilnehmer
```http
GET /api/participants.php
GET /api/participants.php?group_id=1
```

#### Einzelnen Teilnehmer abrufen
```http
GET /api/participants.php?id=1
GET /api/participants.php?token=xxx
```

**Response:**
```json
{
  "success": true,
  "message": "Teilnehmer erfolgreich abgerufen",
  "data": {
    "id": 1,
    "group_id": 1,
    "name": "Max Mustermann",
    "email": "max@example.com",
    "token": "...",
    "assigned_to": 2,
    "wishlist": "Bücher, Schokolade",
    "created_at": "2025-10-10 12:00:00",
    "assigned_partner": {
      "id": 2,
      "name": "Anna Beispiel",
      "wishlist": "Tee, Kerzen"
    },
    "group": {
      "name": "Wichtelgruppe 2025",
      "budget": "25.00",
      "description": "...",
      "gift_exchange_date": "2025-12-24",
      "is_drawn": 1
    }
  }
}
```

#### Neuen Teilnehmer erstellen
```http
POST /api/participants.php
Content-Type: application/json

{
  "group_id": 1,
  "name": "Max Mustermann",
  "email": "max@example.com",
  "wishlist": "Bücher, Schokolade"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Teilnehmer erfolgreich erstellt",
  "data": {
    "id": 1,
    "name": "Max Mustermann",
    "token": "...",
    "participant_link": "https://wichtlä.ch/participant.php?token=..."
  }
}
```

#### Teilnehmer aktualisieren
```http
PUT /api/participants.php?id=1
PUT /api/participants.php?token=xxx
Content-Type: application/json

{
  "wishlist": "Neue Wunschliste"
}
```

**Erlaubte Felder (VOR der Auslosung):**
- `name` (optional): Neuer Name
- `email` (optional): Neue E-Mail
- `wishlist` (optional): Neue Wunschliste

**Erlaubte Felder (NACH der Auslosung):**
- `wishlist` (optional): Nur noch Wunschliste änderbar

**Response:**
```json
{
  "success": true,
  "message": "Teilnehmer erfolgreich aktualisiert",
  "data": {
    "id": 1,
    "name": "Max Mustermann",
    "email": "max@example.com",
    "wishlist": "Neue Wunschliste",
    "token": "..."
  }
}
```

**Error Responses:**
```json
{
  "success": false,
  "message": "Nach der Auslosung kann nur noch die Wunschliste geändert werden",
  "data": null
}
```

#### Teilnehmer löschen
```http
DELETE /api/participants.php?id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Teilnehmer erfolgreich gelöscht",
  "data": {
    "deleted_participant_id": 1
  }
}
```

**Error Responses:**
```json
{
  "success": false,
  "message": "Teilnehmer kann nach der Auslosung nicht gelöscht werden",
  "data": null
}
```

**Hinweis:** Löscht auch alle Ausschlüsse, die diesen Teilnehmer betreffen.

---

### Draw (Auslosung)

#### Auslosung durchführen
```http
POST /api/draw.php
Content-Type: application/json

{
  "group_id": 1,
  "send_emails": true
}
```

**Response:**
```json
{
  "success": true,
  "message": "Auslosung erfolgreich durchgeführt",
  "data": {
    "group_id": 1,
    "is_drawn": true,
    "participants_count": 10,
    "attempts_needed": 3,
    "emails_sent": 8,
    "assignments": [
      {
        "giver_id": 1,
        "receiver_id": 3
      }
    ]
  }
}
```

#### Auslosung zurücksetzen
```http
POST /api/draw.php?action=reset
Content-Type: application/json

{
  "group_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Auslosung erfolgreich zurückgesetzt",
  "data": {
    "group_id": 1,
    "is_drawn": false,
    "reset_participants": 10
  }
}
```

**Hinweis:** Setzt `assigned_to` für alle Teilnehmer auf NULL und `is_drawn` der Gruppe auf 0.

**Error Responses:**
```json
{
  "success": false,
  "message": "Für diese Gruppe wurde noch keine Auslosung durchgeführt",
  "data": null
}
```

---

### Exclusions (Ausschlüsse)

#### Alle Ausschlüsse einer Gruppe
```http
GET /api/exclusions.php?group_id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Ausschlüsse erfolgreich abgerufen",
  "data": [
    {
      "id": 1,
      "participant_id": 1,
      "excluded_participant_id": 2,
      "participant_name": "Max Mustermann",
      "excluded_name": "Anna Beispiel",
      "created_at": "2025-10-10 12:00:00"
    }
  ]
}
```

#### Neuen Ausschluss erstellen
```http
POST /api/exclusions.php
Content-Type: application/json

{
  "group_id": 1,
  "participant_id": 1,
  "excluded_participant_id": 2
}
```

**Validierungen:**
- Beide Teilnehmer müssen in der gleichen Gruppe sein
- Keine Selbst-Ausschlüsse (participant_id ≠ excluded_participant_id)
- Nur vor der Auslosung möglich

**Response:**
```json
{
  "success": true,
  "message": "Ausschluss erfolgreich erstellt",
  "data": {
    "id": 1,
    "participant_id": 1,
    "excluded_participant_id": 2,
    "participant_name": "Max Mustermann",
    "excluded_name": "Anna Beispiel",
    "created_at": "2025-10-10 12:00:00"
  }
}
```

**Error Responses:**
```json
{
  "success": false,
  "message": "Ausschlüsse können nach der Auslosung nicht mehr erstellt werden",
  "data": null
}
```

```json
{
  "success": false,
  "message": "Ein Teilnehmer kann sich nicht selbst ausschließen",
  "data": null
}
```

#### Ausschluss löschen
```http
DELETE /api/exclusions.php?id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Ausschluss erfolgreich gelöscht",
  "data": {
    "deleted_exclusion_id": 1
  }
}
```

**Error Responses:**
```json
{
  "success": false,
  "message": "Ausschlüsse können nach der Auslosung nicht gelöscht werden",
  "data": null
}
```

**Hinweis:** Nur vor der Auslosung möglich.

---

## 📊 Response Format

Alle Responses folgen diesem Format:

```json
{
  "success": true|false,
  "message": "Beschreibung",
  "data": {...}|[...]|null,
  "meta": {
    "timestamp": 1696939200,
    "version": "v1",
    ...
  }
}
```

## ⚠️ HTTP Status Codes

- `200 OK` - Erfolgreiche Anfrage
- `201 Created` - Ressource erfolgreich erstellt
- `400 Bad Request` - Ungültige Anfrage
- `401 Unauthorized` - Ungültiges oder fehlendes Token
- `404 Not Found` - Ressource nicht gefunden
- `405 Method Not Allowed` - HTTP-Methode nicht erlaubt
- `429 Too Many Requests` - Rate Limit überschritten
- `500 Internal Server Error` - Server-Fehler
- `503 Service Unavailable` - API deaktiviert

## 🚦 Rate Limiting

- **Limit:** 60 Anfragen pro Minute pro IP
- **Header bei Überschreitung:** `429 Too Many Requests`

## 🔧 CORS (Cross-Origin Resource Sharing)

Die API unterstützt CORS für Cross-Domain-Anfragen.

**Konfiguration in `api/config.php`:**
```php
define('API_ALLOW_ORIGIN', '*'); // Oder spezifische Domain
define('API_ALLOW_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('API_ALLOW_HEADERS', 'Content-Type, Authorization, X-API-Token');
```

## 📝 Logging

Alle API-Anfragen werden in `logs/api.log` protokolliert:

```
[2025-10-10 12:00:00] 192.168.1.1 GET /api/groups.php - SUCCESS
[2025-10-10 12:01:00] 192.168.1.1 POST /api/draw.php - SUCCESS Draw completed
[2025-10-10 12:02:00] 192.168.1.2 GET /api/groups.php - UNAUTHORIZED
```

## 🐛 Debug-Modus

Für Entwicklung aktiviere Debug-Modus in `api/config.php`:

```php
define('API_DEBUG', true);
```

Dann kannst du zusätzliche Debug-Infos abrufen:

```http
GET /api/groups.php?debug=1
```

**Response mit Debug-Info:**
```json
{
  "success": true,
  "message": "...",
  "data": {...},
  "meta": {...},
  "debug": {
    "request_method": "GET",
    "request_uri": "/api/groups.php",
    "php_version": "8.1.0",
    "execution_time": "0.023s"
  }
}
```

## 🔒 Sicherheit

### Best Practices:

1. **Immer HTTPS verwenden** in Produktion
2. **Token sicher speichern** - Niemals im Client-Code
3. **Rate Limiting beachten**
4. **Input validieren** - API validiert bereits, aber zusätzliche Client-Validierung empfohlen
5. **Fehler-Handling** - Alle API-Calls mit try-catch umgeben

### Beispiel (Android/Kotlin):

```kotlin
val client = OkHttpClient()

val request = Request.Builder()
    .url("https://wichtlä.ch/api/groups.php")
    .addHeader("Authorization", "Bearer YOUR_API_TOKEN")
    .build()

try {
    val response = client.newCall(request).execute()
    if (response.isSuccessful) {
        val json = response.body?.string()
        // JSON parsen
    } else {
        // Fehler-Handling
    }
} catch (e: Exception) {
    // Netzwerk-Fehler
}
```

### Beispiel (JavaScript):

```javascript
const response = await fetch('https://wichtlä.ch/api/groups.php', {
  headers: {
    'Authorization': 'Bearer YOUR_API_TOKEN',
    'Content-Type': 'application/json'
  }
});

const data = await response.json();

if (data.success) {
  console.log(data.data);
} else {
  console.error(data.message);
}
```

## 📱 Android-Integration

### Empfohlene Bibliotheken:

- **Networking:** Retrofit 2 oder OkHttp
- **JSON:** Gson oder Moshi
- **Coroutines:** Kotlin Coroutines für asynchrone Calls

### Beispiel mit Retrofit:

```kotlin
interface WichtelApi {
    @GET("groups.php")
    suspend fun getGroups(
        @Header("Authorization") token: String,
        @Query("page") page: Int = 1
    ): ApiResponse<List<Group>>
    
    @POST("groups.php")
    suspend fun createGroup(
        @Header("Authorization") token: String,
        @Body group: CreateGroupRequest
    ): ApiResponse<Group>
}
```

## 🧪 Testing

### Im Browser testen

Die API kann direkt im Browser getestet werden, indem das Token als Query-Parameter übergeben wird:

#### 1. API Info abrufen (kein Token nötig)
```
https://wichtlä.ch/api/
```
Zeigt alle verfügbaren Endpoints und Informationen zur API.

#### 2. Gruppen abrufen (GET)
```
https://wichtlä.ch/api/groups.php?api_token=YOUR_TOKEN
```

#### 3. Einzelne Gruppe mit Details
```
https://wichtlä.ch/api/groups.php?id=1&api_token=YOUR_TOKEN
https://wichtlä.ch/api/groups.php?admin_token=ADMIN_TOKEN&api_token=YOUR_TOKEN
```

#### 4. Teilnehmer einer Gruppe
```
https://wichtlä.ch/api/participants.php?group_id=1&api_token=YOUR_TOKEN
```

#### 5. Einzelnen Teilnehmer mit Details
```
https://wichtlä.ch/api/participants.php?id=1&api_token=YOUR_TOKEN
https://wichtlä.ch/api/participants.php?token=PARTICIPANT_TOKEN&api_token=YOUR_TOKEN
```

#### 6. Ausschlüsse einer Gruppe
```
https://wichtlä.ch/api/exclusions.php?group_id=1&api_token=YOUR_TOKEN
```

**Hinweis:** POST/PUT/DELETE Requests können im Browser nicht direkt getestet werden. Dafür empfehlen wir:
- Postman (siehe `Wichtel_API.postman_collection.json`)
- Browser DevTools Console (siehe JavaScript-Beispiele unten)
- REST Client Extensions (z.B. für VS Code)

### Browser Console (JavaScript)

Öffne die Browser DevTools (F12) und teste POST-Requests:

```javascript
// Gruppe erstellen
fetch('https://wichtlä.ch/api/groups.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Test Gruppe',
    admin_email: 'admin@example.com',
    budget: 25.00
  })
})
.then(res => res.json())
.then(data => console.log(data));

// Teilnehmer erstellen
fetch('https://wichtlä.ch/api/participants.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    group_id: 1,
    name: 'Max Mustermann',
    email: 'max@example.com',
    wishlist: 'Bücher, Schokolade'
  })
})
.then(res => res.json())
.then(data => console.log(data));

// Auslosung durchführen
fetch('https://wichtlä.ch/api/draw.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    group_id: 1,
    send_emails: true
  })
})
.then(res => res.json())
.then(data => console.log(data));
```

### cURL-Beispiele:

```bash
# Gruppe abrufen
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://wichtlä.ch/api/groups.php?id=1

# Gruppe erstellen
curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"name":"Test Gruppe","admin_email":"test@example.com"}' \
     https://wichtlä.ch/api/groups.php

# Auslosung durchführen
curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"group_id":1,"send_emails":true}' \
     https://wichtlä.ch/api/draw.php
```

### Postman Collection

Importiere die `Wichtel_API.postman_collection.json` in Postman für einfaches Testen aller Endpoints:

1. Öffne Postman
2. Import → File → Wähle `api/Wichtel_API.postman_collection.json`
3. Setze die Variable `API_TOKEN` auf deinen generierten Token
4. Teste alle Endpoints mit einem Klick

## 📚 Weitere Ressourcen

- **Postman Collection:** `api/Wichtel_API.postman_collection.json`
- **OpenAPI/Swagger Spec:** `api/openapi.yaml` - Importiere in Swagger UI oder Postman
- **Quick Start Guide:** `API_QUICK_START.md`
- **GitHub Repository:** https://github.com/piroh1990/wichteln

### OpenAPI Spec verwenden

Die `openapi.yaml` Datei kann in verschiedenen Tools verwendet werden:

**Swagger UI (Online):**
1. Gehe zu https://editor.swagger.io/
2. Import → `api/openapi.yaml`
3. Teste die API direkt im Browser

**Postman:**
1. Import → OpenAPI 3.0 → `api/openapi.yaml`
2. Automatisch generierte Collection mit allen Endpoints

**VS Code:**
1. Extension installieren: "Swagger Viewer"
2. Öffne `openapi.yaml` und drücke `Shift+Alt+P`
3. Preview anzeigen

---

## 📖 Erweiterte Beispiele

### Kompletter Workflow: Gruppe erstellen bis Auslosung

```javascript
// 1. Gruppe erstellen
const createGroupResponse = await fetch('https://wichtlä.ch/api/groups.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Firmen Wichteln 2025',
    admin_email: 'admin@firma.ch',
    budget: 30.00,
    description: 'Weihnachts-Wichteln der IT-Abteilung',
    gift_exchange_date: '2025-12-20'
  })
});

const group = await createGroupResponse.json();
console.log('Gruppe erstellt:', group.data);
// group.data.id = 1
// group.data.invite_link = "https://wichtlä.ch/register.php?token=xxx"

// 2. Teilnehmer hinzufügen (mehrere)
const participants = [
  { name: 'Anna Müller', email: 'anna@firma.ch', wishlist: 'Bücher, Tee' },
  { name: 'Max Meier', email: 'max@firma.ch', wishlist: 'Schokolade, Kaffee' },
  { name: 'Lisa Schmidt', email: 'lisa@firma.ch', wishlist: 'Pflanzen, Kerzen' }
];

for (const participant of participants) {
  await fetch('https://wichtlä.ch/api/participants.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer YOUR_TOKEN',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      group_id: group.data.id,
      ...participant
    })
  });
}

// 3. Ausschlüsse erstellen (Anna und Max sollen sich nicht beschenken)
await fetch('https://wichtlä.ch/api/exclusions.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    group_id: group.data.id,
    participant_id: 1, // Anna
    excluded_participant_id: 2 // Max
  })
});

// 4. Auslosung durchführen
const drawResponse = await fetch('https://wichtlä.ch/api/draw.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    group_id: group.data.id,
    send_emails: true
  })
});

const drawResult = await drawResponse.json();
console.log('Auslosung erfolgreich:', drawResult.data);
```

### Android Retrofit Komplettbeispiel

```kotlin
// 1. Dependencies in build.gradle
dependencies {
    implementation("com.squareup.retrofit2:retrofit:2.9.0")
    implementation("com.squareup.retrofit2:converter-gson:2.9.0")
    implementation("com.squareup.okhttp3:okhttp:4.11.0")
    implementation("com.squareup.okhttp3:logging-interceptor:4.11.0")
    implementation("org.jetbrains.kotlinx:kotlinx-coroutines-android:1.7.1")
}

// 2. Data Models
data class ApiResponse<T>(
    val success: Boolean,
    val message: String,
    val data: T?,
    val meta: Meta?
)

data class Meta(
    val timestamp: Long,
    val version: String,
    val current_page: Int? = null,
    val per_page: Int? = null,
    val total: Int? = null,
    val total_pages: Int? = null,
    val has_more: Boolean? = null
)

data class Group(
    val id: Int,
    val name: String,
    val admin_token: String? = null,
    val invite_token: String? = null,
    val admin_email: String? = null,
    val budget: String,
    val description: String?,
    val gift_exchange_date: String,
    val is_drawn: Int,
    val created_at: String,
    val participants: List<Participant>? = null,
    val exclusions: List<Exclusion>? = null
)

data class Participant(
    val id: Int,
    val group_id: Int,
    val name: String,
    val email: String,
    val token: String? = null,
    val assigned_to: Int?,
    val wishlist: String?,
    val created_at: String,
    val assigned_partner: AssignedPartner? = null,
    val group: Group? = null
)

data class AssignedPartner(
    val id: Int,
    val name: String,
    val wishlist: String?
)

data class Exclusion(
    val id: Int,
    val participant_id: Int,
    val excluded_participant_id: Int,
    val participant_name: String? = null,
    val excluded_name: String? = null,
    val created_at: String
)

data class CreateGroupRequest(
    val name: String,
    val admin_email: String? = null,
    val budget: Double? = null,
    val description: String? = null,
    val gift_exchange_date: String? = null
)

data class CreateParticipantRequest(
    val group_id: Int,
    val name: String,
    val email: String,
    val wishlist: String? = null
)

data class DrawRequest(
    val group_id: Int,
    val send_emails: Boolean = true
)

// 3. API Interface
interface WichtelApi {
    @GET("groups.php")
    suspend fun getGroups(
        @Header("Authorization") token: String,
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 20
    ): ApiResponse<List<Group>>
    
    @GET("groups.php")
    suspend fun getGroup(
        @Header("Authorization") token: String,
        @Query("id") id: Int? = null,
        @Query("admin_token") adminToken: String? = null,
        @Query("invite_token") inviteToken: String? = null
    ): ApiResponse<Group>
    
    @POST("groups.php")
    suspend fun createGroup(
        @Header("Authorization") token: String,
        @Body request: CreateGroupRequest
    ): ApiResponse<Group>
    
    @PUT("groups.php")
    suspend fun updateGroup(
        @Header("Authorization") token: String,
        @Query("id") id: Int,
        @Body request: Map<String, Any>
    ): ApiResponse<Group>
    
    @DELETE("groups.php")
    suspend fun deleteGroup(
        @Header("Authorization") token: String,
        @Query("id") id: Int
    ): ApiResponse<Unit>
    
    @GET("participants.php")
    suspend fun getParticipants(
        @Header("Authorization") token: String,
        @Query("group_id") groupId: Int? = null,
        @Query("id") id: Int? = null,
        @Query("token") participantToken: String? = null
    ): ApiResponse<Any> // List<Participant> or Participant
    
    @POST("participants.php")
    suspend fun createParticipant(
        @Header("Authorization") token: String,
        @Body request: CreateParticipantRequest
    ): ApiResponse<Participant>
    
    @PUT("participants.php")
    suspend fun updateParticipant(
        @Header("Authorization") token: String,
        @Query("id") id: Int? = null,
        @Query("token") participantToken: String? = null,
        @Body request: Map<String, String>
    ): ApiResponse<Participant>
    
    @DELETE("participants.php")
    suspend fun deleteParticipant(
        @Header("Authorization") token: String,
        @Query("id") id: Int
    ): ApiResponse<Unit>
    
    @POST("draw.php")
    suspend fun performDraw(
        @Header("Authorization") token: String,
        @Body request: DrawRequest
    ): ApiResponse<Map<String, Any>>
    
    @POST("draw.php")
    suspend fun resetDraw(
        @Header("Authorization") token: String,
        @Query("action") action: String = "reset",
        @Body request: Map<String, Int>
    ): ApiResponse<Map<String, Any>>
    
    @GET("exclusions.php")
    suspend fun getExclusions(
        @Header("Authorization") token: String,
        @Query("group_id") groupId: Int
    ): ApiResponse<List<Exclusion>>
    
    @POST("exclusions.php")
    suspend fun createExclusion(
        @Header("Authorization") token: String,
        @Body request: Map<String, Int>
    ): ApiResponse<Exclusion>
    
    @DELETE("exclusions.php")
    suspend fun deleteExclusion(
        @Header("Authorization") token: String,
        @Query("id") id: Int
    ): ApiResponse<Unit>
}

// 4. Retrofit Setup
object ApiClient {
    private const val BASE_URL = "https://wichtlä.ch/api/"
    private const val API_TOKEN = "YOUR_API_TOKEN_HERE"
    
    private val loggingInterceptor = HttpLoggingInterceptor().apply {
        level = HttpLoggingInterceptor.Level.BODY
    }
    
    private val client = OkHttpClient.Builder()
        .addInterceptor(loggingInterceptor)
        .connectTimeout(30, TimeUnit.SECONDS)
        .readTimeout(30, TimeUnit.SECONDS)
        .writeTimeout(30, TimeUnit.SECONDS)
        .build()
    
    private val retrofit = Retrofit.Builder()
        .baseUrl(BASE_URL)
        .client(client)
        .addConverterFactory(GsonConverterFactory.create())
        .build()
    
    val api: WichtelApi = retrofit.create(WichtelApi::class.java)
    
    fun getAuthToken() = "Bearer $API_TOKEN"
}

// 5. Repository Pattern
class WichtelRepository {
    private val api = ApiClient.api
    private val authToken = ApiClient.getAuthToken()
    
    suspend fun getGroups(page: Int = 1): Result<List<Group>> = try {
        val response = api.getGroups(authToken, page)
        if (response.success && response.data != null) {
            Result.success(response.data)
        } else {
            Result.failure(Exception(response.message))
        }
    } catch (e: Exception) {
        Result.failure(e)
    }
    
    suspend fun createGroup(request: CreateGroupRequest): Result<Group> = try {
        val response = api.createGroup(authToken, request)
        if (response.success && response.data != null) {
            Result.success(response.data)
        } else {
            Result.failure(Exception(response.message))
        }
    } catch (e: Exception) {
        Result.failure(e)
    }
    
    suspend fun performDraw(groupId: Int, sendEmails: Boolean = true): Result<Map<String, Any>> = try {
        val response = api.performDraw(authToken, DrawRequest(groupId, sendEmails))
        if (response.success && response.data != null) {
            Result.success(response.data)
        } else {
            Result.failure(Exception(response.message))
        }
    } catch (e: Exception) {
        Result.failure(e)
    }
}

// 6. ViewModel Verwendung
class GroupViewModel : ViewModel() {
    private val repository = WichtelRepository()
    
    private val _groups = MutableLiveData<List<Group>>()
    val groups: LiveData<List<Group>> = _groups
    
    private val _error = MutableLiveData<String>()
    val error: LiveData<String> = _error
    
    fun loadGroups() {
        viewModelScope.launch {
            repository.getGroups().fold(
                onSuccess = { _groups.value = it },
                onFailure = { _error.value = it.message }
            )
        }
    }
    
    fun createGroup(name: String, email: String, budget: Double) {
        viewModelScope.launch {
            val request = CreateGroupRequest(
                name = name,
                admin_email = email,
                budget = budget,
                gift_exchange_date = "2025-12-24"
            )
            repository.createGroup(request).fold(
                onSuccess = { loadGroups() },
                onFailure = { _error.value = it.message }
            )
        }
    }
}
```

### Error Handling Best Practices

```kotlin
// Custom Exception Klassen
sealed class ApiException(message: String) : Exception(message) {
    class Unauthorized(message: String = "Invalid API token") : ApiException(message)
    class RateLimitExceeded(message: String = "Too many requests") : ApiException(message)
    class NotFound(message: String = "Resource not found") : ApiException(message)
    class ValidationError(message: String) : ApiException(message)
    class ServerError(message: String = "Internal server error") : ApiException(message)
}

// Response Handler
suspend fun <T> safeApiCall(apiCall: suspend () -> ApiResponse<T>): Result<T> = try {
    val response = apiCall()
    when {
        response.success && response.data != null -> Result.success(response.data)
        !response.success -> {
            val exception = when (response.message) {
                "Ungültiges oder fehlendes API-Token" -> ApiException.Unauthorized()
                "Rate Limit überschritten" -> ApiException.RateLimitExceeded()
                else -> ApiException.ValidationError(response.message)
            }
            Result.failure(exception)
        }
        else -> Result.failure(ApiException.ServerError())
    }
} catch (e: Exception) {
    Result.failure(e)
}

// Usage
suspend fun getGroupSafely(groupId: Int): Result<Group> = safeApiCall {
    api.getGroup(authToken, id = groupId)
}
```

### Offline-First mit Room Database

```kotlin
// Kombiniere API mit lokaler Datenbank
@Database(entities = [GroupEntity::class], version = 1)
abstract class WichtelDatabase : RoomDatabase() {
    abstract fun groupDao(): GroupDao
}

@Entity(tableName = "groups")
data class GroupEntity(
    @PrimaryKey val id: Int,
    val name: String,
    val budget: String,
    val description: String?,
    val giftExchangeDate: String,
    val isDrawn: Int,
    val createdAt: String,
    val lastSynced: Long = System.currentTimeMillis()
)

class OfflineFirstRepository(
    private val api: WichtelApi,
    private val dao: GroupDao
) {
    suspend fun getGroups(forceRefresh: Boolean = false): Flow<List<Group>> = flow {
        // Erst lokale Daten emittieren
        val localGroups = dao.getAllGroups()
        emit(localGroups.map { it.toGroup() })
        
        // Dann von API aktualisieren
        if (forceRefresh || shouldRefresh()) {
            try {
                val response = api.getGroups(authToken)
                if (response.success && response.data != null) {
                    // Lokal speichern
                    dao.insertAll(response.data.map { it.toEntity() })
                    // Aktualisierte Daten emittieren
                    emit(response.data)
                }
            } catch (e: Exception) {
                // Bei Fehler behalten wir die lokalen Daten
            }
        }
    }
}
```

---

**Support:** Bei Fragen: [Issues auf GitHub](https://github.com/piroh1990/wichteln/issues)
