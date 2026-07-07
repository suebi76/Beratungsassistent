# ADR-003: Repository-/Storage-Grenze

## Status

Akzeptiert.

## Entscheidung

Neue Fachlogik greift nicht direkt auf Dateien zu. Persistenz läuft über Repositories und Storage-Klassen.

## Begründung

- Spätere Datenbankversion bleibt möglich.
- Code bleibt testbar.
- Dateioperationen, Locks, Validierung und atomare Writes werden zentralisiert.

## Zielstruktur

```text
Controller -> Service -> Repository -> Storage -> Datei / optional SQLite
```

## Konsequenzen

- Bestehende Funktionen wie `load_project_config()` dürfen als Kompatibilitätswrapper bleiben, müssen intern aber auf Repositories umgestellt werden.
- Neue Funktionen mit direktem `file_get_contents()` oder `file_put_contents()` in Fachlogik sind nicht akzeptiert.
