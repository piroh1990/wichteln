<?php
if (file_exists(__DIR__ . '/../includes/config.php')) {
    require_once __DIR__ . '/../includes/config.php';
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>FAQ - Häufig gestellte Fragen zum Online Wichteln | Wichtlä.ch</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Alle Antworten zu Wichtlä.ch: Kosten, Datenschutz, Funktionen, Probleme und Tipps. Finde schnell Hilfe für dein Wichtel-Event!">
    <meta name="keywords" content="Wichteln FAQ, Wichteln Hilfe, Online Wichteln Fragen, Wichteln Anleitung, Wichteln Support">
    <link rel="canonical" href="https://wichtlä.ch/faq.php">
    
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://wichtlä.ch/faq.php">
    <meta property="og:title" content="FAQ - Häufig gestellte Fragen zum Online Wichteln">
    <meta property="og:description" content="Alle Antworten zu Wichtlä.ch: Kosten, Datenschutz, Funktionen und mehr.">
    
    <link rel="apple-touch-icon" sizes="180x180" href="/images/favicon/apple-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/favicon/favicon-16x16.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <!-- Shared JavaScript -->
    <script src="js/main.js"></script>
    
    <?php include __DIR__ . '/../includes/templates/matomo_tracking.php'; ?>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            {
                "@type": "Question",
                "name": "Was ist Wichtlä.ch?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Wichtlä.ch ist ein kostenloses Online-Tool zum Organisieren von Wichtel-Events. Du kannst damit Wichtel-Gruppen erstellen, Teilnehmer hinzufügen, automatisch und fair Namen ziehen lassen, Lose per E-Mail verschicken und Ausschlüsse definieren. Alles ohne Registrierung, ohne App-Download – einfach im Browser!"
                }
            },
            {
                "@type": "Question",
                "name": "Kostet Wichtlä.ch etwas?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Nein, Wichtlä.ch ist 100% kostenlos! Es gibt keine versteckten Kosten, keine Premium-Accounts und keine Einschränkungen."
                }
            },
            {
                "@type": "Question",
                "name": "Muss ich mich registrieren?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Nein! Es ist keine Registrierung erforderlich. Du erstellst einfach eine Gruppe, fügst Teilnehmer hinzu und erhältst einen Admin-Link per E-Mail."
                }
            },
            {
                "@type": "Question",
                "name": "Wie viele Personen können mitmachen?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Mindestens 3 Personen werden empfohlen für ein funktionierendes Wichteln. Nach oben gibt es praktisch keine Grenze – ob 5, 20 oder 100 Teilnehmer, das System funktioniert für alle Gruppengrössen."
                }
            },
            {
                "@type": "Question",
                "name": "Wie funktioniert das Losziehen?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Der Algorithmus stellt sicher, dass niemand sich selbst zieht, jede Person genau eine andere Person beschenkt, jede Person genau ein Geschenk erhält und definierte Ausschlüsse eingehalten werden. Das Los wird per E-Mail verschickt und bleibt geheim."
                }
            },
            {
                "@type": "Question",
                "name": "Was sind Ausschlüsse und wofür brauche ich sie?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Ausschlüsse verhindern, dass bestimmte Personen einander ziehen. Das ist sinnvoll bei Paaren, engen Freunden, Geschwistern in Familien-Wichteln oder Vorgesetzten/Mitarbeitern im Firmenwichteln."
                }
            },
            {
                "@type": "Question",
                "name": "Kann ich später noch Teilnehmer hinzufügen?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Ja! Als Admin kannst du über deinen Admin-Link jederzeit neue Teilnehmer hinzufügen, Wunschlisten ergänzen oder ändern und weitere Ausschlüsse definieren. Sobald die Namen gezogen wurden, müsste die Ziehung neu durchgeführt werden."
                }
            },
            {
                "@type": "Question",
                "name": "Kann ich die Ziehung wiederholen?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Ja, als Admin kannst du die Ziehung zurücksetzen und neu durchführen lassen. Das ist nützlich, wenn du Ausschlüsse vergessen hast, neue Teilnehmer hinzugekommen sind oder ein Fehler passiert ist."
                }
            },
            {
                "@type": "Question",
                "name": "Was passiert, wenn jemand sein Los verliert?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Kein Problem! Jeder Teilnehmer kann sein Los jederzeit wieder abrufen: Auf der Startseite gibt es den Link 'Los abrufen', E-Mail-Adresse eingeben und das Los wird erneut zugeschickt."
                }
            },
            {
                "@type": "Question",
                "name": "Kann ich mehrere Wichtel-Gruppen erstellen?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Ja, absolut! Du kannst beliebig viele Gruppen erstellen, z.B. eine für die Familie, eine für Freunde und eine für die Arbeit. Jede Gruppe hat ihren eigenen Admin-Link."
                }
            },
            {
                "@type": "Question",
                "name": "Sind meine Daten sicher?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Ja! Wir verwenden SSL-Verschlüsselung für alle Verbindungen, der Server-Standort ist in der Schweiz, wir halten das Schweizer Datenschutzgesetz (DSG) ein und Gruppen werden automatisch nach Ablauf gelöscht."
                }
            },
            {
                "@type": "Question",
                "name": "Welche Daten werden gespeichert?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Wir speichern nur das Nötigste: Namen der Teilnehmer, E-Mail-Adressen (für Benachrichtigungen), Wunschlisten (optional), Ausschlüsse und Ziehungsergebnisse. Nicht gespeichert werden: Telefonnummern, Adressen, Zahlungsdaten oder sonstige persönliche Informationen."
                }
            },
            {
                "@type": "Question",
                "name": "Wie lange werden meine Daten aufbewahrt?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Gruppen und alle zugehörigen Daten werden automatisch nach 90 Tagen gelöscht. Als Admin kannst du deine Gruppe auch jederzeit manuell löschen."
                }
            },
            {
                "@type": "Question",
                "name": "Kann jemand anders mein Los sehen?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Nein! Jedes Los ist durch einen einzigartigen, geheimen Link geschützt. Niemand kann dein Los sehen – auch nicht der Admin –, es sei denn, du teilst deinen Los-Link."
                }
            },
            {
                "@type": "Question",
                "name": "Ich habe keine E-Mail erhalten – was tun?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Prüfe bitte deinen Spam-Ordner, ob du dich bei der E-Mail-Adresse vertippt hast, und warte 1-2 Minuten. Nutze die 'Los erneut zusenden'-Funktion auf der Startseite oder kontaktiere den Admin."
                }
            },
            {
                "@type": "Question",
                "name": "Ich habe meinen Admin-Link verloren!",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Der Admin-Link wurde dir per E-Mail zugeschickt. Schau in deinem Posteingang und Spam-Ordner nach E-Mails von Wichtlä.ch. Tipp: Speichere den Admin-Link als Lesezeichen im Browser!"
                }
            },
            {
                "@type": "Question",
                "name": "Die Ziehung funktioniert nicht – warum?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Mögliche Gründe: Zu viele Ausschlüsse (mathematisch unmöglich), zu wenige Teilnehmer (mindestens 3 erforderlich) oder ein technisches Problem (Browser-Cache leeren). Reduziere die Ausschlüsse oder füge mehr Teilnehmer hinzu."
                }
            },
            {
                "@type": "Question",
                "name": "Kann ich eine Gruppe löschen?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Ja! Als Admin findest du im Admin-Bereich eine 'Gruppe löschen'-Option. Alle Daten werden sofort und unwiderruflich gelöscht."
                }
            },
            {
                "@type": "Question",
                "name": "Funktioniert Wichtlä.ch auf dem Smartphone?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Ja, vollständig! Wichtlä.ch ist komplett responsive und funktioniert auf Smartphones (iOS & Android), Desktop-Computern und Tablets. Keine App nötig – einfach im Browser öffnen!"
                }
            },
            {
                "@type": "Question",
                "name": "Wann ist der beste Zeitpunkt, um zu starten?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "3-4 Wochen vor dem Event ist ideal. Genug Zeit zum Geschenke-Besorgen, Teilnehmer können in Ruhe überlegen und bei Problemen bleibt Zeit für Korrekturen. Für spontane Wichtel-Aktionen reicht auch 1-2 Wochen Vorlauf."
                }
            },
            {
                "@type": "Question",
                "name": "Welches Budget empfehlt ihr?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Das hängt von eurer Gruppe ab: 10-15 CHF für Freunde, Studenten und grosse Gruppen; 15-25 CHF als Standard für die meisten Wichtel-Events; 25-50 CHF für Firmen und kleinere Kreise. Wählt ein Budget, das für alle bezahlbar ist!"
                }
            },
            {
                "@type": "Question",
                "name": "Soll das Wichteln anonym bleiben?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Das ist Geschmackssache: Anonym ist spannender mit mehr Rätselraten, offen ist persönlicher und einfacher bei der Geschenkauswahl. Viele Gruppen machen es so: Geschenke anonym verpacken, aber nach dem Auspacken verrät sich der Schenker."
                }
            },
            {
                "@type": "Question",
                "name": "Was tun, wenn jemand nicht mitmachen will?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Wichteln sollte immer freiwillig sein! Niemand sollte sich gezwungen fühlen. Bei Firmenwichteln: Macht eine Umfrage vorher und organisiert das Event nur, wenn genug Leute mitmachen wollen."
                }
            }
        ]
    }
    </script>

