<?php
declare(strict_types=1);

function supported_extensions(): array
{
    return [
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'md' => 'text/markdown',
        'markdown' => 'text/markdown',
    ];
}

function normalize_uploaded_files(array $files): array
{
    $result = [];
    $names = $files['name'] ?? [];
    if (!is_array($names)) {
        return [$files];
    }

    foreach (array_keys($names) as $index) {
        $result[] = [
            'name' => $files['name'][$index] ?? '',
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return $result;
}

function validate_uploaded_file(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload fehlgeschlagen (Code ' . ($file['error'] ?? '?') . ').'];
    }

    if (($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        return ['ok' => false, 'error' => 'Datei zu gross. Maximal erlaubt sind 20 MB.'];
    }

    $name = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!array_key_exists($extension, supported_extensions())) {
        return ['ok' => false, 'error' => 'Nicht unterstützter Dateityp. Erlaubt sind PDF, TXT und Markdown.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: 'application/octet-stream';

    if ($extension === 'pdf' && $mime !== 'application/pdf') {
        return ['ok' => false, 'error' => 'Die Datei wurde nicht als PDF erkannt.'];
    }

    if ($extension !== 'pdf' && !preg_match('/^(text\\/|application\\/octet-stream$)/', $mime)) {
        return ['ok' => false, 'error' => 'Textdatei konnte nicht sicher gelesen werden. Bitte PDF, TXT oder Markdown verwenden.'];
    }

    return [
        'ok' => true,
        'extension' => $extension,
        'mime' => $mime,
    ];
}

function unique_stored_filename(string $originalName): string
{
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $base = slugify(pathinfo($originalName, PATHINFO_FILENAME));
    $candidate = $base . '-' . date('Ymd-His');
    $full = uploads_dir() . '/' . $candidate . '.' . $extension;
    $suffix = 2;
    while (file_exists($full)) {
        $full = uploads_dir() . '/' . $candidate . '-' . $suffix . '.' . $extension;
        $suffix++;
    }
    return basename($full);
}

function store_uploaded_file(array $file): array
{
    ensure_app_dirs();
    ensure_runtime_placeholders();

    $storedName = unique_stored_filename((string) $file['name']);
    $target = uploads_dir() . '/' . $storedName;

    if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
        return ['ok' => false, 'error' => 'Datei konnte serverseitig nicht gespeichert werden.'];
    }

    return [
        'ok' => true,
        'stored_name' => $storedName,
        'path' => $target,
    ];
}

function read_text_source(string $path): string
{
    $content = (string) @file_get_contents($path, false, null, 0, MAX_TEXT_SOURCE_BYTES);
    return trim($content);
}

