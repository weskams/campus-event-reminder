<?php
declare(strict_types=1);

/** The shared database connection, opened on first use. */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $settings = config('db');

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $settings['host'],
        $settings['port'],
        $settings['name']
    );

    try {
        $pdo = new PDO($dsn, $settings['user'], $settings['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        // Re-throw without the stack trace, which would contain the password.
        throw new RuntimeException(
            'Database connection failed: ' . $e->getMessage()
        );
    }

    // Make MySQL's NOW() use the same time zone as PHP.
    $pdo->exec(
        'SET time_zone = ' . $pdo->quote((new DateTimeImmutable())->format('P'))
    );

    return $pdo;
}

/** Run $callback inside a transaction, rolling back if it throws. */
function transaction(callable $callback): mixed
{
    $pdo = db();
    $pdo->beginTransaction();

    try {
        $result = $callback($pdo);
        $pdo->commit();

        return $result;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        throw $e;
    }
}

function is_duplicate_key(PDOException $e): bool
{
    return (int) ($e->errorInfo[1] ?? 0) === 1062;
}