</head>
<body>
    <?php include __DIR__ . '/../includes/templates/navigation.php'; ?>
    
    <div class="content-page">
        <div class="breadcrumb">
            <a href="index.php">Home</a> / FAQ
        </div>
        
        <header class="page-header">
            <h1 class="page-title">Häufig gestellte Fragen ?</h1>
            <p class="page-subtitle">Alle Antworten zu Wichtlä.ch, Funktionen, Datenschutz und Tipps für erfolgreiches Wichteln</p>
        </header>
        
        <article>
            <!-- Allgemeine Fragen -->
            <section class="faq-section">
                <h2>🎁 Allgemeine Fragen</h2>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-1" id="faq-question-1" onclick="toggleFaq(this)">
                            <span class="faq-title">Was ist Wichtlä.ch?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-1" class="faq-answer" role="region" aria-labelledby="faq-question-1">
<p>
                            Wichtlä.ch ist ein <strong>kostenloses Online-Tool</strong> zum Organisieren von Wichtel-Events. Du kannst damit ganz einfach:
                        </p>
                        <ul>
                            <li>Wichtel-Gruppen erstellen</li>
                            <li>Teilnehmer hinzufügen</li>
                            <li>Automatisch und fair Namen ziehen lassen</li>
                            <li>Lose per E-Mail verschicken</li>
                            <li>Ausschlüsse definieren (z.B. Paare ziehen sich nicht)</li>
                        </ul>
                        <p>
                            Alles ohne Registrierung, ohne App-Download – einfach im Browser!
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-2" id="faq-question-2" onclick="toggleFaq(this)">
                            <span class="faq-title">Kostet Wichtlä.ch etwas?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-2" class="faq-answer" role="region" aria-labelledby="faq-question-2">
