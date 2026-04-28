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
        return ['ok' => false, 'error' => 'Kein gültiger Gemini-API-Key konfiguriert.'];
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

