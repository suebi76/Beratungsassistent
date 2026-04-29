<?php
declare(strict_types=1);

final class OpenAiCompatibleProvider implements ModelProvider
{
    public function __construct(private array $apiConfig)
    {
    }

    public function id(): string
    {
        return 'openai_compatible';
    }

    public function label(): string
    {
        return 'OpenAI-kompatibler Endpunkt';
    }

    public function capabilities(): ProviderCapabilities
    {
        return new ProviderCapabilities(
            streaming: true,
            pdfInput: false,
            jsonMode: true,
            embeddings: false,
            maxContextTokens: 0
        );
    }

    public function generateText(ModelRequest $request): array
    {
        if (!api_key_is_configured($this->apiConfig)) {
            return ['ok' => false, 'error' => 'OpenAI-kompatibler Endpunkt ist nicht vollständig konfiguriert.'];
        }

        $text = $this->partsToText($request->parts());
        if ($text === null) {
            return ['ok' => false, 'error' => 'Der gewählte Anbieter unterstützt keine direkte PDF-/Dateiverarbeitung. Bitte Text extrahieren oder einen Anbieter mit PDF-Unterstützung verwenden.'];
        }

        $messages = [];
        if ($request->systemInstruction() !== '') {
            $messages[] = ['role' => 'system', 'content' => $request->systemInstruction()];
        }
        $messages[] = ['role' => 'user', 'content' => $text];

        $result = $this->postJson('/chat/completions', $this->buildPayload($messages, $request->options(), false), $request->options()['timeout'] ?? 180);
        if (!($result['ok'] ?? false)) {
            return $result;
        }

        $content = $result['data']['choices'][0]['message']['content'] ?? '';
        if (!is_string($content) || trim($content) === '') {
            return ['ok' => false, 'error' => 'OpenAI-kompatibler Endpunkt lieferte keine verwertbare Antwort.', 'raw' => $result['data'] ?? null];
        }

        return ['ok' => true, 'text' => $content, 'raw' => $result['data']];
    }

    public function streamText(ModelRequest $request, callable $onDelta): array
    {
        if (!api_key_is_configured($this->apiConfig)) {
            return ['ok' => false, 'error' => 'OpenAI-kompatibler Endpunkt ist nicht vollständig konfiguriert.'];
        }

        $messages = $this->contentsToMessages($request->contents(), $request->systemInstruction());
        $payload = $this->buildPayload($messages, $request->options(), true);
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return ['ok' => false, 'error' => 'Modellanfrage konnte nicht als JSON erzeugt werden.'];
        }

        $buffer = '';
        $ch = curl_init($this->endpoint('/chat/completions'));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HTTPHEADER => $this->headers(),
            CURLOPT_TIMEOUT => $request->options()['timeout'] ?? 120,
            CURLOPT_WRITEFUNCTION => static function ($ch, $data) use (&$buffer, $onDelta) {
                $buffer .= $data;
                $lines = explode("\n", $buffer);
                $buffer = array_pop($lines) ?: '';

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $payload = trim(substr($line, 5));
                    if ($payload === '' || $payload === '[DONE]') {
                        continue;
                    }

                    $decoded = json_decode($payload, true);
                    if (!is_array($decoded)) {
                        continue;
                    }

                    $text = $decoded['choices'][0]['delta']['content']
                        ?? $decoded['choices'][0]['message']['content']
                        ?? '';
                    if (is_string($text) && $text !== '') {
                        $onDelta($text);
                    }
                }

                return strlen($data);
            },
        ]);

        curl_exec($ch);
        $error = curl_errno($ch) ? curl_error($ch) : '';
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($error !== '') {
            return ['ok' => false, 'error' => 'cURL-Fehler: ' . $error, 'retryable' => true];
        }

        if ($status >= 400) {
            return ['ok' => false, 'error' => 'OpenAI-kompatibles Streaming fehlgeschlagen (HTTP ' . $status . ').', 'status' => $status];
        }

        return ['ok' => true];
    }

    public function testConnection(): array
    {
        return $this->generateText(ModelRequest::textGeneration(
            [['text' => 'Antworte nur mit: ok']],
            ['temperature' => 0, 'maxOutputTokens' => 16, 'timeout' => 30],
            'connection_test'
        ));
    }

    private function partsToText(array $parts): ?string
    {
        $textParts = [];
        foreach ($parts as $part) {
            if (isset($part['inline_data'])) {
                return null;
            }
            if (isset($part['text']) && is_string($part['text'])) {
                $textParts[] = $part['text'];
            }
        }

        return trim(implode("\n\n", $textParts));
    }

    private function contentsToMessages(array $contents, string $systemInstruction): array
    {
        $messages = [];
        if ($systemInstruction !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemInstruction];
        }

        foreach ($contents as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $role = ($entry['role'] ?? '') === 'model' ? 'assistant' : 'user';
            $parts = is_array($entry['parts'] ?? null) ? $entry['parts'] : [];
            $texts = [];
            foreach ($parts as $part) {
                if (isset($part['text']) && is_string($part['text'])) {
                    $texts[] = $part['text'];
                }
            }
            $content = trim(implode("\n\n", $texts));
            if ($content !== '') {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }

        return $messages;
    }

    private function buildPayload(array $messages, array $options, bool $stream): array
    {
        return [
            'model' => (string) ($this->apiConfig['model'] ?? ''),
            'messages' => $messages,
            'temperature' => $options['temperature'] ?? 0.2,
            'max_tokens' => $options['maxOutputTokens'] ?? 4096,
            'stream' => $stream,
        ];
    }

    private function postJson(string $path, array $payload, int $timeout): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return ['ok' => false, 'error' => 'Modellanfrage konnte nicht als JSON erzeugt werden.'];
        }

        $ch = curl_init($this->endpoint($path));
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $this->headers(),
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $response = curl_exec($ch);
        $error = curl_errno($ch) ? curl_error($ch) : '';
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($error !== '') {
            return ['ok' => false, 'error' => 'cURL-Fehler: ' . $error, 'retryable' => true];
        }

        $data = json_decode((string) $response, true);
        if ($status >= 400) {
            $message = is_array($data) ? (string) ($data['error']['message'] ?? ('HTTP ' . $status)) : ('HTTP ' . $status);
            return ['ok' => false, 'error' => 'OpenAI-kompatibler Endpunkt meldet: ' . $message, 'status' => $status, 'raw' => $data];
        }

        return ['ok' => true, 'data' => is_array($data) ? $data : []];
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) ($this->apiConfig['base_url'] ?? ''), '/') . $path;
    }

    private function headers(): array
    {
        $headers = ['Content-Type: application/json'];
        $apiKey = trim((string) ($this->apiConfig['api_key'] ?? ''));
        if ($apiKey !== '') {
            $headers[] = 'Authorization: Bearer ' . $apiKey;
        }

        return $headers;
    }
}
