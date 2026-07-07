<?php
declare(strict_types=1);

final class GeminiProvider implements ModelProvider
{
    public function __construct(private array $apiConfig)
    {
    }

    public function id(): string
    {
        return 'gemini';
    }

    public function label(): string
    {
        return 'Google Gemini';
    }

    public function capabilities(): ProviderCapabilities
    {
        return new ProviderCapabilities(
            streaming: true,
            pdfInput: true,
            jsonMode: false,
            embeddings: false,
            maxContextTokens: 0
        );
    }

    public function generateText(ModelRequest $request): array
    {
        return gemini_generate_text($request->parts(), $this->apiConfig, $request->options());
    }

    public function streamText(ModelRequest $request, callable $onDelta): array
    {
        if (!api_key_is_configured($this->apiConfig)) {
            return ['ok' => false, 'error' => 'Kein gültiger Gemini-API-Schlüssel konfiguriert.'];
        }
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'error' => 'PHP-cURL ist nicht aktiviert. Bitte die PHP-Erweiterung curl auf dem Server aktivieren.'];
        }

        $payload = [
            'contents' => $request->contents(),
        ];
        if ($request->systemInstruction() !== '') {
            $payload['systemInstruction'] = [
                'parts' => [['text' => $request->systemInstruction()]],
            ];
        }

        $geminiPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($geminiPayload === false) {
            return ['ok' => false, 'error' => 'Modellanfrage konnte nicht als JSON erzeugt werden.'];
        }

        $baseUrl = rtrim((string) ($this->apiConfig['base_url'] ?? 'https://generativelanguage.googleapis.com'), '/');
        $url = $baseUrl . '/v1beta/models/'
            . rawurlencode((string) ($this->apiConfig['model'] ?? DEFAULT_MODEL_NAME))
            . ':streamGenerateContent?alt=sse';

        $buffer = '';
        $emittedText = false;
        $emitPayload = static function (string $payload) use ($onDelta, &$emittedText): void {
            $payload = trim($payload);
            if ($payload === '' || $payload === '[DONE]') {
                return;
            }

            $decoded = json_decode($payload, true);
            if (!is_array($decoded)) {
                return;
            }

            $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if (is_string($text) && $text !== '') {
                $emittedText = true;
                $onDelta($text);
            }
        };
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $geminiPayload,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_HTTPHEADER => gemini_api_headers($this->apiConfig),
            CURLOPT_TIMEOUT => $request->options()['timeout'] ?? 120,
            CURLOPT_WRITEFUNCTION => static function ($ch, $data) use (&$buffer, $emitPayload) {
                $buffer .= $data;
                $lines = explode("\n", $buffer);
                $buffer = array_pop($lines) ?: '';

                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $emitPayload(substr($line, 5));
                }

                return strlen($data);
            },
        ]);

        curl_exec($ch);
        $error = curl_errno($ch) ? curl_error($ch) : '';
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($error !== '') {
            return ['ok' => false, 'error' => $error, 'retryable' => true];
        }

        if ($status >= 400) {
            return ['ok' => false, 'error' => 'Gemini-Streaming fehlgeschlagen (HTTP ' . $status . ').', 'status' => $status];
        }

        foreach (explode("\n", $buffer) as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'data:')) {
                $emitPayload(substr($line, 5));
            }
        }

        if (!$emittedText) {
            return ['ok' => false, 'error' => 'Gemini hat keine verwertbare Streaming-Antwort geliefert. Bitte erneut versuchen oder vorübergehend ein anderes Modell wählen.'];
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
}
