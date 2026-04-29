<?php
declare(strict_types=1);

function extract_first_json_object(string $text): ?array
{
    $trimmed = trim($text);
    if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/is', $trimmed, $m)) {
        $trimmed = $m[1];
    }

    $start = strpos($trimmed, '{');
    $end = strrpos($trimmed, '}');
    if ($start === false || $end === false || $end <= $start) {
        return null;
    }

    $candidate = substr($trimmed, $start, $end - $start + 1);
    $decoded = json_decode($candidate, true);
    return is_array($decoded) ? $decoded : null;
}

function profile_text_sentence(string $text): string
{
    $trimmed = trim($text);
    if ($trimmed === '') {
        return '';
    }

    return preg_match('/[.!?]$/u', $trimmed) ? $trimmed : $trimmed . '.';
}

function build_profile_generation_prompt(array $project, array $chunks): string
{
    $title = trim((string) ($project['title'] ?? 'Beratungs-Assistent'));
    $topic = trim((string) ($project['topic'] ?? ''));
    $audience = trim((string) ($project['audience'] ?? ''));

    $chunkDigest = [];
    foreach (array_slice($chunks, 0, 16) as $chunk) {
        $chunkDigest[] = '- Titel: ' . $chunk['title'];
        if ($chunk['quelle'] !== '') {
            $chunkDigest[] = '  Quelle: ' . $chunk['quelle'];
        }
        if ($chunk['tags'] !== []) {
            $chunkDigest[] = '  Tags: ' . implode(', ', $chunk['tags']);
        }
        $chunkDigest[] = '  Auszug: ' . excerpt((string) $chunk['body'], 220);
    }

    return "Erstelle aus einer vorhandenen Wissensbasis die öffentliche Projektkonfiguration für einen deutschen Beratungsassistenten.\n"
        . "Projektangaben:\n"
        . "- Titel: {$title}\n"
        . "- Themenfeld: {$topic}\n"
        . "- Zielgruppe: " . ($audience !== '' ? $audience : 'nicht angegeben') . "\n\n"
        . "Wissensbasis (Auszüge aus vorhandenen Chunks):\n"
        . implode("\n", $chunkDigest) . "\n\n"
        . "Liefere ausschließlich JSON in genau dieser Struktur:\n"
        . "{\n"
        . "  \"subtitle\": \"...\",\n"
        . "  \"assistant_mission\": \"...\",\n"
        . "  \"scope_summary\": \"...\",\n"
        . "  \"scope_bullets\": [\"...\"],\n"
        . "  \"knowledge_profile\": {\n"
        . "    \"document_summary\": \"...\",\n"
        . "    \"focus_areas\": [\"...\"],\n"
        . "    \"limitations\": [\"...\"]\n"
        . "  },\n"
        . "  \"frontend\": {\n"
        . "    \"welcome_heading\": \"...\",\n"
        . "    \"welcome_text\": \"...\",\n"
        . "    \"quick_questions\": [\"...\"],\n"
        . "    \"task_examples\": [\"...\"],\n"
        . "    \"templates\": [\n"
        . "      {\n"
        . "        \"title\": \"...\",\n"
        . "        \"description\": \"...\",\n"
        . "        \"options\": [\n"
        . "          {\"label\": \"...\", \"prompt\": \"...\"}\n"
        . "        ]\n"
        . "      }\n"
        . "    ]\n"
        . "  }\n"
        . "}\n\n"
        . "Regeln:\n"
        . "- Deutsch, sachlich, ohne Marketing-Ton.\n"
        . "- Nur Aussagen, die aus der Wissensbasis ableitbar sind.\n"
        . "- `scope_bullets`: 4 bis 6 knappe Bereiche.\n"
        . "- `quick_questions`: genau 6 kurze, realistische Nutzerfragen.\n"
        . "- `task_examples`: genau 4 konkrete Arbeitsaufträge.\n"
        . "- `templates`: genau 4 Sektionen mit jeweils 3 Optionen.\n"
        . "- Die Prompts müssen unmittelbar als Beratungsfrage oder Arbeitsauftrag verwendbar sein.\n"
        . "- Keine Hinweise auf Dateinamen im UI-Text, außer es ist fachlich notwendig.\n"
        . "- Schreibe mit korrekten deutschen Umlauten und vermeide Umschreibungen wie ae, oe, ue oder ss, sofern echte Umlaute oder ß gemeint sind.\n"
        . "- Vermeide doppelte Satzzeichen und künstlich klingende Formulierungen.";
}

