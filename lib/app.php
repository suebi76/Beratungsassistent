<?php
declare(strict_types=1);

const APP_VERSION = '0.1.0';
const DEFAULT_MODEL_NAME = 'gemini-2.5-flash';
const MAX_UPLOAD_BYTES = 20 * 1024 * 1024;
const MAX_TEXT_SOURCE_BYTES = 900000;
const MAX_RETRIEVAL_CHUNKS = 5;

function app_root(string $path = ''): string
{
    $root = dirname(__DIR__);
    if ($path === '') {
        return $root;
    }

    $clean = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    return $root . DIRECTORY_SEPARATOR . ltrim($clean, DIRECTORY_SEPARATOR);
}

function config_dir(): string { return app_root('config'); }
function rag_dir(): string { return app_root('rag'); }
function chunks_dir(): string { return app_root('rag/chunks'); }
function uploads_dir(): string { return app_root('rag/uploads'); }
function api_config_file(): string { return app_root('config/config.php'); }
function project_config_file(): string { return app_root('config/project.json'); }
function password_file(): string { return app_root('rag/.admin_password'); }

function ensure_app_dirs(): void
{
    foreach ([config_dir(), rag_dir(), chunks_dir(), uploads_dir()] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

function ensure_runtime_placeholders(): void
{
    foreach ([chunks_dir() . '/.gitkeep', uploads_dir() . '/.gitkeep'] as $file) {
        if (!file_exists($file)) {
            file_put_contents($file, '');
        }
    }
}

function now_iso(): string
{
    return date(DATE_ATOM);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function password_is_set(): bool
{
    return file_exists(password_file()) && trim((string) @file_get_contents(password_file())) !== '';
}

function default_api_config(): array
{
    return [
        'api_key' => '',
        'model' => DEFAULT_MODEL_NAME,
    ];
}

function parse_legacy_php_config(string $php): array
{
    $config = default_api_config();

    if (preg_match("/define\\(\\s*'GEMINI_API_KEY'\\s*,\\s*'([^']*)'\\s*\\)/", $php, $m)) {
        $config['api_key'] = stripcslashes($m[1]);
    }

    if (preg_match("/define\\(\\s*'MODEL_NAME'\\s*,\\s*'([^']*)'\\s*\\)/", $php, $m)) {
        $config['model'] = stripcslashes($m[1]);
    }

    return $config;
}

function load_api_config(): array
{
    ensure_app_dirs();
    $file = api_config_file();
    if (!file_exists($file)) {
        return default_api_config();
    }

    $raw = (string) @file_get_contents($file);
    if (strpos($raw, 'return [') !== false) {
        $data = require $file;
        if (is_array($data)) {
            return [
                'api_key' => trim((string) ($data['api_key'] ?? '')),
                'model' => trim((string) ($data['model'] ?? DEFAULT_MODEL_NAME)) ?: DEFAULT_MODEL_NAME,
            ];
        }
    }

    return parse_legacy_php_config($raw);
}

function save_api_config(string $apiKey, string $model = DEFAULT_MODEL_NAME): bool
{
    ensure_app_dirs();
    $content = "<?php\n"
        . "return [\n"
        . "    'api_key' => " . var_export(trim($apiKey), true) . ",\n"
        . "    'model' => " . var_export(trim($model) ?: DEFAULT_MODEL_NAME, true) . ",\n"
        . "];\n";

    return file_put_contents(api_config_file(), $content) !== false;
}

function api_key_is_configured(array $apiConfig): bool
{
    $value = trim((string) ($apiConfig['api_key'] ?? ''));
    if ($value === '') {
        return false;
    }

    return !in_array($value, [
        'DEIN_GEMINI_API_KEY_HIER',
        'DEIN_KEY_AUS_GOOGLE_AI_STUDIO',
    ], true);
}

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
            'pii_notice' => 'Bitte keine personenbezogenen Daten, vertraulichen Einzelfaelle oder geheimhaltungsbeduerftigen Inhalte eingeben.',
            'pii_rejection_message' => 'Bitte geben Sie keine personenbezogenen Daten oder vertraulichen Einzelfaelle ein. Formulieren Sie Ihre Frage allgemeiner, dann helfe ich gerne weiter.',
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

function current_setup_step(array $apiConfig, array $project): string
{
    if (!password_is_set()) {
        return 'password';
    }
    if (!api_key_is_configured($apiConfig)) {
        return 'api';
    }
    if (!project_profile_is_configured($project)) {
        return 'profile';
    }
    if (!knowledge_base_is_configured()) {
        return 'documents';
    }
    return 'done';
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text), 'UTF-8');
    $text = strtr($text, [
        'ae' => 'ae',
        'oe' => 'oe',
        'ue' => 'ue',
        'ss' => 'ss',
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'ß' => 'ss',
    ]);
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text) ?? '';
    $text = trim($text, '-');
    return $text !== '' ? $text : 'eintrag';
}

