<?php
declare(strict_types=1);

function tokenize_query(string $query): array
{
    return expand_query_tokens(tokenize_query_terms($query));
}

function tokenize_query_terms(string $query): array
{
    $stopwords = [
        'aber', 'alle', 'alles', 'auch', 'auf', 'aus', 'bei', 'beim', 'bin', 'bis',
        'bist', 'bitte', 'damit', 'dann', 'das', 'dass', 'dein', 'deine', 'dem',
        'den', 'der', 'des', 'die', 'dies', 'diese', 'dieser', 'doch', 'dort', 'du',
        'ein', 'eine', 'einer', 'einem', 'einen', 'er', 'es', 'etwa', 'euch', 'euer',
        'fuer', 'gelten', 'gilt', 'gib', 'gibt', 'hat', 'haben', 'hier', 'hinter', 'ich', 'ihr', 'ihm',
        'ihn', 'im', 'in', 'ist', 'jede', 'jeder', 'kann', 'kein', 'keine', 'kurz',
        'laut', 'mit', 'muss', 'nach', 'nenne', 'nennen', 'nicht', 'noch', 'nur',
        'oder', 'ohne', 'schon', 'sich', 'sie', 'sind', 'soll', 'sowie', 'und',
        'uns', 'unter', 'vom', 'von', 'vor', 'war', 'waren', 'was', 'weil', 'welche',
        'welcher', 'wie', 'wird', 'wir', 'wo', 'zu', 'zum', 'zur', 'ueber',
        'antwort', 'antworten', 'antworte', 'beantworten', 'beantworte', 'belastbar',
        'informationen', 'quelle', 'quellen', 'wissensbasis', 'zentral', 'zentrale',
        'zentralen',
    ];

    $words = preg_split('/\s+/u', normalize_search_text($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $terms = [];

    foreach ($words as $word) {
        if (mb_strlen($word, 'UTF-8') < 3) {
            continue;
        }
        if (in_array($word, $stopwords, true)) {
            continue;
        }
        $terms[] = $word;
    }

    return array_values(array_unique($terms));
}

function expand_query_tokens(array $terms): array
{
    $bigrams = [];
    for ($i = 0; $i < count($terms) - 1; $i++) {
        $bigrams[] = $terms[$i] . ' ' . $terms[$i + 1];
    }

    return array_values(array_unique(array_merge($terms, $bigrams)));
}

function retrieval_required_match_count(array $terms): int
{
    $semanticTerms = array_values(array_filter(
        $terms,
        static fn(string $term): bool => !preg_match('/^\d+$/', $term)
    ));

    if (count($semanticTerms) >= 2) {
        return 2;
    }

    return $terms === [] ? 0 : 1;
}

function chunk_search_fields(array $chunk): array
{
    $tags = is_array($chunk['tags'] ?? null) ? $chunk['tags'] : [];

    return [
        'title' => normalize_search_text((string) ($chunk['title'] ?? '')),
        'tags' => normalize_search_text(implode(' ', array_map('strval', $tags))),
        'source' => normalize_search_text((string) ($chunk['quelle'] ?? '') . ' ' . (string) ($chunk['source_file'] ?? '')),
        'body' => normalize_search_text((string) ($chunk['body'] ?? '')),
    ];
}

function exact_search_count(string $text, string $token): int
{
    if ($text === '' || $token === '') {
        return 0;
    }

    return substr_count(' ' . $text . ' ', ' ' . $token . ' ');
}

function partial_search_count(string $text, string $token): int
{
    if ($text === '' || $token === '' || str_contains($token, ' ') || mb_strlen($token, 'UTF-8') < 5) {
        return 0;
    }

    return max(0, substr_count($text, $token) - exact_search_count($text, $token));
}

function score_search_field(string $text, string $token, int $exactWeight, int $partialWeight = 0, int $maxExactCount = 6): int
{
    $score = min(exact_search_count($text, $token), $maxExactCount) * $exactWeight;
    if ($partialWeight > 0) {
        $score += min(partial_search_count($text, $token), $maxExactCount) * $partialWeight;
    }

    return $score;
}

function score_chunk(array $chunk, array $tokens, string $normalizedQuery): int
{
    if ($tokens === []) {
        return 0;
    }

    $fields = chunk_search_fields($chunk);

    $score = 0;
    foreach ($tokens as $token) {
        $isPhrase = str_contains($token, ' ');
        $score += score_search_field($fields['title'], $token, $isPhrase ? 20 : 10, $isPhrase ? 0 : 3);
        $score += score_search_field($fields['tags'], $token, $isPhrase ? 16 : 10, $isPhrase ? 0 : 3);
        $score += score_search_field($fields['source'], $token, $isPhrase ? 8 : 5, $isPhrase ? 0 : 2);
        $score += score_search_field($fields['body'], $token, $isPhrase ? 4 : 2, 0, 4);
    }

    if ($normalizedQuery !== '' && exact_search_count($fields['body'], $normalizedQuery) > 0) {
        $score += 12;
    }
    if ($normalizedQuery !== '' && exact_search_count($fields['title'], $normalizedQuery) > 0) {
        $score += 18;
    }

    return $score;
}

function chunk_matched_query_terms(array $chunk, array $terms): array
{
    $fields = chunk_search_fields($chunk);
    $matched = [];

    foreach ($terms as $term) {
        $exact = exact_search_count($fields['title'], $term)
            + exact_search_count($fields['tags'], $term)
            + exact_search_count($fields['source'], $term)
            + exact_search_count($fields['body'], $term);
        $partial = partial_search_count($fields['title'], $term)
            + partial_search_count($fields['tags'], $term)
            + partial_search_count($fields['source'], $term);

        if ($exact > 0 || $partial > 0) {
            $matched[] = $term;
        }
    }

    return array_values(array_unique($matched));
}

function retrieve_relevant_chunks(string $query, int $limit = MAX_RETRIEVAL_CHUNKS): array
{
    $terms = tokenize_query_terms($query);
    $tokens = expand_query_tokens($terms);
    $normalizedQuery = normalize_search_text(implode(' ', $terms));
    $requiredMatches = retrieval_required_match_count($terms);
    if ($tokens === []) {
        return [];
    }

    $scored = [];
    foreach (get_chunks() as $chunk) {
        $score = score_chunk($chunk, $tokens, $normalizedQuery);
        if ($score <= 0) {
            continue;
        }
        $matchedTerms = chunk_matched_query_terms($chunk, $terms);
        if (count($matchedTerms) < $requiredMatches) {
            continue;
        }
        $chunk['score'] = $score;
        $chunk['matched_terms'] = $matchedTerms;
        $chunk['match_coverage'] = count($terms) > 0 ? count($matchedTerms) . '/' . count($terms) : '0/0';
        $scored[] = $chunk;
    }

    usort($scored, fn(array $a, array $b) => $b['score'] <=> $a['score']);
    return array_slice($scored, 0, max(1, $limit));
}

function build_rag_block(array $chunks): string
{
    if ($chunks === []) {
        return '';
    }

    $parts = [];
    $parts[] = '=== WISSENSDATENBANK ===';
    $parts[] = 'Nutze die folgenden Chunks als vorrangige Arbeitsgrundlage. Nenne am Ende unter "**Quellen:**" nur Quellen, die du tatsächlich verwendet hast.';
    $parts[] = '';

    foreach ($chunks as $index => $chunk) {
        $parts[] = '[' . ($index + 1) . '] ' . $chunk['title'];
        if ($chunk['quelle'] !== '') {
            $parts[] = 'Quelle: ' . $chunk['quelle'];
        }
        if ($chunk['source_file'] !== '') {
            $parts[] = 'Datei: ' . $chunk['source_file'];
        }
        if ($chunk['tags'] !== []) {
            $parts[] = 'Tags: ' . implode(', ', $chunk['tags']);
        }
        $parts[] = trim((string) $chunk['body']);
        $parts[] = '';
        $parts[] = '---';
        $parts[] = '';
    }

    $parts[] = '=== ENDE WISSENSDATENBANK ===';

    return implode("\n", $parts);
}
