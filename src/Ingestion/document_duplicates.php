<?php
declare(strict_types=1);

function uploaded_file_sha256(array $file): ?string
{
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_file($tmp) || !is_readable($tmp)) {
        return null;
    }

    $hash = @hash_file('sha256', $tmp);
    return is_string($hash) && preg_match('/^[a-f0-9]{64}$/', $hash) ? $hash : null;
}

function find_duplicate_document_by_sha256(array $project, string $sha256): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $sha256)) {
        return null;
    }

    $documents = is_array($project['documents'] ?? null) ? $project['documents'] : [];
    foreach ($documents as $index => $document) {
        if (!is_array($document)) {
            continue;
        }

        $documentHash = normalize_document_sha256($document);
        if ($documentHash === null) {
            $documentHash = stored_document_sha256($document);
        }
        if ($documentHash !== $sha256) {
            continue;
        }

        $document['_index'] = $index;
        $document['_matched_sha256'] = $sha256;
        return $document;
    }

    return null;
}

function normalize_document_sha256(array $document): ?string
{
    foreach (['sha256', 'content_sha256', 'original_sha256'] as $key) {
        $value = strtolower(trim((string) ($document[$key] ?? '')));
        if (preg_match('/^[a-f0-9]{64}$/', $value)) {
            return $value;
        }
    }

    return null;
}

function stored_document_sha256(array $document): ?string
{
    $storedName = trim((string) ($document['stored_name'] ?? ''));
    if ($storedName === '' || $storedName !== basename($storedName)) {
        return null;
    }

    $path = uploads_dir() . '/' . $storedName;
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }

    $hash = @hash_file('sha256', $path);
    return is_string($hash) && preg_match('/^[a-f0-9]{64}$/', $hash) ? $hash : null;
}

function duplicate_upload_result(string $originalName, string $sha256, array $existingDocument): array
{
    $uploadedAt = trim((string) ($existingDocument['uploaded_at'] ?? ''));
    $existingName = trim((string) ($existingDocument['original_name'] ?? $originalName));
    $message = 'Diese Datei wurde bereits hochgeladen und wird nicht erneut verarbeitet.';
    if ($existingName !== '') {
        $message = 'Diese Datei wurde bereits als "' . $existingName . '" hochgeladen und wird nicht erneut verarbeitet.';
    }
    if ($uploadedAt !== '') {
        $message .= ' Vorhandener Upload: ' . $uploadedAt . '.';
    }

    return [
        'ok' => true,
        'skipped_duplicate' => true,
        'message' => $message,
        'sha256' => $sha256,
        'saved_chunks' => [],
        'document' => $existingDocument,
        'duplicate_document' => $existingDocument,
    ];
}
