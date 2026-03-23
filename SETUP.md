# Setup

## Voraussetzungen

- Apache oder ein anderer PHP-faehiger Webserver
- PHP 8.0+
- PHP-Erweiterungen: `curl`, `fileinfo`, `mbstring`
- Schreibrechte fuer:
  `config/`
  `rag/chunks/`
  `rag/uploads/`
- Ein Gemini-API-Key

## Ersteinrichtung

### 1. Dateien deployen

Den gesamten Projektordner auf den Webserver kopieren.

### 2. Admin-Wizard starten

`admin.php` im Browser aufrufen.

### 3. Schritt 1: Admin-Passwort

- Passwort festlegen
- Das Passwort wird als Hash in `rag/.admin_password` gespeichert

### 4. Schritt 2: API-Key

- Gemini-API-Key eintragen
- Optional das Modell anpassen
- Die Konfiguration wird in `config/config.php` gespeichert

### 5. Schritt 3: Projektprofil

- Titel des Beratungsassistenten festlegen
- Themenfeld beschreiben
- Zielgruppe angeben

Diese Angaben steuern:

- den serverseitigen System-Prompt
- den Titel und Untertitel im Frontend
- den fachlichen Scope des Assistenten

### 6. Schritt 4: Dateien hochladen

- Eine oder mehrere Dateien hochladen
- In dieser ersten Version werden PDF, TXT und Markdown unterstuetzt

Beim Upload passieren drei Dinge:

1. Die Originaldateien werden in `rag/uploads/` gespeichert.
2. Gemini erzeugt daraus Markdown-Chunks in `rag/chunks/`.
3. Aus den vorhandenen Chunks werden Quick Questions, Aufgabenbeispiele und Vorlagen fuer das Frontend neu erzeugt.

### 7. Einsatzbereit

Nach dem letzten Schritt ist der Assistent unter `index.html` direkt nutzbar.

## Laufender Betrieb

Im Admin-Dashboard koennen danach:

- weitere Dateien hochgeladen werden
- das Projektprofil angepasst werden
- Frontend-Beispiele aus der Wissensbasis neu generiert werden
- einzelne Chunks geloescht werden
- API-Key und Passwort geaendert werden

## Hinweise zum Hosting

- Der API-Key darf nie ins Frontend oder in ein oeffentliches JavaScript gelangen.
- Die Verzeichnisse `config/` und `rag/` sollten per Serverregel gegen direkten HTTP-Zugriff geschuetzt sein.
- Wenn der Assistent hinter einem Reverse Proxy laeuft, sollte Streaming fuer `proxy.php` nicht gepuffert werden.
