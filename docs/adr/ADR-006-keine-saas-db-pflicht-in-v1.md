# ADR-006: Keine SaaS- und DB-Pflicht in v1

## Status

Akzeptiert.

## Entscheidung

v1 wird keine SaaS-Mehrmandantenplattform und keine echte Datenbankpflicht.

## Begründung

Das Kernziel ist eine leicht installierbare Behördeninstanz. Mehrmandantenfähigkeit, zentrale Nutzerverwaltung und große Datenbankarchitektur würden v1 unnötig verzögern.

## Konsequenzen

- Eine spätere DB-Version kann als eigene Linie oder Fork entstehen.
- v1 fokussiert lokale/behördliche Einzelinstanzen.
- Mandantenähnliche Profile sind optional, aber kein v1-Kernziel.
