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
    }

    if (!password_is_set()) {
        if ($csrfValid && admin_post_action() === 'setup_password') {
            admin_action_setup_password($state);
        }
        return $state;
    }

    if (!is_admin_authenticated()) {
        if ($csrfValid && admin_post_action() === 'login') {
            admin_action_login($state);
        }
        return $state;
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
        case 'upload_documents':
            admin_action_upload_documents($state, $apiConfig, $project);
            break;
        case 'regenerate_profile':
            admin_action_regenerate_profile($state, $apiConfig, $project);
            break;
        case 'delete_chunk':
            admin_action_delete_chunk($state);
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
    admin_set_message($state, 'success', 'Admin-Passwort gespeichert. Weiter mit Schritt 2.');
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
    $result = $gateway->testConnection();
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
    foreach ($files as $file) {
        $result = process_uploaded_document($file, $project, $apiConfig);
        $state['uploadResults'][] = $result;
        if ($result['ok'] ?? false) {
            $successCount++;
            $project['documents'][] = $result['document'];
        }
    }

    if ($successCount <= 0) {
        admin_set_message($state, 'error', 'Keine Datei konnte verarbeitet werden.');
        return;
    }

    $project['setup']['knowledge_completed_at'] = now_iso();
    save_project_config($project);
    $regen = regenerate_project_profile(load_project_config(), $apiConfig);
    if ($regen['ok'] ?? false) {
        admin_set_message($state, 'success', $successCount . ' Datei(en) verarbeitet. Frontend-Vorlagen und Beispielfragen wurden neu erzeugt.');
        return;
    }

    admin_set_message($state, 'error', $successCount . ' Datei(en) verarbeitet, aber die automatische Frontend-Konfiguration konnte nicht aktualisiert werden.');
}

function admin_action_regenerate_profile(array &$state, array $apiConfig, array $project): void
{
    $regen = regenerate_project_profile($project, $apiConfig);
    if ($regen['ok'] ?? false) {
        admin_set_message($state, 'success', 'Frontend-Konfiguration aus der Wissensbasis neu erzeugt.');
        return;
    }

    admin_set_message($state, 'error', $regen['error'] ?? 'Profil konnte nicht regeneriert werden.');
}

function admin_action_delete_chunk(array &$state): void
{
    $file = basename((string) ($_POST['file'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9._-]+\.md$/', $file)) {
        admin_set_message($state, 'error', 'Ungültiger Dateiname.');
        return;
    }

    $path = chunks_dir() . '/' . $file;
    if (!file_exists($path) || !unlink($path)) {
        admin_set_message($state, 'error', 'Chunk konnte nicht gelöscht werden.');
        return;
    }

    $project = load_project_config();
    if (!knowledge_base_is_configured()) {
        $project['setup']['knowledge_completed_at'] = null;
        save_project_config($project);
    }
    admin_set_message($state, 'success', 'Chunk gelöscht.');
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
