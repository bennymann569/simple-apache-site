<?php

function load_env_file(string $path): void {
    if (!is_readable($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        [$key, $value] = array_map('trim', explode('=', $line, 2) + ['', '']);
        if ($key === '') {
            continue;
        }

        if (getenv($key) !== false) {
            continue;
        }

        putenv("$key=$value");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    load_env_file($envFile);
}

function env(string $key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function env_bool(string $key, bool $default = false): bool {
    $value = strtolower(env($key, $default ? 'true' : 'false'));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function configure_session(): void {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
}

function app_log(string $message): void {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $line = sprintf("[%s] %s\n", date('Y-m-d H:i:s'), trim($message));
    @file_put_contents($logDir . '/app.log', $line, FILE_APPEND | LOCK_EX);
}

function smtp_config(): array {
    return [
        'host' => env('SMTP_HOST', ''),
        'port' => intval(env('SMTP_PORT', 587)),
        'user' => env('SMTP_USER', ''),
        'pass' => env('SMTP_PASS', ''),
        'secure' => env('SMTP_SECURE', 'tls'),
        'from' => env('SMTP_FROM_EMAIL', 'no-reply@your-cleaning-business.com'),
        'to' => env('SMTP_TO_EMAIL', 'info@your-cleaning-business.com'),
        'use_php_mail' => env_bool('SMTP_USE_PHP_MAIL', true),
    ];
}
