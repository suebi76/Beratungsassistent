<?php
declare(strict_types=1);

function admin_render_chunks_table(array $chunks): void
{
    ?>
    <div class="card" style="margin-top:20px">
        <h2>Chunks in der Wissensbasis</h2>
        <p class="muted">Die Retrieval-Stufe durchsucht diese Dateien bei jeder Anfrage erneut.</p>
        <?php if ($chunks === []): ?>
            <p class="muted">Noch keine Chunks vorhanden.</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
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
                        <td>
                            <strong><?= e($chunk['title']) ?></strong><br>
                            <span class="mono"><?= e($chunk['file']) ?></span>
                        </td>
                        <td><?= e($chunk['quelle'] ?: $chunk['source_file']) ?></td>
                        <td><?= e(implode(', ', array_slice($chunk['tags'], 0, 8))) ?></td>
                        <td><?= e(number_format($chunk['bytes'] / 1024, 1)) ?> KB</td>
                        <td>
                            <form method="post" onsubmit="return confirm('Chunk wirklich löschen?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete_chunk">
                                <input type="hidden" name="file" value="<?= e($chunk['file']) ?>">
                                <button class="btn btn-danger" type="submit">Löschen</button>
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

