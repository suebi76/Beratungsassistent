# ADR-004: Modellanbieter über Provider-Interface

## Status

Akzeptiert.

## Entscheidung

KI-Anbieter werden über ein Provider-Interface angebunden. Gemini bleibt Standard, darf aber nicht dauerhaft direkt in Chat, Ingestion oder Profilgenerierung verdrahtet bleiben.

## Begründung

- Behörden benötigen Anbieterfreiheit.
- Datenschutz- und Beschaffungsbewertungen unterscheiden sich je Anbieter.
- Provider-Ausfälle oder Überlastung dürfen nicht die Architektur bestimmen.

## Konsequenzen

- `GeminiProvider` kapselt den heutigen Gemini-Code.
- `OpenAiCompatibleProvider` wird für lokale oder behördliche Modellendpunkte vorbereitet.
- Datenschutz- und Datenflussdokumentation wird providerabhängig.
