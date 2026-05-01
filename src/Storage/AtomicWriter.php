<?php
declare(strict_types=1);

final class AtomicWriter
{
    public function write(string $path, string $content, int $permissions = 0644): void
    {
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Verzeichnis konnte nicht erstellt werden: ' . $dir);
        }

        $tmp = $dir . DIRECTORY_SEPARATOR . '.' . basename($path) . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (file_put_contents($tmp, $content, LOCK_EX) === false) {
            throw new RuntimeException('Temporäre Datei konnte nicht geschrieben werden: ' . $tmp);
        }

        @chmod($tmp, $permissions);

        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException('Datei konnte nicht atomar veröffentlicht werden: ' . $path);
        }
    }
}
