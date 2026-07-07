<?php
declare(strict_types=1);

const FRONTEND_MAX_QUICK_QUESTIONS = 12;
const FRONTEND_MAX_TASK_EXAMPLES = 8;
const FRONTEND_MAX_TEMPLATE_SECTIONS = 6;
const FRONTEND_MAX_TEMPLATE_OPTIONS = 6;

function frontend_clean_ui_text(string $text): string
{
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = str_replace(['\\"', "\\'", '""'], ['"', "'", '"'], $text);
    $text = strtr($text, [
        '„' => '"',
        '“' => '"',
        '”' => '"',
        '«' => '"',
        '»' => '"',
        '‚' => "'",
        '‘' => "'",
        '’' => "'",
        '`' => "'",
    ]);
    $text = preg_replace('/^\s*(?:[-*]+|\d+[.)])\s*/u', '', $text) ?? $text;
    $text = normalize_whitespace($text);
    $text = preg_replace('/\s+([,.!?;:])/u', '$1', $text) ?? $text;
    $text = preg_replace('/([!?]){2,}/u', '$1', $text) ?? $text;

    return trim($text, " \t\n\r\0\x0B\"'");
}

function frontend_dedupe_key(string $text): string
{
    $key = normalize_search_text($text);
    $key = preg_replace('/\s+/u', ' ', $key) ?? $key;
    return $key;
}

function frontend_add_unique_item(array &$items, array &$seen, string $item, int $maxChars): void
{
    $item = frontend_clean_ui_text($item);
    if ($item === '') {
        return;
    }
    if (mb_strlen($item, 'UTF-8') > $maxChars) {
        $item = rtrim(mb_substr($item, 0, $maxChars, 'UTF-8'));
        $item = rtrim($item, " ,.;:");
    }

    $key = frontend_dedupe_key($item);
    if ($key === '' || isset($seen[$key])) {
        return;
    }

    $seen[$key] = true;
    $items[] = $item;
}

function frontend_text_list_from_multiline(string $text, int $limit, int $maxChars = 260): array
{
    $items = [];
    $seen = [];
    foreach (preg_split('/\R+/u', $text) ?: [] as $line) {
        frontend_add_unique_item($items, $seen, (string) $line, $maxChars);
        if (count($items) >= $limit) {
            break;
        }
    }

    return $items;
}

function frontend_form_list(array|string|null $value): array
{
    if (!is_array($value)) {
        return [];
    }

    return array_values($value);
}

function frontend_option_list(array|string|null $value, int $sectionIndex): array
{
    if (!is_array($value)) {
        return [];
    }

    $section = $value[$sectionIndex] ?? [];
    return is_array($section) ? array_values($section) : [];
}

function frontend_templates_from_form(array $titles, array $descriptions, array $optionLabels, array $optionPrompts): array
{
    $templates = [];
    $sectionCount = min(
        max(count($titles), count($descriptions), count($optionLabels), count($optionPrompts)),
        FRONTEND_MAX_TEMPLATE_SECTIONS
    );

    for ($sectionIndex = 0; $sectionIndex < $sectionCount; $sectionIndex++) {
        $title = frontend_clean_ui_text((string) ($titles[$sectionIndex] ?? ''));
        $description = frontend_clean_ui_text((string) ($descriptions[$sectionIndex] ?? ''));
        $labels = frontend_option_list($optionLabels, $sectionIndex);
        $prompts = frontend_option_list($optionPrompts, $sectionIndex);
        $optionCount = min(max(count($labels), count($prompts)), FRONTEND_MAX_TEMPLATE_OPTIONS);
        $options = [];

        for ($optionIndex = 0; $optionIndex < $optionCount; $optionIndex++) {
            $prompt = frontend_clean_ui_text((string) ($prompts[$optionIndex] ?? ''));
            if ($prompt === '') {
                continue;
            }

            $label = frontend_clean_ui_text((string) ($labels[$optionIndex] ?? ''));
            if ($label === '') {
                $label = excerpt($prompt, 48);
            }

            $options[] = [
                'label' => mb_substr($label, 0, 80, 'UTF-8'),
                'prompt' => mb_substr($prompt, 0, 1200, 'UTF-8'),
            ];
        }

        if ($title === '' && $description === '' && $options === []) {
            continue;
        }

        if ($title === '') {
            $title = 'Vorlage ' . (count($templates) + 1);
        }

        $templates[] = [
            'title' => mb_substr($title, 0, 120, 'UTF-8'),
            'description' => mb_substr($description, 0, 300, 'UTF-8'),
            'options' => $options,
        ];
    }

    return $templates;
}

function frontend_content_from_form(array $input): array
{
    return [
        'quick_questions' => frontend_text_list_from_multiline(
            (string) ($input['quick_questions'] ?? ''),
            FRONTEND_MAX_QUICK_QUESTIONS
        ),
        'task_examples' => frontend_text_list_from_multiline(
            (string) ($input['task_examples'] ?? ''),
            FRONTEND_MAX_TASK_EXAMPLES
        ),
        'templates' => frontend_templates_from_form(
            frontend_form_list($input['template_title'] ?? []),
            frontend_form_list($input['template_description'] ?? []),
            is_array($input['template_option_label'] ?? null) ? $input['template_option_label'] : [],
            is_array($input['template_option_prompt'] ?? null) ? $input['template_option_prompt'] : []
        ),
    ];
}
