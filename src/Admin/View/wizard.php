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
    ?>
    <form method="post" class="stack" style="max-width:620px">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_apikey">
        <div>
            <label>Gemini-API-Schlüssel</label>
            <input type="password" name="apikey" required placeholder="AIza..." autocomplete="off">
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
    <form method="post" class="stack" style="max-width:720px">
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
    <form method="post" enctype="multipart/form-data" class="stack" style="max-width:760px">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="upload_documents">
        <div class="dropzone stack">
            <div>
                <h3>Dateien für die Wissensbasis</h3>
                <p class="muted">Laden Sie eine oder mehrere Dateien hoch. In dieser ersten Version werden PDF, TXT und Markdown unterstützt. Daraus werden Textabschnitte, Vorlagen und Beispielaufgaben erzeugt.</p>
            </div>
            <div>
                <label>Dateien</label>
                <input type="file" name="documents[]" multiple accept=".pdf,.txt,.md,.markdown">
            </div>
        </div>
        <div class="actions">
            <button class="btn btn-primary" type="submit">Dateien verarbeiten</button>
        </div>
    </form>
    <?php
}
