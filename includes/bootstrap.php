<?php
declare(strict_types=1);

/**
 * Loaded at the top of every page: configuration, error handling,
 * security headers, a hardened session and the shared functions.
 */

define('APP_ROOT', dirname(__DIR__));

require APP_ROOT . '/includes/helpers.php';
require APP_ROOT . '/includes/db.php';
require APP_ROOT . '/includes/auth.php';
require APP_ROOT . '/includes/events.php';

date_default_timezone_set(config('app.timezone'));

$isDevelopment = config('app.env') === 'development';

error_reporting(E_ALL);
ini_set('display_errors', $isDevelopment ? '1' : '0');
ini_set('log_errors', '1');

// Treat warnings and notices as bugs instead of silently continuing.
// Deprecations are only logged, so a PHP upgrade can't take the site down.
set_error_handler(
    function (int $severity, string $message, string $file, int $line): bool {
        if (
            !(error_reporting() & $severity)
            || ($severity & (E_DEPRECATED | E_USER_DEPRECATED))
        ) {
            return false;
        }

        throw new ErrorException($message, 0, $severity, $file, $line);
    }
);

set_exception_handler(function (Throwable $e) use ($isDevelopment): void {
    error_log((string) $e);

    // Discard any half-rendered page before showing the error page.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    render_error(
        500,
        'Something went wrong',
        $isDevelopment
            ? $e->getMessage()
            : 'An unexpected error occurred. Please try again later.'
    );
});

// Buffer output so redirects and error pages work from anywhere.
ob_start();

send_security_headers();
start_secure_session();
