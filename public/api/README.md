# Wichtlä.ch API Documentation

REST API for the Wichtlä.ch Android App and other clients.

## 🔐 Authentication

The API uses token-based authentication. Every request must include a valid API token.

### Passing the Token

**Option 1: Authorization Header (Recommended)**
```http
Authorization: Bearer YOUR_API_TOKEN
```

**Option 2: X-API-Token Header**
```http
X-API-Token: YOUR_API_TOKEN
```

**Option 3: Query/Body Parameter (Development only)**
```http
?api_token=YOUR_API_TOKEN
```

### Configure Token

1. Generate a secure token:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

2. Set in `includes/api_config.php`:
```php
define('API_TOKEN', 'your_generated_token');
```

## 📋 Base URL

```
https://wichtlä.ch/api/
```

## 🎯 Endpoints

### Groups

#### List all groups
```http
GET /api/groups.php
```

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `per_page` (optional): Items per page (default: 20, max: 100)

**Response:**
```json
{
  "success": true,
  "message": "Groups successfully retrieved",
  "data": [
    {
      "id": 1,
      "name": "Secret Santa 2025",
      "budget": "25.00",
      "description": "Office Party",
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

#### Get single group
```http
GET /api/groups.php?id=1
GET /api/groups.php?admin_token=xxx
GET /api/groups.php?invite_token=xxx
```

**Response:**
```json
{
  "success": true,
  "message": "Group successfully retrieved",
  "data": {
    "id": 1,
    "name": "Secret Santa 2025",
    "admin_token": "...",
    "invite_token": "...",
    "admin_email": "admin@example.com",
    "budget": "25.00",
    "description": "Office Party",
    "gift_exchange_date": "2025-12-24",
    "is_drawn": 0,
    "created_at": "2025-10-10 12:00:00",
    "participants": [...],
    "exclusions": [...]
  }
}
```

#### Create new group
```http
POST /api/groups.php
Content-Type: application/json

{
  "name": "Secret Santa 2025",
  "admin_email": "admin@example.com",
  "budget": 25.00,
  "description": "Office Party",
  "gift_exchange_date": "2025-12-24"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Group successfully created",
  "data": {
    "id": 1,
    "name": "Secret Santa 2025",
    "admin_token": "...",
    "invite_token": "...",
    "admin_link": "https://wichtlä.ch/admin.php?token=...",
    "invite_link": "https://wichtlä.ch/register.php?token=..."
  }
}
```

#### Update group
```http
PUT /api/groups.php?id=1
Content-Type: application/json

{
  "budget": 30.00,
  "description": "New text",
  "gift_exchange_date": "2025-12-25"
}
```

**Allowed Fields:**
- `budget` (optional): New budget (numeric)
- `description` (optional): New description
- `gift_exchange_date` (optional): New date (Format: YYYY-MM-DD)

**Response:**
```json
{
  "success": true,
  "message": "Group successfully updated",
  "data": {
    "id": 1,
    "name": "Secret Santa 2025",
    "budget": "30.00",
    "description": "New text",
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
  "message": "Group not found",
  "data": null
}
```

#### Delete group
```http
DELETE /api/groups.php?id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Group and all associated data deleted",
  "data": {
    "deleted_group_id": 1,
    "deleted_participants": 5,
    "deleted_exclusions": 2
  }
}
```

**Note:** Deletes all participants and exclusions of the group as well (CASCADE DELETE).

---

### Participants

#### List all participants
```http
GET /api/participants.php
GET /api/participants.php?group_id=1
```

#### Get single participant
```http
GET /api/participants.php?id=1
GET /api/participants.php?token=xxx
```

**Response:**
```json
{
  "success": true,
  "message": "Participant successfully retrieved",
  "data": {
    "id": 1,
    "group_id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "token": "...",
    "assigned_to": 2,
    "wishlist": "Books, Chocolate",
    "created_at": "2025-10-10 12:00:00",
    "assigned_partner": {
      "id": 2,
      "name": "Jane Example",
      "wishlist": "Tea, Candles"
    },
    "group": {
      "name": "Secret Santa 2025",
      "budget": "25.00",
      "description": "...",
      "gift_exchange_date": "2025-12-24",
      "is_drawn": 1
    }
  }
}
```

#### Create new participant
```http
POST /api/participants.php
Content-Type: application/json

