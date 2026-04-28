<?php
declare(strict_types=1);

function public_project_config(array $project): array
{
    $configured = project_profile_is_configured($project) && knowledge_base_is_configured();

    return [
        'configured' => $configured,
        'title' => trim((string) ($project['title'] ?? 'Beratungs-Assistent')),
        'subtitle' => trim((string) ($project['subtitle'] ?? '')),
        'topic' => trim((string) ($project['topic'] ?? '')),
        'audience' => trim((string) ($project['audience'] ?? '')),
        'assistant_mission' => trim((string) ($project['assistant_mission'] ?? '')),
        'scope_summary' => trim((string) ($project['scope_summary'] ?? '')),
        'scope_bullets' => array_values($project['scope_bullets'] ?? []),
        'safety' => [
            'pii_notice' => trim((string) ($project['safety']['pii_notice'] ?? '')),
        ],
        'frontend' => [
            'welcome_heading' => trim((string) ($project['frontend']['welcome_heading'] ?? 'Beratungs-Assistent')),
            'welcome_text' => trim((string) ($project['frontend']['welcome_text'] ?? '')),
            'quick_questions' => array_values($project['frontend']['quick_questions'] ?? []),
            'task_examples' => array_values($project['frontend']['task_examples'] ?? []),
            'templates' => array_values($project['frontend']['templates'] ?? []),
        ],
        'knowledge_profile' => [
            'document_summary' => trim((string) ($project['knowledge_profile']['document_summary'] ?? '')),
            'focus_areas' => array_values($project['knowledge_profile']['focus_areas'] ?? []),
            'limitations' => array_values($project['knowledge_profile']['limitations'] ?? []),
        ],
    ];
}

