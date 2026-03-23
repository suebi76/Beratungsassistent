<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/lib/app.php';

ensure_app_dirs();
ensure_runtime_placeholders();

function set_flash(string $type, string $text): void
{
    $_SESSION['flash'] = ['type' => $type, 'text' => $text];
}

function pull_flash(): array
{
    $flash = $_SESSION['flash'] ?? ['type' => '', 'text' => ''];
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : ['type' => '', 'text' => ''];
}

function is_admin_authenticated(): bool
{
    return !empty($_SESSION['admin_ok']);
}

$apiConfig = load_api_config();
$project = load_project_config();
$flash = pull_flash();
$message = $flash['text'] ?? '';
$messageType = $flash['type'] ?? 'info';
$uploadResults = [];

if (isset($_GET['logout'])) {
    session_destroy();
    redirect('admin.php');
}

if (!password_is_set()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'setup_password') {
        $pw1 = trim((string) ($_POST['pw1'] ?? ''));
        $pw2 = trim((string) ($_POST['pw2'] ?? ''));

        if (mb_strlen($pw1, 'UTF-8') < 8) {
            $message = 'Das Admin-Passwort muss mindestens 8 Zeichen lang sein.';
            $messageType = 'error';
        } elseif ($pw1 !== $pw2) {
            $message = 'Die Passwoerter stimmen nicht ueberein.';
            $messageType = 'error';
        } else {
            file_put_contents(password_file(), password_hash($pw1, PASSWORD_DEFAULT));
            $_SESSION['admin_ok'] = true;
            $apiConfig = load_api_config();
            $project = load_project_config();
            $message = 'Admin-Passwort gespeichert. Weiter mit Schritt 2.';
            $messageType = 'success';
        }
    }
} elseif (!is_admin_authenticated()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
        $hash = trim((string) @file_get_contents(password_file()));
        if ($hash !== '' && password_verify((string) ($_POST['pw'] ?? ''), $hash)) {
            $_SESSION['admin_ok'] = true;
            redirect('admin.php');
        }
        $message = 'Falsches Passwort.';
        $messageType = 'error';
    }
} else {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'save_apikey') {
        $key = trim((string) ($_POST['apikey'] ?? ''));
        $model = trim((string) ($_POST['model'] ?? DEFAULT_MODEL_NAME));

        if (strlen($key) < 10) {
            $message = 'Bitte den vollstaendigen Gemini-API-Key eintragen.';
            $messageType = 'error';
        } elseif (!save_api_config($key, $model)) {
            $message = 'API-Konfiguration konnte nicht gespeichert werden.';
            $messageType = 'error';
        } else {
            $apiConfig = load_api_config();
            $message = 'API-Key gespeichert. Weiter mit dem Projektprofil.';
            $messageType = 'success';
        }
    }

    if ($action === 'save_project') {
        $title = normalize_whitespace((string) ($_POST['title'] ?? ''));
        $topic = normalize_whitespace((string) ($_POST['topic'] ?? ''));
        $audience = normalize_whitespace((string) ($_POST['audience'] ?? ''));

        if ($title === '' || $topic === '') {
            $message = 'Titel und Themenfeld sind Pflichtfelder.';
            $messageType = 'error';
        } else {
            $project['title'] = $title;
            $project['slug'] = slugify($title);
            $project['topic'] = $topic;
            $project['audience'] = $audience;
            $project['frontend']['welcome_heading'] = $title;
            if (trim((string) $project['subtitle']) === '') {
                $project['subtitle'] = 'Konfigurierbarer Beratungsassistent';
            }
            $project['setup']['profile_completed_at'] = now_iso();

            if (!save_project_config($project)) {
                $message = 'Projektprofil konnte nicht gespeichert werden.';
                $messageType = 'error';
            } else {
                $project = load_project_config();
                $message = 'Projektprofil gespeichert. Jetzt Dokumente hochladen.';
                $messageType = 'success';
            }
        }
    }

    if ($action === 'upload_documents') {
        if (!api_key_is_configured($apiConfig)) {
            $message = 'Bitte zuerst einen gueltigen API-Key hinterlegen.';
            $messageType = 'error';
        } elseif (!project_profile_is_configured($project)) {
            $message = 'Bitte zuerst Titel und Themenfeld speichern.';
            $messageType = 'error';
        } else {
            $files = normalize_uploaded_files($_FILES['documents'] ?? []);
            $files = array_values(array_filter($files, static fn(array $file): bool => ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE));

            if ($files === []) {
                $message = 'Bitte mindestens eine Datei auswaehlen.';
                $messageType = 'error';
            } else {
                $successCount = 0;
                foreach ($files as $file) {
                    $result = process_uploaded_document($file, $project, $apiConfig);
                    $uploadResults[] = $result;
                    if ($result['ok'] ?? false) {
                        $successCount++;
                        $project['documents'][] = $result['document'];
                    }
                }

                if ($successCount > 0) {
                    $project['setup']['knowledge_completed_at'] = now_iso();
                    save_project_config($project);
                    $regen = regenerate_project_profile(load_project_config(), $apiConfig);
                    if ($regen['ok'] ?? false) {
                        $project = $regen['project'];
                        $message = $successCount . ' Datei(en) verarbeitet. Frontend-Vorlagen und Beispielfragen wurden neu erzeugt.';
                        $messageType = 'success';
                    } else {
                        $project = load_project_config();
                        $message = $successCount . ' Datei(en) verarbeitet, aber die automatische Frontend-Konfiguration konnte nicht aktualisiert werden.';
                        $messageType = 'error';
                    }
                } else {
                    $message = 'Keine Datei konnte verarbeitet werden.';
                    $messageType = 'error';
                }
            }
        }
    }

    if ($action === 'regenerate_profile') {
        $regen = regenerate_project_profile($project, $apiConfig);
        if ($regen['ok'] ?? false) {
            $project = $regen['project'];
            $message = 'Frontend-Konfiguration aus der Wissensbasis neu erzeugt.';
            $messageType = 'success';
        } else {
            $message = $regen['error'] ?? 'Profil konnte nicht regeneriert werden.';
            $messageType = 'error';
        }
    }

    if ($action === 'delete_chunk') {
        $file = basename((string) ($_POST['file'] ?? ''));
        if (!preg_match('/^[A-Za-z0-9._-]+\.md$/', $file)) {
            $message = 'Ungueltiger Dateiname.';
            $messageType = 'error';
        } else {
            $path = chunks_dir() . '/' . $file;
            if (file_exists($path) && unlink($path)) {
                $project = load_project_config();
                if (!knowledge_base_is_configured()) {
                    $project['setup']['knowledge_completed_at'] = null;
                    save_project_config($project);
                }
                $message = 'Chunk geloescht.';
                $messageType = 'success';
            } else {
                $message = 'Chunk konnte nicht geloescht werden.';
                $messageType = 'error';
            }
        }
    }

    if ($action === 'reset_password') {
        $pw1 = trim((string) ($_POST['pw1'] ?? ''));
        $pw2 = trim((string) ($_POST['pw2'] ?? ''));
        if (mb_strlen($pw1, 'UTF-8') < 8) {
            $message = 'Das neue Passwort muss mindestens 8 Zeichen lang sein.';
            $messageType = 'error';
        } elseif ($pw1 !== $pw2) {
            $message = 'Die Passwoerter stimmen nicht ueberein.';
            $messageType = 'error';
        } else {
            file_put_contents(password_file(), password_hash($pw1, PASSWORD_DEFAULT));
            session_destroy();
            session_start();
            set_flash('success', 'Passwort geaendert. Bitte neu anmelden.');
            redirect('admin.php');
        }
    }
}

