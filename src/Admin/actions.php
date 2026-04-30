<?php
declare(strict_types=1);

function admin_handle_request(): array
{
    $apiConfig = load_api_config();
    $project = load_project_config();
    $flash = pull_flash();
    $state = [
        'message' => $flash['text'] ?? '',
        'messageType' => $flash['type'] ?? 'info',
        'uploadResults' => [],
    ];

    $csrfValid = $_SERVER['REQUEST_METHOD'] !== 'POST' || csrf_is_valid();
    if (!$csrfValid) {
        admin_set_message($state, 'error', 'Sicherheitsprüfung fehlgeschlagen. Bitte Formular erneut absenden.');
        if (admin_is_async_admin_request()) {
            admin_json_action_response($state, 403);
        }
    }

    if (!password_is_set()) {
        if ($csrfValid && admin_post_action() === 'setup_password') {
            admin_action_setup_password($state);
        }
        if (admin_is_async_admin_request() || admin_is_async_status_request()) {
            admin_set_message($state, 'error', 'Bitte zuerst das Admin-Passwort einrichten.');
            admin_json_action_response($state, 403);
        }
        return $state;
    }

    if (!is_admin_authenticated()) {
        if ($csrfValid && admin_post_action() === 'login') {
            admin_action_login($state);
        }
        if (admin_is_async_admin_request() || admin_is_async_status_request()) {
            admin_set_message($state, 'error', 'Die Admin-Sitzung ist abgelaufen. Bitte neu anmelden.');
            admin_json_action_response($state, 401);
        }
        return $state;
    }

    if (admin_is_async_status_request()) {
        admin_action_upload_job_status();
    }

    if (!$csrfValid || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $state;
    }

    $action = admin_post_action();
    switch ($action) {
        case 'logout':
            admin_action_logout();
            break;
        case 'save_apikey':
            admin_action_save_api_key($state, $apiConfig);
            break;
        case 'test_model_provider':
            admin_action_test_model_provider($state, $apiConfig);
            break;
        case 'save_project':
            admin_action_save_project($state, $project);
            break;
        case 'save_frontend_content':
            admin_action_save_frontend_content($state, $project);
            break;
        case 'upload_documents':
            if (admin_is_async_upload_request()) {
                admin_action_upload_documents_async($state, $apiConfig, $project);
                admin_json_action_response($state);
            }
            admin_action_upload_documents($state, $apiConfig, $project);
            break;
        case 'finalize_upload_queue':
            admin_action_finalize_upload_queue($state, $apiConfig);
            if (admin_is_async_admin_request()) {
                admin_json_action_response($state);
            }
            break;
        case 'regenerate_profile':
            admin_action_regenerate_profile($state, $apiConfig, $project);
            break;
        case 'delete_chunk':
            admin_action_delete_chunk($state);
            break;
        case 'delete_chunks':
            admin_action_delete_chunks($state);
            break;
        case 'reset_password':
            admin_action_reset_password($state);
            break;
    }

    return $state;
}

function admin_post_action(): string
{
    return $_SERVER['REQUEST_METHOD'] === 'POST' ? (string) ($_POST['action'] ?? '') : '';
}

function admin_is_async_admin_request(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST'
        && (
            (string) ($_POST['async_upload'] ?? '') === '1'
            || (string) ($_POST['async_action'] ?? '') === '1'
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'fetch'
        );
}

function admin_is_async_upload_request(): bool
{
    return admin_is_async_admin_request() && admin_post_action() === 'upload_documents';
}

function admin_is_async_status_request(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'GET'
        && (string) ($_GET['async_status'] ?? '') === 'upload_job';
}

function admin_json_action_response(array $state, int $status = 200): never
{
    json_response([
        'ok' => ($state['messageType'] ?? '') === 'success',
        'messageType' => (string) ($state['messageType'] ?? 'info'),
        'message' => (string) ($state['message'] ?? ''),
        'uploadResults' => is_array($state['uploadResults'] ?? null) ? $state['uploadResults'] : [],
        'chunkCount' => chunk_count(),
        'jobId' => (string) ($state['jobId'] ?? ''),
    ], $status);
}

function admin_action_upload_job_status(): never
{
    $jobId = admin_normalize_upload_job_id((string) ($_GET['job_id'] ?? ''));
    if ($jobId === null) {
        json_response(['ok' => false, 'message' => 'Ungültige Job-ID.'], 400);
    }

    $status = admin_upload_job_read($jobId);
    if ($status === []) {
        json_response(['ok' => false, 'message' => 'Jobstatus wurde nicht gefunden.'], 404);
    }

    $status['ok'] = true;
    json_response($status);
}

