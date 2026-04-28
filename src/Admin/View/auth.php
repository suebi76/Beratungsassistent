<?php
declare(strict_types=1);

function admin_render_setup_password(array $model): void
{
    ?>
    <div class="center">
        <div class="auth stack">
            <div class="brand">BA</div>
            <div>
                <h1>Schritt 1 von 4</h1>
                <p class="muted">Legen Sie beim ersten Start das Admin-Passwort fest. Danach führt der Wizard durch API, Projektprofil und Wissensbasis.</p>
            </div>
            <?php admin_render_flash($model['message'], $model['messageType']); ?>
            <form method="post" class="stack">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="setup_password">
                <div>
                    <label>Admin-Passwort</label>
                    <input type="password" name="pw1" required autocomplete="new-password" placeholder="Mindestens 8 Zeichen">
                </div>
                <div>
                    <label>Passwort wiederholen</label>
                    <input type="password" name="pw2" required autocomplete="new-password">
                </div>
                <button class="btn btn-primary" type="submit">Passwort speichern</button>
            </form>
        </div>
    </div>
    <?php
}

function admin_render_login(array $model): void
{
    ?>
    <div class="center">
        <div class="auth stack">
            <div class="brand">BA</div>
            <div>
                <h1>Admin-Anmeldung</h1>
                <p class="muted">Melden Sie sich an, um Projektprofil, API-Key und Wissensbasis zu verwalten.</p>
            </div>
            <?php admin_render_flash($model['message'], $model['messageType']); ?>
            <form method="post" class="stack">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="login">
                <div>
                    <label>Passwort</label>
                    <input type="password" name="pw" required autocomplete="current-password">
                </div>
                <button class="btn btn-primary" type="submit">Anmelden</button>
            </form>
        </div>
    </div>
    <?php
}

