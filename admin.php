<?php
declare(strict_types=1);

require __DIR__ . '/lib/app.php';
require __DIR__ . '/src/Security/admin_session.php';
require __DIR__ . '/src/Admin/bootstrap.php';

start_admin_session();

ensure_app_dirs();
ensure_runtime_placeholders();

$requestState = admin_handle_request();
$pageModel = admin_build_page_model($requestState);

admin_render_page($pageModel);

