<?php
declare(strict_types=1);

function default_api_config(): array
{
    return [
        'provider' => 'gemini',
        'base_url' => 'https://generativelanguage.googleapis.com',
        'api_key' => '',
        'model' => DEFAULT_MODEL_NAME,
    ];
}

function allowed_model_providers(): array
{
    return [
        'gemini' => 'Google Gemini',
        'openai_compatible' => 'OpenAI-kompatibler Endpunkt',
    ];
}

function normalize_model_provider(string $provider): string
{
    return array_key_exists($provider, allowed_model_providers()) ? $provider : 'gemini';
}

function default_base_url_for_provider(string $provider): string
{
    return match (normalize_model_provider($provider)) {
        'openai_compatible' => 'http://localhost:11434/v1',
        default => 'https://generativelanguage.googleapis.com',
    };
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
    return api_config_repository()->load();
}

function save_api_config(string $apiKey, string $model = DEFAULT_MODEL_NAME, string $provider = 'gemini', string $baseUrl = ''): bool
{
    try {
        api_config_repository()->save($apiKey, $model, $provider, $baseUrl);
        return true;
    } catch (Throwable) {
        return false;
    }
}

function api_key_is_configured(array $apiConfig): bool
{
    return api_config_repository()->isConfigured($apiConfig);
}
