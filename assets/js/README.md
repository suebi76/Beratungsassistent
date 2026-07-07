# Frontend-Struktur ohne Build-Schritt

Das Frontend bleibt bewusst build-frei, damit das bestehende Deployment unverändert funktioniert. Alle JSX-Dateien werden im Browser durch die lokal ausgelieferte `vendor/babel.min.js` verarbeitet.

## Lade-Reihenfolge

1. `markdown.js`: Markdown-Rendering und HTML-Sanitizer.
2. `pdf-export.js`: PDF-Export über `jsPDF`.
3. `icons.jsx`: zentrale Icon-Komponente.
4. `app-shell.jsx`: Header, Footer und technische Start-/Fehlerzustände.
5. `chat-view.jsx`: Chat-Ansicht, Nachrichten, Eingabefeld und Fehleranzeige.
6. `templates-view.jsx`: Vorlagen- und Schnelleinstieg-Ansicht.
7. `privacy-modals.jsx`: Datenschutz- und technische Hinweisdialoge.
8. `app.jsx`: Zustand, API-Aufrufe und Verdrahtung der Komponenten.

## Regeln

- Jede JSX-Datei kapselt sich in einer IIFE `(function () { ... })();`.
- Exporte laufen nur über eindeutige `window.Beratungsassistent...`-Namen.
- Keine neuen top-level `const`/`let`-Namen außerhalb einer IIFE, weil `text/babel`-Skripte sonst im globalen Scope kollidieren können.
- `app.jsx` bleibt Orchestrierung: State, Events, Fetch/Streaming, Komponenten-Verdrahtung.
- Reine Darstellung kommt in spezialisierte Komponenten-Dateien.

## Nächster sauberer Schritt

Wenn ein Build-Schritt akzeptiert wird, sollten diese globalen Exporte in echte ES-Module überführt werden. Bis dahin ist diese Struktur der risikoarme Kompromiss für Hosting ohne Node/npm.
