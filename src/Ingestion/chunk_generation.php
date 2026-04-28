<?php
declare(strict_types=1);

function build_chunk_generation_prompt(array $project, string $originalName, string $extension): string
{
    $title = trim((string) ($project['title'] ?? 'Beratungs-Assistent'));
    $topic = trim((string) ($project['topic'] ?? ''));
    $audience = trim((string) ($project['audience'] ?? ''));

    return "Du erstellst Wissens-Chunks für eine Retrieval-Augmented-Generation-Wissensbasis.\n"
        . "Projektkontext:\n"
        . "- Titel des Assistenten: {$title}\n"
        . "- Themenfeld: {$topic}\n"
        . "- Zielgruppe: " . ($audience !== '' ? $audience : 'nicht angegeben') . "\n"
        . "- Quelldatei: {$originalName} ({$extension})\n\n"
        . "Aufgabe:\n"
        . "- Teile das Dokument in klar abgegrenzte Wissens-Chunks auf.\n"
        . "- Jeder Chunk soll genau ein Thema, einen Prozess, eine Regel oder eine wiederkehrende Beratungsfrage abdecken.\n"
        . "- Schreibe sachlich, nah an der Quelle und ohne Marketing-Sprache.\n"
        . "- Nutze Deutsch.\n"
        . "- Pro Chunk: etwa 180 bis 500 Wörter.\n"
        . "- Bei Dokumenten mit mehr als 50 Seiten erzeuge mindestens einen Chunk pro 10 Seiten.\n"
        . "- Nutze Markdown mit sinnvollen Überschriften und kompakten Listen.\n"
        . "- Füge 8 bis 12 aussagekräftige Tags hinzu, inklusive gebräuchlicher Synonyme.\n"
        . "- Nutze im Feld `quelle` den erkennbaren Dokumenttitel, sonst den Dateinamen.\n"
        . "- Erfinde keine Fakten, die nicht aus der Datei ableitbar sind.\n"
        . "- Wenn ein Dokument mehrere Themen enthält, erzeuge mehrere Chunks.\n\n"
        . "Gib ausschließlich dieses Format zurück, ohne Einleitung und ohne Schlusskommentar:\n\n"
        . "CHUNK_START\n"
        . "---\n"
        . "title: Prägnanter Titel\n"
        . "tags: tag1, tag2, tag3, tag4, tag5, tag6, tag7, tag8\n"
        . "quelle: Dokumenttitel oder Dateiname\n"
        . "---\n\n"
        . "## Kernaussage\n"
        . "Inhalt des Chunks.\n\n"
        . "CHUNK_END";
}

function build_document_parts_for_gemini(string $path, string $originalName, string $extension): array
{
    if ($extension === 'pdf') {
        return [[
            'inline_data' => [
                'mime_type' => 'application/pdf',
                'data' => base64_encode((string) file_get_contents($path)),
            ],
        ]];
    }

    $content = read_text_source($path);
    return [[
        'text' => "Dateiname: {$originalName}\n\n{$content}",
    ]];
}

