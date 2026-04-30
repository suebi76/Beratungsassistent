<?php
declare(strict_types=1);

$testRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'beratungsassistent-test-' . bin2hex(random_bytes(4));
putenv('BERATUNGSASSISTENT_DATA_DIR=' . $testRoot);

require __DIR__ . '/../lib/app.php';
require __DIR__ . '/../src/Admin/upload_jobs.php';
require __DIR__ . '/../src/Admin/system_check.php';
require __DIR__ . '/../src/Admin/actions.php';

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

    $_POST = [
        'quick_questions' => "Was ist wichtig?\nWas ist wichtig?\nWelche Fristen gelten?",
        'task_examples' => "Erstelle eine Checkliste.\nFasse die Regeln zusammen.",
        'template_title' => ['Beratung vorbereiten', ''],
        'template_description' => ['Hilfen für die Vorbereitung.', ''],
        'template_option_label' => [
            ['Checkliste', ''],
            [''],
        ],
        'template_option_prompt' => [
            ['Erstelle eine kurze Checkliste auf Basis der Wissensbasis.', ''],
            [''],
        ],
    ];
    $state = [];
    admin_action_save_frontend_content($state, load_project_config());
    $frontendProject = load_project_config();
    test_assert($state['messageType'] === 'success', 'Frontend-Inhalte sollten gespeichert werden.');
    test_assert(count($frontendProject['frontend']['quick_questions']) === 2, 'Schnellfragen sollten bereinigt und dedupliziert werden.');
    test_assert($frontendProject['frontend']['templates'][0]['options'][0]['label'] === 'Checkliste', 'Vorlagenoption wurde nicht korrekt gespeichert.');
    $_POST = [];

    test_assert(!api_key_is_configured(load_api_config()), 'Leere API-Konfiguration sollte nicht konfiguriert sein.');
    test_assert(model_gateway(load_api_config())->providerId() === 'gemini', 'Standardanbieter sollte Gemini sein.');
    test_assert(model_gateway(load_api_config())->capabilities()['streaming'] === true, 'Gemini-Provider sollte Streaming melden.');
    test_assert(!(model_generate_text([['text' => 'Test']], load_api_config())['ok'] ?? false), 'Leere API-Konfiguration darf keinen Modellaufruf erlauben.');
    $streamResult = model_stream_chat([], '', load_api_config(), static function (string $delta): void {});
    test_assert(!($streamResult['ok'] ?? false), 'Leere API-Konfiguration darf kein Streaming erlauben.');
    test_assert(save_api_config('test-api-key-12345', 'test-model'), 'API-Konfiguration konnte nicht gespeichert werden.');
    $apiConfig = load_api_config();
    test_assert($apiConfig['provider'] === 'gemini', 'Provider wurde nicht geladen.');
    test_assert($apiConfig['api_key'] === 'test-api-key-12345', 'API-Schlüssel wurde nicht geladen.');
    test_assert($apiConfig['model'] === 'test-model', 'Modell wurde nicht geladen.');
    test_assert(api_key_is_configured($apiConfig), 'API-Konfiguration sollte als konfiguriert gelten.');

    $_POST = [
        'provider' => 'gemini',
        'base_url' => default_base_url_for_provider('gemini'),
        'apikey' => '',
        'model' => 'test-model-2',
    ];
    $state = [];
    admin_action_save_api_key($state, $apiConfig);
    $preservedGeminiConfig = load_api_config();
    test_assert($state['messageType'] === 'success', 'Admin-Speichern mit vorhandenem Schlüssel sollte erfolgreich sein.');
    test_assert($preservedGeminiConfig['api_key'] === 'test-api-key-12345', 'Vorhandener Schlüssel sollte bei gleichem Provider erhalten bleiben.');

    $_POST = [
        'provider' => 'openai_compatible',
        'base_url' => default_base_url_for_provider('gemini'),
        'apikey' => '',
        'model' => 'llama3.1',
    ];
    $state = [];
    admin_action_save_api_key($state, $preservedGeminiConfig);
    $adminOpenAiConfig = load_api_config();
    test_assert($state['messageType'] === 'success', 'Admin-Speichern des OpenAI-kompatiblen Providers sollte erfolgreich sein.');
    test_assert($adminOpenAiConfig['provider'] === 'openai_compatible', 'Admin-Speichern sollte den Provider wechseln.');
    test_assert($adminOpenAiConfig['base_url'] === default_base_url_for_provider('openai_compatible'), 'Admin-Speichern sollte beim Providerwechsel die Standard-Base-URL anpassen.');
    test_assert($adminOpenAiConfig['api_key'] === '', 'Admin-Speichern darf den Gemini-Schlüssel beim Providerwechsel nicht als Token wiederverwenden.');
    $_POST = [];

    $state = [];
    admin_action_test_model_provider($state, default_api_config());
    test_assert($state['messageType'] === 'error', 'Verbindungstest ohne vollständige Konfiguration sollte blockiert werden.');

    test_assert(save_api_config('', 'llama3.1', 'openai_compatible', 'http://localhost:11434/v1'), 'OpenAI-kompatible Konfiguration konnte nicht gespeichert werden.');
    $openAiConfig = load_api_config();
    test_assert($openAiConfig['provider'] === 'openai_compatible', 'OpenAI-kompatibler Provider wurde nicht gespeichert.');
    test_assert(api_key_is_configured($openAiConfig), 'OpenAI-kompatibler Endpunkt sollte ohne Token als konfiguriert gelten.');
    test_assert(model_gateway($openAiConfig)->providerId() === 'openai_compatible', 'OpenAI-kompatibler Provider wurde nicht ausgewählt.');
    test_assert(model_gateway($openAiConfig)->capabilities()['pdf_input'] === false, 'OpenAI-kompatibler Provider sollte PDF-Direktinput nicht melden.');

    $fakePdf = "%PDF-1.4\n1 0 obj\n<< /Type /Pages >>\nendobj\n2 0 obj\n<< /Type /Page >>\nendobj\n3 0 obj\n<< /Type /Page >>\nendobj\n";
    test_assert(pdf_estimate_page_count_from_content($fakePdf) === 2, 'PDF-Seitenschätzung sollte Page-Objekte zählen, aber Pages-Objekt ignorieren.');
    $splitPlan = pdf_split_plan('Orientierungsrahmen Gymnasiale Oberstufe.pdf', 808, 25);
    test_assert($splitPlan['part_count'] === 33, 'PDF-Splitplan sollte 808 Seiten in 33 Teile zu 25 Seiten teilen.');
    test_assert($splitPlan['parts'][0]['file'] === 'orientierungsrahmen-gymnasiale-oberstufe_teil-001_seiten-001-025.pdf', 'PDF-Splitplan erzeugt keinen stabilen ersten Dateinamen.');
    test_assert($splitPlan['parts'][32]['file'] === 'orientierungsrahmen-gymnasiale-oberstufe_teil-033_seiten-801-808.pdf', 'PDF-Splitplan erzeugt keinen stabilen letzten Dateinamen.');
    $splitAdvice = pdf_split_advice_for_file('Orientierungsrahmen.pdf', LARGE_PDF_BYTES + 1);
    test_assert($splitAdvice['large_pdf'] === true, 'Große PDF sollte Split-Hinweis auslösen.');
    test_assert($splitAdvice['example_first_file'] === 'orientierungsrahmen_teil-001_seiten-001-025.pdf', 'Split-Hinweis sollte einen logischen Beispieldateinamen liefern.');

    $uploadProbe = runtime_root('tmp/upload-probe.txt');
    file_put_contents($uploadProbe, 'gleicher Inhalt');
    $uploadHash = uploaded_file_sha256(['tmp_name' => $uploadProbe]);
    test_assert($uploadHash === hash('sha256', 'gleicher Inhalt'), 'Upload-Hash sollte SHA-256 der Datei liefern.');

    $duplicateProject = load_project_config();
    $duplicateProject['documents'] = [[
        'original_name' => 'probe.txt',
        'stored_name' => 'probe.txt',
        'sha256' => $uploadHash,
        'uploaded_at' => '2026-04-30T12:00:00+02:00',
    ]];
    $duplicateDocument = find_duplicate_document_by_sha256($duplicateProject, (string) $uploadHash);
    test_assert($duplicateDocument !== null, 'Duplikaterkennung sollte Dokumente anhand gespeicherter SHA-256 erkennen.');
    $duplicateResult = duplicate_upload_result('probe.txt', (string) $uploadHash, $duplicateDocument);
    test_assert(($duplicateResult['ok'] ?? false) && ($duplicateResult['skipped_duplicate'] ?? false), 'Duplikat-Result sollte als übersprungener Erfolg gelten.');

    file_put_contents(uploads_dir() . '/legacy.txt', 'legacy Inhalt');
    $legacyHash = hash('sha256', 'legacy Inhalt');
    $legacyProject = ['documents' => [['original_name' => 'legacy.txt', 'stored_name' => 'legacy.txt']]];
    test_assert(find_duplicate_document_by_sha256($legacyProject, $legacyHash) !== null, 'Duplikaterkennung sollte Legacy-Dokumente über gespeicherte Upload-Datei erkennen.');
    test_assert(delete_stored_upload_file('legacy.txt'), 'Gespeicherte Upload-Datei sollte sicher gelöscht werden können.');
    test_assert(!file_exists(uploads_dir() . '/legacy.txt'), 'Gelöschte Upload-Datei sollte nicht mehr existieren.');

    $jobId = admin_create_upload_job_id();
    admin_upload_job_start($jobId, 'test.txt', 123);
    admin_upload_job_update($jobId, 'model_processing', 45, 'KI verarbeitet die Datei.');
    $jobStatus = admin_upload_job_read($jobId);
    test_assert($jobStatus['job_id'] === $jobId, 'Upload-Jobstatus sollte die Job-ID speichern.');
    test_assert($jobStatus['percent'] === 45, 'Upload-Jobstatus sollte Fortschritt speichern.');
    test_assert($jobStatus['message'] === 'KI verarbeitet die Datei.', 'Upload-Jobstatus sollte Meldungen speichern.');

    $checks = admin_system_check_items(['apiConfig' => $openAiConfig]);
    test_assert($checks !== [], 'Systemcheck sollte Prüfpunkte liefern.');
    test_assert((bool) array_filter($checks, static fn(array $item): bool => ($item['label'] ?? '') === 'PHP-Erweiterung cURL'), 'Systemcheck sollte cURL prüfen.');
    test_assert((bool) array_filter($checks, static fn(array $item): bool => ($item['label'] ?? '') === 'Serverseitiges PDF-Splitting'), 'Systemcheck sollte PDF-Splitting transparent ausweisen.');

    file_put_contents(chunks_dir() . '/eins.md', build_chunk_markdown(['title' => 'Eins'], 'Inhalt eins'));
    file_put_contents(chunks_dir() . '/zwei.md', build_chunk_markdown(['title' => 'Zwei'], 'Inhalt zwei'));
    $_POST = ['files' => ['eins.md', 'zwei.md']];
    $state = [];
    admin_action_delete_chunks($state);
    test_assert($state['messageType'] === 'success', 'Mehrfachlöschung von Textabschnitten sollte erfolgreich sein.');
    test_assert(!file_exists(chunks_dir() . '/eins.md'), 'Erster Textabschnitt wurde nicht gelöscht.');
    test_assert(!file_exists(chunks_dir() . '/zwei.md'), 'Zweiter Textabschnitt wurde nicht gelöscht.');
    $_POST = [];

    echo "All tests passed.\n";
} finally {
    remove_tree($testRoot);
}
