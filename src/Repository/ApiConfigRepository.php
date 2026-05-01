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
                    'provider' => normalize_model_provider(trim((string) ($data['provider'] ?? 'gemini'))),
                    'base_url' => trim((string) ($data['base_url'] ?? default_base_url_for_provider((string) ($data['provider'] ?? 'gemini')))) ?: default_base_url_for_provider((string) ($data['provider'] ?? 'gemini')),
                    'api_key' => trim((string) ($data['api_key'] ?? '')),
                    'model' => trim((string) ($data['model'] ?? DEFAULT_MODEL_NAME)) ?: DEFAULT_MODEL_NAME,
                ]);
            }
        }

        return parse_legacy_php_config($raw);
    }

    public function save(string $apiKey, string $model = DEFAULT_MODEL_NAME, string $provider = 'gemini', string $baseUrl = ''): void
    {
        ensure_app_dirs();
        $current = $this->load();
        $provider = normalize_model_provider($provider !== '' ? $provider : (string) ($current['provider'] ?? 'gemini'));
        $baseUrl = trim($baseUrl) !== '' ? trim($baseUrl) : default_base_url_for_provider($provider);
        $content = "<?php\n"
            . "return [\n"
            . "    'provider' => " . var_export($provider, true) . ",\n"
            . "    'base_url' => " . var_export($baseUrl, true) . ",\n"
            . "    'api_key' => " . var_export(trim($apiKey), true) . ",\n"
            . "    'model' => " . var_export(trim($model) ?: DEFAULT_MODEL_NAME, true) . ",\n"
            . "];\n";

        $this->writer->write(api_config_file(), $content);
    }

    public function isConfigured(array $apiConfig): bool
    {
        $provider = normalize_model_provider((string) ($apiConfig['provider'] ?? 'gemini'));
        if ($provider === 'openai_compatible') {
            return trim((string) ($apiConfig['model'] ?? '')) !== ''
                && filter_var((string) ($apiConfig['base_url'] ?? ''), FILTER_VALIDATE_URL) !== false;
        }

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
