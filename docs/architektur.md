# Architektur

Der Beratungsassistent ist eine PHP-Anwendung ohne Framework mit build-freiem React-Frontend. Er beantwortet Nutzerfragen auf Basis einer upload-basierten, kuratierten Wissensbasis (RAG) über einen serverseitigen KI-Proxy.

## Schichtenmodell

Alle Persistenzzugriffe folgen dieser verbindlichen Grenze (siehe [ADR-003](adr/ADR-003-repository-storage-grenze.md)):

```text
Controller/Einstiegspunkt -> Service/Fachmodul -> Repository -> Storage -> Datei (optional SQLite-Index)
```

Direkte Dateizugriffe (`file_get_contents`/`file_put_contents`) in neuer Fachlogik sind nicht erlaubt. Dateien sind die fachliche Wahrheit; Indizes müssen jederzeit aus ihnen neu erzeugbar sein ([ADR-001](adr/ADR-001-dateibasierter-v1-standard.md), [ADR-002](adr/ADR-002-sqlite-als-optionaler-index.md)).

## Einstiegspunkte

| Datei | Aufgabe |
|---|---|
| `index.html` + `assets/js/*.jsx` | Öffentliches Nutzer-Frontend (React ohne Build-Schritt, Babel im Browser) |
| `admin.php` | Setup-Wizard und Admin-Dashboard (Sektionen, linke Navigation) |
| `proxy.php` | Chat-Endpunkt: Retrieval + KI-Aufruf, Streaming, Rate-Limit |
| `project.php` | Öffentliche Laufzeitkonfiguration für das Frontend |
| `lib/app.php` | Bootstrap: lädt alle `src/`-Module (nur Includes, keine Logik) |

## Module unter `src/`

| Modul | Verantwortung |
|---|---|
| `Runtime/` | Konstanten, Pfade, HTTP-Helfer, Textnormalisierung, Rate-Limit |
| `Storage/` | `AtomicWriter` (temporäre Datei + Rename), `JsonStore`, `LockManager` |
| `Repository/` | `ProjectRepository`, `ApiConfigRepository` — einzige Wege zu Konfigurationsdateien |
| `Config/` | Lade-/Speicher-Wrapper für Projekt- und API-Konfiguration (intern auf Repositories umgestellt) |
| `AI/` | Provider-Schicht: `ModelGateway`, `ModelProvider`-Interface, `GeminiProvider`, `OpenAiCompatibleProvider`, normalisiertes Streaming ([ADR-004](adr/ADR-004-modellanbieter-interface.md), [ADR-005](adr/ADR-005-openai-kompatibler-endpunkt.md)) |
| `Ingestion/` | Upload-Verarbeitung, Duplikaterkennung, Chunk-Erzeugung, PDF-Split-Planung ([ADR-008](adr/ADR-008-pdf-splitting-fallback-kette.md)) |
| `Knowledge/` | Chunk-Verwaltung (Markdown mit Frontmatter unter `rag/chunks/`), Keyword-Retrieval |
| `Profile/`, `Prompt/` | Generierung des Projektprofils und der Systemanweisung |
| `Project/`, `PublicApi/` | Frontend-Inhalte (Schnellfragen, Vorlagen) und öffentliche Payload |
| `Admin/` | Actions, Seitenmodell, Views, Systemcheck, Qualitätstest, Upload-Jobs |
| `Security/` | Admin-Session |

## Datenfluss Chat-Anfrage

1. Frontend sendet Frage an `proxy.php`.
2. Rate-Limit- und Setup-Prüfung.
3. Retrieval: relevante Textabschnitte aus der Wissensbasis (`src/Knowledge/retrieval.php`).
4. Systemprompt + Kontext-Chunks + Frage gehen über das `ModelGateway` an den konfigurierten Provider.
5. Antwort wird als normalisierter Stream an den Browser gereicht; der API-Schlüssel bleibt serverseitig.

## Datenfluss Dokument-Upload

1. Admin lädt Datei(en) hoch; sequentielle Warteschlange (`src/Admin/upload_jobs.php`).
2. Validierung und Duplikaterkennung (`src/Ingestion/`).
3. Chunk-Erzeugung: Textabschnitte als Markdown-Dateien mit Frontmatter-Metadaten in `rag/chunks/`.
4. Optionale Regenerierung der Frontend-Inhalte (Schnellfragen, Beispiele, Vorlagen).

## Persistente Daten

Standardmäßig `config/` (Konfiguration, gehashtes Admin-Passwort) und `rag/` (Uploads, Chunks). Über die Umgebungsvariable `BERATUNGSASSISTENT_DATA_DIR` können alle Laufzeitdaten in ein externes Verzeichnis (empfohlen: außerhalb des Webroots) gelegt werden. Pfadauflösung in `src/Runtime/paths.php`.

## Architekturentscheidungen

Verbindliche Entscheidungen mit Begründung liegen in [`docs/adr/`](adr/). Neue Grundsatzentscheidungen werden dort als fortlaufend nummerierte ADRs ergänzt.
