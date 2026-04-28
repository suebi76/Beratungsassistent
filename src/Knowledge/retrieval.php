<?php
declare(strict_types=1);

function tokenize_query(string $query): array
{
    $stopwords = [
        'aber', 'alle', 'alles', 'auch', 'auf', 'aus', 'bei', 'beim', 'bin', 'bis',
        'bist', 'damit', 'dann', 'das', 'dass', 'dein', 'deine', 'dem', 'den', 'der',
        'des', 'die', 'dies', 'diese', 'dieser', 'doch', 'dort', 'du', 'ein', 'eine',
        'einer', 'einem', 'einen', 'er', 'es', 'etwa', 'euch', 'euer', 'fuer', 'hat',
        'haben', 'hier', 'hinter', 'ich', 'ihr', 'ihm', 'ihn', 'im', 'in', 'ist',
        'jede', 'jeder', 'kann', 'kein', 'keine', 'mit', 'muss', 'nach', 'nicht',
        'noch', 'nur', 'oder', 'ohne', 'schon', 'sich', 'sie', 'sind', 'soll', 'sowie',
        'und', 'uns', 'unter', 'vom', 'von', 'vor', 'war', 'waren', 'was', 'weil',
        'welche', 'welcher', 'wie', 'wird', 'wir', 'wo', 'zu', 'zum', 'zur', 'ueber',
    ];

    $words = preg_split('/\s+/u', normalize_search_text($query), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $tokens = [];

    foreach ($words as $word) {
        if (mb_strlen($word, 'UTF-8') < 3) {
            continue;
        }
        if (in_array($word, $stopwords, true)) {
            continue;
        }
        $tokens[] = $word;
    }

    $bigrams = [];
    for ($i = 0; $i < count($tokens) - 1; $i++) {
        $bigrams[] = $tokens[$i] . ' ' . $tokens[$i + 1];
    }

    return array_values(array_unique(array_merge($tokens, $bigrams)));
}

function score_chunk(array $chunk, array $tokens, string $normalizedQuery): int
{
    if ($tokens === []) {
        return 0;
    }

    $title = normalize_search_text((string) $chunk['title']);
    $tags = normalize_search_text(implode(' ', $chunk['tags']));
    $source = normalize_search_text((string) $chunk['quelle'] . ' ' . (string) $chunk['source_file']);
    $body = normalize_search_text((string) $chunk['body']);

    $score = 0;
    foreach ($tokens as $token) {
        $score += substr_count($title, $token) * 6;
        $score += substr_count($tags, $token) * 8;
        $score += substr_count($source, $token) * 4;
        $score += substr_count($body, $token);
    }

    if ($normalizedQuery !== '' && str_contains($body, $normalizedQuery)) {
        $score += 12;
    }
    if ($normalizedQuery !== '' && str_contains($title, $normalizedQuery)) {
        $score += 18;
    }

    return $score;
}

function retrieve_relevant_chunks(string $query, int $limit = MAX_RETRIEVAL_CHUNKS): array
{
    $normalizedQuery = normalize_search_text($query);
    $tokens = tokenize_query($query);
    if ($tokens === []) {
        return [];
    }

    $scored = [];
    foreach (get_chunks() as $chunk) {
        $score = score_chunk($chunk, $tokens, $normalizedQuery);
        if ($score <= 0) {
            continue;
        }
        $chunk['score'] = $score;
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

