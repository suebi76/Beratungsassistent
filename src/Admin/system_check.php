<?php
declare(strict_types=1);

function admin_system_check_items(array $model): array
{
    $apiConfig = is_array($model['apiConfig'] ?? null) ? $model['apiConfig'] : load_api_config();
    $uploadMax = admin_ini_bytes('upload_max_filesize');
    $postMax = admin_ini_bytes('post_max_size');
    $maxUploads = (int) ini_get('max_file_uploads');
    $executionTime = (int) ini_get('max_execution_time');
    $memoryLimit = admin_ini_bytes('memory_limit');
    $uploadTmpDir = trim((string) ini_get('upload_tmp_dir'));

    return [
        admin_check_item('PHP-Version', version_compare(PHP_VERSION, '8.1.0', '>=') ? 'ok' : 'error', PHP_VERSION, 'Empfohlen ist PHP 8.1 oder neuer.'),
        admin_check_item('PHP-Erweiterung cURL', extension_loaded('curl') && function_exists('curl_init') ? 'ok' : 'error', admin_yes_no(extension_loaded('curl')), 'Ohne cURL können KI-Anbieter nicht aufgerufen werden.'),
        admin_check_item('PHP-Erweiterung fileinfo', extension_loaded('fileinfo') ? 'ok' : 'error', admin_yes_no(extension_loaded('fileinfo')), 'Wird benötigt, um Upload-Dateien sicher zu erkennen.'),
        admin_check_item('PHP-Erweiterung mbstring', extension_loaded('mbstring') ? 'ok' : 'error', admin_yes_no(extension_loaded('mbstring')), 'Wird für robuste UTF-8-Textverarbeitung benötigt.'),
        admin_check_item('upload_max_filesize', $uploadMax >= MAX_UPLOAD_BYTES ? 'ok' : 'error', ini_get('upload_max_filesize'), 'Muss mindestens ' . admin_format_bytes(MAX_UPLOAD_BYTES) . ' erlauben.'),
        admin_check_item('post_max_size', $postMax >= MAX_UPLOAD_BYTES ? 'ok' : 'error', ini_get('post_max_size'), 'Muss größer oder gleich upload_max_filesize sein.'),
        admin_check_item('max_file_uploads', $maxUploads >= 5 ? 'ok' : 'warning', (string) $maxUploads, 'Für Warteschlangen reichen Einzeluploads; für Fallback-Mehrfachupload sind mehrere Dateien sinnvoll.'),
        admin_check_item('max_execution_time', $executionTime === 0 || $executionTime >= 240 ? 'ok' : 'warning', $executionTime === 0 ? 'unbegrenzt' : $executionTime . ' Sekunden', 'Große PDFs und langsame KI-Antworten brauchen längere Laufzeit.'),
        admin_check_item('memory_limit', $memoryLimit === PHP_INT_MAX || $memoryLimit >= 128 * 1024 * 1024 ? 'ok' : 'warning', ini_get('memory_limit'), 'PDF-Verarbeitung benötigt ausreichend Speicher.'),
        admin_check_item('upload_tmp_dir', admin_upload_tmp_dir_is_usable($uploadTmpDir) ? 'ok' : 'warning', $uploadTmpDir !== '' ? $uploadTmpDir : 'Systemstandard', 'Temporäres PHP-Uploadverzeichnis muss vom Webserver nutzbar sein.'),
        admin_check_item('PDF-Seitenzählung', 'warning', 'heuristisch', 'Ohne geprüfte PDF-Bibliothek wird die Seitenzahl nur grob aus der Datei geschätzt. Das reicht für Hinweise, nicht für verbindliches Splitten.'),
        admin_check_item('Serverseitiges PDF-Splitting', 'warning', 'nicht aktiviert', 'Aktuell ist bewusst kein ungeprüfter PDF-Splitter aktiv. Fallbacks: lokale/browserseitige Teilung und später ein geprüftes Backend.'),
        admin_check_writable('Konfiguration beschreibbar', config_dir()),
        admin_check_writable('Uploads beschreibbar', uploads_dir()),
        admin_check_writable('Textabschnitte beschreibbar', chunks_dir()),
        admin_check_writable('Jobstatus beschreibbar', admin_upload_job_dir(), true),
        admin_check_item('KI-Anbieter konfiguriert', api_key_is_configured($apiConfig) ? 'ok' : 'error', api_key_is_configured($apiConfig) ? 'ja' : 'nein', 'Die Live-Erreichbarkeit wird über den Verbindungstest geprüft.'),
    ];
}

function admin_check_item(string $label, string $status, string $value, string $detail): array
{
    return [
        'label' => $label,
        'status' => in_array($status, ['ok', 'warning', 'error'], true) ? $status : 'warning',
        'value' => $value,
        'detail' => $detail,
    ];
}

function admin_check_writable(string $label, string $path, bool $createIfMissing = false): array
{
    if ($createIfMissing && !is_dir($path)) {
        @mkdir($path, 0755, true);
    }
    $writable = admin_directory_accepts_write($path);
    return admin_check_item(
        $label,
        $writable ? 'ok' : 'error',
        $writable ? 'ja' : 'nein',
        $path
    );
}

function admin_directory_accepts_write(string $path): bool
{
    if (!is_dir($path)) {
        return false;
    }

    $probe = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '.admin-write-probe-' . bin2hex(random_bytes(4));
    if (@file_put_contents($probe, 'ok') === false) {
        return false;
    }
    @unlink($probe);
    return true;
}

function admin_ini_bytes(string $key): int
{
    $value = trim((string) ini_get($key));
    if ($value === '') {
        return 0;
    }
    if ($value === '-1') {
        return PHP_INT_MAX;
    }

    $unit = strtolower(substr($value, -1));
    $number = (float) $value;
    return (int) match ($unit) {
        'g' => $number * 1024 * 1024 * 1024,
        'm' => $number * 1024 * 1024,
        'k' => $number * 1024,
        default => $number,
    };
}

function admin_upload_tmp_dir_is_usable(string $uploadTmpDir): bool
{
    if ($uploadTmpDir === '') {
        return true;
    }
    return is_dir($uploadTmpDir) && is_writable($uploadTmpDir);
}

function admin_format_bytes(int $bytes): string
{
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / 1024 / 1024, 1, ',', '.') . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 1, ',', '.') . ' KB';
    }
    return $bytes . ' B';
}

function admin_yes_no(bool $value): string
{
    return $value ? 'ja' : 'nein';
}
