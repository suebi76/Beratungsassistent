# ADR-007: Datenschutz vor API-Aufruf

## Status

Akzeptiert.

## Entscheidung

Der Modus "keine personenbezogenen Daten" darf nicht nur über die Systemanweisung umgesetzt werden. v1 benötigt eine serverseitige Vorprüfung, bevor Inhalte an den KI-Anbieter übertragen werden.

## Begründung

Für Behörden ist entscheidend, ob personenbezogene Daten technisch vor der Übertragung blockiert werden. Ein Prompt-Verbot reicht dafür nicht.

## Konsequenzen

- PII-/Secret-Prüfung wird als eigene Schutzschicht geplant.
- Datenschutztexte müssen die technische Realität korrekt beschreiben.
- Audit-Logs dürfen keine sensiblen Inhalte speichern.
