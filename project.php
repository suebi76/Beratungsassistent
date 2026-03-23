<?php
declare(strict_types=1);

require __DIR__ . '/lib/app.php';

ensure_app_dirs();
ensure_runtime_placeholders();

$project = load_project_config();
json_response(public_project_config($project));