<p>
                            <strong>Nein, Wichtlä.ch ist 100% kostenlos!</strong>
                        </p>
                        <p>
                            Es gibt keine versteckten Kosten, keine Premium-Accounts und keine Einschränkungen. Der Service wird durch kleine Werbeanzeigen finanziert, die dezent platziert sind und nicht stören.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-3" id="faq-question-3" onclick="toggleFaq(this)">
                            <span class="faq-title">Muss ich mich registrieren?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-3" class="faq-answer" role="region" aria-labelledby="faq-question-3">
<p>
                            <strong>Nein!</strong> Es ist keine Registrierung erforderlich. Du erstellst einfach eine Gruppe, fügst Teilnehmer hinzu und erhältst einen Admin-Link per E-Mail. Fertig!
                        </p>
                        <p>
                            Das macht Wichtlä.ch besonders einfach und schnell in der Nutzung.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-4" id="faq-question-4" onclick="toggleFaq(this)">
                            <span class="faq-title">Wie viele Personen können mitmachen?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-4" class="faq-answer" role="region" aria-labelledby="faq-question-4">
<p>
                            <strong>Mindestens 3 Personen</strong> werden empfohlen für ein funktionierendes Wichteln. Nach oben gibt es praktisch keine Grenze – ob 5, 20 oder 100 Teilnehmer, das System funktioniert für alle Gruppengrößen.
                        </p>
                        <p>
                            Besonders beliebt sind Gruppen von 5-30 Personen (z.B. für Familien, Freundeskreise oder Teams).
                        </p>
                    </div>
                </div>
            </section>
            
            <!-- Funktionen -->
            <section class="faq-section">
                <h2>⚙️ Funktionen & Nutzung</h2>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-5" id="faq-question-5" onclick="toggleFaq(this)">
                            <span class="faq-title">Wie funktioniert das Losziehen?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-5" class="faq-answer" role="region" aria-labelledby="faq-question-5">
