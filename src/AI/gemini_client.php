<?php
declare(strict_types=1);

function gemini_api_headers(array $apiConfig): array
{
    return [
        'Content-Type: application/json',
        'x-goog-api-key: ' . trim((string) ($apiConfig['api_key'] ?? '')),
    ];
}

function gemini_generate_text(array $parts, array $apiConfig, array $options = []): array
{
    if (!api_key_is_configured($apiConfig)) {
        return ['ok' => false, 'error' => 'Kein gültiger Gemini-API-Schlüssel konfiguriert.'];
    }

    $maxAttempts = max(1, 1 + (int) ($options['retries'] ?? 0));
    $retryDelaySeconds = max(0, (int) ($options['retryDelaySeconds'] ?? 2));
    $lastResult = ['ok' => false, 'error' => 'Gemini-Aufruf wurde nicht ausgeführt.'];

    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        $lastResult = gemini_generate_text_once($parts, $apiConfig, $options);
        $lastResult['attempts'] = $attempt;
        if ($lastResult['ok'] ?? false) {
            return $lastResult;
        }

        if (!gemini_failure_is_retryable($lastResult) || $attempt >= $maxAttempts) {
            break;
        }

        if ($retryDelaySeconds > 0) {
            sleep(min($retryDelaySeconds * $attempt, 12));
        }
    }

    if (($lastResult['retryable'] ?? false) && $maxAttempts > 1) {
        $lastResult['error'] .= ' Der Aufruf wurde ' . $maxAttempts . ' Mal versucht. Bitte den Upload später erneut starten oder vorübergehend ein anderes Gemini-Modell in der Admin-Konfiguration eintragen.';
    }

    return $lastResult;
}

function gemini_generate_text_once(array $parts, array $apiConfig, array $options = []): array
{
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
        . ':generateContent';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => gemini_api_headers($apiConfig),
        CURLOPT_TIMEOUT => $options['timeout'] ?? 180,
    ]);

    $response = curl_exec($ch);
    $error = curl_errno($ch) ? curl_error($ch) : '';
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($error !== '') {
        return [
            'ok' => false,
            'error' => 'cURL-Fehler: ' . $error,
            'retryable' => true,
        ];
    }

    $data = json_decode((string) $response, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (!is_string($text) || trim($text) === '') {
        $apiMessage = $data['error']['message'] ?? ('HTTP ' . $status . ' ohne verwertbare Antwort');
        $retryable = gemini_api_error_is_retryable($status, (string) $apiMessage);
        return [
            'ok' => false,
            'error' => gemini_human_error((string) $apiMessage, $status, $retryable),
            'status' => $status,
            'api_message' => (string) $apiMessage,
            'retryable' => $retryable,
            'raw' => $data,
        ];
    }

    return ['ok' => true, 'text' => $text, 'raw' => $data];
}

function gemini_api_error_is_retryable(int $status, string $message): bool
{
    if (in_array($status, [408, 409, 429, 500, 502, 503, 504], true)) {
        return true;
    }

    $normalized = mb_strtolower($message, 'UTF-8');
    foreach (['high demand', 'overload', 'overloaded', 'temporarily unavailable', 'try again later', 'rate limit', 'resource exhausted'] as $needle) {
        if (str_contains($normalized, $needle)) {
            return true;
        }
    }

    return false;
}

function gemini_failure_is_retryable(array $result): bool
{
    return (bool) ($result['retryable'] ?? false);
}

function gemini_human_error(string $apiMessage, int $status, bool $retryable): string
{
    if ($retryable) {
        return 'Gemini ist momentan nicht zuverlässig erreichbar oder das gewählte Modell ist überlastet. Originalmeldung: ' . $apiMessage;
    }

    return 'Gemini-Fehler: ' . $apiMessage . ($status > 0 ? ' (HTTP ' . $status . ')' : '');
}