function fallback_profile(array $project, array $chunks): array
{
    $topic = trim((string) ($project['topic'] ?? 'diesem Themenfeld'));
    $topicSentence = profile_text_sentence($topic);
    $welcomeText = $topicSentence !== ''
        ? 'Stellen Sie Fragen zu diesem Themenfeld: ' . $topicSentence . ' Die Antworten beziehen sich auf die im Hintergrund geladenen Dateien.'
        : 'Stellen Sie Fragen zu diesem Themenfeld. Die Antworten beziehen sich auf die im Hintergrund geladenen Dateien.';
    $titles = array_values(array_unique(array_map(
        fn(array $chunk): string => $chunk['title'],
        array_slice($chunks, 0, 12)
    )));

    $quickQuestions = [];
    foreach (array_slice($titles, 0, 6) as $title) {
        $quickQuestions[] = 'Was ist bei "' . $title . '" zu beachten?';
    }

    $taskExamples = [];
    foreach (array_slice($titles, 0, 4) as $title) {
        $taskExamples[] = 'Fasse die wichtigsten Punkte zu "' . $title . '" für eine Beratungssituation zusammen.';
    }

    $templateOptions = [];
    foreach (array_slice($titles, 0, 12) as $title) {
        $templateOptions[] = [
            'label' => mb_substr($title, 0, 34, 'UTF-8'),
            'prompt' => 'Erläutere die wichtigsten Inhalte und Handlungsoptionen zu "' . $title . '" auf Basis der hinterlegten Wissensbasis.',
        ];
    }

    $templates = [];
    foreach (array_chunk($templateOptions, 3) as $idx => $group) {
        if ($idx >= 4) {
            break;
        }
        $templates[] = [
            'title' => 'Vorlage ' . ($idx + 1),
            'description' => 'Dokumentbasierte Fragen aus dem geladenen Wissensbestand.',
            'options' => $group,
        ];
    }

    return [
        'subtitle' => 'Konfigurierbarer Assistent für ' . $topic,
        'assistant_mission' => 'Unterstützt bei fachlichen Fragen und Arbeitsaufträgen auf Basis der hinterlegten Dokumente.',
        'scope_summary' => 'Antwortet innerhalb des konfigurierten Themenfelds auf Basis des geladenen Wissensbestands.',
        'scope_bullets' => array_slice($titles, 0, 5),
        'knowledge_profile' => [
            'document_summary' => 'Die Wissensbasis besteht aus hochgeladenen Dokumenten, die in thematische Chunks zerlegt wurden.',
            'focus_areas' => array_slice($titles, 0, 5),
            'limitations' => ['Antworten sind nur belastbar, wenn passende Inhalte in den geladenen Dateien vorhanden sind.'],
        ],
        'frontend' => [
            'welcome_heading' => trim((string) ($project['title'] ?? 'Beratungs-Assistent')),
            'welcome_text' => $welcomeText,
            'quick_questions' => $quickQuestions,
            'task_examples' => $taskExamples,
            'templates' => $templates,
        ],
    ];
}

function regenerate_project_profile(array $project, array $apiConfig): array
{
    $chunks = get_chunks();
    if ($chunks === []) {
        return ['ok' => false, 'error' => 'Es sind noch keine Chunks vorhanden.'];
    }

    $prompt = build_profile_generation_prompt($project, $chunks);
    $generation = model_generate_text([['text' => $prompt]], $apiConfig, [
        'temperature' => 0.3,
        'maxOutputTokens' => 8192,
        'timeout' => 180,
        'retries' => 1,
        'retryDelaySeconds' => 3,
    ], 'profile_generation');

    $profile = null;
    if ($generation['ok'] ?? false) {
        $profile = extract_first_json_object((string) $generation['text']);
    }

    if (!is_array($profile)) {
        $profile = fallback_profile($project, $chunks);
    }

    $project['subtitle'] = trim((string) ($profile['subtitle'] ?? $project['subtitle']));
    $project['assistant_mission'] = trim((string) ($profile['assistant_mission'] ?? $project['assistant_mission']));
    $project['scope_summary'] = trim((string) ($profile['scope_summary'] ?? $project['scope_summary']));
    $project['scope_bullets'] = array_slice(array_values(array_filter(array_map('trim', $profile['scope_bullets'] ?? []))), 0, 6);
    $project['knowledge_profile'] = merge_project_config(
        $project['knowledge_profile'],
        is_array($profile['knowledge_profile'] ?? null) ? $profile['knowledge_profile'] : []
    );
    $project['frontend'] = merge_project_config(
        $project['frontend'],
        is_array($profile['frontend'] ?? null) ? $profile['frontend'] : []
    );
    $project['setup']['last_profile_generation_at'] = now_iso();

    if (save_project_config($project)) {
        return ['ok' => true, 'project' => $project];
    }

    return ['ok' => false, 'error' => 'Projektprofil konnte nicht gespeichert werden.'];
}