function admin_set_message(array &$state, string $type, string $text): void
{
    $state['messageType'] = $type;
    $state['message'] = $text;
}

function admin_action_setup_password(array &$state): void
{
    $pw1 = trim((string) ($_POST['pw1'] ?? ''));
    $pw2 = trim((string) ($_POST['pw2'] ?? ''));

    if (mb_strlen($pw1, 'UTF-8') < 8) {
        admin_set_message($state, 'error', 'Das Admin-Passwort muss mindestens 8 Zeichen lang sein.');
        return;
    }
    if ($pw1 !== $pw2) {
        admin_set_message($state, 'error', 'Die Passwörter stimmen nicht überein.');
        return;
    }

    file_put_contents(password_file(), password_hash($pw1, PASSWORD_DEFAULT));
    session_regenerate_id(true);
    $_SESSION['admin_ok'] = true;
    $nextStep = current_setup_step(load_api_config(), load_project_config());
    $message = match ($nextStep) {
        'done' => 'Admin-Passwort gespeichert. Die Einrichtung ist vollständig.',
        'profile' => 'Admin-Passwort gespeichert. Weiter mit dem Projektprofil.',
        'documents' => 'Admin-Passwort gespeichert. Weiter mit der Wissensbasis.',
        default => 'Admin-Passwort gespeichert. Weiter mit Schritt 2.',
    };
    admin_set_message($state, 'success', $message);
}

function admin_action_login(array &$state): void
{
    $hash = trim((string) @file_get_contents(password_file()));
    if ($hash !== '' && password_verify((string) ($_POST['pw'] ?? ''), $hash)) {
        session_regenerate_id(true);
        $_SESSION['admin_ok'] = true;
        redirect('admin.php');
    }

    admin_set_message($state, 'error', 'Falsches Passwort.');
}

function admin_action_logout(): never
{
    $_SESSION = [];
    session_destroy();
    redirect('admin.php');
}

function admin_action_save_api_key(array &$state, array $apiConfig): void
{
    $provider = normalize_model_provider((string) ($_POST['provider'] ?? ($apiConfig['provider'] ?? 'gemini')));
    $key = trim((string) ($_POST['apikey'] ?? ''));
    $model = trim((string) ($_POST['model'] ?? DEFAULT_MODEL_NAME));
    $existingKey = trim((string) ($apiConfig['api_key'] ?? ''));
    $existingProvider = normalize_model_provider((string) ($apiConfig['provider'] ?? 'gemini'));
    $submittedBaseUrl = normalize_whitespace((string) ($_POST['base_url'] ?? ''));
    $baseUrl = $submittedBaseUrl !== '' ? $submittedBaseUrl : default_base_url_for_provider($provider);

    if ($provider !== $existingProvider && $submittedBaseUrl === default_base_url_for_provider($existingProvider)) {
        $baseUrl = default_base_url_for_provider($provider);
    }

    if ($key === '' && $existingKey !== '' && $provider === $existingProvider) {
        $key = $existingKey;
    }

    if ($model === '') {
        admin_set_message($state, 'error', 'Bitte einen Modellnamen eintragen.');
        return;
    }

    if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
        admin_set_message($state, 'error', 'Bitte eine gültige Anbieter-URL eintragen.');
        return;
    }

    if ($provider === 'gemini' && strlen($key) < 10) {
        admin_set_message($state, 'error', 'Bitte den vollständigen Gemini-API-Schlüssel eintragen.');
        return;
    }

    if (!save_api_config($key, $model, $provider, $baseUrl)) {
        admin_set_message($state, 'error', 'API-Konfiguration konnte nicht gespeichert werden.');
        return;
    }

    if (trim((string) ($_POST['apikey'] ?? '')) === '') {
        $message = $provider === $existingProvider && $existingKey !== ''
            ? 'API-Konfiguration gespeichert. Der vorhandene API-Schlüssel bleibt unverändert.'
            : 'API-Konfiguration gespeichert.';
    } else {
        $message = 'API-Schlüssel oder Token gespeichert. Weiter mit dem Projektprofil.';
    }
    admin_set_message($state, 'success', $message);
}