function normalize_whitespace(string $text): string
{
    $text = str_replace(["\r\n", "\r"], "\n", $text);
    $text = preg_replace("/[ \t]+/u", ' ', $text) ?? $text;
    return trim($text);
}

function normalize_search_text(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = strtr($text, [
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'ß' => 'ss',
    ]);
    $text = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text) ?? '';
    $text = preg_replace('/\s+/u', ' ', $text) ?? '';
    return trim($text);
}

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

function excerpt(string $text, int $limit = 260): string
{
    $plain = preg_replace('/\s+/u', ' ', strip_tags(str_replace("\n", ' ', $text))) ?? '';
    $plain = trim($plain);
    if (mb_strlen($plain, 'UTF-8') <= $limit) {
        return $plain;
    }
    return rtrim(mb_substr($plain, 0, $limit, 'UTF-8')) . '...';
}

function get_chunks(): array
{
    $items = array_map('parse_chunk_file', get_chunk_files());
    usort($items, fn(array $a, array $b) => strcasecmp($a['title'], $b['title']));
    return $items;
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
    $parts[] = 'Nutze die folgenden Chunks als vorrangige Arbeitsgrundlage. Nenne am Ende unter "**Quellen:**" nur Quellen, die du tatsaechlich verwendet hast.';
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

function supported_extensions(): array
{
    return [
        'pdf' => 'application/pdf',
        'txt' => 'text/plain',
        'md' => 'text/markdown',
        'markdown' => 'text/markdown',
    ];
}

function normalize_uploaded_files(array $files): array
{
    $result = [];
    $names = $files['name'] ?? [];
    if (!is_array($names)) {
        return [$files];
    }

    foreach (array_keys($names) as $index) {
        $result[] = [
            'name' => $files['name'][$index] ?? '',
            'type' => $files['type'][$index] ?? '',
            'tmp_name' => $files['tmp_name'][$index] ?? '',
            'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$index] ?? 0,
        ];
    }

    return $result;
}

function validate_uploaded_file(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Upload fehlgeschlagen (Code ' . ($file['error'] ?? '?') . ').'];
    }

    if (($file['size'] ?? 0) > MAX_UPLOAD_BYTES) {
        return ['ok' => false, 'error' => 'Datei zu gross. Maximal erlaubt sind 20 MB.'];
    }

    $name = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!array_key_exists($extension, supported_extensions())) {
        return ['ok' => false, 'error' => 'Nicht unterstuetzter Dateityp. Erlaubt sind PDF, TXT und Markdown.'];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp) ?: 'application/octet-stream';

    if ($extension === 'pdf' && $mime !== 'application/pdf') {
        return ['ok' => false, 'error' => 'Die Datei wurde nicht als PDF erkannt.'];
    }

    if ($extension !== 'pdf' && !preg_match('/^(text\\/|application\\/octet-stream$)/', $mime)) {
        return ['ok' => false, 'error' => 'Textdatei konnte nicht sicher gelesen werden. Bitte PDF, TXT oder Markdown verwenden.'];
    }

    return [
        'ok' => true,
        'extension' => $extension,
        'mime' => $mime,
    ];
}