{
  "group_id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "wishlist": "Books, Chocolate"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Participant successfully created",
  "data": {
    "id": 1,
    "name": "John Doe",
    "token": "...",
    "participant_link": "https://wichtlä.ch/participant.php?token=..."
  }
}
```

#### Update participant
```http
PUT /api/participants.php?id=1
PUT /api/participants.php?token=xxx
Content-Type: application/json

{
  "wishlist": "New Wishlist"
}
```

**Allowed Fields (BEFORE draw):**
- `name` (optional): New name
- `email` (optional): New email
- `wishlist` (optional): New wishlist

**Allowed Fields (AFTER draw):**
- `wishlist` (optional): Only wishlist can be changed

**Response:**
```json
{
  "success": true,
  "message": "Participant successfully updated",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "wishlist": "New Wishlist",
    "token": "..."
  }
}
```

**Error Responses:**
```json
{
  "success": false,
  "message": "Only wishlist can be changed after the draw",
  "data": null
}
```

#### Delete participant
```http
DELETE /api/participants.php?id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Participant successfully deleted",
  "data": {
    "deleted_participant_id": 1
  }
}
```

**Error Responses:**
```json
{
  "success": false,
  "message": "Participant cannot be deleted after the draw",
  "data": null
}
```

**Note:** Also deletes all exclusions involving this participant.

---

### Draw

#### Perform Draw
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
  "message": "Draw successfully completed",
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

#### Reset Draw
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
  "message": "Draw successfully reset",
  "data": {
    "group_id": 1,
    "is_drawn": false,
    "reset_participants": 10
  }
}
```

**Note:** Sets `assigned_to` to NULL for all participants and `is_drawn` of the group to 0.

**Error Responses:**
```json
{
  "success": false,
  "message": "No draw has been performed for this group yet",
  "data": null
}
```

---

### Exclusions

#### All exclusions for a group
```http
GET /api/exclusions.php?group_id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Exclusions successfully retrieved",
  "data": [
    {
      "id": 1,
      "participant_id": 1,
      "excluded_participant_id": 2,
      "participant_name": "John Doe",
      "excluded_name": "Jane Example",
      "created_at": "2025-10-10 12:00:00"
    }
  ]
}
```

#### Create new exclusion
```http
POST /api/exclusions.php
Content-Type: application/json

{
  "group_id": 1,
  "participant_id": 1,
  "excluded_participant_id": 2
}
```

**Validations:**
- Both participants must be in the same group
- No self-exclusions (participant_id ≠ excluded_participant_id)
- Only possible before the draw

**Response:**
```json
{
  "success": true,
  "message": "Exclusion successfully created",
  "data": {
    "id": 1,
    "participant_id": 1,
    "excluded_participant_id": 2,
    "participant_name": "John Doe",
    "excluded_name": "Jane Example",
    "created_at": "2025-10-10 12:00:00"
  }
}
```

**Error Responses:**
```json
{
  "success": false,
  "message": "Exclusions cannot be created after the draw",
  "data": null
}
```

```json
{
  "success": false,
  "message": "A participant cannot exclude themselves",
  "data": null
}
```

#### Delete exclusion
```http
DELETE /api/exclusions.php?id=1
```

**Response:**
```json
{
  "success": true,
  "message": "Exclusion successfully deleted",
  "data": {
    "deleted_exclusion_id": 1
  }
}
```

**Error Responses:**
```json
{
  "success": false,
  "message": "Exclusions cannot be deleted after the draw",
  "data": null
}
```

**Note:** Only possible before the draw.

---

## 📊 Response Format

All responses follow this format:

```json
{
  "success": true|false,
  "message": "Description",
  "data": {...}|[...]|null,
  "meta": {
    "timestamp": 1696939200,
    "version": "v1",
    ...
  }
}
```

