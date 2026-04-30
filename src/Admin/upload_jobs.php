<?php
declare(strict_types=1);

function admin_upload_job_dir(): string
{
    return tmp_dir() . '/admin-upload-jobs';
}

function admin_create_upload_job_id(): string
{
    return bin2hex(random_bytes(12));
}

function admin_normalize_upload_job_id(string $jobId): ?string
{
    $jobId = trim($jobId);
    return preg_match('/^[a-f0-9]{24}$/', $jobId) ? $jobId : null;
}

function admin_upload_job_path(string $jobId): string
{
    return admin_upload_job_dir() . '/' . $jobId . '.json';
}

function admin_upload_job_write(string $jobId, array $status): void
{
    ensure_app_dirs();
    if (!is_dir(admin_upload_job_dir())) {
        mkdir(admin_upload_job_dir(), 0755, true);
    }

    $status['job_id'] = $jobId;
    $status['updated_at'] = now_iso();
    $writer = new AtomicWriter();
    $writer->write(admin_upload_job_path($jobId), json_encode($status, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?: '{}');
}

function admin_upload_job_start(string $jobId, string $fileName, int $bytes): void
{
    admin_upload_job_write($jobId, [
        'status' => 'queued',
        'stage' => 'queued',
        'percent' => 1,
        'file_name' => $fileName,
        'bytes' => $bytes,
        'message' => 'Datei wartet auf Verarbeitung.',
        'started_at' => now_iso(),
    ]);
}

function admin_upload_job_update(string $jobId, string $stage, int $percent, string $message, array $extra = []): void
{
    $current = admin_upload_job_read($jobId);
    $status = array_merge($current, $extra, [
        'status' => $extra['status'] ?? 'running',
        'stage' => $stage,
        'percent' => max(0, min(100, $percent)),
        'message' => $message,
    ]);
    admin_upload_job_write($jobId, $status);
}

function admin_upload_job_finish(string $jobId, array $result): void
{
    $ok = (bool) ($result['ok'] ?? false);
    admin_upload_job_update(
        $jobId,
        $ok ? 'done' : 'error',
        100,
        $ok ? 'Datei wurde verarbeitet.' : (string) ($result['error'] ?? 'Datei konnte nicht verarbeitet werden.'),
        [
            'status' => $ok ? 'done' : 'error',
            'result' => $result,
            'finished_at' => now_iso(),
        ]
    );
}

function admin_upload_job_read(string $jobId): array
{
    $jobId = admin_normalize_upload_job_id($jobId) ?? '';
    if ($jobId === '') {
        return [];
    }

    $path = admin_upload_job_path($jobId);
    $data = json_decode((string) @file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function admin_upload_job_cleanup(int $maxAgeSeconds = 86400): void
{
    $dir = admin_upload_job_dir();
    if (!is_dir($dir)) {
        return;
    }

    $threshold = time() - $maxAgeSeconds;
    foreach (glob($dir . '/*.json') ?: [] as $file) {
        if ((int) @filemtime($file) < $threshold) {
            @unlink($file);
        }
    }
}