function unique_stored_filename(string $originalName): string
{
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $base = slugify(pathinfo($originalName, PATHINFO_FILENAME));
    $candidate = $base . '-' . date('Ymd-His');
    $full = uploads_dir() . '/' . $candidate . '.' . $extension;
    $suffix = 2;
    while (file_exists($full)) {
        $full = uploads_dir() . '/' . $candidate . '-' . $suffix . '.' . $extension;
        $suffix++;
    }
    return basename($full);
}

function store_uploaded_file(array $file): array
{
    ensure_app_dirs();
    ensure_runtime_placeholders();

    $storedName = unique_stored_filename((string) $file['name']);
    $target = uploads_dir() . '/' . $storedName;

    if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
        return ['ok' => false, 'error' => 'Datei konnte serverseitig nicht gespeichert werden.'];
    }

    return [
        'ok' => true,
        'stored_name' => $storedName,
        'path' => $target,
    ];
}

function read_text_source(string $path): string
{
    $content = (string) @file_get_contents($path, false, null, 0, MAX_TEXT_SOURCE_BYTES);
    return trim($content);
}

function build_chunk_generation_prompt(array $project, string $originalName, string $extension): string
{
    $title = trim((string) ($project['title'] ?? 'Beratungs-Assistent'));
    $topic = trim((string) ($project['topic'] ?? ''));
    $audience = trim((string) ($project['audience'] ?? ''));

    return "Du erstellst Wissens-Chunks fuer eine Retrieval-Augmented-Generation-Wissensbasis.\n"
        . "Projektkontext:\n"
        . "- Titel des Assistenten: {$title}\n"
        . "- Themenfeld: {$topic}\n"
        . "- Zielgruppe: " . ($audience !== '' ? $audience : 'nicht angegeben') . "\n"
        . "- Quelldatei: {$originalName} ({$extension})\n\n"
        . "Aufgabe:\n"
        . "- Teile das Dokument in klar abgegrenzte Wissens-Chunks auf.\n"
        . "- Jeder Chunk soll genau ein Thema, einen Prozess, eine Regel oder eine wiederkehrende Beratungsfrage abdecken.\n"
        . "- Schreibe sachlich, nah an der Quelle und ohne Marketing-Sprache.\n"
        . "- Nutze Deutsch.\n"
        . "- Pro Chunk: etwa 180 bis 500 Woerter.\n"
        . "- Nutze Markdown mit sinnvollen Ueberschriften und kompakten Listen.\n"
        . "- Fuege 8 bis 12 aussagekraeftige Tags hinzu, inklusive gebraeuchlicher Synonyme.\n"
        . "- Nutze im Feld `quelle` den erkennbaren Dokumenttitel, sonst den Dateinamen.\n"
        . "- Erfinde keine Fakten, die nicht aus der Datei ableitbar sind.\n"
        . "- Wenn ein Dokument mehrere Themen enthaelt, erzeuge mehrere Chunks.\n\n"
        . "Gib ausschliesslich dieses Format zurueck, ohne Einleitung und ohne Schlusskommentar:\n\n"
        . "CHUNK_START\n"
        . "---\n"
        . "title: Praegnanter Titel\n"
        . "tags: tag1, tag2, tag3, tag4, tag5, tag6, tag7, tag8\n"
        . "quelle: Dokumenttitel oder Dateiname\n"
        . "---\n\n"
        . "## Kernaussage\n"
        . "Inhalt des Chunks.\n\n"
        . "CHUNK_END";
}

function build_document_parts_for_gemini(string $path, string $originalName, string $extension): array
{
    if ($extension === 'pdf') {
        return [[
            'inline_data' => [
                'mime_type' => 'application/pdf',
                'data' => base64_encode((string) file_get_contents($path)),
            ],
        ]];
    }

    $content = read_text_source($path);
    return [[
        'text' => "Dateiname: {$originalName}\n\n{$content}",
    ]];
}

