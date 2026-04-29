<?php
declare(strict_types=1);

function app_root(string $path = ''): string
{
    $root = dirname(__DIR__, 2);
    if ($path === '') {
        return $root;
    }

    $clean = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    return $root . DIRECTORY_SEPARATOR . ltrim($clean, DIRECTORY_SEPARATOR);
}

function configured_data_root(): ?string
{
    $value = getenv('BERATUNGSASSISTENT_DATA_DIR');
    if (!is_string($value)) {
        return null;
    }

    $value = trim($value);
    if ($value === '') {
        return null;
    }

    return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $value), DIRECTORY_SEPARATOR);
}

function runtime_uses_external_data_root(): bool
{
    return configured_data_root() !== null;
}

function runtime_root(string $path = ''): string
{
    $root = configured_data_root() ?? app_root();
    if ($path === '') {
        return $root;
    }

    $clean = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    return $root . DIRECTORY_SEPARATOR . ltrim($clean, DIRECTORY_SEPARATOR);
}

function config_dir(): string { return runtime_root('config'); }
function rag_dir(): string { return runtime_root('rag'); }
function chunks_dir(): string { return runtime_root('rag/chunks'); }
function uploads_dir(): string { return runtime_root('rag/uploads'); }
function rate_limit_dir(): string { return runtime_root('rag/.rate-limit'); }
function api_config_file(): string { return runtime_root('config/config.php'); }
function project_config_file(): string { return runtime_root('config/project.json'); }
function password_file(): string { return runtime_root('rag/.admin_password'); }

function ensure_app_dirs(): void
{
    foreach ([config_dir(), rag_dir(), chunks_dir(), uploads_dir(), rate_limit_dir()] as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

function ensure_runtime_placeholders(): void
{
    foreach ([chunks_dir() . '/.gitkeep', uploads_dir() . '/.gitkeep'] as $file) {
        if (!file_exists($file)) {
            file_put_contents($file, '');
        }
    }
}

