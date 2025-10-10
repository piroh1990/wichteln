# 📱 API Quick Start Guide

Schnellstart-Anleitung für die Wichtlä.ch API

## 🚀 Setup in 5 Minuten

### 1. API-Token generieren

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Kopiere den generierten Token.

### 2. API-Konfiguration erstellen

```bash
cd api/
cp config.example.php config.php
```

Bearbeite `api/config.php` und setze deinen Token:

```php
define('API_TOKEN', 'DEIN_GENERIERTER_TOKEN_HIER');
```

### 3. API testen

```bash
# API-Info abrufen (ohne Auth)
curl https://wichtlä.ch/api/

# Gruppen abrufen (mit Auth)
curl -H "Authorization: Bearer DEIN_TOKEN" \
     https://wichtlä.ch/api/groups.php
```

## 📖 API-Übersicht

### Basis-URL
```
https://wichtlä.ch/api/
```

### Authentifizierung
```http
Authorization: Bearer YOUR_API_TOKEN
```

### Verfügbare Endpoints

| Endpoint | Methoden | Beschreibung |
|----------|----------|--------------|
| `/api/groups.php` | GET, POST, PUT, DELETE | Gruppen verwalten |
| `/api/participants.php` | GET, POST, PUT, DELETE | Teilnehmer verwalten |
| `/api/draw.php` | POST | Auslosung durchführen/zurücksetzen |
| `/api/exclusions.php` | GET, POST, DELETE | Ausschlüsse verwalten |

## 💻 Beispiel-Code

### Android (Kotlin)

```kotlin
// Retrofit Interface
interface WichtelApi {
    @GET("groups.php")
    suspend fun getGroups(
        @Header("Authorization") token: String
    ): ApiResponse<List<Group>>
}

// Verwendung
val api = retrofit.create(WichtelApi::class.java)
val response = api.getGroups("Bearer $API_TOKEN")
```

### JavaScript

```javascript
const response = await fetch('https://wichtlä.ch/api/groups.php', {
  headers: {
    'Authorization': 'Bearer YOUR_TOKEN'
  }
});
const data = await response.json();
console.log(data);
```

### cURL

```bash
# Gruppe erstellen
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Weihnachten 2025",
    "admin_email": "admin@example.com",
    "budget": 25.00
  }' \
  https://wichtlä.ch/api/groups.php
```

## 📊 Response Format

Alle Responses sind JSON:

```json
{
  "success": true,
  "message": "Beschreibung",
  "data": {...},
  "meta": {
    "timestamp": 1696939200,
    "version": "v1"
  }
}
```

## 🔒 Sicherheit

- ✅ Immer HTTPS verwenden
- ✅ Token sicher speichern (nicht im Code)
- ✅ Rate Limit beachten (60 req/min)
- ✅ Input validieren
- ✅ Fehler-Handling implementieren

## 📚 Vollständige Dokumentation

Siehe [`api/README.md`](api/README.md) für:
- Detaillierte Endpoint-Beschreibungen
- Request/Response-Beispiele
- Error Codes
- Best Practices
- Android-Integration
- Testing

## 🛠️ Entwicklung

### Debug-Modus aktivieren

In `api/config.php`:
```php
define('API_DEBUG', true);
```

Dann kannst du Debug-Infos abrufen:
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
     "https://wichtlä.ch/api/groups.php?debug=1"
```

### Logs anzeigen

```bash
tail -f logs/api.log
```

## ⚡ Rate Limiting

- **Limit:** 60 Anfragen pro Minute
- **Basis:** Pro IP-Adresse
- **Response:** 429 Too Many Requests bei Überschreitung

## 🆘 Fehlerbehebung

### 401 Unauthorized
- Token prüfen
- Authorization Header korrekt?

### 429 Too Many Requests
- Rate Limit erreicht
- 1 Minute warten

### 500 Internal Server Error
- Logs prüfen: `logs/api.log`
- PHP-Fehler prüfen

## 📞 Support

- **Dokumentation:** [`api/README.md`](api/README.md)
- **GitHub Issues:** [https://github.com/piroh1990/wichteln/issues](https://github.com/piroh1990/wichteln/issues)
- **E-Mail:** support@wichtlä.ch

---

**Happy Coding! 🎁**
