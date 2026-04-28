<?php
declare(strict_types=1);

function start_admin_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secureSessionCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '',
        'domain' => '',
        'secure' => $secureSessionCookie,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

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

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_is_valid(): bool
{
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    $postedToken = $_POST['csrf_token'] ?? '';

    return is_string($sessionToken)
        && is_string($postedToken)
        && $sessionToken !== ''
        && hash_equals($sessionToken, $postedToken);
}

function is_admin_authenticated(): bool
{
    return !empty($_SESSION['admin_ok']);
}

