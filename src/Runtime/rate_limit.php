<?php
declare(strict_types=1);

function check_rate_limit(string $scope, string $identity, int $maxRequests, int $windowSeconds): array
{
    ensure_app_dirs();

    $scope = preg_replace('/[^a-z0-9_-]/i', '-', $scope) ?: 'default';
    $file = rate_limit_dir() . '/' . $scope . '-' . hash('sha256', $identity) . '.json';
    $now = time();
    $windowStart = $now;
    $count = 0;

    $handle = @fopen($file, 'c+');
    if (!is_resource($handle)) {
        return ['allowed' => true, 'remaining' => $maxRequests, 'retry_after' => 0];
    }

    try {
        if (flock($handle, LOCK_EX)) {
            $raw = stream_get_contents($handle);
            $data = json_decode(is_string($raw) ? $raw : '', true);
            if (is_array($data)) {
                $windowStart = (int) ($data['window_start'] ?? $now);
                $count = (int) ($data['count'] ?? 0);
            }

            if ($windowStart <= 0 || ($now - $windowStart) >= $windowSeconds) {
                $windowStart = $now;
                $count = 0;
            }

            $count++;
            $allowed = $count <= $maxRequests;
            $retryAfter = $allowed ? 0 : max(1, $windowSeconds - ($now - $windowStart));

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode([
                'window_start' => $windowStart,
                'count' => $count,
                'updated_at' => $now,
            ], JSON_UNESCAPED_SLASHES));
            fflush($handle);
            flock($handle, LOCK_UN);

            return [
                'allowed' => $allowed,
                'remaining' => max(0, $maxRequests - $count),
                'retry_after' => $retryAfter,
            ];
        }
    } finally {
        fclose($handle);
    }

    return ['allowed' => true, 'remaining' => $maxRequests, 'retry_after' => 0];
}

