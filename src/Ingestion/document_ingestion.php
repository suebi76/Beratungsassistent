<?php
declare(strict_types=1);

function process_uploaded_document(array $file, array $project, array $apiConfig, ?callable $onProgress = null): array
{
    document_ingestion_progress($onProgress, 'validating', 8, 'Datei wird geprüft.');
    $validation = validate_uploaded_file($file);
    if (!($validation['ok'] ?? false)) {
        return ['ok' => false, 'error' => $validation['error'] ?? 'Datei konnte nicht validiert werden.'];
    }

    document_ingestion_progress($onProgress, 'storing', 18, 'Datei wird gespeichert.');
    $stored = store_uploaded_file($file);
    if (!($stored['ok'] ?? false)) {
        return ['ok' => false, 'error' => $stored['error'] ?? 'Datei konnte nicht gespeichert werden.'];
    }

    if ((string) $validation['extension'] === 'pdf' && (int) ($file['size'] ?? 0) >= 8 * 1024 * 1024) {
        document_ingestion_progress($onProgress, 'large_pdf', 22, 'Große PDF erkannt. Die Verarbeitung kann mehrere Minuten dauern.');
    }

    document_ingestion_progress($onProgress, 'preparing', 28, 'Datei wird für die KI-Verarbeitung vorbereitet.');
    $parts = build_document_parts_for_gemini($stored['path'], (string) $file['name'], (string) $validation['extension']);
    $parts[] = ['text' => build_chunk_generation_prompt($project, (string) $file['name'], (string) $validation['extension'])];

    document_ingestion_progress($onProgress, 'model_processing', 45, 'KI verarbeitet die Datei. Bitte warten.');
    $generation = model_generate_text($parts, $apiConfig, [
        'temperature' => 0.2,
        'maxOutputTokens' => 65536,
        'timeout' => 240,
        'retries' => 2,
        'retryDelaySeconds' => 4,
    ], 'chunk_generation');

    if (!($generation['ok'] ?? false)) {
        return [
            'ok' => false,
            'error' => $generation['error'] ?? 'Gemini konnte die Datei nicht verarbeiten.',
            'stored_name' => $stored['stored_name'],
        ];
    }

    document_ingestion_progress($onProgress, 'saving_chunks', 84, 'Textabschnitte werden gespeichert.');
    $savedChunks = save_chunks_from_response((string) $generation['text'], (string) $file['name'], (string) $validation['extension']);
    if ($savedChunks === []) {
        return [
            'ok' => false,
            'error' => 'Gemini hat keine verwertbaren Chunks geliefert. Bitte die Datei erneut hochladen oder als kleinere Teildateien bereitstellen.',
            'stored_name' => $stored['stored_name'],
        ];
    }

    document_ingestion_progress($onProgress, 'done', 96, count($savedChunks) . ' Textabschnitt(e) erzeugt.');
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

function document_ingestion_progress(?callable $onProgress, string $stage, int $percent, string $message): void
{
    if ($onProgress === null) {
        return;
    }
    $onProgress($stage, $percent, $message);
}