## ⚠️ HTTP Status Codes

- `200 OK` - Successful request
- `201 Created` - Resource successfully created
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Invalid or missing token
- `404 Not Found` - Resource not found
- `405 Method Not Allowed` - HTTP method not allowed
- `429 Too Many Requests` - Rate limit exceeded
- `500 Internal Server Error` - Server error
- `503 Service Unavailable` - API disabled

## 🚦 Rate Limiting

- **Limit:** 60 requests per minute per IP
- **Header on exceeded:** `429 Too Many Requests`

## 🔧 CORS (Cross-Origin Resource Sharing)

The API supports CORS for cross-domain requests.

**Configuration in `includes/api_config.php`:**
```php
define('API_ALLOW_ORIGIN', '*'); // Or specific domain
define('API_ALLOW_METHODS', 'GET, POST, PUT, DELETE, OPTIONS');
define('API_ALLOW_HEADERS', 'Content-Type, Authorization, X-API-Token');
```

## 📝 Logging

All API requests are logged in `logs/api.log`:

```
[2025-10-10 12:00:00] 192.168.1.1 GET /api/groups.php - SUCCESS
[2025-10-10 12:01:00] 192.168.1.1 POST /api/draw.php - SUCCESS Draw completed
[2025-10-10 12:02:00] 192.168.1.2 GET /api/groups.php - UNAUTHORIZED
```

## 🐛 Debug Mode

Enable debug mode in `includes/api_config.php` for development:

```php
define('API_DEBUG', true);
```

Then you can request additional debug info:

```http
GET /api/groups.php?debug=1
```

**Response with Debug Info:**
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

## 🔒 Security

### Best Practices:

1. **Always use HTTPS** in production
2. **Store tokens securely** - Never in client code
3. **Respect Rate Limiting**
4. **Validate Input** - API validates already, but additional client validation is recommended
5. **Error Handling** - Wrap all API calls in try-catch blocks

### Example (Android/Kotlin):

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
        // Parse JSON
    } else {
        // Error Handling
    }
} catch (e: Exception) {
    // Network Error
}
```

### Example (JavaScript):

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

## 📱 Android Integration

### Recommended Libraries:

- **Networking:** Retrofit 2 or OkHttp
- **JSON:** Gson or Moshi
- **Coroutines:** Kotlin Coroutines for asynchronous calls

### Example with Retrofit:

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

### Test in Browser

The API can be tested directly in the browser by passing the token as a query parameter:

#### 1. Get API Info (no token needed)
```
https://wichtlä.ch/api/
```
Shows all available endpoints and API information.

#### 2. Get Groups (GET)
```
https://wichtlä.ch/api/groups.php?api_token=YOUR_TOKEN
```

#### 3. Single Group with Details
```
https://wichtlä.ch/api/groups.php?id=1&api_token=YOUR_TOKEN
https://wichtlä.ch/api/groups.php?admin_token=ADMIN_TOKEN&api_token=YOUR_TOKEN
```

#### 4. Participants of a Group
```
https://wichtlä.ch/api/participants.php?group_id=1&api_token=YOUR_TOKEN
```

#### 5. Single Participant with Details
```
https://wichtlä.ch/api/participants.php?id=1&api_token=YOUR_TOKEN
https://wichtlä.ch/api/participants.php?token=PARTICIPANT_TOKEN&api_token=YOUR_TOKEN
```

#### 6. Exclusions of a Group
```
https://wichtlä.ch/api/exclusions.php?group_id=1&api_token=YOUR_TOKEN
```

**Note:** POST/PUT/DELETE requests cannot be tested directly in the browser. We recommend:
- Postman (see `Wichtel_API.postman_collection.json`)
- Browser DevTools Console (see JavaScript examples below)
- REST Client Extensions (e.g., for VS Code)

### Browser Console (JavaScript)

Open Browser DevTools (F12) and test POST requests:

```javascript
// Create Group
fetch('https://wichtlä.ch/api/groups.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Test Group',
    admin_email: 'admin@example.com',
    budget: 25.00
  })
})
.then(res => res.json())
.then(data => console.log(data));

