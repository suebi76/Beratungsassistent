<?php
declare(strict_types=1);

const FRONTEND_GENERATED_QUICK_QUESTIONS = 6;
const FRONTEND_GENERATED_TASK_EXAMPLES = 4;
const FRONTEND_GENERATED_TEMPLATE_SECTIONS = 4;
const FRONTEND_GENERATED_TEMPLATE_OPTIONS = 3;

function frontend_question_looks_generated_title_prompt(string $question): bool
{
    $normalized = normalize_search_text($question);
    $legacyGenericQuestions = [
        'welche zentralen vorgaben sind fuer die beratung wichtig',
        'welche aufgaben ergeben sich aus der wissensbasis',
        'welche schritte sollte ich bei der umsetzung beachten',
        'welche quellen stuetzen die antwort',
        'welche unterschiede oder ausnahmen sind wichtig',
        'welche offenen punkte sollte ich vor einer entscheidung klaeren',
    ];
    if (in_array(rtrim($normalized, '?'), $legacyGenericQuestions, true)) {
        return true;
    }
    if (preg_match('/^was ist bei .+ zu beachten$/u', $normalized)) {
        return true;
    }
    if (preg_match('/^(analysieren|beschreiben|erlaeutern|erklaeren|fassen|formulieren|geben|nennen|stellen|vergleichen) sie\b/u', $normalized)) {
        return true;
    }
    if (preg_match('/\b(wissensbasis|beratungsassistent|assistent|antwort|quellen?)\b/u', $normalized)) {
        return true;
    }

    return substr_count($question, '"') > 1;
}

function frontend_clean_generated_list(array $values, array $fallback, int $limit, int $maxChars, bool $questions): array
{
    $items = [];
    $seen = [];

    foreach ($values as $value) {
        $item = frontend_clean_ui_text((string) $value);
        if ($item === '') {
            continue;
        }
        if ($questions && frontend_question_looks_generated_title_prompt($item)) {
            continue;
        }
        if ($questions && !str_ends_with($item, '?')) {
            $item = rtrim($item, ".!;:") . '?';
        }

        frontend_add_unique_item($items, $seen, $item, $maxChars);
        if (count($items) >= $limit) {
            return $items;
        }
    }

    foreach ($fallback as $item) {
        frontend_add_unique_item($items, $seen, (string) $item, $maxChars);
        if (count($items) >= $limit) {
            break;
        }
    }

    return $items;
}

function frontend_default_content(array $project = [], array $chunks = []): array
{
    $heading = trim((string) ($project['title'] ?? ''));
    if ($heading === '') {
        $heading = 'Beratungs-Assistent';
    }

    return [
        'welcome_heading' => $heading,
        'welcome_text' => 'Stellen Sie eine fachliche Frage. Die Antwort wird aus der geladenen Wissensbasis abgeleitet und mit Quellen belegt.',
        'quick_questions' => [
            'Wie kann ich ein Beratungsgespräch sinnvoll vorbereiten?',
            'Welche Ziele und Kriterien sollte ich vorab klären?',
            'Welche Schritte sind für die Umsetzung sinnvoll?',
            'Welche typischen Stolperstellen sollte ich beachten?',
            'Wie kann ich Ergebnisse verständlich dokumentieren?',
            'Welche offenen Punkte sollte ich vor einer Entscheidung klären?',
        ],
        'task_examples' => [
            'Erstelle eine kurze Checkliste mit den wichtigsten Vorgaben.',
            'Fasse die relevanten Punkte für ein Beratungsgespräch zusammen.',
            'Vergleiche die passenden Aussagen aus der Wissensbasis.',
            'Formuliere eine verständliche Handlungsempfehlung mit Quellen.',
        ],
        'templates' => [
            [
                'title' => 'Schnell klären',
                'description' => 'Kurze Einstiege für schnelle Orientierung.',
                'options' => [
                    ['label' => 'Kurzüberblick', 'prompt' => 'Gib einen kurzen Überblick zu meiner Frage. Nutze nur die Wissensbasis und nenne Quellen.'],
                    ['label' => 'Zentrale Vorgaben', 'prompt' => 'Welche zentralen Vorgaben sind zu meiner Frage in der Wissensbasis enthalten?'],
                    ['label' => 'Quellen prüfen', 'prompt' => 'Welche Quellen aus der Wissensbasis sind für meine Frage besonders relevant?'],
                ],
            ],
            [
                'title' => 'Beratung vorbereiten',
                'description' => 'Hilfen für Gespräch, Planung und Entscheidung.',
                'options' => [
                    ['label' => 'Checkliste', 'prompt' => 'Erstelle eine kurze Checkliste für eine Beratungssituation auf Basis der Wissensbasis.'],
                    ['label' => 'Gesprächsleitfaden', 'prompt' => 'Erstelle einen verständlichen Gesprächsleitfaden mit den wichtigsten Punkten und Quellen.'],
                    ['label' => 'Entscheidungsvorlage', 'prompt' => 'Formuliere eine knappe Entscheidungsvorlage mit Sachstand, Bewertung und nächsten Schritten.'],
                ],
            ],
            [
                'title' => 'Dokumente auswerten',
                'description' => 'Strukturierte Auswertung der hinterlegten Inhalte.',
                'options' => [
                    ['label' => 'Kernaussagen', 'prompt' => 'Fasse die Kernaussagen zu meiner Frage präzise zusammen und nenne Quellen.'],
                    ['label' => 'Vergleich', 'prompt' => 'Vergleiche die relevanten Aussagen aus der Wissensbasis und zeige Gemeinsamkeiten und Unterschiede.'],
                    ['label' => 'Offene Punkte', 'prompt' => 'Welche offenen Punkte oder Grenzen bleiben nach Auswertung der Wissensbasis?'],
                ],
            ],
            [
                'title' => 'Qualität sichern',
                'description' => 'Antworten prüfen, Risiken erkennen und nächste Schritte ableiten.',
                'options' => [
                    ['label' => 'Antwort prüfen', 'prompt' => 'Prüfe die Antwort fachlich: Was ist gut belegt, was bleibt unsicher?'],
                    ['label' => 'Risiken', 'prompt' => 'Welche Risiken, Missverständnisse oder Grenzen sollte ich bei dieser Frage beachten?'],
                    ['label' => 'Nächste Schritte', 'prompt' => 'Leite aus der Wissensbasis konkrete nächste Schritte ab.'],
                ],
            ],
        ],
    ];
}

