# ADR-008: PDF-Splitting als Fallback-Kette

## Status

Akzeptiert.

## Entscheidung

Große PDFs werden in v1 nicht über eine einzige verpflichtende Servertechnik verarbeitet. Das System plant eine Fallback-Kette:

1. Serverseitiges PDF-Splitting, wenn die konkrete Installation es sicher unterstützt.
2. Browserseitiges lokales Splitting, wenn Server-Splitting nicht verfügbar oder riskant ist.
3. Optionaler Windows-Portable-Splitter für Behörden und einfache Webhoster.
4. Manuelles Splitting als letzter, dokumentierter Fallback.

Bei jedem Split muss der Admin die Seitenanzahl pro Teil bestimmen können. Die erzeugten Dateien erhalten deterministische Namen mit Teilnummer und Seitenbereich.

Es wird kein eigener PDF-Parser von null entwickelt. Eigene Entwicklung bedeutet hier: eigener Bedienablauf, eigenes Namensschema, eigenes Manifest und Integration in den Beratungsassistenten. Die eigentliche PDF-Verarbeitung nutzt eine geprüfte, wartbare PDF-Bibliothek oder ein kontrolliertes Backend.

## Begründung

Einfache Webhoster unterscheiden sich stark. Manche erlauben keine Shell-Kommandos, haben niedrige Speicherlimits oder brechen lange Prozesse ab. Eine robuste v1 darf deshalb nicht voraussetzen, dass ein mehrhundertseitiges PDF immer serverseitig verarbeitet werden kann.

Gleichzeitig ist Groß-PDF-Verarbeitung für den Beratungsassistenten fachlich zentral. Ein realer Anwendungsfall hat gezeigt, dass große Fachwerke nachvollziehbar in die Wissensbasis gelangen müssen, statt unbemerkt nur dünn verarbeitet zu werden.

## Konsequenzen

- Der Adminbereich braucht klare Handlungsoptionen statt technischer Fehlermeldungen.
- Systemcheck muss PDF-Fähigkeiten und relevante Hosting-Limits anzeigen.
- Dokumentgruppen und Split-Metadaten werden Teil des Datenmodells.
- Chunks müssen Seitenbereiche aus dem Originaldokument nachvollziehbar speichern können.
- Ein optionales Portable-Tool darf lokal helfen, ersetzt aber nicht die Webhost-Kompatibilität.
- Es wird kein eigener PDF-Kernparser geschrieben; PDF-Verarbeitung nutzt geprüfte Bibliotheken oder kontrollierte Backends.
- Vor Auswahl einer Bibliothek gibt es eine Lizenz-, Sicherheits- und Wartbarkeitsprüfung.

## Nicht-Ziele

- Keine Pflicht zu Docker oder CLI-Tools für v1.
- Keine Zusage, dass jedes beliebige defekte, verschlüsselte oder gescannte PDF automatisch korrekt verarbeitet wird.
- Keine Datenbankpflicht für Split-Metadaten.
- Kein selbst entwickelter PDF-Kernparser.
