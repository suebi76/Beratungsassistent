<?php
declare(strict_types=1);

function process_uploaded_document(array $file, array $project, array $apiConfig, ?callable $onProgress = null): array
{
    document_ingestion_progress($onProgress, 'validating', 8, 'Datei wird geprüft.');
    $validation = validate_uploaded_file($file);
    if (!($validation['ok'] ?? false)) {
        return ['ok' => false, 'error' => $validation['error'] ?? 'Datei konnte nicht validiert werden.'];
    }

    $fileHash = uploaded_file_sha256($file);
    if ($fileHash !== null) {
        $duplicateDocument = find_duplicate_document_by_sha256($project, $fileHash);
        if ($duplicateDocument !== null) {
            document_ingestion_progress($onProgress, 'duplicate_detected', 100, 'Datei ist bereits vorhanden und wird übersprungen.');
            return duplicate_upload_result((string) $file['name'], $fileHash, $duplicateDocument);
        }
    }

    $pdfAdvice = null;
    if ((string) $validation['extension'] === 'pdf') {
        $pdfAdvice = pdf_split_advice_for_file((string) $file['name'], (int) ($file['size'] ?? 0), (string) ($file['tmp_name'] ?? ''));
    }

    document_ingestion_progress($onProgress, 'storing', 18, 'Datei wird gespeichert.');
    $stored = store_uploaded_file($file);
    if (!($stored['ok'] ?? false)) {
        return ['ok' => false, 'error' => $stored['error'] ?? 'Datei konnte nicht gespeichert werden.'];
    }

    if ($pdfAdvice !== null && !empty($pdfAdvice['large_pdf'])) {
        document_ingestion_progress($onProgress, 'large_pdf', 22, (string) $pdfAdvice['message']);
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
            'stored_file_deleted' => delete_stored_upload_file((string) $stored['stored_name']),
            'pdf_split_advice' => $pdfAdvice,
        ];
    }

    document_ingestion_progress($onProgress, 'saving_chunks', 84, 'Textabschnitte werden gespeichert.');
    $savedChunks = save_chunks_from_response((string) $generation['text'], (string) $file['name'], (string) $validation['extension']);
    if ($savedChunks === []) {
        $error = 'Gemini hat keine verwertbaren Chunks geliefert. Bitte die Datei erneut hochladen oder als kleinere Teildateien bereitstellen.';
        if ($pdfAdvice !== null && !empty($pdfAdvice['message'])) {
            $error .= ' ' . (string) $pdfAdvice['message'];
        }
        return [
            'ok' => false,
            'error' => $error,
            'stored_name' => $stored['stored_name'],
            'stored_file_deleted' => delete_stored_upload_file((string) $stored['stored_name']),
            'pdf_split_advice' => $pdfAdvice,
        ];
    }

    $document = [
        'original_name' => (string) $file['name'],
        'stored_name' => $stored['stored_name'],
        'mime_type' => (string) $validation['mime'],
        'extension' => (string) $validation['extension'],
        'bytes' => (int) ($file['size'] ?? 0),
        'uploaded_at' => now_iso(),
        'chunks_created' => count($savedChunks),
    ];
    if ($fileHash !== null) {
        $document['sha256'] = $fileHash;
    }
    if ($pdfAdvice !== null) {
        $document['pdf_analysis'] = pdf_split_document_metadata($pdfAdvice);
    }

    document_ingestion_progress($onProgress, 'done', 96, count($savedChunks) . ' Textabschnitt(e) erzeugt.');
    return [
        'ok' => true,
        'stored_name' => $stored['stored_name'],
        'saved_chunks' => $savedChunks,
        'document' => $document,
        'sha256' => $fileHash,
        'pdf_split_advice' => $pdfAdvice,
    ];
}

function document_ingestion_progress(?callable $onProgress, string $stage, int $percent, string $message): void
{
    if ($onProgress === null) {
        return;
    }
    $onProgress($stage, $percent, $message);
}
