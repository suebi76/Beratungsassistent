<?php
declare(strict_types=1);

function get_chunk_files(): array
{
    $files = glob(chunks_dir() . '/*.md') ?: [];
    sort($files);
    return $files;
}

function chunk_count(): int
{
    return count(get_chunk_files());
}

function knowledge_base_is_configured(): bool
{
    return chunk_count() > 0;
}

function split_frontmatter_and_body(string $content): array
{
    $content = str_replace(["\r\n", "\r"], "\n", trim($content));
    if (preg_match('/\A---\n(.*?)\n---\n?(.*)\z/s', $content, $m)) {
        return [$m[1], trim($m[2])];
    }
    return ['', trim($content)];
}

function parse_frontmatter_lines(string $frontmatter): array
{
    $result = [];
    foreach (preg_split('/\n/u', trim($frontmatter)) ?: [] as $line) {
        if (!str_contains($line, ':')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode(':', $line, 2));
        $result[$key] = $value;
    }
    return $result;
}

function normalize_tags(mixed $value): array
{
    if (is_array($value)) {
        $tags = $value;
    } else {
        $tags = preg_split('/[,;]+/u', (string) $value) ?: [];
    }

    $clean = [];
    foreach ($tags as $tag) {
        $tag = normalize_whitespace((string) $tag);
        if ($tag === '') {
            continue;
        }
        $clean[] = $tag;
    }

    return array_values(array_unique($clean));
}

function build_chunk_markdown(array $meta, string $body): string
{
    $body = trim($body);
    $frontmatter = [
        'title' => trim((string) ($meta['title'] ?? '')),
        'tags' => implode(', ', normalize_tags($meta['tags'] ?? [])),
        'quelle' => trim((string) ($meta['quelle'] ?? '')),
        'source_file' => trim((string) ($meta['source_file'] ?? '')),
        'doc_type' => trim((string) ($meta['doc_type'] ?? '')),
        'chunk_index' => trim((string) ($meta['chunk_index'] ?? '')),
    ];

    $lines = ['---'];
    foreach ($frontmatter as $key => $value) {
        if ($value === '') {
            continue;
        }
        $lines[] = $key . ': ' . $value;
    }
    $lines[] = '---';
    $lines[] = '';
    $lines[] = $body;

    return implode("\n", $lines) . "\n";
}

function parse_chunk_file(string $path): array
{
    $content = (string) @file_get_contents($path);
    [$frontmatter, $body] = split_frontmatter_and_body($content);
    $meta = parse_frontmatter_lines($frontmatter);

    return [
        'file' => basename($path),
        'path' => $path,
        'title' => trim((string) ($meta['title'] ?? basename($path))),
        'tags' => normalize_tags($meta['tags'] ?? ''),
        'quelle' => trim((string) ($meta['quelle'] ?? '')),
        'source_file' => trim((string) ($meta['source_file'] ?? '')),
        'doc_type' => trim((string) ($meta['doc_type'] ?? '')),
        'chunk_index' => trim((string) ($meta['chunk_index'] ?? '')),
        'body' => $body,
        'bytes' => strlen($content),
    ];
}

function get_chunks(): array
{
    $items = array_map('parse_chunk_file', get_chunk_files());
    usort($items, fn(array $a, array $b) => strcasecmp($a['title'], $b['title']));
    return $items;
}

function save_chunks_from_response(string $geminiText, string $originalName, string $extension): array
{
    preg_match_all('/CHUNK_START\\s*(.*?)\\s*CHUNK_END/s', $geminiText, $matches);
    $blocks = $matches[1] ?? [];
    if ($blocks === []) {
        return [];
    }

    ensure_app_dirs();
    ensure_runtime_placeholders();

    $saved = [];
    foreach ($blocks as $index => $block) {
        [$frontmatter, $body] = split_frontmatter_and_body(trim($block));
        $meta = parse_frontmatter_lines($frontmatter);

        $title = trim((string) ($meta['title'] ?? ''));
        if ($title === '') {
            $title = pathinfo($originalName, PATHINFO_FILENAME) . ' Teil ' . ($index + 1);
        }

        $canonicalMeta = [
            'title' => $title,
            'tags' => normalize_tags($meta['tags'] ?? ''),
            'quelle' => trim((string) ($meta['quelle'] ?? $originalName)),
            'source_file' => $originalName,
            'doc_type' => $extension,
            'chunk_index' => (string) ($index + 1),
        ];

        $slug = slugify($title);
        if ($slug === 'eintrag') {
            $slug = slugify(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . ($index + 1);
        }

        $candidate = chunks_dir() . '/' . $slug . '.md';
        $suffix = 2;
        while (file_exists($candidate)) {
            $candidate = chunks_dir() . '/' . $slug . '-' . $suffix . '.md';
            $suffix++;
        }

        $content = build_chunk_markdown($canonicalMeta, $body);
        if (file_put_contents($candidate, $content) !== false) {
            $saved[] = basename($candidate);
        }
    }

    return $saved;
}