<p>
                            Der Algorithmus stellt sicher, dass:
                        </p>
                        <ul>
                            <li>✅ Niemand sich selbst zieht</li>
                            <li>✅ Jede Person genau eine andere Person beschenkt</li>
                            <li>✅ Jede Person genau ein Geschenk erhält</li>
                            <li>✅ Definierte Ausschlüsse eingehalten werden</li>
                        </ul>
                        <p>
                            Das Los wird per E-Mail verschickt und bleibt geheim – du siehst nur, wen DU beschenken sollst.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-6" id="faq-question-6" onclick="toggleFaq(this)">
                            <span class="faq-title">Was sind Ausschlüsse und wofür brauche ich sie?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-6" class="faq-answer" role="region" aria-labelledby="faq-question-6">
<p>
                            <strong>Ausschlüsse</strong> verhindern, dass bestimmte Personen einander ziehen. Das ist sinnvoll bei:
                        </p>
                        <ul>
                            <li>Paaren oder Ehepartner (kennen die Wünsche meist schon)</li>
                            <li>Enge Freunde, die sich sowieso beschenken</li>
                            <li>Geschwistern in Familien-Wichteln</li>
                            <li>Vorgesetzte/Mitarbeiter im Firmenwichteln</li>
                        </ul>
                        <p>
                            Beispiel: Du gibst an "Anna ↔ Peter" – dann kann Anna nicht Peter ziehen und Peter nicht Anna.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-7" id="faq-question-7" onclick="toggleFaq(this)">
                            <span class="faq-title">Kann ich später noch Teilnehmer hinzufügen?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-7" class="faq-answer" role="region" aria-labelledby="faq-question-7">
<p>
                            <strong>Ja!</strong> Als Admin kannst du über deinen Admin-Link jederzeit:
                        </p>
                        <ul>
                            <li>Neue Teilnehmer hinzufügen</li>
                            <li>Wunschlisten ergänzen oder ändern</li>
                            <li>Weitere Ausschlüsse definieren</li>
                        </ul>
                        <p>
                            <strong>Wichtig:</strong> Sobald die Namen gezogen wurden, können keine Teilnehmer mehr hinzugefügt werden. Die Ziehung müsste neu durchgeführt werden.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-8" id="faq-question-8" onclick="toggleFaq(this)">
                            <span class="faq-title">Kann ich die Ziehung wiederholen?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-8" class="faq-answer" role="region" aria-labelledby="faq-question-8">
<p>
                            <strong>Ja</strong>, als Admin kannst du die Ziehung zurücksetzen und neu durchführen lassen. Das ist nützlich, wenn:
                        </p>
                        <ul>
                            <li>Du Ausschlüsse vergessen hast</li>
                            <li>Neue Teilnehmer hinzugekommen sind</li>
                            <li>Ein Fehler passiert ist</li>
                        </ul>
                        <p>
                            <strong>Achtung:</strong> Bei einer neuen Ziehung werden alle bisherigen Zuordnungen gelöscht!
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-9" id="faq-question-9" onclick="toggleFaq(this)">
                            <span class="faq-title">Was passiert, wenn jemand sein Los verliert?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-9" class="faq-answer" role="region" aria-labelledby="faq-question-9">
<p>
                            Kein Problem! Jeder Teilnehmer kann sein Los jederzeit wieder abrufen:
                        </p>
                        <ul>
                            <li>Auf der Startseite gibt es den Link "Los abrufen"</li>
                            <li>E-Mail-Adresse eingeben → Los wird erneut zugeschickt</li>
                            <li>Oder: Der Admin kann über den Admin-Bereich die Los-Links einsehen</li>
                        </ul>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-10" id="faq-question-10" onclick="toggleFaq(this)">
                            <span class="faq-title">Kann ich mehrere Wichtel-Gruppen erstellen?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-10" class="faq-answer" role="region" aria-labelledby="faq-question-10">
