<?php
declare(strict_types=1);

final class JsonStore
{
    public function __construct(private AtomicWriter $writer)
    {
    }

    public function read(string $path, array $default = []): array
    {
        if (!is_file($path)) {
            return $default;
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '') {
            return $default;
        }

        $data = json_decode($raw, true);
        return is_array($data) ? $data : $default;
    }

    public function write(string $path, array $data): void
    {
        $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            throw new RuntimeException('JSON konnte nicht erzeugt werden: ' . json_last_error_msg());
        }

        $this->writer->write($path, $payload . "\n");
    }
}
