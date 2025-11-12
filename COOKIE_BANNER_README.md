# Cookie Banner - Anleitung 🍪

## Was ist das?

Ein einfacher, schöner Cookie-Banner für die Wichteln-Website. Er:
- ✅ Informiert User über Cookie-Nutzung
- ✅ Kann mit X wegge clickt werden
- ✅ Speichert Zustimmung für 1 Jahr
- ✅ Erscheint nur beim ersten Besuch
- ✅ Ist responsive (Mobile & Desktop)
- ✅ Hat schöne Animationen

## Installation

Der Banner ist bereits auf folgenden Seiten eingebaut:
- ✅ `index.php` (Landing Page)
- ✅ `datenschutz.php` (Datenschutzerklärung)
- ✅ `impressum.php` (Impressum)

### Auf weiteren Seiten hinzufügen:

Füge **vor dem schließenden `</body>` Tag** ein:

```php
<!-- Cookie Banner -->
<?php include 'cookie-banner.php'; ?>
</body>
</html>
```

### Beispiel für andere Seiten:

**participant.php:**
```php
    </div>
    
    <!-- Cookie Banner -->
    <?php include 'cookie-banner.php'; ?>
</body>
</html>
```

**create_group.php:**
```php
    </div>
    
    <!-- Cookie Banner -->
    <?php include 'cookie-banner.php'; ?>
</body>
</html>
```

**admin.php:**
```php
    </div>
    
    <!-- Cookie Banner -->
    <?php include 'cookie-banner.php'; ?>
</body>
</html>
```

## Funktionsweise

### Cookie-Speicherung
- **Name:** `cookie_consent`
- **Wert:** `accepted`
- **Lebensdauer:** 365 Tage
- **Pfad:** `/` (ganze Website)
- **SameSite:** `Lax`

### Logik
1. **Erster Besuch:** Banner erscheint am unteren Bildschirmrand
2. **User klickt X:** Banner verschwindet mit Animation
3. **Cookie wird gesetzt:** `cookie_consent=accepted`
4. **Nächster Besuch:** Banner erscheint NICHT mehr (für 1 Jahr)

## Design

### Desktop
- Banner am unteren Bildschirmrand
- Dunkler Gradient-Hintergrund (#2b2d42 → #3a3d5c)
- X-Button rechts (runder Button)
- Text links mit Link zur Datenschutzerklärung

### Mobile
- Banner über gesamte Breite
- X-Button oben rechts (absolute position)
- Zentrierter Text
- Angepasste Schriftgrößen

## Anpassungen

### Farben ändern

In `cookie-banner.php`, Zeile ca. 16:
```css
background: linear-gradient(135deg, #2b2d42 0%, #3a3d5c 100%);
```

### Text ändern

In `cookie-banner.php`, Zeile ca. 7-10:
```html
<strong>🍪 Diese Website verwendet Cookies</strong>
<p>Wir verwenden Cookies für...</p>
```

### Cookie-Lebensdauer ändern

In `cookie-banner.php`, Zeile ca. 93:
```javascript
setCookie('cookie_consent', 'accepted', 365); // 365 = 1 Jahr
```

Ändern zu z.B.:
- `30` = 1 Monat
- `180` = 6 Monate
- `730` = 2 Jahre

## Rechtliches

### Schweiz 🇨🇭
- ✅ Banner ist ausreichend (keine Opt-In Pflicht)
- ✅ Hinweis auf Cookie-Nutzung vorhanden
- ✅ Link zur Datenschutzerklärung vorhanden
- ✅ Rechtlich OK!

### EU/Deutschland 🇪🇺
- ⚠️ DSGVO verlangt aktive Einwilligung (Opt-In)
- ⚠️ Aktuell: Banner kann einfach weggeklickt werden (Opt-Out)
- ⚠️ Für volle DSGVO-Konformität: Braucht "Akzeptieren" + "Ablehnen" Buttons

**Wenn viele EU-User:** Erweitere auf Opt-In Banner mit:
- Button "Akzeptieren" (setzt Cookies)
- Button "Ablehnen" (keine Cookies, außer essentielle)
- Separate Cookie-Kategorien (Funktional, Werbung, Analyse)

## Testen

### Manuell
1. Öffne Website im Browser
2. Banner sollte unten erscheinen
3. Klicke auf X
4. Banner verschwindet
5. Lade Seite neu → Banner erscheint NICHT mehr
6. DevTools öffnen (F12) → Application → Cookies
7. Prüfe: `cookie_consent=accepted` ist vorhanden

### Cookie löschen (für erneuten Test)
1. DevTools → Application → Cookies
2. Rechtsklick auf `cookie_consent` → Delete
3. Seite neu laden → Banner erscheint wieder

## FAQ

**Q: Banner erscheint nicht?**
A: 
- Prüfe ob `cookie-banner.php` existiert
- Prüfe ob `<?php include 'cookie-banner.php'; ?>` eingefügt ist
- Prüfe Browser-Console auf Fehler (F12)

**Q: Banner erscheint immer wieder?**
A: 
- Cookie wird nicht gespeichert
- Prüfe Browser-Einstellungen: Cookies erlaubt?
- Prüfe ob Website über HTTPS läuft

**Q: Banner überlappt Content?**
A:
- Banner hat `position: fixed` und `z-index: 9999`
- Sollte über allem anderen sein
- Falls Probleme: Erhöhe `z-index` in `cookie-banner.php`

**Q: Soll ich für jede Seite einen eigenen Banner erstellen?**
A:
- Nein! Nutze **eine** `cookie-banner.php` Datei
- Include sie auf **allen** Seiten
- Cookie gilt für gesamte Domain

**Q: Brauche ich zusätzliche Libraries?**
A:
- Nein! Vanilla JavaScript + CSS
- Keine Dependencies
- Funktioniert in allen modernen Browsern

## Performance

- **Dateigröße:** ~4 KB (HTML + CSS + JS zusammen)
- **HTTP Requests:** 0 (inline)
- **Load Time:** <1ms
- **Impact:** Minimal

## Browser-Support

- ✅ Chrome/Edge (Chromium) - alle Versionen
- ✅ Firefox - alle Versionen
- ✅ Safari - ab 12+
- ✅ Mobile Browser - alle modernen

## Sicherheit

- ✅ Kein externer Code
- ✅ Keine Third-Party Scripts
- ✅ XSS-sicher (nur eigener Code)
- ✅ Cookie mit `SameSite=Lax` (CSRF-Schutz)

## Zusammenfassung

**Was du hast:**
- ✅ Schöner Cookie-Banner
- ✅ Auf 3 Hauptseiten eingebaut
- ✅ Rechtlich OK für Schweiz
- ✅ Einfach erweiterbar

**Was du noch machen kannst:**
- [ ] Auf weitere Seiten hinzufügen (participant.php, create_group.php, etc.)
- [ ] Bei Bedarf: Opt-In Version für EU-User
- [ ] Bei Bedarf: Cookie-Kategorien (Funktional, Werbung, Analyse)

**Aktueller Stand: Produktiv einsatzbereit! ✅**
