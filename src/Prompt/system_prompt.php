<?php
declare(strict_types=1);

function build_system_prompt(array $project, array $retrievedChunks): string
{
    $title = trim((string) ($project['title'] ?? 'Beratungs-Assistent'));
    $topic = trim((string) ($project['topic'] ?? 'dem konfigurierten Themenfeld'));
    $audience = trim((string) ($project['audience'] ?? 'der vorgesehenen Zielgruppe'));
    $mission = trim((string) ($project['assistant_mission'] ?? ''));
    $scopeSummary = trim((string) ($project['scope_summary'] ?? ''));
    $outOfScope = trim((string) ($project['safety']['out_of_scope_message'] ?? 'Zu dieser Frage liegen im aktuell geladenen Wissensbestand keine belastbaren Informationen vor.'));
    $piiReject = trim((string) ($project['safety']['pii_rejection_message'] ?? 'Bitte geben Sie keine personenbezogenen Daten oder vertraulichen Einzelfälle ein.'));

    $lines = [];
    $lines[] = 'ROLLE UND KONTEXT:';
    $lines[] = "Du bist \"{$title}\", ein spezialisierter Beratungsassistent für {$audience}.";
    $lines[] = "Themenfeld: {$topic}.";
    if ($mission !== '') {
        $lines[] = 'Mission: ' . $mission;
    }
    if ($scopeSummary !== '') {
        $lines[] = 'Fachlicher Rahmen: ' . $scopeSummary;
    }
    if (!empty($project['scope_bullets'])) {
        $lines[] = '';
        $lines[] = 'FOKUSBEREICHE:';
        foreach (array_slice($project['scope_bullets'], 0, 6) as $index => $bullet) {
            $lines[] = ($index + 1) . '. ' . trim((string) $bullet);
        }
    }

    $lines[] = '';
    $lines[] = 'ARBEITSREGELN:';
    $lines[] = '- Antworte ausschließlich auf Deutsch.';
    $lines[] = '- Antworte strukturiert in Markdown.';
    $lines[] = '- Nutze vorrangig die beigefügte Wissensdatenbank.';
    $lines[] = '- Wenn die Frage mit den verfügbaren Quellen nicht belastbar beantwortbar ist oder klar außerhalb des Themenfelds liegt, antworte exakt: "' . $outOfScope . '"';
    $lines[] = '- Verarbeite keine personenbezogenen Daten, vertraulichen Einzelfälle, Kennungen, Seriennummern oder Geheimnisse.';
    $lines[] = '- Wenn eine Anfrage solche Inhalte enthält, antworte exakt: "' . $piiReject . '"';
    $lines[] = '- Nenne am Ende unter "**Quellen:**" die tatsächlich genutzten Quellen aus den Chunks.';
    $lines[] = '- Erfinde keine Fakten, wenn die Quellen unklar oder unvollständig sind.';
    $lines[] = '';
    $lines[] = build_rag_block($retrievedChunks);

    return implode("\n", array_filter($lines, static fn($line) => $line !== ''));
}

function normalize_chat_messages(array $messages, string $currentQuery): array
{
    $normalized = [];

    foreach ($messages as $message) {
        if (!is_array($message)) {
            continue;
        }
        $role = $message['role'] ?? '';
        $text = normalize_whitespace((string) ($message['text'] ?? ''));
        if ($text === '') {
            continue;
        }
        if ($role !== 'user' && $role !== 'assistant') {
            continue;
        }
        $normalized[] = [
            'role' => $role === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $text]],
        ];
    }

    $query = normalize_whitespace($currentQuery);
    if ($query !== '') {
        $normalized[] = [
            'role' => 'user',
            'parts' => [['text' => $query]],
        ];
    }

    return $normalized;
}

