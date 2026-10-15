<?php
declare(strict_types=1);

/**
 * Application settings.
 *
 * Do not put real credentials here. Override values per machine in
 * config/config.local.php (copy config.local.example.php) or with
 * environment variables.
 */

$defaults = [
    'app' => [
        // "development" shows error details; anything else hides them.
        'env' => getenv('APP_ENV') ?: 'production',
        'timezone' => getenv('APP_TIMEZONE') ?: 'Africa/Nairobi',
        'session_idle_minutes' => 120,
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int) (getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'campus_event_reminder',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ],
];

$localFile = __DIR__ . '/config.local.php';

return is_file($localFile)
    ? array_replace_recursive($defaults, require $localFile)
    : $defaults;