function admin_action_test_model_provider(array &$state, array $apiConfig): void
{
    if (!api_key_is_configured($apiConfig)) {
        admin_set_message($state, 'error', 'Bitte zuerst einen KI-Anbieter vollständig konfigurieren.');
        return;
    }

    $gateway = model_gateway($apiConfig);
    try {
        $result = $gateway->testConnection();
    } catch (Throwable $exception) {
        admin_set_message($state, 'error', $gateway->providerLabel() . ' ist nicht erreichbar: ' . $exception->getMessage());
        return;
    }
    if ($result['ok'] ?? false) {
        $answer = trim((string) ($result['text'] ?? 'ok'));
        if ($answer === '') {
            $answer = 'ok';
        }
        admin_set_message($state, 'success', $gateway->providerLabel() . ' ist erreichbar. Testantwort: ' . mb_substr($answer, 0, 80, 'UTF-8'));
        return;
    }

    $message = trim((string) ($result['error'] ?? 'Unbekannter Fehler beim Verbindungstest.'));
    if (!empty($result['retryable'])) {
        $message .= ' Der Fehler kann vorübergehend sein; bitte später erneut testen.';
    }

    admin_set_message($state, 'error', $gateway->providerLabel() . ' ist nicht erreichbar: ' . $message);
}

function admin_action_save_project(array &$state, array $project): void
{
    $title = normalize_whitespace((string) ($_POST['title'] ?? ''));
    $topic = normalize_whitespace((string) ($_POST['topic'] ?? ''));
    $audience = normalize_whitespace((string) ($_POST['audience'] ?? ''));

    if ($title === '' || $topic === '') {
        admin_set_message($state, 'error', 'Titel und Themenfeld sind Pflichtfelder.');
        return;
    }

    $project['title'] = $title;
    $project['slug'] = slugify($title);
    $project['topic'] = $topic;
    $project['audience'] = $audience;
    $project['frontend']['welcome_heading'] = $title;
    if (trim((string) $project['subtitle']) === '') {
        $project['subtitle'] = 'Konfigurierbarer Beratungsassistent';
    }
    $project['setup']['profile_completed_at'] = now_iso();

    if (!save_project_config($project)) {
        admin_set_message($state, 'error', 'Projektprofil konnte nicht gespeichert werden.');
        return;
    }

    admin_set_message($state, 'success', 'Projektprofil gespeichert. Jetzt Dokumente hochladen.');
}

function admin_action_save_frontend_content(array &$state, array $project): void
{
    $frontend = frontend_content_from_form($_POST);
    $project['frontend'] = merge_project_config($project['frontend'] ?? [], $frontend);

    if (!save_project_config($project)) {
        admin_set_message($state, 'error', 'Schnellfragen und Vorlagen konnten nicht gespeichert werden.');
        return;
    }

    admin_set_message($state, 'success', 'Schnellfragen und Vorlagen gespeichert.');
}

function admin_action_upload_documents(array &$state, array $apiConfig, array $project): void
{
    if (!api_key_is_configured($apiConfig)) {
        admin_set_message($state, 'error', 'Bitte zuerst einen gültigen API-Schlüssel hinterlegen.');
        return;
    }
    if (!project_profile_is_configured($project)) {
        admin_set_message($state, 'error', 'Bitte zuerst Titel und Themenfeld speichern.');
        return;
    }

    $files = normalize_uploaded_files($_FILES['documents'] ?? []);
    $files = array_values(array_filter($files, static fn(array $file): bool => ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE));
    if ($files === []) {
        admin_set_message($state, 'error', 'Bitte mindestens eine Datei auswählen.');
        return;
    }

    $successCount = 0;
    $skippedCount = 0;
    foreach ($files as $file) {
        $result = admin_process_uploaded_document_safely($file, $project, $apiConfig);
        $state['uploadResults'][] = $result;
        if ($result['ok'] ?? false) {
            if (!empty($result['skipped_duplicate'])) {
                $skippedCount++;
            } else {
                $successCount++;
                $project['documents'][] = $result['document'];
            }
        }
    }

    if ($successCount <= 0 && $skippedCount <= 0) {
        admin_set_message($state, 'error', 'Keine Datei konnte verarbeitet werden.');
        return;
    }
    if ($successCount <= 0) {
        admin_set_message($state, 'success', $skippedCount . ' Datei(en) übersprungen, weil sie bereits vorhanden sind.');
        return;
    }

    $project['setup']['knowledge_completed_at'] = now_iso();
    save_project_config($project);
    $regen = admin_regenerate_project_profile_safely(load_project_config(), $apiConfig);
    if ($regen['ok'] ?? false) {
        $message = $successCount . ' Datei(en) verarbeitet';
        if ($skippedCount > 0) {
            $message .= ', ' . $skippedCount . ' bereits vorhandene übersprungen';
        }
        admin_set_message($state, 'success', $message . '. Frontend-Vorlagen und Beispielfragen wurden neu erzeugt.');
        return;
    }

    admin_set_message($state, 'error', $successCount . ' Datei(en) verarbeitet, aber die automatische Frontend-Konfiguration konnte nicht aktualisiert werden.');
}

