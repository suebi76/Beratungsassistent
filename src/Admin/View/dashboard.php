<?php
declare(strict_types=1);

function admin_render_dashboard(array $model): void
{
    ?>
    <div class="admin-workspace">
        <aside class="admin-sidebar">
            <?php admin_render_section_nav($model); ?>
        </aside>
        <main class="admin-content" id="admin-main">
            <?php admin_render_mobile_section_nav($model); ?>
            <?php admin_render_active_section($model); ?>
        </main>
    </div>
    <?php
}

function admin_render_active_section(array $model): void
{
    $activeSection = (string) ($model['activeSection'] ?? 'overview');
    $section = $model['sections'][$activeSection] ?? $model['sections']['overview'];

    admin_render_section_header((string) $section['label'], (string) $section['description']);

    switch ($activeSection) {
        case 'project':
            admin_render_project_profile_card($model['project']);
            break;
        case 'contents':
            admin_render_content_curation_card($model['project']);
            break;
        case 'knowledge':
            ?>
            <div class="grid two">
                <?php admin_render_knowledge_upload_card($model['uploadResults']); ?>
                <?php admin_render_document_overview_card($model['project'], $model['chunks']); ?>
            </div>
            <?php
            break;
        case 'chunks':
            admin_render_chunks_table($model['chunks']);
            break;
        case 'quality':
            admin_render_quality_section($model);
            break;
        case 'provider':
            admin_render_provider_card($model);
            break;
        case 'security':
            admin_render_security_status_card($model);
            break;
        case 'operations':
            admin_render_operations_card($model);
            break;
        case 'overview':
        default:
            admin_render_overview_section($model);
            break;
    }
}

function admin_render_overview_section(array $model): void
{
    $project = $model['project'];
    $chunks = $model['chunks'];
    $documentCount = admin_document_count($project);
    $frontend = is_array($model['publicConfig']['frontend'] ?? null) ? $model['publicConfig']['frontend'] : [];
    ?>
    <div class="metric-grid">
        <div class="metric-card">
            <span class="metric-label">Projektprofil</span>
            <strong><?= project_profile_is_configured($project) ? 'vollständig' : 'offen' ?></strong>
            <span class="muted"><?= e((string) ($project['title'] ?: 'Noch kein Titel')) ?></span>
        </div>
        <div class="metric-card">
            <span class="metric-label">Wissensbasis</span>
            <strong><?= e((string) count($chunks)) ?></strong>
            <span class="muted">Textabschnitte aus <?= e((string) $documentCount) ?> Datei(en)</span>
        </div>
        <div class="metric-card">
            <span class="metric-label">KI-Anbieter</span>
            <strong><?= e((string) $model['modelProvider']['label']) ?></strong>
            <span class="muted"><?= $model['apiKeyConfigured'] ? 'konfiguriert' : 'nicht vollständig konfiguriert' ?></span>
        </div>
        <div class="metric-card">
            <span class="metric-label">Frontend-Inhalte</span>
            <strong><?= e((string) count($frontend['quick_questions'] ?? [])) ?></strong>
            <span class="muted">Schnellfragen, <?= e((string) count($frontend['templates'] ?? [])) ?> Vorlagen-Sektionen</span>
        </div>
    </div>

    <div class="grid two">
        <?php admin_render_next_steps_card($model); ?>
        <?php admin_render_live_config_card($model['publicConfig'], $chunks); ?>
    </div>
    <?php
}

function admin_render_next_steps_card(array $model): void
{
    $project = $model['project'];
    $chunks = $model['chunks'];
    $steps = [];
    if (!$model['apiKeyConfigured']) {
        $steps[] = ['KI-Anbieter konfigurieren', 'Ohne erreichbaren Anbieter können Uploads und Antworttests nicht zuverlässig laufen.', 'provider'];
    }
    if (!project_profile_is_configured($project)) {
        $steps[] = ['Projektprofil vervollständigen', 'Titel und Themenfeld steuern die fachliche Rolle des Assistenten.', 'project'];
    }
    if ($chunks === []) {
        $steps[] = ['Wissensbasis füllen', 'Laden Sie die ersten Dokumente hoch, damit Antworten belegbar werden.', 'knowledge'];
    }
    if ($steps === []) {
        $steps[] = ['Inhalte prüfen', 'Schnellfragen, Aufgabenbeispiele und Vorlagen fachlich bereinigen.', 'contents'];
        $steps[] = ['Qualitätstest vorbereiten', 'Prüffragen sammeln, damit Antworten später reproduzierbar getestet werden können.', 'quality'];
    }
    ?>
    <div class="card">
        <h2>Nächste Schritte</h2>
        <p class="muted">Die Liste ist bewusst kurz gehalten, damit der Admin-Bereich nicht überlädt.</p>
        <div class="task-list">
            <?php foreach ($steps as $step): ?>
                <a class="task-item" href="<?= e(admin_section_url($step[2])) ?>">
                    <strong><?= e($step[0]) ?></strong>
                    <span><?= e($step[1]) ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}

