<?php
declare(strict_types=1);

function admin_render_wizard(array $model): void
{
    ?>
    <div class="card">
        <h2>Ersteinrichtung</h2>
        <p class="muted">Der Assistent wird geführt in einzelnen Schritten eingerichtet.</p>
        <div class="stepper">
            <?php foreach ($model['steps'] as $key => $label): ?>
                <span class="<?= e(admin_step_class($key, $model['setupStep'], $model['steps'])) ?>"><?= e($label) ?></span>
            <?php endforeach; ?>
        </div>

        <?php
        if ($model['setupStep'] !== 'api' && $model['setupStep'] !== 'password' && (bool) $model['apiKeyConfigured']) {
            admin_render_model_test_form($model, 'max-width:620px; margin-bottom:18px');
        }

        if ($model['setupStep'] === 'api') {
            admin_render_wizard_api_form($model['apiConfig']);
        } elseif ($model['setupStep'] === 'profile') {
            admin_render_wizard_profile_form($model['project']);
        } elseif ($model['setupStep'] === 'documents') {
            admin_render_wizard_documents_form();
        }
        ?>
    </div>
    <?php
}

function admin_render_wizard_api_form(array $apiConfig): void
{
    $provider = normalize_model_provider((string) ($apiConfig['provider'] ?? 'gemini'));
    ?>
    <form method="post" class="stack" style="max-width:620px" data-working-label="API-Konfiguration wird gespeichert ...">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_apikey">
        <div>
            <label>KI-Anbieter</label>
            <select name="provider">
                <?php foreach (allowed_model_providers() as $id => $label): ?>
                    <option value="<?= e((string) $id) ?>" <?= $provider === $id ? 'selected' : '' ?>><?= e((string) $label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Base URL</label>
            <input type="url" name="base_url" value="<?= e((string) ($apiConfig['base_url'] ?? default_base_url_for_provider($provider))) ?>">
        </div>
        <div>
            <label>API-Schlüssel oder Token</label>
            <input type="password" name="apikey" placeholder="AIza... oder Token des Modellgateways" autocomplete="off">
        </div>
        <div>
            <label>Modell</label>
            <input type="text" name="model" value="<?= e($apiConfig['model'] ?? DEFAULT_MODEL_NAME) ?>">
        </div>
        <div class="actions">
            <button class="btn btn-primary" type="submit">API-Schlüssel speichern</button>
        </div>
    </form>
    <?php
}

function admin_render_wizard_profile_form(array $project): void
{
    ?>
    <form method="post" class="stack" style="max-width:720px" data-working-label="Profil wird gespeichert ...">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_project">
        <div>
            <label>Titel des Assistenten</label>
            <input type="text" name="title" required value="<?= e($project['title']) ?>" placeholder="z. B. Beratungsassistent Fortbildung">
        </div>
        <div>
            <label>Themenfeld</label>
            <textarea name="topic" required placeholder="Beschreiben Sie das fachliche Gebiet, für das der Assistent eingesetzt wird."><?= e((string) $project['topic']) ?></textarea>
        </div>
        <div>
            <label>Zielgruppe</label>
            <input type="text" name="audience" value="<?= e((string) $project['audience']) ?>" placeholder="z. B. Schulleitungen, Fachberater, Verwaltung">
        </div>
        <div class="actions">
            <button class="btn btn-primary" type="submit">Profil speichern</button>
        </div>
    </form>
    <?php
}

function admin_render_wizard_documents_form(): void
{
    ?>
    <form method="post" action="admin.php" enctype="multipart/form-data" class="stack upload-form" style="max-width:760px" data-upload-queue data-working-label="Dateien werden verarbeitet ...">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload_documents">
        <div class="dropzone upload-dropzone stack" data-upload-dropzone>
            <div class="upload-dropzone-copy">
                <h3>Dateien für die Wissensbasis</h3>
                <p class="muted">Laden Sie eine oder mehrere Dateien hoch oder ziehen Sie sie direkt in diesen Bereich. Daraus werden Textabschnitte, Vorlagen und Beispielaufgaben erzeugt.</p>
            </div>
            <div class="actions">
                <label class="btn btn-secondary" for="wizard-documents">Dateien auswählen</label>
                <span class="muted">PDF, TXT oder Markdown</span>
            </div>
            <input class="visually-hidden" id="wizard-documents" type="file" name="documents[]" data-upload-input multiple accept=".pdf,.txt,.md,.markdown">
        </div>
        <div class="upload-queue" data-upload-panel hidden>
            <div class="upload-queue-head">
                <div>
                    <strong>Warteschlange</strong><br>
                    <span class="muted" data-upload-summary>Keine Dateien ausgewählt.</span>
                </div>
                <div class="upload-progress" aria-hidden="true">
                    <span data-upload-progress></span>
                </div>
            </div>
            <div class="upload-list" data-upload-list></div>
        </div>
        <div class="actions">
            <button class="btn btn-primary" type="submit" data-upload-submit>Warteschlange starten</button>
        </div>
    </form>
    <?php
}
