# Deployment

## Docker

### Starten

```bash
docker compose up -d --build
```

Danach ist die Anwendung unter `http://localhost:8080` erreichbar.
Die Ersteinrichtung startet unter `http://localhost:8080/admin.php`.

### Persistente Daten

Das bereitgestellte `docker-compose.yml` verwendet ein Docker-Volume für `/data`.
Dort speichert die Anwendung:

- API-Konfiguration
- Projektprofil
- Admin-Passwort
- Uploads
- generierte Wissens-Chunks

### Aktualisieren

```bash
git pull
docker compose up -d --build
```

## VPS oder klassischer PHP-Webserver

### Paket verwenden

Im Release steht ein VPS-ZIP bereit. Es enthält die komplette Anwendung ohne Git-Metadaten.

### Manuelles Deployment

1. Projekt entpacken oder deployen
2. Optional ein externes Datenverzeichnis anlegen, zum Beispiel `/var/lib/beratungsassistent`
3. Die Umgebungsvariable `BERATUNGSASSISTENT_DATA_DIR` darauf setzen
4. Schreibrechte für den Webserver-Benutzer sicherstellen
5. `admin.php` aufrufen und die Ersteinrichtung durchlaufen

### Apache-Beispiel

```apache
<VirtualHost *:80>
    ServerName beratung.example.org
    DocumentRoot /var/www/beratungsassistent

    SetEnv BERATUNGSASSISTENT_DATA_DIR /var/lib/beratungsassistent

    <Directory /var/www/beratungsassistent>
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>
```

## Release-Paket lokal erzeugen

Unter Windows oder in PowerShell:

```powershell
./scripts/create-release-package.ps1 -ArchiveName beratungsassistent-vps.zip
```

Das ZIP wird unter `dist/` erzeugt.
