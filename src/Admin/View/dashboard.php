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
            <?php admin_render_pdf_split_guidance_card(); ?>
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
    $result = is_array($model['qualityTest'] ?? null) ? $model['qualityTest'] : admin_empty_quality_test_result();
    $query = (string) ($result['query'] ?? '');
    $limit = admin_quality_test_limit($result['limit'] ?? ADMIN_QUALITY_DEFAULT_CHUNKS);
    $withAnswer = (bool) ($result['with_answer'] ?? true);
    $ran = (bool) ($result['ran'] ?? false);
    $tokens = is_array($result['tokens'] ?? null) ? $result['tokens'] : [];
    $retrievedChunks = is_array($result['chunks'] ?? null) ? $result['chunks'] : [];
    $topScore = admin_quality_top_score($retrievedChunks);
    $limitOptions = [3, 5, 8, 10, 12];
    ?>
    <div class="grid two quality-layout">
        <div class="card">
            <h2>Testfrage ausführen</h2>
            <p class="muted">Der Test nutzt dieselbe Retrieval-Logik wie das öffentliche Frontend. So sehen Sie, welche Textabschnitte gefunden werden und ob die Antwort wirklich auf passenden Quellen beruht.</p>
            <form method="post" action="<?= e(admin_section_url('quality')) ?>" class="stack" data-working-label="Qualitätstest läuft ...">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="run_quality_test">
                <div>
                    <label>Testfrage</label>
                    <textarea name="quality_query" rows="4" required placeholder="Zum Beispiel: Was sind zentrale Aufgaben der Fachkonferenz Biologie?"><?= e($query) ?></textarea>
                </div>
                <div class="grid two">
                    <div>
                        <label>Maximale Treffer</label>
                        <select name="quality_limit">
                            <?php foreach ($limitOptions as $option): ?>
                                <option value="<?= e((string) $option) ?>" <?= $limit === $option ? 'selected' : '' ?>><?= e((string) $option) ?> Textabschnitte</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Antworttest</label>
                        <label class="checkbox-row">
                            <input type="checkbox" name="with_answer" value="1" <?= $withAnswer ? 'checked' : '' ?>>
                            <span>KI-Antwort mit aktuellem Anbieter erzeugen</span>
                        </label>
                    </div>
                </div>
                <div class="actions">
                    <button class="btn btn-primary" type="submit">Qualitätstest starten</button>
                    <a class="btn btn-secondary" href="<?= e(admin_section_url('chunks')) ?>">Textabschnitte prüfen</a>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Worauf achten?</h2>
            <div class="quality-checklist">
                <div>
                    <strong>1. Retrieval</strong>
                    <span class="muted">Die besten Treffer müssen fachlich zur Frage passen. Hohe Scores bei falschen Abschnitten sind ein Chunking- oder Suchproblem.</span>
                </div>
                <div>
                    <strong>2. Quellen</strong>
                    <span class="muted">Die Antwort darf nur Quellen nennen, die in den gefundenen Textabschnitten vorkommen.</span>
                </div>
                <div>
                    <strong>3. Abgrenzung</strong>
                    <span class="muted">Bei fachfremden Fragen sollte keine freie Weltwissensantwort entstehen, sondern eine klare Nicht-beantwortbar-Meldung.</span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$ran): ?>
        <div class="card">
            <h2>Noch kein Testlauf</h2>
            <p class="muted">Starten Sie mit einer realistischen Nutzerfrage. Für die fachliche Abnahme sollten später typische Fragen, Grenzfälle und bewusst fachfremde Fragen dokumentiert werden.</p>
        </div>
        <?php return; ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Retrieval-Ergebnis</h2>
                <p class="muted"><?= e((string) count($retrievedChunks)) ?> Treffer für die aktuelle Testfrage.</p>
            </div>
            <span class="count-pill"><?= e((string) count($tokens)) ?> Suchbegriffe</span>
        </div>

        <?php if ($tokens !== []): ?>
            <div class="token-list" aria-label="Suchbegriffe">
                <?php foreach (array_slice($tokens, 0, 24) as $token): ?>
                    <span><?= e((string) $token) ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($retrievedChunks === []): ?>
            <div class="empty-state" style="margin-top:16px">
                <strong>Keine passenden Textabschnitte gefunden.</strong>
                <p class="muted">Das ist bei fachfremden Fragen korrekt. Bei fachlich erwartbaren Fragen sollten Suchbegriffe, Chunk-Titel, Tags und Dokumentenqualität geprüft werden.</p>
            </div>
        <?php else: ?>
            <div class="retrieval-list">
                <?php foreach ($retrievedChunks as $index => $chunk): ?>
                    <?php
                    $score = (int) ($chunk['score'] ?? 0);
                    $scoreLabel = admin_quality_score_label($score, $topScore);
                    $chunkSource = trim((string) ($chunk['quelle'] ?? ''));
                    $source = $chunkSource !== '' ? $chunkSource : trim((string) ($chunk['source_file'] ?? ''));
                    $tags = is_array($chunk['tags'] ?? null) ? $chunk['tags'] : [];
                    $matchedTerms = is_array($chunk['matched_terms'] ?? null) ? array_map('strval', $chunk['matched_terms']) : [];
                    ?>
                    <article class="retrieval-item">
                        <div class="card-head">
                            <div>
                                <strong>[<?= e((string) ($index + 1)) ?>] <?= e((string) ($chunk['title'] ?? 'Ohne Titel')) ?></strong><br>
                                <span class="mono"><?= e((string) ($chunk['file'] ?? '')) ?></span>
                            </div>
                            <span class="score-badge">Score <?= e((string) $score) ?> · <?= e($scoreLabel) ?></span>
                        </div>
                        <?php if ($source !== ''): ?>
                            <p class="muted"><strong>Quelle:</strong> <?= e($source) ?></p>
                        <?php endif; ?>
                        <?php if ($tags !== []): ?>
                            <p class="muted"><strong>Tags:</strong> <?= e(implode(', ', array_slice(array_map('strval', $tags), 0, 10))) ?></p>
                        <?php endif; ?>
                        <?php if ($matchedTerms !== []): ?>
                            <p class="muted"><strong>Trefferbegriffe:</strong> <?= e(implode(', ', array_slice($matchedTerms, 0, 12))) ?></p>
                        <?php endif; ?>
                        <p><?= e(excerpt((string) ($chunk['body'] ?? ''), 520)) ?></p>
                        <details>
                            <summary>Volltext anzeigen</summary>
                            <pre class="chunk-preview"><?= e((string) ($chunk['body'] ?? '')) ?></pre>
                        </details>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2>KI-Antwort</h2>
        <?php if (!$withAnswer): ?>
            <div class="empty-state">
                <strong>Antworttest wurde übersprungen.</strong>
                <p class="muted">Aktivieren Sie den Antworttest, wenn Retrieval und finale Modellantwort zusammen geprüft werden sollen.</p>
            </div>
        <?php elseif ((string) ($result['answer_error'] ?? '') !== ''): ?>
            <div class="alert warning"><?= e((string) $result['answer_error']) ?></div>
        <?php else: ?>
            <pre class="answer-preview"><?= e((string) ($result['answer'] ?? '')) ?></pre>
        <?php endif; ?>
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
    $checks = admin_system_check_items($model);
    ?>
    <div class="grid two">
        <div class="card">
            <h2>Systemcheck</h2>
            <p class="muted">Diese Prüfungen zeigen vor Uploads, ob der Webhost die wichtigsten technischen Voraussetzungen erfüllt.</p>
            <div class="check-list">
                <?php foreach ($checks as $check): ?>
                    <div class="check-item <?= e((string) $check['status']) ?>">
                        <span class="check-dot" aria-hidden="true"></span>
                        <div>
                            <strong><?= e((string) $check['label']) ?></strong>
                            <span class="muted"><?= e((string) $check['value']) ?> · <?= e((string) $check['detail']) ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card">
            <h2>Betrieb</h2>
            <p class="muted">Technischer Status für Installation, Wartung und spätere Übergabe an IT-Verantwortliche.</p>
            <div class="definition-grid">
                <div><strong>PHP-Version</strong><span><?= e(PHP_VERSION) ?></span></div>
                <div><strong>Datenverzeichnis</strong><span><?= e($model['dataRootStatus']) ?></span></div>
                <div><strong>Textabschnitte</strong><span><?= e((string) count($model['chunks'])) ?></span></div>
                <div><strong>Anbieter</strong><span><?= e((string) $model['modelProvider']['label']) ?></span></div>
            </div>
            <?php admin_render_model_test_form($model, 'margin-top:18px'); ?>
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
        <p class="muted">Neue Dateien werden in Textabschnitte umgewandelt. Mit JavaScript läuft der Upload als Warteschlange Datei für Datei inklusive Serverstatus; ohne JavaScript verarbeitet der Server die Mehrfachauswahl klassisch in einem Request.</p>
        <form method="post" action="<?= e(admin_section_url('knowledge')) ?>" enctype="multipart/form-data" class="stack upload-form" data-upload-queue data-working-label="Dateien werden verarbeitet ...">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="upload_documents">
            <div class="upload-dropzone" data-upload-dropzone>
                <div class="upload-dropzone-copy">
                    <h3>Dateien hinzufügen</h3>
                    <p class="muted">PDF, TXT oder Markdown hier hineinziehen oder über den Button auswählen. Große PDFs werden erkannt und können mehrere Minuten pro Datei benötigen.</p>
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
                        <?php if (($result['ok'] ?? false) && !empty($result['skipped_duplicate'])): ?>
                            <strong>Übersprungen</strong><br>
                            <span class="muted"><?= e((string) ($result['message'] ?? 'Datei ist bereits vorhanden.')) ?></span>
                        <?php elseif ($result['ok'] ?? false): ?>
                            <strong><?= e($result['document']['original_name'] ?? '') ?></strong><br>
                            <span class="muted"><?= count($result['saved_chunks'] ?? []) ?> Textabschnitte erzeugt</span>
                        <?php else: ?>
                            <strong>Fehler</strong><br>
                            <span class="muted"><?= e($result['error'] ?? '') ?></span>
                        <?php endif; ?>
                        <?php if (!empty($result['pdf_split_advice']['message'])): ?>
                            <p class="muted" style="margin:8px 0 0"><?= e((string) $result['pdf_split_advice']['message']) ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

