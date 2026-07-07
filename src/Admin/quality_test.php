<?php
declare(strict_types=1);

const ADMIN_QUALITY_MAX_CHUNKS = 12;
const ADMIN_QUALITY_DEFAULT_CHUNKS = 5;

function admin_empty_quality_test_result(): array
{
    return [
        'ran' => false,
        'query' => '',
        'limit' => ADMIN_QUALITY_DEFAULT_CHUNKS,
        'with_answer' => true,
        'tokens' => [],
        'chunks' => [],
        'answer' => '',
        'answer_error' => '',
        'error' => '',
    ];
}

function admin_quality_test_limit(mixed $value): int
{
    $limit = (int) $value;
    if ($limit <= 0) {
        $limit = ADMIN_QUALITY_DEFAULT_CHUNKS;
    }

    return min(max(1, $limit), ADMIN_QUALITY_MAX_CHUNKS);
}

function admin_build_quality_test_result(string $rawQuery, mixed $rawLimit, bool $withAnswer, array $apiConfig, array $project): array
{
    $query = normalize_whitespace($rawQuery);
    $limit = admin_quality_test_limit($rawLimit);
    $result = admin_empty_quality_test_result();
    $result['ran'] = true;
    $result['query'] = $query;
    $result['limit'] = $limit;
    $result['with_answer'] = $withAnswer;

    if ($query === '') {
        $result['error'] = 'Bitte eine Testfrage eingeben.';
        return $result;
    }

    if (mb_strlen($query, 'UTF-8') > MAX_PROXY_QUERY_CHARS) {
        $result['error'] = 'Die Testfrage ist zu lang. Bitte kürzer formulieren.';
        return $result;
    }

    $result['tokens'] = tokenize_query($query);
    $result['chunks'] = retrieve_relevant_chunks($query, $limit);

    if (!$withAnswer) {
        return $result;
    }

    if (!api_key_is_configured($apiConfig)) {
        $result['answer_error'] = 'KI-Antwort übersprungen: Es ist kein vollständiger Anbieter konfiguriert.';
        return $result;
    }

    $answer = '';
    $streamResult = model_stream_chat(
        normalize_chat_messages([], $query),
        build_system_prompt($project, $result['chunks']),
        $apiConfig,
        static function (string $delta) use (&$answer): void {
            $answer .= $delta;
        },
        [
            'temperature' => 0.2,
            'maxOutputTokens' => 2048,
            'timeout' => 120,
        ]
    );

    if (!($streamResult['ok'] ?? false)) {
        $result['answer_error'] = (string) ($streamResult['error'] ?? 'KI-Antwort konnte nicht erzeugt werden.');
        return $result;
    }

    $answer = trim($answer);
    if ($answer === '') {
        $result['answer_error'] = 'KI-Antwort konnte nicht erzeugt werden: Das Modell lieferte keinen Text.';
        return $result;
    }

    $result['answer'] = $answer;
    return $result;
}

function admin_quality_top_score(array $chunks): int
{
    $scores = array_map(static fn(array $chunk): int => (int) ($chunk['score'] ?? 0), $chunks);
    return $scores === [] ? 0 : max($scores);
}

function admin_quality_score_label(int $score, int $topScore): string
{
    if ($score <= 0 || $topScore <= 0) {
        return 'keine Treffer';
    }
    if ($score >= $topScore * 0.75) {
        return 'stark';
    }
    if ($score >= $topScore * 0.35) {
        return 'mittel';
    }

    return 'schwach';
}