function gemini_generate_text(array $parts, array $apiConfig, array $options = []): array
{
    if (!api_key_is_configured($apiConfig)) {
        return ['ok' => false, 'error' => 'Kein gueltiger Gemini-API-Key konfiguriert.'];
    }

    $payload = [
        'contents' => [[
            'parts' => $parts,
        ]],
        'generationConfig' => [
            'temperature' => $options['temperature'] ?? 0.2,
            'maxOutputTokens' => $options['maxOutputTokens'] ?? 16384,
        ],
    ];

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . rawurlencode((string) ($apiConfig['model'] ?? DEFAULT_MODEL_NAME))
        . ':generateContent?key=' . rawurlencode((string) $apiConfig['api_key']);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => $options['timeout'] ?? 180,
    ]);

    $response = curl_exec($ch);
    $error = curl_errno($ch) ? curl_error($ch) : '';
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($error !== '') {
        return ['ok' => false, 'error' => 'cURL-Fehler: ' . $error];
    }

    $data = json_decode((string) $response, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (!is_string($text) || trim($text) === '') {
        $apiMessage = $data['error']['message'] ?? ('HTTP ' . $status . ' ohne verwertbare Antwort');
        return ['ok' => false, 'error' => 'Gemini-Fehler: ' . $apiMessage, 'raw' => $data];
    }

    return ['ok' => true, 'text' => $text, 'raw' => $data];
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

function process_uploaded_document(array $file, array $project, array $apiConfig): array
{
    $validation = validate_uploaded_file($file);
    if (!($validation['ok'] ?? false)) {
        return ['ok' => false, 'error' => $validation['error'] ?? 'Datei konnte nicht validiert werden.'];
    }

    $stored = store_uploaded_file($file);
    if (!($stored['ok'] ?? false)) {
        return ['ok' => false, 'error' => $stored['error'] ?? 'Datei konnte nicht gespeichert werden.'];
    }

    $parts = build_document_parts_for_gemini($stored['path'], (string) $file['name'], (string) $validation['extension']);
    $parts[] = ['text' => build_chunk_generation_prompt($project, (string) $file['name'], (string) $validation['extension'])];

    $generation = gemini_generate_text($parts, $apiConfig, [
        'temperature' => 0.2,
        'maxOutputTokens' => 16384,
        'timeout' => 240,
    ]);

    if (!($generation['ok'] ?? false)) {
        return [
            'ok' => false,
            'error' => $generation['error'] ?? 'Gemini konnte die Datei nicht verarbeiten.',
            'stored_name' => $stored['stored_name'],
        ];
    }

    $savedChunks = save_chunks_from_response((string) $generation['text'], (string) $file['name'], (string) $validation['extension']);
    if ($savedChunks === []) {
        return [
            'ok' => false,
            'error' => 'Gemini hat keine verwertbaren Chunks geliefert. Bitte die Datei erneut hochladen oder als kleinere Teildateien bereitstellen.',
            'stored_name' => $stored['stored_name'],
        ];
    }

    return [
        'ok' => true,
        'stored_name' => $stored['stored_name'],
        'saved_chunks' => $savedChunks,
        'document' => [
            'original_name' => (string) $file['name'],
            'stored_name' => $stored['stored_name'],
            'mime_type' => (string) $validation['mime'],
            'extension' => (string) $validation['extension'],
            'bytes' => (int) ($file['size'] ?? 0),
            'uploaded_at' => now_iso(),
            'chunks_created' => count($savedChunks),
        ],
    ];
}

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

    return "Erstelle aus einer vorhandenen Wissensbasis die oeffentliche Projektkonfiguration fuer einen deutschen Beratungsassistenten.\n"
        . "Projektangaben:\n"
        . "- Titel: {$title}\n"
        . "- Themenfeld: {$topic}\n"
        . "- Zielgruppe: " . ($audience !== '' ? $audience : 'nicht angegeben') . "\n\n"
        . "Wissensbasis (Auszuege aus vorhandenen Chunks):\n"
        . implode("\n", $chunkDigest) . "\n\n"
        . "Liefere ausschliesslich JSON in genau dieser Struktur:\n"
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
        . "- `task_examples`: genau 4 konkrete Arbeitsauftraege.\n"
        . "- `templates`: genau 4 Sektionen mit jeweils 3 Optionen.\n"
        . "- Die Prompts muessen unmittelbar als Beratungsfrage oder Arbeitsauftrag verwendbar sein.\n"
        . "- Keine Hinweise auf Dateinamen im UI-Text, ausser es ist fachlich notwendig.";
}