// Create Participant
fetch('https://wichtlä.ch/api/participants.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    group_id: 1,
    name: 'John Doe',
    email: 'john@example.com',
    wishlist: 'Books, Chocolate'
  })
})
.then(res => res.json())
.then(data => console.log(data));

// Perform Draw
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

### cURL Examples:

```bash
# Get Group
curl -H "Authorization: Bearer YOUR_TOKEN" \
     https://wichtlä.ch/api/groups.php?id=1

# Create Group
curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"name":"Test Group","admin_email":"test@example.com"}' \
     https://wichtlä.ch/api/groups.php

# Perform Draw
curl -X POST \
     -H "Authorization: Bearer YOUR_TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"group_id":1,"send_emails":true}' \
     https://wichtlä.ch/api/draw.php
```

### Postman Collection

Import `Wichtel_API.postman_collection.json` into Postman for easy testing of all endpoints:

1. Open Postman
2. Import → File → Select `api/Wichtel_API.postman_collection.json`
3. Set the variable `API_TOKEN` to your generated token
4. Test all endpoints with one click

## 📚 Further Resources

- **Postman Collection:** `api/Wichtel_API.postman_collection.json`
- **OpenAPI/Swagger Spec:** `api/openapi.yaml` - Import into Swagger UI or Postman
- **Quick Start Guide:** `API_QUICK_START.md`
- **GitHub Repository:** https://github.com/piroh1990/wichteln

### Using OpenAPI Spec

The `openapi.yaml` file can be used in various tools:

**Swagger UI (Online):**
1. Go to https://editor.swagger.io/
2. Import → `api/openapi.yaml`
3. Test the API directly in the browser

**Postman:**
1. Import → OpenAPI 3.0 → `api/openapi.yaml`
2. Automatically generated collection with all endpoints

**VS Code:**
1. Install extension: "Swagger Viewer"
2. Open `openapi.yaml` and press `Shift+Alt+P`
3. View Preview

---

## 📖 Extended Examples

### Complete Workflow: Create Group to Draw

```javascript
// 1. Create Group
const createGroupResponse = await fetch('https://wichtlä.ch/api/groups.php', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    name: 'Company Secret Santa 2025',
    admin_email: 'admin@company.com',
    budget: 30.00,
    description: 'IT Department Christmas Secret Santa',
    gift_exchange_date: '2025-12-20'
  })
});

const group = await createGroupResponse.json();
console.log('Group created:', group.data);
// group.data.id = 1
// group.data.invite_link = "https://wichtlä.ch/register.php?token=xxx"

// 2. Add Participants (multiple)
const participants = [
  { name: 'Anna Miller', email: 'anna@company.com', wishlist: 'Books, Tea' },
  { name: 'Max Meyer', email: 'max@company.com', wishlist: 'Chocolate, Coffee' },
  { name: 'Lisa Smith', email: 'lisa@company.com', wishlist: 'Plants, Candles' }
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

// 3. Create Exclusions (Anna and Max should not gift each other)
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

// 4. Perform Draw
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
console.log('Draw successful:', drawResult.data);
```

### Android Retrofit Complete Example

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

// 6. ViewModel Usage
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
// Custom Exception Classes
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
                "Invalid or missing API token" -> ApiException.Unauthorized()
                "Rate Limit exceeded" -> ApiException.RateLimitExceeded()
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

### Offline-First with Room Database

```kotlin
// Combine API with local database
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
        // Emit local data first
        val localGroups = dao.getAllGroups()
        emit(localGroups.map { it.toGroup() })
        
        // Then update from API
        if (forceRefresh || shouldRefresh()) {
            try {
                val response = api.getGroups(authToken)
                if (response.success && response.data != null) {
                    // Save locally
                    dao.insertAll(response.data.map { it.toEntity() })
                    // Emit updated data
                    emit(response.data)
                }
            } catch (e: Exception) {
                // On error, keep local data
            }
        }
    }
}
```

---

**Support:** If you have questions: [Issues on GitHub](https://github.com/piroh1990/wichteln/issues)
