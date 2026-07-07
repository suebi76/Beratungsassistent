# Entwicklung

Einstieg für Entwicklerinnen und Entwickler: lokales Setup, Prüfungen, Konventionen.

## Voraussetzungen

- PHP 8.x mit `curl`, `fileinfo`, `mbstring`
- Node.js (nur für den JS-Syntaxcheck, kein Build nötig)
- Optional Docker für den Container-Weg

## Lokal starten

```bash
php -S localhost:8080
```

Dann `http://localhost:8080/admin.php` öffnen und die Ersteinrichtung durchlaufen (Passwort, API-Schlüssel, Projektprofil, Wissensbasis). Alternativ `docker compose up -d --build`.

Laufzeitdaten landen in `config/` und `rag/` (gitignored) oder im Verzeichnis aus `BERATUNGSASSISTENT_DATA_DIR`.

## Prüfungen (vor jedem Commit)

```bash
php tests/run.php                     # Testsuite
php -l <geänderte Datei>              # Lint pro Datei; CI prüft alle
node --check assets/js/admin.js       # JS-Syntax
git diff --check                      # Whitespace-Fehler
```

Die GitHub Action `.github/workflows/ci.yml` führt dieselben Prüfungen bei jedem Push aus. Ein Merge mit roter CI ist nicht erlaubt.

## Konventionen

- **Schichtenregel:** Neue Fachlogik greift nie direkt auf Dateien zu — immer über Repository/Storage ([ADR-003](adr/ADR-003-repository-storage-grenze.md)). Schreibvorgänge atomar (`AtomicWriter`), mutierende Aktionen mit Lock (`LockManager`).
- **KI-Zugriff** nur über das `ModelGateway`, nie direkt gegen eine Anbieter-API ([ADR-004](adr/ADR-004-modellanbieter-interface.md)).
- **Sprache:** Code-Bezeichner Englisch; UI-Texte, Fehlermeldungen und Doku Deutsch mit korrekten Umlauten.
- **Kein toter Code:** Ersetztes wird gelöscht, nicht auskommentiert; Historie liegt in Git.
- **Kommentare** erklären Grenzen und Gründe (Host-Limits, Fallbacks, Format-Eigenheiten), nicht das offensichtliche Was.
- **Fehlerbehandlung:** Jede Operation endet in Erfolg, Warnung oder Fehler mit verständlicher deutscher Meldung und Handlungsoption — keine stillen Fehler, keine leeren Erfolgsmeldungen bei dünnem Ergebnis.
- **Kleine Commits** mit aussagekräftiger Message, thematisch geschlossen.

## Definition of Done

Eine Änderung ist fertig, wenn:

1. `php -l` und `php tests/run.php` fehlerfrei sind,
2. neue Kernlogik durch Testfälle in `tests/run.php` abgesichert ist,
3. keine direkte Dateioperation in neuer Fachlogik entstanden ist,
4. Nutzertexte korrekt deutsch formuliert sind,
5. Datenschutz-/Sicherheitsauswirkung bedacht ist,
6. die betroffene Doku unter `docs/` aktualisiert ist (Architektur bei Strukturänderungen, ADR bei Grundsatzentscheidungen).

## Wo was liegt

Siehe [`docs/architektur.md`](architektur.md) für Schichtenmodell, Modulkarte und Datenflüsse sowie [`docs/adr/`](adr/) für die Begründung der wichtigsten Entscheidungen.
