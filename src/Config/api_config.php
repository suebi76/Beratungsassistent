<?php
declare(strict_types=1);

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
    return api_config_repository()->load();
}

function save_api_config(string $apiKey, string $model = DEFAULT_MODEL_NAME): bool
{
    try {
        api_config_repository()->save($apiKey, $model);
        return true;
    } catch (Throwable) {
        return false;
    }
}

function api_key_is_configured(array $apiConfig): bool
{
    return api_config_repository()->isConfigured($apiConfig);
}