function admin_action_upload_documents_async(array &$state, array $apiConfig, array $project): void
{
    $jobId = admin_normalize_upload_job_id((string) ($_POST['job_id'] ?? '')) ?? admin_create_upload_job_id();
    admin_upload_job_cleanup();

    if (!api_key_is_configured($apiConfig)) {
        admin_set_message($state, 'error', 'Bitte zuerst einen gültigen API-Schlüssel hinterlegen.');
        admin_upload_job_update($jobId, 'error', 100, $state['message'], ['status' => 'error']);
        return;
    }
    if (!project_profile_is_configured($project)) {
        admin_set_message($state, 'error', 'Bitte zuerst Titel und Themenfeld speichern.');
        admin_upload_job_update($jobId, 'error', 100, $state['message'], ['status' => 'error']);
        return;
    }

    $files = normalize_uploaded_files($_FILES['documents'] ?? []);
    $files = array_values(array_filter($files, static fn(array $file): bool => ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE));
    if ($files === []) {
        admin_set_message($state, 'error', 'Bitte mindestens eine Datei auswählen.');
        admin_upload_job_update($jobId, 'error', 100, $state['message'], ['status' => 'error']);
        return;
    }

    $successCount = 0;
    $skippedCount = 0;
    foreach ($files as $file) {
        admin_upload_job_start($jobId, (string) ($file['name'] ?? 'Datei'), (int) ($file['size'] ?? 0));
        $progress = static function (string $stage, int $percent, string $message) use ($jobId): void {
            admin_upload_job_update($jobId, $stage, $percent, $message);
        };
        $result = admin_process_uploaded_document_safely($file, $project, $apiConfig, $progress);
        $state['uploadResults'][] = $result;
        admin_upload_job_finish($jobId, $result);
        if ($result['ok'] ?? false) {
            if (!empty($result['skipped_duplicate'])) {
                $skippedCount++;
            } else {
                $successCount++;
                $project['documents'][] = $result['document'];
            }
        }
    }

    if ($successCount <= 0 && $skippedCount <= 0) {
        admin_set_message($state, 'error', 'Datei konnte nicht verarbeitet werden.');
        return;
    }

    if ($successCount > 0) {
        $project['setup']['knowledge_completed_at'] = now_iso();
        if (!save_project_config($project)) {
            admin_set_message($state, 'error', 'Datei verarbeitet, aber die Projektkonfiguration konnte nicht gespeichert werden.');
            return;
        }
    }

    $fileCount = count($files);
    if ($successCount <= 0 && $skippedCount > 0) {
        $message = $fileCount === 1
            ? 'Datei übersprungen, weil sie bereits vorhanden ist.'
            : $skippedCount . ' Datei(en) übersprungen, weil sie bereits vorhanden sind.';
    } else {
        $message = $fileCount === 1
            ? 'Datei verarbeitet. Die Warteschlange fährt mit der nächsten Datei fort.'
            : $successCount . ' Datei(en) verarbeitet. Die Warteschlange fährt fort.';
        if ($skippedCount > 0) {
            $message .= ' ' . $skippedCount . ' bereits vorhandene Datei(en) wurden übersprungen.';
        }
    }
    admin_set_message($state, 'success', $message);
    $state['jobId'] = $jobId;
}

function admin_action_finalize_upload_queue(array &$state, array $apiConfig): void
{
    if (!api_key_is_configured($apiConfig)) {
        admin_set_message($state, 'error', 'Bitte zuerst einen gültigen API-Schlüssel hinterlegen.');
        return;
    }
    if (!knowledge_base_is_configured()) {
        admin_set_message($state, 'error', 'Es wurden noch keine Textabschnitte erzeugt.');
        return;
    }

    $regen = admin_regenerate_project_profile_safely(load_project_config(), $apiConfig);
    if ($regen['ok'] ?? false) {
        admin_set_message($state, 'success', 'Warteschlange abgeschlossen. Frontend-Vorlagen und Beispielfragen wurden neu erzeugt.');
        return;
    }

    admin_set_message($state, 'error', $regen['error'] ?? 'Warteschlange abgeschlossen, aber die Frontend-Konfiguration konnte nicht aktualisiert werden.');
}