$apiConfig = load_api_config();
$project = load_project_config();
$setupStep = current_setup_step($apiConfig, $project);
$chunks = get_chunks();
$publicConfig = public_project_config($project);
$wizardActive = is_admin_authenticated() && $setupStep !== 'done';
$steps = [
    'password' => '1. Passwort',
    'api' => '2. API',
    'profile' => '3. Profil',
    'documents' => '4. Dateien',
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Beratungsassistent</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: linear-gradient(180deg, #f7f5ef 0%, #eef2f5 100%); color: #18212b; }
        a { color: inherit; }
        .shell { min-height: 100vh; }
        .center { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .auth, .card { background: rgba(255,255,255,0.92); backdrop-filter: blur(10px); border: 1px solid rgba(24,33,43,0.08); border-radius: 24px; box-shadow: 0 24px 60px rgba(24,33,43,0.08); }
        .auth { width: 100%; max-width: 440px; padding: 32px; }
        .brand { width: 56px; height: 56px; border-radius: 18px; display: inline-flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1f5eff, #f15b2a); color: white; font-weight: 800; font-size: 20px; margin-bottom: 18px; }
        h1, h2, h3 { margin: 0; }
        p { line-height: 1.55; }
        .muted { color: #68717c; }
        .stack { display: grid; gap: 18px; }
        label { display: block; font-size: 12px; font-weight: 700; letter-spacing: 0.02em; text-transform: uppercase; color: #4b5560; margin-bottom: 6px; }
        input[type=text], input[type=password], textarea, select { width: 100%; padding: 12px 14px; border-radius: 14px; border: 1px solid #d5dbe2; background: #fff; font: inherit; color: #18212b; }
        textarea { min-height: 120px; resize: vertical; }
        input:focus, textarea:focus, select:focus { outline: none; border-color: #1f5eff; box-shadow: 0 0 0 4px rgba(31,94,255,0.12); }
        .btn { appearance: none; border: none; border-radius: 14px; padding: 12px 18px; font: inherit; font-weight: 700; cursor: pointer; transition: transform .12s ease, opacity .12s ease; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: linear-gradient(135deg, #1f5eff, #f15b2a); color: white; }
        .btn-secondary { background: #edf1f6; color: #18212b; }
        .btn-danger { background: #fff1f0; color: #b42318; }
        .alert { border-radius: 16px; padding: 14px 16px; font-size: 14px; }
        .alert.success { background: #eefaf0; color: #136c2e; border: 1px solid #a8ddb5; }
        .alert.error { background: #fff3f2; color: #a33024; border: 1px solid #f1b0a8; }
        .page { max-width: 1120px; margin: 0 auto; padding: 28px 20px 56px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; gap: 18px; margin-bottom: 22px; }
        .title-line { display: flex; align-items: center; gap: 14px; }
        .title-chip { display: inline-flex; gap: 8px; flex-wrap: wrap; }
        .chip { font-size: 12px; font-weight: 700; letter-spacing: .03em; text-transform: uppercase; padding: 6px 10px; border-radius: 999px; background: rgba(255,255,255,0.7); border: 1px solid rgba(24,33,43,0.08); color: #4b5560; }
        .grid { display: grid; gap: 20px; }
        .grid.two { grid-template-columns: 1.1fr .9fr; }
        .card { padding: 24px; }
        .card h2 { font-size: 20px; margin-bottom: 6px; }
        .card h3 { font-size: 16px; margin-bottom: 6px; }
        .stepper { display: flex; flex-wrap: wrap; gap: 10px; margin: 16px 0 20px; }
        .step { padding: 8px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; background: #edf1f6; color: #5a6470; }
        .step.active { background: #18212b; color: white; }
        .step.done { background: #dff4e4; color: #136c2e; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 16px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
        .table th, .table td { text-align: left; padding: 12px 10px; border-bottom: 1px solid #edf1f6; vertical-align: top; }
        .table th { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #68717c; }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12px; color: #68717c; }
        .dropzone { border: 2px dashed #cdd6df; border-radius: 18px; padding: 26px; background: #fafcfe; }
        .dropzone p { margin: 0; }
        .result-list { display: grid; gap: 12px; margin-top: 14px; }
        .result-item { border-radius: 16px; padding: 14px; background: #f8fafc; border: 1px solid #e6ebf0; }
        details { border-top: 1px solid #edf1f6; padding-top: 16px; margin-top: 18px; }
        summary { cursor: pointer; font-weight: 700; color: #18212b; }
        @media (max-width: 900px) {
            .grid.two { grid-template-columns: 1fr; }
            .topbar { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>
<div class="shell">
<?php if (!password_is_set()): ?>
    <div class="center">
        <div class="auth stack">
            <div class="brand">BA</div>
            <div>
                <h1>Schritt 1 von 4</h1>
                <p class="muted">Legen Sie beim ersten Start das Admin-Passwort fest. Danach fuehrt der Wizard durch API, Projektprofil und Wissensbasis.</p>
            </div>
            <?php if ($message): ?><div class="alert <?= e($messageType) ?>"><?= e($message) ?></div><?php endif; ?>
            <form method="post" class="stack">
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
<?php elseif (!is_admin_authenticated()): ?>
    <div class="center">
        <div class="auth stack">
            <div class="brand">BA</div>
            <div>
                <h1>Admin-Anmeldung</h1>
                <p class="muted">Melden Sie sich an, um Projektprofil, API-Key und Wissensbasis zu verwalten.</p>
            </div>
            <?php if ($message): ?><div class="alert <?= e($messageType) ?>"><?= e($message) ?></div><?php endif; ?>
            <form method="post" class="stack">
                <input type="hidden" name="action" value="login">
                <div>
                    <label>Passwort</label>
                    <input type="password" name="pw" required autocomplete="current-password">
                </div>
                <button class="btn btn-primary" type="submit">Anmelden</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="page">
        <div class="topbar">
            <div class="title-line">
                <div class="brand" style="margin:0">BA</div>
                <div>
                    <h1>Beratungsassistent Admin</h1>
                    <p class="muted" style="margin:4px 0 0">Konfiguration, Wissensbasis und Frontend-Beispiele kommen aus denselben Projektdateien.</p>
                </div>
            </div>
            <div class="title-chip">
                <span class="chip"><?= e($project['title'] ?: 'Noch kein Titel') ?></span>
                <a class="chip" href="?logout" style="text-decoration:none">Abmelden</a>
            </div>
        </div>

        <?php if ($message): ?><div class="alert <?= e($messageType) ?>" style="margin-bottom:18px"><?= e($message) ?></div><?php endif; ?>

        <?php if ($wizardActive): ?>
            <div class="card">
                <h2>Ersteinrichtung</h2>
                <p class="muted">Der Assistent wird gefuehrt in einzelnen Schritten eingerichtet.</p>
                <div class="stepper">
                    <?php foreach ($steps as $key => $label): ?>
                        <?php
                        $class = 'step';
                        if ($key === $setupStep) {
                            $class .= ' active';
                        } elseif (array_search($key, array_keys($steps), true) < array_search($setupStep, array_keys($steps), true)) {
                            $class .= ' done';
                        }
                        ?>
                        <span class="<?= $class ?>"><?= e($label) ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($setupStep === 'api'): ?>
                    <form method="post" class="stack" style="max-width:620px">
                        <input type="hidden" name="action" value="save_apikey">
                        <div>
                            <label>Gemini API-Key</label>
                            <input type="text" name="apikey" required placeholder="AIza..." autocomplete="off">
                        </div>
                        <div>
                            <label>Modell</label>
                            <input type="text" name="model" value="<?= e($apiConfig['model'] ?? DEFAULT_MODEL_NAME) ?>">
                        </div>
                        <div class="actions">
                            <button class="btn btn-primary" type="submit">API-Key speichern</button>
                        </div>
                    </form>
                <?php elseif ($setupStep === 'profile'): ?>
                    <form method="post" class="stack" style="max-width:720px">
                        <input type="hidden" name="action" value="save_project">
                        <div>
                            <label>Titel des Assistenten</label>
                            <input type="text" name="title" required value="<?= e($project['title']) ?>" placeholder="z. B. Beratungsassistent Fortbildung">
                        </div>
                        <div>
                            <label>Themenfeld</label>
                            <textarea name="topic" required placeholder="Beschreiben Sie das fachliche Gebiet, fuer das der Assistent eingesetzt wird."><?= e((string) $project['topic']) ?></textarea>
                        </div>
                        <div>
                            <label>Zielgruppe</label>
                            <input type="text" name="audience" value="<?= e((string) $project['audience']) ?>" placeholder="z. B. Schulleitungen, Fachberater, Verwaltung">
                        </div>
                        <div class="actions">
                            <button class="btn btn-primary" type="submit">Profil speichern</button>
                        </div>
                    </form>
                <?php elseif ($setupStep === 'documents'): ?>
                    <form method="post" enctype="multipart/form-data" class="stack" style="max-width:760px">
                        <input type="hidden" name="action" value="upload_documents">
                        <div class="dropzone stack">
                            <div>
                                <h3>Dateien fuer die Wissensbasis</h3>
                                <p class="muted">Laden Sie eine oder mehrere Dateien hoch. In dieser ersten Version werden PDF, TXT und Markdown unterstuetzt. Daraus werden Chunks, Vorlagen und Beispielaufgaben erzeugt.</p>
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
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid two">
                <div class="grid">
                    <div class="card">
                        <h2>Projektprofil</h2>
                        <p class="muted">Diese Angaben steuern Titel, Themenrahmen, Begruessung und den Server-Prompt.</p>
                        <form method="post" class="stack">
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
                                <button class="btn btn-secondary" type="submit" formaction="project.php" formmethod="get">Oeffentliche Konfiguration ansehen</button>
                            </div>
                        </form>
                    </div>

                    <div class="card">
                        <h2>Wissensbasis erweitern</h2>
                        <p class="muted">Neue Dateien werden in Chunks umgewandelt. Danach werden Quick Questions, Aufgabenbeispiele und Vorlagen automatisch aus der Wissensbasis neu erzeugt.</p>
                        <form method="post" enctype="multipart/form-data" class="stack">
                            <input type="hidden" name="action" value="upload_documents">
                            <div>
                                <label>Dateien</label>
                                <input type="file" name="documents[]" multiple accept=".pdf,.txt,.md,.markdown">
                            </div>
                            <div class="actions">
                                <button class="btn btn-primary" type="submit">Dateien hochladen und verarbeiten</button>
                                <button class="btn btn-secondary" type="submit" name="action" value="regenerate_profile">Frontend-Beispiele neu erzeugen</button>
                            </div>
                        </form>

                        <?php if ($uploadResults !== []): ?>
                            <div class="result-list">
                                <?php foreach ($uploadResults as $result): ?>
                                    <div class="result-item">
                                        <?php if ($result['ok'] ?? false): ?>
                                            <strong><?= e($result['document']['original_name'] ?? '') ?></strong><br>
                                            <span class="muted"><?= count($result['saved_chunks'] ?? []) ?> Chunks erzeugt</span>
                                        <?php else: ?>
                                            <strong>Fehler</strong><br>
                                            <span class="muted"><?= e($result['error'] ?? '') ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid">
                    <div class="card">
                        <h2>Live-Konfiguration</h2>
                        <p class="muted">Was die normale Nutzeransicht aktuell verwendet.</p>
                        <div class="stack">
                            <div><strong>Titel</strong><br><span class="muted"><?= e($publicConfig['title']) ?></span></div>
                            <div><strong>Untertitel</strong><br><span class="muted"><?= e($publicConfig['subtitle']) ?></span></div>
                            <div><strong>Quick Questions</strong><br><span class="muted"><?= e((string) count($publicConfig['frontend']['quick_questions'])) ?> Eintraege</span></div>
                            <div><strong>Vorlagen</strong><br><span class="muted"><?= e((string) count($publicConfig['frontend']['templates'])) ?> Sektionen</span></div>
                            <div><strong>Chunks</strong><br><span class="muted"><?= e((string) count($chunks)) ?> Dateien im RAG-Verzeichnis</span></div>
                        </div>
                    </div>

                    <div class="card">
                        <h2>API und Sicherheit</h2>
                        <form method="post" class="stack">
                            <input type="hidden" name="action" value="save_apikey">
                            <div>
                                <label>Gemini API-Key</label>
                                <input type="text" name="apikey" value="<?= e((string) ($apiConfig['api_key'] ?? '')) ?>" autocomplete="off">
                            </div>
                            <div>
                                <label>Modell</label>
                                <input type="text" name="model" value="<?= e((string) ($apiConfig['model'] ?? DEFAULT_MODEL_NAME)) ?>">
                            </div>
                            <button class="btn btn-secondary" type="submit">API-Konfiguration speichern</button>
                        </form>

                        <details>
                            <summary>Admin-Passwort aendern</summary>
                            <form method="post" class="stack" style="margin-top:12px">
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
                </div>
            </div>

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
                                <th>Groesse</th>
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
                                    <form method="post" onsubmit="return confirm('Chunk wirklich loeschen?');">
                                        <input type="hidden" name="action" value="delete_chunk">
                                        <input type="hidden" name="file" value="<?= e($chunk['file']) ?>">
                                        <button class="btn btn-danger" type="submit">Loeschen</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
</div>
</body>
</html>
