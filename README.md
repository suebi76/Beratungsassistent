# Beratungsassistent

Konfigurierbarer KI-Beratungsassistent mit dateibasierter Wissensbasis, serverseitigem Gemini-Proxy und einer gefuehrten Ersteinrichtung.

Die Anwendung ist als generischer Starter aufgebaut: Beim ersten Start werden Admin-Passwort, API-Key, Titel, Themenfeld und Zielgruppe festgelegt. Anschliessend werden eine oder mehrere Dateien hochgeladen, aus denen die Wissensbasis, die Quick Questions und die Vorlagen fuer das Frontend erzeugt werden.

## Funktionen

- Gefuehrter First-Run-Wizard in vier Schritten: Passwort, API, Profil, Dateien
- Serverseitiger Gemini-Proxy: Kein API-Key im Browser
- Generische Projektkonfiguration: Titel, Scope, Zielgruppe und UI-Beispiele kommen aus einer gemeinsamen Konfigurationsdatei
- Dokumentgestuetzte Wissensbasis: Upload von PDF, TXT und Markdown
- Automatische Chunk-Erzeugung per Gemini
- Automatische Frontend-Generierung aus der Wissensbasis:
  Quick Questions, Aufgabenbeispiele und Vorlagen werden nach dem Upload neu erzeugt
- RAG-Antworten mit Quellenhinweis
- Lokale Bibliotheken in `vendor/`, kein CDN im Frontend

## Projektstruktur

```text
index.html              React-Frontend fuer normale Nutzer
admin.php               Wizard und Admin-Dashboard
proxy.php               Serverseitiger Gemini-Proxy mit Retrieval
project.php             Oeffentliche Laufzeit-Konfiguration fuer das Frontend
lib/app.php             Gemeinsame PHP-Helfer fuer Konfiguration, Upload, Chunking und Retrieval
config/
  config.php.example    Beispiel fuer API-Konfiguration
  project.json.example  Beispiel fuer die Projektkonfiguration
rag/
  ANLEITUNG.md          Hinweise zum Chunk-Format und zur Wissensbasis
  chunks/               Generierte Wissens-Chunks
  uploads/              Originaldateien der Wissensbasis
vendor/                 Lokal eingebundene Bibliotheken
```

## Setup

1. Die Projektdateien auf einen PHP-Webserver mit `curl`, `fileinfo` und `mbstring` laden.
2. `admin.php` im Browser aufrufen.
3. Im Wizard:
   Admin-Passwort setzen
   Gemini-API-Key und Modell eintragen
   Titel, Themenfeld und Zielgruppe festlegen
   Eine oder mehrere Dateien hochladen
4. Nach der Verarbeitung ist der Assistent unter `index.html` sofort einsatzbereit.

Eine ausfuehrlichere Schritt-fuer-Schritt-Anleitung steht in `SETUP.md`.

## Wie das RAG hier funktioniert

Dieses Projekt ist ein RAG-System, aber in einer bewusst einfachen ersten Ausbaustufe:

- Die hochgeladenen Dokumente werden in fachliche Markdown-Chunks zerlegt.
- Bei jeder Nutzerfrage werden diese Chunks serverseitig erneut durchsucht.
- Die besten Treffer werden an den System-Prompt angehaengt.
- Das Modell generiert die Antwort auf Basis der Frage plus der abgerufenen Chunks.

Wichtig fuer die Einordnung:

- Ja, das ist Retrieval-Augmented Generation, weil vor der Antwort relevante Wissensfragmente aus einer externen Wissensbasis geholt und in die Generierung eingebettet werden.
- Nein, es ist kein Vektor-RAG mit Embeddings oder einer Vector Database.
- Die Retrieval-Stufe ist aktuell lexikalisch:
  Titel, Tags, Quelle und Chunk-Text werden per gewichteter Keyword-Suche bewertet.
- Die Chunk-Erzeugung selbst ist kein Retrieval, sondern ein vorgeschalteter Ingest/Preprocessing-Schritt.

## Technische Bewertung der bisherigen 1:1-Version

Die bestehende 1:1-Version unter `KI Chatbot-1zu1` war bereits ein funktionierendes RAG-System im engeren Sinn:

- Es gab persistente Wissens-Chunks ausserhalb des Modells.
- Vor jeder Antwort wurden passende Chunks aus dem Dateisystem gesucht.
- Diese Chunks wurden an den Prompt angehaengt.

Die Grenzen der alten Version lagen nicht im "Ob", sondern im "Wie":

- Das Retrieval war ebenfalls nur keyword-basiert.
- Es gab keine Embeddings, kein Re-Ranking und keine semantische Vektor-Suche.
- Die fachlichen Prompts und Frontend-Beispiele waren hart auf die 1:1-Ausstattung zugeschnitten.

Die neue Version verallgemeinert genau diese Architektur.

## Dateitypen in dieser ersten Version

- PDF
- TXT
- Markdown (`.md`, `.markdown`)

Weitere Formate koennen spaeter ueber dieselbe Ingest-Pipeline ergaenzt werden.

## Sicherheit und Betrieb

- API-Key bleibt serverseitig in `config/config.php`
- Admin-Passwort wird gehasht in `rag/.admin_password` gespeichert
- Wissensdateien und Chunks liegen ausserhalb des normalen Frontend-Zugriffs und werden nur ueber PHP verwendet
- Der serverseitige Prompt wird nicht aus dem Frontend uebernommen, sondern aus der Projektkonfiguration erzeugt

## Lizenz

CC BY 4.0.