function admin_render_pdf_split_guidance_card(): void
{
    ?>
    <div class="card pdf-split-planner" data-pdf-split-planner>
        <h2>Große PDF vorbereiten</h2>
        <p class="muted">Diese Hilfe berechnet die spätere Split-Logik: Seiten pro Teil, Anzahl der Teile und logische Dateinamen. Die PDF wird hier noch nicht geteilt; der nächste technische Schritt ist ein geprüfter Browser-, Server- oder Portable-Splitter.</p>
        <div class="grid two">
            <div>
                <label>Originaldateiname</label>
                <input type="text" value="beispieldokument.pdf" data-pdf-split-name>
            </div>
            <div>
                <label>Gesamtseiten</label>
                <input type="number" min="1" step="1" value="200" data-pdf-split-pages>
            </div>
            <div>
                <label>Seiten pro Teil</label>
                <input type="number" min="<?= e((string) PDF_SPLIT_MIN_PAGES_PER_PART) ?>" max="<?= e((string) PDF_SPLIT_MAX_PAGES_PER_PART) ?>" step="1" value="<?= e((string) pdf_split_default_pages_per_part(400)) ?>" data-pdf-split-size>
            </div>
        </div>
        <div class="split-preview" data-pdf-split-output role="status" aria-live="polite"></div>
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
