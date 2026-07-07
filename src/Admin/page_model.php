<?php
declare(strict_types=1);

function admin_build_page_model(array $requestState): array
{
    $apiConfig = load_api_config();
    $project = load_project_config();
    $setupStep = current_setup_step($apiConfig, $project);
    $chunks = get_chunks();
    $publicConfig = public_project_config($project);
    $gateway = model_gateway($apiConfig);
    $sections = admin_sections();
    $activeSection = admin_normalize_section((string) ($_GET['section'] ?? 'overview'));

    return [
        'apiConfig' => $apiConfig,
        'modelProvider' => [
            'id' => $gateway->providerId(),
            'label' => $gateway->providerLabel(),
            'capabilities' => $gateway->capabilities(),
            'allowed' => allowed_model_providers(),
        ],
        'project' => $project,
        'message' => (string) ($requestState['message'] ?? ''),
        'messageType' => (string) ($requestState['messageType'] ?? 'info'),
        'uploadResults' => is_array($requestState['uploadResults'] ?? null) ? $requestState['uploadResults'] : [],
        'qualityTest' => is_array($requestState['qualityTest'] ?? null) ? $requestState['qualityTest'] : admin_empty_quality_test_result(),
        'setupStep' => $setupStep,
        'chunks' => $chunks,
        'sections' => $sections,
        'activeSection' => $activeSection,
        'publicConfig' => $publicConfig,
        'wizardActive' => is_admin_authenticated() && $setupStep !== 'done',
        'apiKeyConfigured' => api_key_is_configured($apiConfig),
        'dataRootStatus' => runtime_uses_external_data_root()
            ? 'Externes Datenverzeichnis aktiv.'
            : 'Standard-Datenverzeichnis im Projekt. Für produktive Installationen BERATUNGSASSISTENT_DATA_DIR außerhalb des Webroots setzen.',
        'steps' => [
            'password' => '1. Passwort',
            'api' => '2. API',
            'profile' => '3. Profil',
            'documents' => '4. Dateien',
        ],
    ];
}

function admin_sections(): array
{
    return [
        'overview' => [
            'label' => 'Überblick',
            'description' => 'Status, nächste sinnvolle Schritte und die öffentliche Live-Konfiguration auf einen Blick.',
            'hint' => 'Status',
        ],
        'project' => [
            'label' => 'Projekt',
            'description' => 'Grunddaten des Assistenten: Titel, Themenfeld, Zielgruppe und fachlicher Rahmen.',
            'hint' => 'Profil',
        ],
        'contents' => [
            'label' => 'Inhalte',
            'description' => 'Schnellfragen, Aufgabenbeispiele und Vorlagen für die Nutzeroberfläche kuratieren.',
            'hint' => 'Frontend',
        ],
        'knowledge' => [
            'label' => 'Wissensbasis',
            'description' => 'Fachdokumente hochladen und daraus Textabschnitte für die Antwortsuche erzeugen.',
            'hint' => 'Upload',
        ],
        'chunks' => [
            'label' => 'Textabschnitte',
            'description' => 'Erzeugte Wissensbasis-Abschnitte prüfen, einzeln oder gesammelt bereinigen.',
            'hint' => 'Abschnitte',
        ],
        'quality' => [
            'label' => 'Qualitätstest',
            'description' => 'Testfragen ausführen, gefundene Textabschnitte mit Score prüfen und KI-Antworten fachlich bewerten.',
            'hint' => 'Prüfung',
        ],
        'provider' => [
            'label' => 'KI-Anbieter',
            'description' => 'Modellanbieter, Base-URL, Modellname, Token und Verbindungstest verwalten.',
            'hint' => 'Modelle',
        ],
        'security' => [
            'label' => 'Datenschutz & Sicherheit',
            'description' => 'Schlüsselstatus, Datenverzeichnis und Admin-Passwort kontrollieren.',
            'hint' => 'Schutz',
        ],
        'operations' => [
            'label' => 'Betrieb',
            'description' => 'Technischer Laufzeitstatus und Betriebsnotizen für Installation und Wartung.',
            'hint' => 'System',
        ],
    ];
}

function admin_normalize_section(string $section): string
{
    return array_key_exists($section, admin_sections()) ? $section : 'overview';
}
