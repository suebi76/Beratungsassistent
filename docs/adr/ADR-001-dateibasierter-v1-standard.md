# ADR-001: Dateibasierter v1-Standard

## Status

Akzeptiert.

## Entscheidung

Version 1 bleibt standardmäßig dateibasiert. Das System benötigt keine MySQL-, PostgreSQL- oder sonstige externe Datenbank.

## Begründung

- Einfache Webhoster bleiben möglich.
- Backups sind verständlich: Datenverzeichnis sichern.
- Behörden-IT kann Dateien prüfen und exportieren.
- Installation bleibt niedrigschwellig.

## Konsequenzen

- Dateizugriffe müssen robust gebaut werden.
- Mutierende Aktionen brauchen Locks.
- Schreibvorgänge erfolgen atomar über temporäre Dateien und Rename.
- Spätere DB-Version wird vorbereitet, aber nicht in v1 erzwungen.
