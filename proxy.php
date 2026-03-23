<?php
declare(strict_types=1);

require __DIR__ . '/lib/app.php';

ensure_app_dirs();
ensure_runtime_placeholders();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method Not Allowed'], 405);
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

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    json_response(['error' => 'Ungültiges JSON.'], 400);
}

$messages = is_array($payload['messages'] ?? null) ? $payload['messages'] : [];
$query = trim((string) ($payload['query'] ?? ''));

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

$geminiPayload = json_encode([
    'contents' => $contents,
    'systemInstruction' => [
        'parts' => [['text' => $systemPrompt]],
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$url = 'https://generativelanguage.googleapis.com/v1beta/models/'
    . rawurlencode((string) ($apiConfig['model'] ?? DEFAULT_MODEL_NAME))
    . ':streamGenerateContent?key=' . rawurlencode((string) $apiConfig['api_key']) . '&alt=sse';

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $geminiPayload,
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 120,
    CURLOPT_WRITEFUNCTION => static function ($ch, $data) {
        echo $data;
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
        return strlen($data);
    },
]);

$curlError = '';
curl_exec($ch);
if (curl_errno($ch)) {
    $curlError = curl_error($ch);
}
curl_close($ch);

if ($curlError !== '') {
    echo 'data: ' . json_encode(['error' => $curlError], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    flush();
}
