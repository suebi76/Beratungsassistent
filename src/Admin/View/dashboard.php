<?php
declare(strict_types=1);

function admin_render_dashboard(array $model): void
{
    ?>
    <div class="grid two">
        <div class="grid">
            <?php admin_render_project_profile_card($model['project']); ?>
            <?php admin_render_knowledge_upload_card($model['uploadResults']); ?>
        </div>

        <div class="grid">
            <?php admin_render_live_config_card($model['publicConfig'], $model['chunks']); ?>
            <?php admin_render_api_security_card($model); ?>
        </div>
    </div>

    <?php admin_render_chunks_table($model['chunks']); ?>
    <?php
}

function admin_render_project_profile_card(array $project): void
{
    ?>
    <div class="card">
        <h2>Projektprofil</h2>
        <p class="muted">Diese Angaben steuern Titel, Themenrahmen, Begrüßung und die serverseitige Systemanweisung.</p>
        <form method="post" class="stack">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_project">
            <div>
                <label>Titel</label>
                <input type="text" name="title" required value="<?= e((string) $project['title']) ?>">
            </div>
            <div>
                <label>Themenfeld</label>
                <textarea name="topic" required><?= e((string) $project['topic']) ?></textarea>
            </div>
            <div>
                <label>Zielgruppe</label>
                <input type="text" name="audience" value="<?= e((string) $project['audience']) ?>">
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Projektprofil aktualisieren</button>
                <button class="btn btn-secondary" type="submit" formaction="project.php" formmethod="get">Öffentliche Konfiguration ansehen</button>
            </div>
        </form>
    </div>
    <?php
}

function admin_render_knowledge_upload_card(array $uploadResults): void
{
    ?>
    <div class="card">
        <h2>Wissensbasis erweitern</h2>
        <p class="muted">Neue Dateien werden in Textabschnitte umgewandelt. Danach werden Schnellfragen, Aufgabenbeispiele und Vorlagen automatisch aus der Wissensbasis neu erzeugt.</p>
        <form method="post" enctype="multipart/form-data" class="stack">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="upload_documents">
            <div>
                <label>Dateien</label>
                <input type="file" name="documents[]" multiple accept=".pdf,.txt,.md,.markdown">
            </div>
            <div class="actions">
                <button class="btn btn-primary" type="submit">Dateien hochladen und verarbeiten</button>
                <button class="btn btn-secondary" type="submit" name="action" value="regenerate_profile">Beispielinhalte neu erzeugen</button>
            </div>
        </form>

        <?php if ($uploadResults !== []): ?>
            <div class="result-list">
                <?php foreach ($uploadResults as $result): ?>
                    <div class="result-item">
                        <?php if ($result['ok'] ?? false): ?>
                            <strong><?= e($result['document']['original_name'] ?? '') ?></strong><br>
                            <span class="muted"><?= count($result['saved_chunks'] ?? []) ?> Textabschnitte erzeugt</span>
                        <?php else: ?>
                            <strong>Fehler</strong><br>
                            <span class="muted"><?= e($result['error'] ?? '') ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function admin_render_live_config_card(array $publicConfig, array $chunks): void
{
    ?>
    <div class="card">
        <h2>Live-Konfiguration</h2>
        <p class="muted">Was die normale Nutzeransicht aktuell verwendet.</p>
        <div class="stack">
            <div><strong>Titel</strong><br><span class="muted"><?= e($publicConfig['title']) ?></span></div>
            <div><strong>Untertitel</strong><br><span class="muted"><?= e($publicConfig['subtitle']) ?></span></div>
            <div><strong>Schnellfragen</strong><br><span class="muted"><?= e((string) count($publicConfig['frontend']['quick_questions'])) ?> Einträge</span></div>
            <div><strong>Vorlagen</strong><br><span class="muted"><?= e((string) count($publicConfig['frontend']['templates'])) ?> Sektionen</span></div>
            <div><strong>Textabschnitte</strong><br><span class="muted"><?= e((string) count($chunks)) ?> Dateien im RAG-Verzeichnis</span></div>
        </div>
    </div>
    <?php
}

function admin_render_api_security_card(array $model): void
{
    $apiConfig = $model['apiConfig'];
    $apiKeyConfigured = (bool) $model['apiKeyConfigured'];
    $modelProvider = $model['modelProvider'];
    ?>
    <div class="card">
        <h2>API und Sicherheit</h2>
        <form method="post" class="stack">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_apikey">
            <div>
                <strong>KI-Anbieter</strong><br>
                <span class="muted"><?= e((string) $modelProvider['label']) ?> <?= $apiKeyConfigured ? 'ist konfiguriert.' : 'ist noch nicht vollständig konfiguriert.' ?></span>
            </div>
            <div>
                <label>Anbieter</label>
                <select name="provider">
                    <?php foreach ($modelProvider['allowed'] as $id => $label): ?>
                        <option value="<?= e((string) $id) ?>" <?= ($apiConfig['provider'] ?? 'gemini') === $id ? 'selected' : '' ?>><?= e((string) $label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Base URL</label>
                <input type="url" name="base_url" value="<?= e((string) ($apiConfig['base_url'] ?? default_base_url_for_provider((string) ($apiConfig['provider'] ?? 'gemini')))) ?>">
                <p class="muted" style="margin:6px 0 0">Für lokale KI zum Beispiel <code>http://localhost:11434/v1</code> oder ein internes Modellgateway.</p>
            </div>
            <div>
                <label>API-Schlüssel oder Token</label>
                <input type="password" name="apikey" value="" placeholder="<?= e($apiKeyConfigured ? 'Leer lassen, um den vorhandenen Schlüssel zu behalten' : 'AIza...') ?>" autocomplete="off">
                <p class="muted" style="margin:6px 0 0">Der gespeicherte Schlüssel wird nicht mehr im HTML ausgegeben. Lokale Endpunkte können ohne Token betrieben werden.</p>
            </div>
            <div>
                <label>Modell</label>
                <input type="text" name="model" value="<?= e((string) ($apiConfig['model'] ?? DEFAULT_MODEL_NAME)) ?>">
            </div>
            <div>
                <strong>Datenverzeichnis</strong><br>
                <span class="muted"><?= e($model['dataRootStatus']) ?></span>
            </div>
            <div>
                <strong>Fähigkeiten</strong><br>
                <span class="muted">
                    Streaming: <?= !empty($modelProvider['capabilities']['streaming']) ? 'ja' : 'nein' ?> ·
                    PDF-Direktverarbeitung: <?= !empty($modelProvider['capabilities']['pdf_input']) ? 'ja' : 'nein' ?> ·
                    JSON-Modus: <?= !empty($modelProvider['capabilities']['json_mode']) ? 'ja' : 'nein' ?>
                </span>
            </div>
            <button class="btn btn-secondary" type="submit">API-Konfiguration speichern</button>
        </form>

        <details>
            <summary>Admin-Passwort ändern</summary>
            <form method="post" class="stack" style="margin-top:12px">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="reset_password">
                <div>
                    <label>Neues Passwort</label>
                    <input type="password" name="pw1" autocomplete="new-password">
                </div>
                <div>
                    <label>Passwort wiederholen</label>
                    <input type="password" name="pw2" autocomplete="new-password">
                </div>
                <button class="btn btn-secondary" type="submit">Passwort aktualisieren</button>
            </form>
        </details>
    </div>
    <?php
}
