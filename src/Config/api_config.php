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