function fallback_profile(array $project, array $chunks): array
{
    $topic = trim((string) ($project['topic'] ?? 'diesem Themenfeld'));
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
        $taskExamples[] = 'Fasse die wichtigsten Punkte zu "' . $title . '" fuer eine Beratungssituation zusammen.';
    }

    $templateOptions = [];
    foreach (array_slice($titles, 0, 12) as $title) {
        $templateOptions[] = [
            'label' => mb_substr($title, 0, 34, 'UTF-8'),
            'prompt' => 'Erlaeutere die wichtigsten Inhalte und Handlungsoptionen zu "' . $title . '" auf Basis der hinterlegten Wissensbasis.',
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
        'subtitle' => 'Konfigurierbarer Assistent fuer ' . $topic,
        'assistant_mission' => 'Unterstuetzt bei fachlichen Fragen und Arbeitsauftraegen auf Basis der hinterlegten Dokumente.',
        'scope_summary' => 'Antwortet innerhalb des konfigurierten Themenfelds auf Basis des geladenen Wissensbestands.',
        'scope_bullets' => array_slice($titles, 0, 5),
        'knowledge_profile' => [
            'document_summary' => 'Die Wissensbasis besteht aus hochgeladenen Dokumenten, die in thematische Chunks zerlegt wurden.',
            'focus_areas' => array_slice($titles, 0, 5),
            'limitations' => ['Antworten sind nur belastbar, wenn passende Inhalte in den geladenen Dateien vorhanden sind.'],
        ],
        'frontend' => [
            'welcome_heading' => trim((string) ($project['title'] ?? 'Beratungs-Assistent')),
            'welcome_text' => 'Stellen Sie Fragen zu ' . $topic . '. Die Antworten beziehen sich auf die im Hintergrund geladenen Dateien.',
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
    $generation = gemini_generate_text([['text' => $prompt]], $apiConfig, [
        'temperature' => 0.3,
        'maxOutputTokens' => 8192,
        'timeout' => 180,
    ]);

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

function build_system_prompt(array $project, array $retrievedChunks): string
{
    $title = trim((string) ($project['title'] ?? 'Beratungs-Assistent'));
    $topic = trim((string) ($project['topic'] ?? 'dem konfigurierten Themenfeld'));
    $audience = trim((string) ($project['audience'] ?? 'der vorgesehenen Zielgruppe'));
    $mission = trim((string) ($project['assistant_mission'] ?? ''));
    $scopeSummary = trim((string) ($project['scope_summary'] ?? ''));
    $outOfScope = trim((string) ($project['safety']['out_of_scope_message'] ?? 'Zu dieser Frage liegen im aktuell geladenen Wissensbestand keine belastbaren Informationen vor.'));
    $piiReject = trim((string) ($project['safety']['pii_rejection_message'] ?? 'Bitte geben Sie keine personenbezogenen Daten oder vertraulichen Einzelfaelle ein.'));

    $lines = [];
    $lines[] = 'ROLLE UND KONTEXT:';
    $lines[] = "Du bist \"{$title}\", ein spezialisierter Beratungsassistent fuer {$audience}.";
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
    $lines[] = '- Antworte ausschliesslich auf Deutsch.';
    $lines[] = '- Antworte strukturiert in Markdown.';
    $lines[] = '- Nutze vorrangig die beigefuegte Wissensdatenbank.';
    $lines[] = '- Wenn die Frage mit den verfuegbaren Quellen nicht belastbar beantwortbar ist oder klar ausserhalb des Themenfelds liegt, antworte exakt: "' . $outOfScope . '"';
    $lines[] = '- Verarbeite keine personenbezogenen Daten, vertraulichen Einzelfaelle, Kennungen, Seriennummern oder Geheimnisse.';
    $lines[] = '- Wenn eine Anfrage solche Inhalte enthaelt, antworte exakt: "' . $piiReject . '"';
    $lines[] = '- Nenne am Ende unter "**Quellen:**" die tatsaechlich genutzten Quellen aus den Chunks.';
    $lines[] = '- Erfinde keine Fakten, wenn die Quellen unklar oder unvollstaendig sind.';
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