<p>
                            <strong>Ja, absolut!</strong> Du kannst beliebig viele Gruppen erstellen, z.B.:
                        </p>
                        <ul>
                            <li>Eine für die Familie</li>
                            <li>Eine für Freunde</li>
                            <li>Eine für die Arbeit</li>
                        </ul>
                        <p>
                            Jede Gruppe hat ihren eigenen Admin-Link, den du per E-Mail erhältst.
                        </p>
                    </div>
                </div>
            </section>
            
            <!-- Datenschutz & Sicherheit -->
            <section class="faq-section">
                <h2>🔒 Datenschutz & Sicherheit</h2>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-11" id="faq-question-11" onclick="toggleFaq(this)">
                            <span class="faq-title">Sind meine Daten sicher?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-11" class="faq-answer" role="region" aria-labelledby="faq-question-11">
<p>
                            <strong>Ja!</strong> Wir nehmen Datenschutz sehr ernst:
                        </p>
                        <ul>
                            <li>🔐 SSL-Verschlüsselung für alle Verbindungen</li>
                            <li>🇨🇭 Server-Standort in der Schweiz</li>
                            <li>📜 Einhaltung des Schweizer Datenschutzgesetzes (DSG)</li>
                            <li>🗑️ Automatische Löschung nach Ablauf der Gruppe</li>
                            <li>🚫 Keine Weitergabe an Dritte (ausser Google AdSense für Werbung)</li>
                        </ul>
                        <p>
                            Mehr Details in unserer <a href="datenschutz.php">Datenschutzerklärung</a>.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-12" id="faq-question-12" onclick="toggleFaq(this)">
                            <span class="faq-title">Welche Daten werden gespeichert?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-12" class="faq-answer" role="region" aria-labelledby="faq-question-12">
<p>
                            Wir speichern nur das Nötigste:
                        </p>
                        <ul>
                            <li>Namen der Teilnehmer</li>
                            <li>E-Mail-Adressen (für Benachrichtigungen)</li>
                            <li>Wunschlisten (optional, falls angegeben)</li>
                            <li>Ausschlüsse</li>
                            <li>Ziehungsergebnisse</li>
                        </ul>
                        <p>
                            <strong>Nicht gespeichert werden:</strong> Telefonnummern, Adressen, Zahlungsdaten oder sonstige persönliche Informationen.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-13" id="faq-question-13" onclick="toggleFaq(this)">
                            <span class="faq-title">Wie lange werden meine Daten aufbewahrt?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-13" class="faq-answer" role="region" aria-labelledby="faq-question-13">
<p>
                            Gruppen und alle zugehörigen Daten werden <strong>automatisch nach 90 Tagen gelöscht</strong>.
                        </p>
                        <p>
                            Als Admin kannst du deine Gruppe auch jederzeit manuell löschen – alle Daten werden dann sofort entfernt.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-14" id="faq-question-14" onclick="toggleFaq(this)">
                            <span class="faq-title">Kann jemand anders mein Los sehen?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-14" class="faq-answer" role="region" aria-labelledby="faq-question-14">
<p>
                            <strong>Nein!</strong> Jedes Los ist durch einen einzigartigen, geheimen Link geschützt. Niemand kann dein Los sehen – auch nicht der Admin –, es sei denn, du teilst deinen Los-Link.
                        </p>
                        <p>
                            Der Admin sieht nur, wer bereits sein Los abgerufen hat, aber nicht den Inhalt.
                        </p>
                    </div>
                </div>
            </section>
            
            <!-- Probleme & Support -->
            <section class="faq-section">
                <h2>🆘 Probleme & Support</h2>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-15" id="faq-question-15" onclick="toggleFaq(this)">
                            <span class="faq-title">Ich habe keine E-Mail erhalten – was tun?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-15" class="faq-answer" role="region" aria-labelledby="faq-question-15">
