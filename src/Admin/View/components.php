<?php
declare(strict_types=1);

function admin_render_flash(string $message, string $messageType, string $style = ''): void
{
    if ($message === '') {
        return;
    }
    $styleAttribute = $style !== '' ? ' style="' . e($style) . '"' : '';
    ?>
    <div class="alert <?= e($messageType) ?>"<?= $styleAttribute ?>><?= e($message) ?></div>
    <?php
}

function admin_step_class(string $stepKey, string $setupStep, array $steps): string
{
    $class = 'step';
    if ($stepKey === $setupStep) {
        return $class . ' active';
    }

    if (array_search($stepKey, array_keys($steps), true) < array_search($setupStep, array_keys($steps), true)) {
        return $class . ' done';
    }

    return $class;
}

function admin_render_topbar(array $project): void
{
    ?>
    <div class="topbar">
        <div class="title-line">
            <div class="brand" style="margin:0">BA</div>
            <div>
                <h1>Beratungsassistent Admin</h1>
                <p class="muted" style="margin:4px 0 0">Konfiguration, Wissensbasis und Beispielinhalte kommen aus denselben Projektdateien.</p>
            </div>
        </div>
        <div class="title-chip">
            <span class="chip"><?= e($project['title'] ?: 'Noch kein Titel') ?></span>
            <form method="post" style="margin:0">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="logout">
                <button class="chip" type="submit" style="border:0; cursor:pointer">Abmelden</button>
            </form>
        </div>
    </div>
    <?php
}

function admin_render_model_test_form(array $model, string $style = ''): void
{
    $apiKeyConfigured = (bool) $model['apiKeyConfigured'];
    $modelProvider = $model['modelProvider'];
    $styleAttribute = $style !== '' ? ' style="' . e($style) . '"' : '';
    ?>
    <form method="post" class="stack"<?= $styleAttribute ?>>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="test_model_provider">
        <div>
            <strong>Verbindungstest</strong><br>
            <span class="muted">Sendet eine kurze Testanfrage an <?= e((string) $modelProvider['label']) ?> und prüft, ob Modell, Token und Base-URL zusammenpassen.</span>
        </div>
        <button class="btn btn-secondary" type="submit" <?= $apiKeyConfigured ? '' : 'disabled' ?>>KI-Verbindung testen</button>
    </form>
    <?php
}
