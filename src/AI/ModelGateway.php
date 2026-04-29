<?php
declare(strict_types=1);

final class ModelGateway
{
    public function __construct(private ModelProvider $provider)
    {
    }

    public function providerId(): string
    {
        return $this->provider->id();
    }

    public function providerLabel(): string
    {
        return $this->provider->label();
    }

    public function capabilities(): array
    {
        return $this->provider->capabilities()->toArray();
    }

    public function generateText(array $parts, array $options = [], string $purpose = 'generation'): array
    {
        return $this->provider->generateText(ModelRequest::textGeneration($parts, $options, $purpose));
    }

    public function streamChat(array $contents, string $systemInstruction, callable $onDelta, array $options = []): array
    {
        return $this->provider->streamText(ModelRequest::chatStream($contents, $systemInstruction, $options), $onDelta);
    }

    public function testConnection(): array
    {
        return $this->provider->testConnection();
    }
}

function model_provider_from_config(array $apiConfig): ModelProvider
{
    $provider = trim((string) ($apiConfig['provider'] ?? 'gemini'));

    return match ($provider) {
        'openai_compatible' => new OpenAiCompatibleProvider($apiConfig),
        'gemini', '' => new GeminiProvider($apiConfig),
        default => new GeminiProvider($apiConfig),
    };
}

function model_gateway(array $apiConfig): ModelGateway
{
    return new ModelGateway(model_provider_from_config($apiConfig));
}

function model_generate_text(array $parts, array $apiConfig, array $options = [], string $purpose = 'generation'): array
{
    return model_gateway($apiConfig)->generateText($parts, $options, $purpose);
}

function model_stream_chat(array $contents, string $systemInstruction, array $apiConfig, callable $onDelta, array $options = []): array
{
    return model_gateway($apiConfig)->streamChat($contents, $systemInstruction, $onDelta, $options);
}