<p>
                            Wenn du keine E-Mail erhalten hast, prüfe bitte:
                        </p>
                        <ul>
                            <li>📧 <strong>Spam-Ordner:</strong> Manchmal landen unsere E-Mails im Spam</li>
                            <li>✉️ <strong>E-Mail-Adresse:</strong> Hast du dich vielleicht vertippt?</li>
                            <li>⏱️ <strong>Wartezeit:</strong> E-Mails können 1-2 Minuten dauern</li>
                            <li>🚫 <strong>E-Mail-Filter:</strong> Firmen-E-Mails blockieren manchmal externe Absender</li>
                        </ul>
                        <p>
                            <strong>Lösung:</strong> Nutze die "Los erneut zusenden"-Funktion auf der Startseite oder kontaktiere den Admin.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-16" id="faq-question-16" onclick="toggleFaq(this)">
                            <span class="faq-title">Ich habe meinen Admin-Link verloren!</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-16" class="faq-answer" role="region" aria-labelledby="faq-question-16">
<p>
                            Der Admin-Link wurde dir per E-Mail zugeschickt. Schau in deinem Posteingang (und Spam-Ordner) nach E-Mails von Wichtlä.ch.
                        </p>
                        <p>
                            Falls du die E-Mail nicht mehr findest, gibt es leider keine Wiederherstellungsmöglichkeit (keine Registrierung = keine Passwort-Reset-Funktion). Du musst eine neue Gruppe erstellen.
                        </p>
                        <div class="highlight-box">
                            <strong>💡 Tipp:</strong> Speichere den Admin-Link als Lesezeichen im Browser!
                    </div>
                </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-17" id="faq-question-17" onclick="toggleFaq(this)">
                            <span class="faq-title">Die Ziehung funktioniert nicht – warum?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-17" class="faq-answer" role="region" aria-labelledby="faq-question-17">
<p>
                            Mögliche Gründe:
                        </p>
                        <ul>
                            <li><strong>Zu viele Ausschlüsse:</strong> Bei zu vielen Ausschlüssen ist eine gültige Ziehung mathematisch unmöglich</li>
                            <li><strong>Zu wenige Teilnehmer:</strong> Mindestens 3 Personen erforderlich</li>
                            <li><strong>Technisches Problem:</strong> Browser-Cache leeren und erneut versuchen</li>
                        </ul>
                        <p>
                            <strong>Tipp:</strong> Reduziere die Anzahl der Ausschlüsse oder füge mehr Teilnehmer hinzu.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-18" id="faq-question-18" onclick="toggleFaq(this)">
                            <span class="faq-title">Kann ich eine Gruppe löschen?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-18" class="faq-answer" role="region" aria-labelledby="faq-question-18">
<p>
                            <strong>Ja!</strong> Als Admin findest du im Admin-Bereich eine "Gruppe löschen"-Option. Alle Daten werden sofort und unwiderruflich gelöscht.
                        </p>
                        <p>
                            <strong>Achtung:</strong> Dies kann nicht rückgängig gemacht werden!
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-19" id="faq-question-19" onclick="toggleFaq(this)">
                            <span class="faq-title">Funktioniert Wichtlä.ch auf dem Smartphone?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-19" class="faq-answer" role="region" aria-labelledby="faq-question-19">
<p>
                            <strong>Ja, vollständig!</strong> Wichtlä.ch ist komplett responsive und funktioniert auf allen Geräten:
                        </p>
                        <ul>
                            <li>📱 Smartphones (iOS & Android)</li>
                            <li>💻 Desktop-Computer</li>
                            <li>🖥️ Tablets</li>
                        </ul>
                        <p>
                            Keine App nötig – einfach im Browser öffnen!
                        </p>
                    </div>
                </div>
            </section>
            
            <!-- Tipps & Best Practices -->
            <section class="faq-section">
                <h2>💡 Tipps & Best Practices</h2>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-20" id="faq-question-20" onclick="toggleFaq(this)">
                            <span class="faq-title">Wann ist der beste Zeitpunkt, um zu starten?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-20" class="faq-answer" role="region" aria-labelledby="faq-question-20">