function admin_render_document_overview_card(array $project, array $chunks): void
{
    $documents = array_values(is_array($project['documents'] ?? null) ? $project['documents'] : []);
    ?>
    <div class="card">
        <h2>Dokumentenstatus</h2>
        <p class="muted">Diese Übersicht bleibt kompakt. Die erzeugten Textabschnitte liegen im eigenen Bereich.</p>
        <div class="stack">
            <div>
                <strong><?= e((string) count($documents)) ?> Datei(en)</strong><br>
                <span class="muted"><?= e((string) count($chunks)) ?> Textabschnitte erzeugt</span>
            </div>
            <?php if ($documents === []): ?>
                <div class="empty-state">
                    <strong>Noch keine Dokumente registriert.</strong>
                    <p class="muted">Nach einem Upload wird die Wissensbasis automatisch erweitert.</p>
                </div>
            <?php else: ?>
                <div class="document-list">
                    <?php foreach (array_slice($documents, -6) as $document): ?>
                        <div class="document-item">
                            <strong><?= e((string) ($document['original_name'] ?? 'Unbekannte Datei')) ?></strong>
                            <span class="muted"><?= e((string) ($document['uploaded_at'] ?? '')) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function admin_render_quality_section(array $model): void
{
    ?>
    <div class="card">
        <h2>Qualitätstest vorbereiten</h2>
        <p class="muted">Dieser Bereich ist als eigener Arbeitsbereich angelegt, damit spätere Testläufe nicht zwischen Uploads, Vorlagen und API-Konfiguration untergehen.</p>
        <div class="empty-state">
            <strong>Geplante nächste Ausbaustufe</strong>
            <p class="muted">Sinnvoll sind Prüffragen, erwartete Kernaussagen, Quellenkontrolle, Antwortbewertung und ein Export für fachliche Abnahme. Bis dahin kann der Verbindungstest im Bereich KI-Anbieter genutzt werden.</p>
            <a class="btn btn-secondary" href="<?= e(admin_section_url('provider')) ?>">KI-Verbindung testen</a>
        </div>
    </div>
    <?php
}

function admin_render_security_status_card(array $model): void
{
    ?>
    <div class="grid two">
        <div class="card">
            <h2>Datenschutz und Schlüsselstatus</h2>
            <div class="stack">
                <div>
                    <strong>API-Schlüssel</strong><br>
                    <span class="muted"><?= $model['apiKeyConfigured'] ? 'Ein Schlüssel oder lokaler Endpunkt ist konfiguriert. Der gespeicherte Wert wird nicht im HTML ausgegeben.' : 'Noch kein vollständiger Anbieter konfiguriert.' ?></span>
                </div>
                <div>
                    <strong>Datenverzeichnis</strong><br>
                    <span class="muted"><?= e($model['dataRootStatus']) ?></span>
                </div>
                <div>
                    <strong>Empfehlung für produktive Behördeninstallationen</strong><br>
                    <span class="muted">Laufzeitdaten außerhalb des Webroots speichern, Serververzeichnis gegen direkten Zugriff sperren und nur notwendige Modellanbieter aktivieren.</span>
                </div>
            </div>
        </div>
        <div class="card">
            <h2>Admin-Passwort ändern</h2>
            <p class="muted">Nach Änderung wird die aktuelle Sitzung beendet und eine neue Anmeldung verlangt.</p>
            <form method="post" action="<?= e(admin_section_url('security')) ?>" class="stack" data-working-label="Passwort wird aktualisiert ...">
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
        </div>
    </div>
    <?php
}

function admin_render_operations_card(array $model): void
{
    ?>
    <div class="card">
        <h2>Betrieb</h2>
        <p class="muted">Technischer Status für Installation, Wartung und spätere Übergabe an IT-Verantwortliche.</p>
        <div class="definition-grid">
            <div><strong>PHP-Version</strong><span><?= e(PHP_VERSION) ?></span></div>
            <div><strong>Datenverzeichnis</strong><span><?= e($model['dataRootStatus']) ?></span></div>
            <div><strong>Textabschnitte</strong><span><?= e((string) count($model['chunks'])) ?></span></div>
            <div><strong>Anbieter</strong><span><?= e((string) $model['modelProvider']['label']) ?></span></div>
        </div>
    </div>
    <?php
}

function admin_document_count(array $project): int
{
    return count(is_array($project['documents'] ?? null) ? $project['documents'] : []);
}

function admin_render_project_profile_card(array $project): void
{
    ?>
    <div class="card">
        <h2>Projektprofil</h2>
        <p class="muted">Diese Angaben steuern Titel, Themenrahmen, Begrüßung und die serverseitige Systemanweisung.</p>
        <form method="post" action="<?= e(admin_section_url('project')) ?>" class="stack" data-working-label="Projektprofil wird gespeichert ...">
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
        <p class="muted">Neue Dateien werden in Textabschnitte umgewandelt. Mit JavaScript läuft der Upload als Warteschlange Datei für Datei; ohne JavaScript verarbeitet der Server die Mehrfachauswahl klassisch in einem Request.</p>
        <form method="post" action="<?= e(admin_section_url('knowledge')) ?>" enctype="multipart/form-data" class="stack upload-form" data-upload-queue data-working-label="Dateien werden verarbeitet ...">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="upload_documents">
            <div class="upload-dropzone" data-upload-dropzone>
                <div class="upload-dropzone-copy">
                    <h3>Dateien hinzufügen</h3>
                    <p class="muted">PDF, TXT oder Markdown hier hineinziehen oder über den Button auswählen. Große PDFs können mehrere Minuten pro Datei benötigen.</p>
                </div>
                <div class="actions">
                    <label class="btn btn-secondary" for="knowledge-documents">Dateien auswählen</label>
                    <span class="muted">oder per Drag and Drop ablegen</span>
                </div>
                <input class="visually-hidden" id="knowledge-documents" type="file" name="documents[]" data-upload-input multiple accept=".pdf,.txt,.md,.markdown">
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
                <button class="btn btn-secondary" type="submit" name="action" value="regenerate_profile" data-working-label="Beispielinhalte werden erzeugt ...">Beispielinhalte neu erzeugen</button>
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
            <div><strong>Textabschnitte</strong><br><span class="muted"><?= e((string) count($chunks)) ?> Dateien in der Wissensbasis</span></div>
        </div>
    </div>
    <?php
}

function admin_render_content_curation_card(array $project): void
{
    $frontend = is_array($project['frontend'] ?? null) ? $project['frontend'] : [];
    $quickQuestionSource = is_array($frontend['quick_questions'] ?? null) ? $frontend['quick_questions'] : [];
    $taskExampleSource = is_array($frontend['task_examples'] ?? null) ? $frontend['task_examples'] : [];
    $quickQuestions = array_values(array_filter(array_map('strval', $quickQuestionSource)));
    $taskExamples = array_values(array_filter(array_map('strval', $taskExampleSource)));
    $templates = array_values(is_array($frontend['templates'] ?? null) ? $frontend['templates'] : []);
    $sectionCount = min(max(count($templates) + 1, 4), FRONTEND_MAX_TEMPLATE_SECTIONS);
    ?>
    <div class="card">
        <h2>Schnellfragen und Vorlagen kuratieren</h2>
        <p class="muted">Diese Inhalte werden öffentlich im Assistenten angezeigt. Automatisch erzeugte Vorschläge können hier fachlich bereinigt, umsortiert oder ersetzt werden.</p>
        <form method="post" action="<?= e(admin_section_url('contents')) ?>" class="stack" data-working-label="Inhalte werden gespeichert ...">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save_frontend_content">
            <div class="grid two">
                <div>
                    <label>Schnellfragen</label>
                    <textarea name="quick_questions" rows="8" placeholder="Eine Frage pro Zeile"><?= e(implode("\n", $quickQuestions)) ?></textarea>
                    <p class="muted" style="margin:6px 0 0">Maximal <?= FRONTEND_MAX_QUICK_QUESTIONS ?> Einträge; doppelte Zeilen werden entfernt.</p>
                </div>
                <div>
                    <label>Aufgabenbeispiele</label>
                    <textarea name="task_examples" rows="8" placeholder="Ein Arbeitsauftrag pro Zeile"><?= e(implode("\n", $taskExamples)) ?></textarea>
                    <p class="muted" style="margin:6px 0 0">Diese Beispiele können später ebenfalls im Frontend hervorgehoben werden.</p>
                </div>
            </div>

            <div class="template-admin">
                <?php for ($sectionIndex = 0; $sectionIndex < $sectionCount; $sectionIndex++): ?>
                    <?php
                    $section = is_array($templates[$sectionIndex] ?? null) ? $templates[$sectionIndex] : [];
                    $options = array_values(is_array($section['options'] ?? null) ? $section['options'] : []);
                    $optionCount = min(max(count($options) + 1, 3), FRONTEND_MAX_TEMPLATE_OPTIONS);
                    ?>
                    <div class="template-section">
                        <div class="template-section-head">
                            <h3>Vorlagen-Sektion <?= e((string) ($sectionIndex + 1)) ?></h3>
                            <span class="muted">Leer lassen, um eine Sektion nicht zu speichern.</span>
                        </div>
                        <div class="grid two">
                            <div>
                                <label>Titel</label>
                                <input type="text" name="template_title[]" value="<?= e((string) ($section['title'] ?? '')) ?>">
                            </div>
                            <div>
                                <label>Beschreibung</label>
                                <input type="text" name="template_description[]" value="<?= e((string) ($section['description'] ?? '')) ?>">
                            </div>
                        </div>
                        <?php for ($optionIndex = 0; $optionIndex < $optionCount; $optionIndex++): ?>
                            <?php $option = is_array($options[$optionIndex] ?? null) ? $options[$optionIndex] : []; ?>
                            <div class="template-option">
                                <div>
                                    <label>Button-Text <?= e((string) ($optionIndex + 1)) ?></label>
                                    <input type="text" name="template_option_label[<?= e((string) $sectionIndex) ?>][]" value="<?= e((string) ($option['label'] ?? '')) ?>">
                                </div>
                                <div>
                                    <label>Prompt <?= e((string) ($optionIndex + 1)) ?></label>
                                    <textarea name="template_option_prompt[<?= e((string) $sectionIndex) ?>][]" rows="3"><?= e((string) ($option['prompt'] ?? '')) ?></textarea>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="actions">
                <button class="btn btn-primary" type="submit">Schnellfragen und Vorlagen speichern</button>
            </div>
        </form>
    </div>
    <?php
}

function admin_render_provider_card(array $model): void
{
    $apiConfig = $model['apiConfig'];
    $apiKeyConfigured = (bool) $model['apiKeyConfigured'];
    $modelProvider = $model['modelProvider'];
    ?>
    <div class="card">
        <h2>KI-Anbieter</h2>
        <p class="muted">Hier wird festgelegt, ob das System Gemini, ein OpenAI-kompatibles Gateway oder später eine lokale KI nutzt.</p>
        <form method="post" action="<?= e(admin_section_url('provider')) ?>" class="stack" data-working-label="API-Konfiguration wird gespeichert ...">
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
                <strong>Fähigkeiten</strong><br>
                <span class="muted">
                    Streaming: <?= !empty($modelProvider['capabilities']['streaming']) ? 'ja' : 'nein' ?> ·
                    PDF-Direktverarbeitung: <?= !empty($modelProvider['capabilities']['pdf_input']) ? 'ja' : 'nein' ?> ·
                    JSON-Modus: <?= !empty($modelProvider['capabilities']['json_mode']) ? 'ja' : 'nein' ?>
                </span>
            </div>
            <button class="btn btn-secondary" type="submit">API-Konfiguration speichern</button>
        </form>

        <?php admin_render_model_test_form($model, 'margin-top:18px'); ?>
    </div>
    <?php
}
