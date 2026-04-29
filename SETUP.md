# Setup

## Voraussetzungen

- PHP 8.0+
- PHP-Erweiterungen: `curl`, `fileinfo`, `mbstring`
- Schreibrechte für das Laufzeitdatenverzeichnis
- Ein Gemini-API-Schlüssel

## Laufzeitdaten

Standardmäßig schreibt der Assistent in `config/` und `rag/` innerhalb des Projekts.

Für produktive Deployments wird empfohlen, die Umgebungsvariable `BERATUNGSASSISTENT_DATA_DIR` auf ein Verzeichnis außerhalb des Webroots zu setzen. Dort werden dann gespeichert:

- `config/config.php`
- `config/project.json`
- `rag/.admin_password`
- `rag/chunks/`
- `rag/uploads/`

## Ersteinrichtung

### 1. Dateien deployen

Den Projektordner auf den Webserver kopieren oder das VPS-Paket aus dem Release entpacken.

### 2. Optional externes Datenverzeichnis setzen

Beispiel unter Apache:

```apache
SetEnv BERATUNGSASSISTENT_DATA_DIR /var/lib/beratungsassistent
```

### 3. Admin-Wizard starten

`admin.php` im Browser aufrufen.

### 4. Schritt 1: Admin-Passwort

- Passwort festlegen
- Das Passwort wird als Hash im Datenverzeichnis gespeichert

### 5. Schritt 2: API-Schlüssel

- Gemini-API-Schlüssel eintragen
- Optional das Modell anpassen
- Die Konfiguration wird im Datenverzeichnis unter `config/config.php` gespeichert

### 6. Schritt 3: Projektprofil

- Titel des Beratungsassistenten festlegen
- Themenfeld beschreiben
- Zielgruppe angeben

Diese Angaben steuern:

- die serverseitige Systemanweisung
- den Titel und Untertitel im Frontend
- den fachlichen Scope des Assistenten

### 7. Schritt 4: Dateien hochladen

- Eine oder mehrere Dateien hochladen
- In dieser ersten Version werden PDF, TXT und Markdown unterstützt

Beim Upload passieren drei Dinge:

1. Die Originaldateien werden im Datenverzeichnis unter `rag/uploads/` gespeichert.
2. Gemini erzeugt daraus Markdown-Chunks in `rag/chunks/`.
3. Aus den vorhandenen Chunks werden Schnellfragen, Aufgabenbeispiele und Vorlagen für das Frontend neu erzeugt.

### 8. Einsatzbereit

Nach dem letzten Schritt ist der Assistent unter `index.html` direkt nutzbar.

## Hinweise zum Hosting

- Der API-Schlüssel darf nie ins Frontend oder in öffentliches JavaScript gelangen.
- Das Datenverzeichnis sollte in Produktion außerhalb des Webroots liegen.
- Wenn der Assistent hinter einem Reverse Proxy läuft, sollte Streaming für `proxy.php` nicht gepuffert werden.
- Für Docker und VPS-Bereitstellung siehe `DEPLOY.md`.
