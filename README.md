# Beratungsassistent

Konfigurierbarer KI-Beratungsassistent mit Upload-basierter Wissensbasis, serverseitigem KI-Proxy und geführter Ersteinrichtung.

Beim ersten Start werden Admin-Passwort, Gemini-API-Schlüssel, Titel, Themenfeld und Zielgruppe festgelegt. Anschließend werden eine oder mehrere Dateien hochgeladen, aus denen die Wissensbasis sowie die Beispielinhalte für den Assistenten erzeugt werden.

## Funktionen

- Geführter Setup-Wizard für Passwort, API, Projektprofil und Wissensbasis
- Serverseitiger Gemini-Proxy ohne API-Schlüssel im Browser
- Dokumentgestützte Antworten mit Quellenhinweis
- Upload von PDF, TXT und Markdown
- Automatische Chunk-Erzeugung für die Wissensbasis
- Automatische Generierung von Schnellfragen, Aufgabenbeispielen und Vorlagen
- Docker-Deployment mit persistentem Datenverzeichnis
- VPS-Paket für klassische PHP-Webserver
- Lokale Bibliotheken in `vendor/`, kein CDN im Frontend

## Schnellstart

### Docker

1. `docker compose up -d --build`
2. `http://localhost:8080/admin.php` öffnen
3. Im Wizard Passwort, API-Schlüssel, Projektprofil und Wissensbasis einrichten

Persistente Daten liegen im Container-Setup standardmäßig unter `/data`.

### VPS oder klassischer Webserver

1. Das Release-ZIP herunterladen oder das Repository deployen
2. Die Dateien auf einen PHP-Webserver mit `curl`, `fileinfo` und `mbstring` kopieren
3. Optional `BERATUNGSASSISTENT_DATA_DIR` auf ein Verzeichnis außerhalb des Webroots setzen
4. `admin.php` im Browser öffnen und die Ersteinrichtung durchführen

## Deployment

- Ausführliche Setup-Schritte: `SETUP.md`
- Docker- und VPS-Hinweise: `DEPLOY.md`
- VPS-Paket lokal erstellen: `scripts/create-release-package.ps1`

## Projektstruktur

```text
index.html              React-Frontend für normale Nutzer
admin.php               Wizard und Admin-Dashboard
proxy.php               Serverseitiger KI-Proxy mit Wissensbasis-Kontext
project.php             Öffentliche Laufzeit-Konfiguration für das Frontend
lib/app.php             Kompatibler Bootstrap für die modularisierte PHP-Struktur
src/                    Fachmodule für Runtime, Config, Security, Wissensbasis, Ingestion, Admin und API
assets/                 Frontend-CSS und build-freie React/JSX-Komponenten
config/                 Beispielkonfiguration im Repository
rag/                    Beispielstruktur für Uploads und generierte Chunks
docker/                 Container-Konfiguration
scripts/                Hilfsskripte für Auslieferung und Releases
vendor/                 Lokal eingebundene Bibliotheken
```

## Persistente Daten

Standardmäßig speichert der Assistent Laufzeitdaten im Projekt unter `config/` und `rag/`. Für Deployment-Umgebungen kann stattdessen die Umgebungsvariable `BERATUNGSASSISTENT_DATA_DIR` gesetzt werden. Dann landen Konfiguration, Passwort, Uploads und Chunks in diesem externen Verzeichnis.

## Sicherheit und Betrieb

- API-Schlüssel bleibt serverseitig in `config/config.php` oder im externen Datenverzeichnis
- Admin-Passwort wird gehasht gespeichert
- Wissensdateien und Chunks werden nur serverseitig verwendet
- Die Systemanweisung wird nicht aus dem Frontend übernommen
- Für Produktion sollte das Datenverzeichnis außerhalb des Webroots liegen

## Kontakt

S. Schwabe  
`s.schwabe@nibis.de`

## Lizenz

- Der Softwarecode in diesem Repository steht unter der MIT-Lizenz.
- Dokumentation, Vorlagen, Beispielinhalte, sichtbare redaktionelle UI-Texte, Schulungsmaterial und nicht-codebezogene Designassets stehen unter CC BY-SA 4.0, sofern im Einzelfall nichts anderes vermerkt ist.
- Hinweise zu Autor, Drittanbieterbestandteilen und Forks stehen in `NOTICE.md` und `TRADEMARKS.md`.
- Die vollständigen Lizenzhinweise stehen in `LICENSE` und `CONTENT-LICENSE.md`.
