<?php
declare(strict_types=1);

function default_project_config(): array
{
    return [
        'version' => 1,
        'slug' => 'beratungsassistent',
        'title' => 'Beratungs-Assistent',
        'subtitle' => 'Konfigurierbarer KI-Assistent mit dateibasierter Wissensbasis',
        'topic' => '',
        'audience' => '',
        'language' => 'de-DE',
        'assistant_mission' => '',
        'scope_summary' => '',
        'scope_bullets' => [],
        'safety' => [
            'pii_notice' => 'Bitte keine personenbezogenen Daten, vertraulichen Einzelfälle oder geheimhaltungsbedürftigen Inhalte eingeben.',
            'pii_rejection_message' => 'Bitte geben Sie keine personenbezogenen Daten oder vertraulichen Einzelfälle ein. Formulieren Sie Ihre Frage allgemeiner, dann helfe ich gerne weiter.',
            'out_of_scope_message' => 'Zu dieser Frage liegen im aktuell geladenen Wissensbestand keine belastbaren Informationen vor.',
            'citation_required' => true,
            'scope_guard' => true,
        ],
        'frontend' => [
            'welcome_heading' => 'Beratungs-Assistent',
            'welcome_text' => 'Dieser Assistent beantwortet Fragen auf Basis der im Hintergrund geladenen Dateien.',
            'quick_questions' => [],
            'task_examples' => [],
            'templates' => [],
        ],
        'knowledge_profile' => [
            'document_summary' => '',
            'focus_areas' => [],
            'limitations' => [],
        ],
        'documents' => [],
        'setup' => [
            'profile_completed_at' => null,
            'knowledge_completed_at' => null,
            'last_profile_generation_at' => null,
        ],
    ];
}

function merge_project_config(array $base, array $overrides): array
{
    foreach ($overrides as $key => $value) {
        if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && !array_is_list($value)) {
            $base[$key] = merge_project_config($base[$key], $value);
            continue;
        }
        $base[$key] = $value;
    }
    return $base;
}

function load_project_config(): array
{
    ensure_app_dirs();
    $file = project_config_file();
    if (!file_exists($file)) {
        return default_project_config();
    }

    $raw = (string) @file_get_contents($file);
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        return default_project_config();
    }

    return merge_project_config(default_project_config(), $data);
}

function save_project_config(array $project): bool
{
    ensure_app_dirs();
    $payload = json_encode(
        $project,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    return $payload !== false && file_put_contents(project_config_file(), $payload . "\n") !== false;
}

function project_profile_is_configured(array $project): bool
{
    return trim((string) ($project['title'] ?? '')) !== ''
        && trim((string) ($project['topic'] ?? '')) !== '';
}

