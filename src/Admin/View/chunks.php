<?php
declare(strict_types=1);

function admin_render_chunks_table(array $chunks): void
{
    ?>
    <div class="card">
        <div class="card-head">
            <div>
                <h2>Textabschnitte in der Wissensbasis</h2>
                <p class="muted">Die Antwortsuche nutzt diese Dateien im Hintergrund. Große Listen bleiben deshalb in diesem eigenen Bereich.</p>
            </div>
            <span class="count-pill"><?= e((string) count($chunks)) ?> vorhanden</span>
        </div>
        <?php if ($chunks === []): ?>
            <div class="empty-state">
                <strong>Noch keine Textabschnitte vorhanden.</strong>
                <p class="muted">Laden Sie zuerst Dateien in der Wissensbasis hoch. Daraus werden automatisch Textabschnitte erzeugt.</p>
            </div>
        <?php else: ?>
            <form id="chunk-bulk-form" method="post" action="<?= e(admin_section_url('chunks')) ?>" data-confirm="Ausgewählte Textabschnitte wirklich löschen?" data-working-label="Textabschnitte werden gelöscht ...">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete_chunks">
            </form>
            <div class="table-actions">
                <button class="btn btn-danger" type="submit" form="chunk-bulk-form">Ausgewählte löschen</button>
                <span class="muted">Mehrfachauswahl ist für Bereinigungsläufe gedacht.</span>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th class="select-cell">
                            <input type="checkbox" aria-label="Alle Textabschnitte auswählen" data-select-all="chunk-bulk-form">
                        </th>
                        <th>Titel</th>
                        <th>Quelle</th>
                        <th>Tags</th>
                        <th>Größe</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($chunks as $chunk): ?>
                    <tr>
                        <td class="select-cell">
                            <input type="checkbox" form="chunk-bulk-form" name="files[]" value="<?= e($chunk['file']) ?>" aria-label="<?= e($chunk['title']) ?> auswählen">
                        </td>
                        <td>
                            <strong><?= e($chunk['title']) ?></strong><br>
                            <span class="mono"><?= e($chunk['file']) ?></span>
                        </td>
                        <td><?= e($chunk['quelle'] ?: $chunk['source_file']) ?></td>
                        <td><?= e(implode(', ', array_slice($chunk['tags'], 0, 8))) ?></td>
                        <td><?= e(number_format($chunk['bytes'] / 1024, 1)) ?> KB</td>
                        <td>
                            <form method="post" action="<?= e(admin_section_url('chunks')) ?>" data-confirm="Textabschnitt wirklich löschen?" data-working-label="Textabschnitt wird gelöscht ...">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_chunk">
                                <input type="hidden" name="file" value="<?= e($chunk['file']) ?>">
                                <button class="btn btn-danger btn-compact" type="submit">Löschen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}
