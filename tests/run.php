<?php
declare(strict_types=1);

$testRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'beratungsassistent-test-' . bin2hex(random_bytes(4));
putenv('BERATUNGSASSISTENT_DATA_DIR=' . $testRoot);

require __DIR__ . '/../lib/app.php';

function test_assert(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function remove_tree(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isDir()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }

    rmdir($path);
}

try {
    ensure_app_dirs();

    $writer = new AtomicWriter();
    $jsonStore = new JsonStore($writer);
    $jsonPath = runtime_root('tmp/test.json');
    $jsonStore->write($jsonPath, ['name' => 'Beratungsassistent', 'ok' => true]);
    test_assert($jsonStore->read($jsonPath)['name'] === 'Beratungsassistent', 'JsonStore liest geschriebenen Wert nicht korrekt.');
    $jsonStore->write($jsonPath, ['name' => 'Beratungsassistent', 'ok' => false]);
    test_assert($jsonStore->read($jsonPath)['ok'] === false, 'JsonStore überschreibt bestehenden Wert nicht korrekt.');

    $lockManager = new LockManager();
    $locked = $lockManager->withLock('test', static fn(): string => 'locked');
    test_assert($locked === 'locked', 'LockManager gibt Callback-Ergebnis nicht zurück.');

    $project = load_project_config();
    test_assert($project['title'] === 'Beratungs-Assistent', 'Default-Projektkonfiguration fehlt.');
    $project['topic'] = 'Testthema';
    test_assert(save_project_config($project), 'Projektkonfiguration konnte nicht gespeichert werden.');
    test_assert(load_project_config()['topic'] === 'Testthema', 'Projektkonfiguration wurde nicht persistent geladen.');
    $project['topic'] = 'Geändertes Testthema';
    test_assert(save_project_config($project), 'Projektkonfiguration konnte nicht überschrieben werden.');
    test_assert(load_project_config()['topic'] === 'Geändertes Testthema', 'Projektkonfiguration wurde nach Überschreiben nicht korrekt geladen.');
    test_assert(project_profile_is_configured(load_project_config()), 'Projektprofil sollte als konfiguriert gelten.');

    test_assert(!api_key_is_configured(load_api_config()), 'Leere API-Konfiguration sollte nicht konfiguriert sein.');
    test_assert(model_gateway(load_api_config())->providerId() === 'gemini', 'Standardanbieter sollte Gemini sein.');
    test_assert(model_gateway(load_api_config())->capabilities()['streaming'] === true, 'Gemini-Provider sollte Streaming melden.');
    test_assert(!(model_generate_text([['text' => 'Test']], load_api_config())['ok'] ?? false), 'Leere API-Konfiguration darf keinen Modellaufruf erlauben.');
    test_assert(save_api_config('test-api-key-12345', 'test-model'), 'API-Konfiguration konnte nicht gespeichert werden.');
    $apiConfig = load_api_config();
    test_assert($apiConfig['provider'] === 'gemini', 'Provider wurde nicht geladen.');
    test_assert($apiConfig['api_key'] === 'test-api-key-12345', 'API-Schlüssel wurde nicht geladen.');
    test_assert($apiConfig['model'] === 'test-model', 'Modell wurde nicht geladen.');
    test_assert(api_key_is_configured($apiConfig), 'API-Konfiguration sollte als konfiguriert gelten.');

    echo "All tests passed.\n";
} finally {
    remove_tree($testRoot);
}
