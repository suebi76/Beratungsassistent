<?php
declare(strict_types=1);

final class LockManager
{
    public function withLock(string $name, callable $callback, int $timeoutSeconds = 30): mixed
    {
        ensure_app_dirs();

        $safeName = preg_replace('/[^a-z0-9_.-]+/i', '-', $name) ?: 'lock';
        $path = locks_dir() . DIRECTORY_SEPARATOR . $safeName . '.lock';
        $handle = fopen($path, 'c+');
        if (!is_resource($handle)) {
            throw new RuntimeException('Lockdatei konnte nicht geöffnet werden: ' . $path);
        }

        $deadline = time() + max(1, $timeoutSeconds);
        try {
            do {
                if (flock($handle, LOCK_EX | LOCK_NB)) {
                    try {
                        ftruncate($handle, 0);
                        fwrite($handle, (string) getmypid());
                        return $callback();
                    } finally {
                        flock($handle, LOCK_UN);
                    }
                }
                usleep(100000);
            } while (time() < $deadline);

            throw new RuntimeException('Lock konnte nicht rechtzeitig gesetzt werden: ' . $name);
        } finally {
            fclose($handle);
        }
    }
}