function frontend_clean_generated_templates(array $templates, array $fallback): array
{
    $cleaned = [];
    $seenSections = [];

    foreach ($templates as $template) {
        if (!is_array($template)) {
            continue;
        }

        $title = frontend_clean_ui_text((string) ($template['title'] ?? ''));
        $description = frontend_clean_ui_text((string) ($template['description'] ?? ''));
        if (preg_match('/^vorlage\s+\d+$/iu', normalize_search_text($title))) {
            continue;
        }

        $options = [];
        $seenOptions = [];

        foreach (array_values(is_array($template['options'] ?? null) ? $template['options'] : []) as $option) {
            if (!is_array($option)) {
                continue;
            }

            $prompt = frontend_clean_ui_text((string) ($option['prompt'] ?? ''));
            if ($prompt === '') {
                continue;
            }

            $label = frontend_clean_ui_text((string) ($option['label'] ?? ''));
            if ($label === '') {
                $label = excerpt($prompt, 42);
            }

            $key = frontend_dedupe_key($label . ' ' . $prompt);
            if ($key === '' || isset($seenOptions[$key])) {
                continue;
            }
            $seenOptions[$key] = true;
            $options[] = [
                'label' => mb_substr($label, 0, 42, 'UTF-8'),
                'prompt' => mb_substr($prompt, 0, 900, 'UTF-8'),
            ];
            if (count($options) >= FRONTEND_GENERATED_TEMPLATE_OPTIONS) {
                break;
            }
        }

        if ($title === '' || $options === []) {
            continue;
        }

        $sectionKey = frontend_dedupe_key($title);
        if (isset($seenSections[$sectionKey])) {
            continue;
        }
        $seenSections[$sectionKey] = true;
        $cleaned[] = [
            'title' => mb_substr($title, 0, 70, 'UTF-8'),
            'description' => mb_substr($description, 0, 180, 'UTF-8'),
            'options' => $options,
        ];
        if (count($cleaned) >= FRONTEND_GENERATED_TEMPLATE_SECTIONS) {
            return $cleaned;
        }
    }

    foreach ($fallback as $template) {
        if (!is_array($template)) {
            continue;
        }
        $sectionKey = frontend_dedupe_key((string) ($template['title'] ?? ''));
        if ($sectionKey === '' || isset($seenSections[$sectionKey])) {
            continue;
        }
        $seenSections[$sectionKey] = true;
        $cleaned[] = $template;
        if (count($cleaned) >= FRONTEND_GENERATED_TEMPLATE_SECTIONS) {
            break;
        }
    }

    return $cleaned;
}

function frontend_normalize_generated_content(array $frontend, array $project = [], array $chunks = []): array
{
    $fallback = frontend_default_content($project, $chunks);
    $quickQuestions = array_values(is_array($frontend['quick_questions'] ?? null) ? $frontend['quick_questions'] : []);
    $taskExamples = array_values(is_array($frontend['task_examples'] ?? null) ? $frontend['task_examples'] : []);
    $templates = array_values(is_array($frontend['templates'] ?? null) ? $frontend['templates'] : []);

    return [
        'welcome_heading' => frontend_clean_ui_text((string) ($frontend['welcome_heading'] ?? $fallback['welcome_heading'])),
        'welcome_text' => frontend_clean_ui_text((string) ($frontend['welcome_text'] ?? $fallback['welcome_text'])),
        'quick_questions' => frontend_clean_generated_list(
            $quickQuestions,
            $fallback['quick_questions'],
            FRONTEND_GENERATED_QUICK_QUESTIONS,
            140,
            true
        ),
        'task_examples' => frontend_clean_generated_list(
            $taskExamples,
            $fallback['task_examples'],
            FRONTEND_GENERATED_TASK_EXAMPLES,
            190,
            false
        ),
        'templates' => frontend_clean_generated_templates($templates, $fallback['templates']),
    ];
}
