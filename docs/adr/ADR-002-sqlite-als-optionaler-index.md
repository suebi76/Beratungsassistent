# ADR-002: SQLite als optionaler Index

## Status

Akzeptiert.

## Entscheidung

SQLite darf als optionaler Such- und Diagnoseindex verwendet werden. SQLite ist nicht die fachliche Wahrheit.

## Begründung

- SQLite ist auf vielen PHP-Hostern verfügbar.
- FTS5 kann Volltextsuche und BM25-ähnliches Ranking stark verbessern.
- Ohne SQLite muss das System weiter lauffähig bleiben.

## Konsequenzen

- Alle SQLite-Indizes müssen aus Dateien neu erzeugbar sein.
- Setup erkennt SQLite-Verfügbarkeit.
- Admin zeigt transparent, ob Dateiindex oder SQLite-Index aktiv ist.
