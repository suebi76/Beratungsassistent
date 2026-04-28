# Beratungsassistent Source Structure

Diese Struktur trennt fachliche Verantwortlichkeiten. `lib/app.php` bleibt nur als kompatibler Bootstrap fuer bestehende Einstiegspunkte (`admin.php`, `proxy.php`, `project.php`).

## Module

- `Runtime/`: technische Basis wie Konstanten, Pfade, HTTP-Antworten, Text-Helfer und Rate-Limit.
- `Config/`: API-Konfiguration, Projektkonfiguration und Setup-Status.
- `Security/`: Admin-Session, Flash-Messages und CSRF-Helfer.
- `Knowledge/`: Chunk-Dateien, Frontmatter, RAG-Retrieval und RAG-Kontextblock.
- `Ingestion/`: Uploads, Dokumentvalidierung, Chunk-Generierung und Dokumentverarbeitung.
- `AI/`: Gemini-Client und API-Header.
- `Profile/`: automatische Frontend-/Projektprofil-Generierung aus der Wissensbasis.
- `PublicApi/`: oeffentliche Konfiguration fuer das Frontend.
- `Prompt/`: Systemprompt und Chat-Normalisierung fuer Gemini.
- `Admin/`: Admin-Controller, Request-Actions, Page-Model und serverseitige View-Templates.

## Entwicklungsregeln

- Neue Fachlogik kommt in ein passendes `src/`-Modul, nicht in `lib/app.php`.
- `lib/app.php` soll nur Includes enthalten.
- Bestehende globale Funktionsnamen bleiben vorerst erhalten, damit die Anwendung ohne Big-Bang-Umbau weiter laeuft.
- Nach jeder Aenderung: PHP-Lint fuer alle PHP-Dateien ausfuehren.
- Sicherheitslogik nicht im HTML/View-Code duplizieren, sondern in `src/Security/` kapseln.

## Naechste Refactoring-Schritte

- Erste automatische Tests fuer Config, Retrieval und Uploadvalidierung ergaenzen.
- Frontend-Komponenten mittelfristig aus der Build-freien `assets/js/`-Struktur in echte ES-Module ueberfuehren, falls ein Build-Schritt akzeptiert wird.
