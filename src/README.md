# Beratungsassistent Source Structure

Diese Struktur trennt fachliche Verantwortlichkeiten. `lib/app.php` bleibt nur als kompatibler Bootstrap für bestehende Einstiegspunkte (`admin.php`, `proxy.php`, `project.php`).

## Module

- `Runtime/`: technische Basis wie Konstanten, Pfade, HTTP-Antworten, Text-Helfer und Rate-Limit.
- `Config/`: API-Konfiguration, Projektkonfiguration und Setup-Status.
- `Security/`: Admin-Session, Flash-Messages und CSRF-Helfer.
- `Knowledge/`: Chunk-Dateien, Frontmatter, RAG-Retrieval und RAG-Kontextblock.
- `Ingestion/`: Uploads, Dokumentvalidierung, Chunk-Generierung und Dokumentverarbeitung.
- `AI/`: Gemini-Client und API-Header.
- `Profile/`: automatische Frontend-/Projektprofil-Generierung aus der Wissensbasis.
- `PublicApi/`: öffentliche Konfiguration für das Frontend.
- `Prompt/`: Systemprompt und Chat-Normalisierung für Gemini.
- `Admin/`: Admin-Controller, Request-Actions, Page-Model und serverseitige View-Templates.

## Entwicklungsregeln

- Neue Fachlogik kommt in ein passendes `src/`-Modul, nicht in `lib/app.php`.
- `lib/app.php` soll nur Includes enthalten.
- Bestehende globale Funktionsnamen bleiben vorerst erhalten, damit die Anwendung ohne Big-Bang-Umbau weiter läuft.
- Nach jeder Änderung: PHP-Lint für alle PHP-Dateien ausführen.
- Sicherheitslogik nicht im HTML/View-Code duplizieren, sondern in `src/Security/` kapseln.

## Nächste Refactoring-Schritte

- Erste automatische Tests für Config, Retrieval und Uploadvalidierung ergänzen.
- Frontend-Komponenten mittelfristig aus der Build-freien `assets/js/`-Struktur in echte ES-Module überführen, falls ein Build-Schritt akzeptiert wird.
