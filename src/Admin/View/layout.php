<?php
declare(strict_types=1);

function admin_render_page(array $model): void
{
    ?>
    <!DOCTYPE html>
    <html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 64 64%27%3E%3Crect width=%2764%27 height=%2764%27 rx=%2720%27 fill=%27%230a192f%27/%3E%3Ctext x=%2732%27 y=%2740%27 font-size=%2726%27 font-family=%27Segoe UI, Arial, sans-serif%27 font-weight=%27800%27 text-anchor=%27middle%27 fill=%27%23e50046%27%3EBA%3C/text%3E%3C/svg%3E">
        <title>Admin - Beratungsassistent</title>
        <?php admin_render_styles(); ?>
    </head>
    <body>
    <div class="shell">
        <?php if (!password_is_set()): ?>
            <?php admin_render_setup_password($model); ?>
        <?php elseif (!is_admin_authenticated()): ?>
            <?php admin_render_login($model); ?>
        <?php else: ?>
            <div class="page">
                <?php admin_render_topbar($model['project']); ?>
                <?php admin_render_flash($model['message'], $model['messageType'], 'margin-bottom:18px'); ?>

                <?php if ($model['wizardActive']): ?>
                    <?php admin_render_wizard($model); ?>
                <?php else: ?>
                    <?php admin_render_dashboard($model); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php admin_render_scripts(); ?>
    </body>
    </html>
    <?php
}
