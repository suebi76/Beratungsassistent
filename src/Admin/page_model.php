<?php
declare(strict_types=1);

function admin_build_page_model(array $requestState): array
{
    $apiConfig = load_api_config();
    $project = load_project_config();
    $setupStep = current_setup_step($apiConfig, $project);
    $chunks = get_chunks();
    $publicConfig = public_project_config($project);

    return [
        'apiConfig' => $apiConfig,
        'project' => $project,
        'message' => (string) ($requestState['message'] ?? ''),
        'messageType' => (string) ($requestState['messageType'] ?? 'info'),
        'uploadResults' => is_array($requestState['uploadResults'] ?? null) ? $requestState['uploadResults'] : [],
        'setupStep' => $setupStep,
        'chunks' => $chunks,
        'publicConfig' => $publicConfig,
        'wizardActive' => is_admin_authenticated() && $setupStep !== 'done',
        'apiKeyConfigured' => api_key_is_configured($apiConfig),
        'dataRootStatus' => runtime_uses_external_data_root()
            ? 'Externes Datenverzeichnis aktiv.'
            : 'Standard-Datenverzeichnis im Projekt. Fuer produktive Installationen BERATUNGSASSISTENT_DATA_DIR ausserhalb des Webroots setzen.',
        'steps' => [
            'password' => '1. Passwort',
            'api' => '2. API',
            'profile' => '3. Profil',
            'documents' => '4. Dateien',
        ],
    ];
}

