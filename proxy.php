<?php
declare(strict_types=1);

require __DIR__ . '/lib/app.php';

ensure_app_dirs();
ensure_runtime_placeholders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method Not Allowed'], 405);
}

$contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > MAX_PROXY_BODY_BYTES) {
    json_response(['error' => 'Anfrage zu groß. Bitte Chatverlauf kürzen und erneut versuchen.'], 413);
}

$rateLimit = check_rate_limit(
    'proxy',
    request_client_ip(),
    PROXY_RATE_LIMIT_MAX_REQUESTS,
    PROXY_RATE_LIMIT_WINDOW_SECONDS
);
if (!($rateLimit['allowed'] ?? true)) {
    header('Retry-After: ' . (int) ($rateLimit['retry_after'] ?? PROXY_RATE_LIMIT_WINDOW_SECONDS));
    json_response(['error' => 'Zu viele Anfragen. Bitte kurz warten und erneut versuchen.'], 429);
}

$apiConfig = load_api_config();
$project = load_project_config();

if (current_setup_step($apiConfig, $project) !== 'done') {
    json_response(['error' => 'Der Beratungsassistent ist noch nicht fertig eingerichtet.'], 503);
}

$rawBody = file_get_contents('php://input');
if (!is_string($rawBody) || trim($rawBody) === '') {
    json_response(['error' => 'Leere Anfrage.'], 400);
}
if (strlen($rawBody) > MAX_PROXY_BODY_BYTES) {
    json_response(['error' => 'Anfrage zu groß. Bitte Chatverlauf kürzen und erneut versuchen.'], 413);
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    json_response(['error' => 'Ungültiges JSON.'], 400);
}

$messages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
$messages = array_slice($messages, -MAX_PROXY_MESSAGES);
$query = trim((string) ($payload['query'] ?? ''));

foreach ($messages as $message) {
    if (!is_array($message)) {
        continue;
    }
    $text = (string) ($message['text'] ?? '');
    if (mb_strlen($text, 'UTF-8') > MAX_PROXY_MESSAGE_CHARS) {
        json_response(['error' => 'Chatverlauf ist zu lang. Bitte neuen Chat starten oder Verlauf kürzen.'], 413);
    }
}

if ($query === '') {
    foreach (array_reverse($messages) as $message) {
        if (($message['role'] ?? '') === 'user' && trim((string) ($message['text'] ?? '')) !== '') {
            $query = trim((string) $message['text']);
            break;
        }
    }
}

if ($query === '') {
    json_response(['error' => 'Keine Nutzerfrage gefunden.'], 400);
}
if (mb_strlen($query, 'UTF-8') > MAX_PROXY_QUERY_CHARS) {
    json_response(['error' => 'Die Frage ist zu lang. Bitte kuerzer formulieren.'], 413);
}

$retrievedChunks = retrieve_relevant_chunks($query);
$systemPrompt = build_system_prompt($project, $retrievedChunks);
$contents = normalize_chat_messages($messages, $query);

while (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no');
header('Content-Encoding: none');

$streamResult = model_stream_chat(
    $contents,
    $systemPrompt,
    $apiConfig,
    static function (string $data): void {
        echo $data;
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    },
    ['timeout' => 120]
);

if (!($streamResult['ok'] ?? false)) {
    echo 'data: ' . json_encode(['error' => $streamResult['error'] ?? 'Streaming fehlgeschlagen.'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    flush();
}
