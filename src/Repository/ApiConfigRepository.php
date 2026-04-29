<?php
declare(strict_types=1);

final class ApiConfigRepository
{
    public function __construct(private AtomicWriter $writer)
    {
    }

    public function load(): array
    {
        ensure_app_dirs();
        $file = api_config_file();
        if (!file_exists($file)) {
            return default_api_config();
        }

        $raw = (string) file_get_contents($file);
        if (strpos($raw, 'return [') !== false) {
            $data = require $file;
            if (is_array($data)) {
                return array_merge(default_api_config(), [
                    'provider' => trim((string) ($data['provider'] ?? 'gemini')) ?: 'gemini',
                    'base_url' => trim((string) ($data['base_url'] ?? 'https://generativelanguage.googleapis.com')) ?: 'https://generativelanguage.googleapis.com',
                    'api_key' => trim((string) ($data['api_key'] ?? '')),
                    'model' => trim((string) ($data['model'] ?? DEFAULT_MODEL_NAME)) ?: DEFAULT_MODEL_NAME,
                ]);
            }
        }

        return parse_legacy_php_config($raw);
    }

    public function save(string $apiKey, string $model = DEFAULT_MODEL_NAME): void
    {
        ensure_app_dirs();
        $current = $this->load();
        $content = "<?php\n"
            . "return [\n"
            . "    'provider' => " . var_export(trim((string) ($current['provider'] ?? 'gemini')) ?: 'gemini', true) . ",\n"
            . "    'base_url' => " . var_export(trim((string) ($current['base_url'] ?? 'https://generativelanguage.googleapis.com')) ?: 'https://generativelanguage.googleapis.com', true) . ",\n"
            . "    'api_key' => " . var_export(trim($apiKey), true) . ",\n"
            . "    'model' => " . var_export(trim($model) ?: DEFAULT_MODEL_NAME, true) . ",\n"
            . "];\n";

        $this->writer->write(api_config_file(), $content);
    }

    public function isConfigured(array $apiConfig): bool
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
}

function api_config_repository(): ApiConfigRepository
{
    static $repository = null;
    if (!$repository instanceof ApiConfigRepository) {
        $repository = new ApiConfigRepository(new AtomicWriter());
    }

    return $repository;
}