<p>
                            <strong>3-4 Wochen vor dem Event</strong> ist ideal:
                        </p>
                        <ul>
                            <li>Genug Zeit zum Geschenke-Besorgen</li>
                            <li>Teilnehmer können in Ruhe überlegen</li>
                            <li>Bei Problemen bleibt Zeit für Korrekturen</li>
                        </ul>
                        <p>
                            Für spontane Wichtel-Aktionen reicht auch 1-2 Wochen Vorlauf.
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-21" id="faq-question-21" onclick="toggleFaq(this)">
                            <span class="faq-title">Welches Budget empfehlt ihr?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-21" class="faq-answer" role="region" aria-labelledby="faq-question-21">
<p>
                            Das hängt von eurer Gruppe ab:
                        </p>
                        <ul>
                            <li><strong>10-15 CHF:</strong> Freunde, Studenten, große Gruppen</li>
                            <li><strong>15-25 CHF:</strong> Standard für die meisten Wichtel-Events</li>
                            <li><strong>25-50 CHF:</strong> Firmen, kleinere Gruppen, engere Kreise</li>
                        </ul>
                        <p>
                            <strong>Wichtig:</strong> Wählt ein Budget, das für alle bezahlbar ist!
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-22" id="faq-question-22" onclick="toggleFaq(this)">
                            <span class="faq-title">Soll das Wichteln anonym bleiben?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-22" class="faq-answer" role="region" aria-labelledby="faq-question-22">
<p>
                            Das ist Geschmackssache:
                        </p>
                        <ul>
                            <li><strong>Anonym:</strong> Spannender, mehr Rätselraten, klassische Variante</li>
                            <li><strong>Offen:</strong> Persönlicher, einfacher bei der Geschenkauswahl</li>
                        </ul>
                        <p>
                            Viele Gruppen machen es so: Geschenke anonym verpacken, aber nach dem Auspacken verrät sich der Schenker. Das Beste aus beiden Welten!
                        </p>
                    </div>
                </div>
                
                                <div class="faq-item">
                    <h3>
                        <button class="faq-question" aria-expanded="false" aria-controls="faq-answer-23" id="faq-question-23" onclick="toggleFaq(this)">
                            <span class="faq-title">Was tun, wenn jemand nicht mitmachen will?</span>
                            <span class="faq-icon" aria-hidden="true">▼</span>
                        </button>
                    </h3>
                    <div id="faq-answer-23" class="faq-answer" role="region" aria-labelledby="faq-question-23">
<p>
                            <strong>Wichteln sollte immer freiwillig sein!</strong> Niemand sollte sich gezwungen fühlen.
                        </p>
                        <p>
                            Bei Firmenwichteln: Macht eine Umfrage vorher und organisiert das Event nur, wenn genug Leute mitmachen wollen. Nicht-Teilnehmer sollten sich nicht ausgeschlossen fühlen.
                        </p>
                    </div>
                </div>
            </section>
            
            <div class="cta-section">
                <h2>Noch Fragen?</h2>
                <p>Schreib uns eine E-Mail oder starte einfach dein erstes Wichteln – es ist kinderleicht!</p>
                <a href="create_group.php" class="cta-button-white">Jetzt Gruppe erstellen →</a>
            </div>
        </article>
    </div>
    
    <script>
        function toggleFaq(button) {
            toggleFAQ(button);
        }
    </script>
    
    <!-- Simple Footer -->
    <footer class="site-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Wichteln</h4>
                    <ul class="footer-links">
                        <li><a href="was-ist-wichteln.php">Was ist Wichteln?</a></li>
                        <li><a href="wichtel-ideen.php">Geschenkideen</a></li>
                        <li><a href="firmenwichteln-tipps.php">Firmenwichteln</a></li>
                        <li><a href="faq.php">FAQ</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Loslegen</h4>
                    <ul class="footer-links">
                        <li><a href="create_group.php">Gruppe erstellen</a></li>
                        <li><a href="participant.php">Teilnehmerbereich</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Rechtliches</h4>
                    <ul class="footer-links">
                        <li><a href="ueber-uns.php">Über uns</a></li>
                        <li><a href="impressum.php">Impressum</a></li>
                        <li><a href="datenschutz.php">Datenschutz</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> wichtlä.ch</p>
            </div>
        </div>
    </footer>
    
    <?php include __DIR__ . '/cookie-banner.php'; ?>
</body>
</html>
