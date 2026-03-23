# Beratungsassistent

Konfigurierbarer KI-Beratungsassistent mit Upload-basierter Wissensbasis, serverseitigem Gemini-Proxy und geführter Ersteinrichtung.

Beim ersten Start werden Admin-Passwort, Gemini-API-Key, Titel, Themenfeld und Zielgruppe festgelegt. Anschließend werden eine oder mehrere Dateien hochgeladen, aus denen die Wissensbasis sowie die UI-Beispiele für den Assistenten erzeugt werden.

## Funktionen

- Geführter Setup-Wizard für Passwort, API, Projektprofil und Wissensbasis
- Serverseitiger Gemini-Proxy ohne API-Key im Browser
- Dokumentgestützte Antworten mit Quellenhinweis
- Upload von PDF, TXT und Markdown
- Automatische Chunk-Erzeugung für die Wissensbasis
- Automatische Generierung von Quick Questions, Aufgabenbeispielen und Vorlagen
- Lokale Bibliotheken in `vendor/`, kein CDN im Frontend

## Einrichtung

1. Die Projektdateien auf einen PHP-Webserver mit `curl`, `fileinfo` und `mbstring` laden.
2. `admin.php` im Browser öffnen.
3. Im Wizard Admin-Passwort, Gemini-API-Key, Titel, Themenfeld und Zielgruppe festlegen.
4. Eine oder mehrere Dateien für die Wissensbasis hochladen.
5. Danach ist der Assistent unter `index.html` einsatzbereit.

Die ausführliche Schritt-für-Schritt-Anleitung steht in `SETUP.md`.

## Dateiformate

- PDF
- TXT
- Markdown (`.md`, `.markdown`)

## Projektstruktur

```text
index.html              React-Frontend für normale Nutzer
admin.php               Wizard und Admin-Dashboard
proxy.php               Serverseitiger Gemini-Proxy mit Retrieval
project.php             Öffentliche Laufzeit-Konfiguration für das Frontend
lib/app.php             Gemeinsame PHP-Helfer für Konfiguration, Upload, Chunking und Retrieval
config/                 API- und Projektkonfiguration
rag/                    Hochgeladene Dateien und generierte Wissens-Chunks
vendor/                 Lokal eingebundene Bibliotheken
```

## Technischer Hinweis

Der Assistent nutzt eine dokumentbasierte Wissensbasis: Hochgeladene Inhalte werden in Chunks aufgeteilt, serverseitig durchsucht und als Kontext für die Antwortgenerierung verwendet.

## Sicherheit und Betrieb

- API-Key bleibt serverseitig in `config/config.php`
- Admin-Passwort wird gehasht in `rag/.admin_password` gespeichert
- Wissensdateien und Chunks werden nur serverseitig verwendet
- Der System-Prompt wird nicht aus dem Frontend übernommen

## Kontakt

S. Schwabe  
`s.schwabe@nibis.de`

## Lizenz

- Der Softwarecode in diesem Repository steht unter der MIT-Lizenz.
- Dokumentation, Vorlagen, Beispielinhalte und sonstige nicht-codebezogene Inhalte stehen unter CC BY 4.0, sofern im Einzelfall nichts anderes vermerkt ist.
- Die vollständigen Lizenztexte stehen in `LICENSE` und `docs/content-license.md`.
