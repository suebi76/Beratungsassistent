<?php
declare(strict_types=1);

function process_uploaded_document(array $file, array $project, array $apiConfig): array
{
    $validation = validate_uploaded_file($file);
    if (!($validation['ok'] ?? false)) {
        return ['ok' => false, 'error' => $validation['error'] ?? 'Datei konnte nicht validiert werden.'];
    }

    $stored = store_uploaded_file($file);
    if (!($stored['ok'] ?? false)) {
        return ['ok' => false, 'error' => $stored['error'] ?? 'Datei konnte nicht gespeichert werden.'];
    }

    $parts = build_document_parts_for_gemini($stored['path'], (string) $file['name'], (string) $validation['extension']);
    $parts[] = ['text' => build_chunk_generation_prompt($project, (string) $file['name'], (string) $validation['extension'])];

    $generation = gemini_generate_text($parts, $apiConfig, [
        'temperature' => 0.2,
        'maxOutputTokens' => 65536,
        'timeout' => 240,
    ]);

    if (!($generation['ok'] ?? false)) {
        return [
            'ok' => false,
            'error' => $generation['error'] ?? 'Gemini konnte die Datei nicht verarbeiten.',
            'stored_name' => $stored['stored_name'],
        ];
    }

    $savedChunks = save_chunks_from_response((string) $generation['text'], (string) $file['name'], (string) $validation['extension']);
    if ($savedChunks === []) {
        return [
            'ok' => false,
            'error' => 'Gemini hat keine verwertbaren Chunks geliefert. Bitte die Datei erneut hochladen oder als kleinere Teildateien bereitstellen.',
            'stored_name' => $stored['stored_name'],
        ];
    }

    return [
        'ok' => true,
        'stored_name' => $stored['stored_name'],
        'saved_chunks' => $savedChunks,
        'document' => [
            'original_name' => (string) $file['name'],
            'stored_name' => $stored['stored_name'],
            'mime_type' => (string) $validation['mime'],
            'extension' => (string) $validation['extension'],
            'bytes' => (int) ($file['size'] ?? 0),
            'uploaded_at' => now_iso(),
            'chunks_created' => count($savedChunks),
        ],
    ];
}