function admin_action_regenerate_profile(array &$state, array $apiConfig, array $project): void
{
    $regen = admin_regenerate_project_profile_safely($project, $apiConfig);
    if ($regen['ok'] ?? false) {
        admin_set_message($state, 'success', 'Frontend-Konfiguration aus der Wissensbasis neu erzeugt.');
        return;
    }

    admin_set_message($state, 'error', $regen['error'] ?? 'Profil konnte nicht regeneriert werden.');
}

function admin_process_uploaded_document_safely(array $file, array $project, array $apiConfig, ?callable $onProgress = null): array
{
    try {
        return process_uploaded_document($file, $project, $apiConfig, $onProgress);
    } catch (Throwable $exception) {
        return [
            'ok' => false,
            'error' => 'Technischer Fehler bei der Datei-Verarbeitung: ' . $exception->getMessage(),
        ];
    }
}

function admin_regenerate_project_profile_safely(array $project, array $apiConfig): array
{
    try {
        return regenerate_project_profile($project, $apiConfig);
    } catch (Throwable $exception) {
        return [
            'ok' => false,
            'error' => 'Technischer Fehler bei der Profil-Aktualisierung: ' . $exception->getMessage(),
        ];
    }
}

function admin_action_delete_chunk(array &$state): void
{
    $file = admin_normalize_chunk_filename((string) ($_POST['file'] ?? ''));
    if ($file === null) {
        admin_set_message($state, 'error', 'Ungültiger Dateiname.');
        return;
    }

    admin_delete_chunk_files($state, [$file], 'Textabschnitt gelöscht.');
}

function admin_action_delete_chunks(array &$state): void
{
    $submittedFiles = $_POST['files'] ?? [];
    if (!is_array($submittedFiles)) {
        admin_set_message($state, 'error', 'Bitte mindestens einen Textabschnitt auswählen.');
        return;
    }

    $files = [];
    foreach ($submittedFiles as $submittedFile) {
        $file = admin_normalize_chunk_filename((string) $submittedFile);
        if ($file === null) {
            admin_set_message($state, 'error', 'Mindestens ein ausgewählter Dateiname ist ungültig.');
            return;
        }
        $files[] = $file;
    }

    $files = array_values(array_unique($files));
    if ($files === []) {
        admin_set_message($state, 'error', 'Bitte mindestens einen Textabschnitt auswählen.');
        return;
    }

    admin_delete_chunk_files($state, $files, count($files) . ' Textabschnitt(e) gelöscht.');
}

function admin_normalize_chunk_filename(string $file): ?string
{
    $file = trim($file);
    if ($file === '' || $file !== basename($file)) {
        return null;
    }

    return preg_match('/^[A-Za-z0-9._-]+\.md$/', $file) ? $file : null;
}

function admin_delete_chunk_files(array &$state, array $files, string $successMessage): void
{
    $deleted = 0;
    $failed = [];
    foreach ($files as $file) {
        $path = chunks_dir() . '/' . $file;
        if (!file_exists($path) || !unlink($path)) {
            $failed[] = $file;
            continue;
        }
        $deleted++;
    }

    $project = load_project_config();
    if (!knowledge_base_is_configured()) {
        $project['setup']['knowledge_completed_at'] = null;
        save_project_config($project);
    }

    if ($failed !== []) {
        admin_set_message($state, 'error', $deleted . ' Textabschnitt(e) gelöscht, ' . count($failed) . ' konnten nicht gelöscht werden.');
        return;
    }

    admin_set_message($state, 'success', $successMessage);
}

function admin_action_reset_password(array &$state): void
{
    $pw1 = trim((string) ($_POST['pw1'] ?? ''));
    $pw2 = trim((string) ($_POST['pw2'] ?? ''));
    if (mb_strlen($pw1, 'UTF-8') < 8) {
        admin_set_message($state, 'error', 'Das neue Passwort muss mindestens 8 Zeichen lang sein.');
        return;
    }
    if ($pw1 !== $pw2) {
        admin_set_message($state, 'error', 'Die Passwörter stimmen nicht überein.');
        return;
    }

    file_put_contents(password_file(), password_hash($pw1, PASSWORD_DEFAULT));
    $_SESSION = [];
    session_regenerate_id(true);
    set_flash('success', 'Passwort geändert. Bitte neu anmelden.');
    redirect('admin.php');
}
