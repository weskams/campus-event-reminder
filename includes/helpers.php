<?php
declare(strict_types=1);

/**
 * General helpers: configuration, escaping, request input, redirects,
 * flash messages, formatting and error pages.
 */

const EVENT_CATEGORIES = ['General', 'Academic', 'Sports', 'Social'];
const EVENTS_PER_PAGE = 12;

function config(string $key): mixed
{
    static $config = null;
    $config ??= require APP_ROOT . '/config/config.php';

    $value = $config;

    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            throw new RuntimeException("Missing config key: $key");
        }

        $value = $value[$part];
    }

    return $value;
}

/** Escape a value for safe output in HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// ---------------------------------------------------------------------
// Request input
// ---------------------------------------------------------------------

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/** A trimmed query-string value, or "" if missing or not a string. */
function query_string(string $key, int $maxLength = 100): string
{
    $value = $_GET[$key] ?? '';

    return is_string($value) ? mb_substr(trim($value), 0, $maxLength) : '';
}

/** A trimmed POST value, or "" if missing or not a string. */
function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';

    return is_string($value) ? trim($value) : '';
}

/** An untrimmed POST value, for passwords. */
function post_raw(string $key): string
{
    $value = $_POST[$key] ?? '';

    return is_string($value) ? $value : '';
}

function query_id(string $key = 'id'): ?int
{
    $id = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    return is_int($id) ? $id : null;
}

function post_id(string $key): ?int
{
    $id = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1],
    ]);

    return is_int($id) ? $id : null;
}

function current_page(): int
{
    $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 10000],
    ]);

    return is_int($page) ? $page : 1;
}

/** Escape LIKE wildcards so user input is matched literally. */
function like_escape(string $value): string
{
    return addcslashes($value, '\\%_');
}

// ---------------------------------------------------------------------
// Responses
// ---------------------------------------------------------------------

function redirect(string $path): never
{
    header('Location: ' . $path, true, 303);
    exit;
}

/**
 * Only allow redirects back to one of our own pages, never to an
 * arbitrary URL supplied by the browser.
 */
function safe_return_path(string $path, string $fallback): string
{
    return preg_match('/^[a-z0-9-]+\.php(\?[A-Za-z0-9_=&%+.-]*)?$/', $path)
        ? $path
        : $fallback;
}

/** The current page's URL with some query parameters changed. */
function current_url_with(array $changes = []): string
{
    $query = array_filter(
        array_merge($_GET, $changes),
        fn($value) => $value !== null && $value !== ''
    );

    $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');

    return $script . ($query ? '?' . http_build_query($query) : '');
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return is_array($messages) ? $messages : [];
}

function render_error(int $status, string $title, string $message): never
{
    http_response_code($status);
    $pageTitle = $title;

    require APP_ROOT . '/partials/header.php';
    ?>
    <section class="error-page">
        <h1><?= e($title) ?></h1>
        <p><?= e($message) ?></p>
        <p><a href="index.php">Back to events</a></p>
    </section>
    <?php
    require APP_ROOT . '/partials/footer.php';
    exit;
}

function not_found(
    string $message = 'The page you are looking for does not exist or is no longer available.'
): never {
    render_error(404, 'Not found', $message);
}

function send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header(
        "Content-Security-Policy: default-src 'self'; "
        . "img-src 'self' data:; "
        . "style-src 'self' https://fonts.googleapis.com; "
        . "font-src 'self' https://fonts.gstatic.com; "
        . "form-action 'self'; frame-ancestors 'none'; "
        . "base-uri 'self'; object-src 'none'"
    );

    if (is_https()) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

// ---------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------

function paginate(int $total, int $page, int $perPage): array
{
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);

    return [
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'offset' => ($page - 1) * $perPage,
    ];
}

// ---------------------------------------------------------------------
// Formatting
// ---------------------------------------------------------------------

/** e.g. "Wed, 14 Oct 2026" */
function format_date(string $dateTime): string
{
    return (new DateTimeImmutable($dateTime))->format('D, j M Y');
}

/** e.g. "2:30 PM" */
function format_time(string $dateTime): string
{
    return (new DateTimeImmutable($dateTime))->format('g:i A');
}

/** Machine-readable date for <time datetime="..."> */
function format_iso(string $dateTime): string
{
    return (new DateTimeImmutable($dateTime))->format(DATE_ATOM);
}

/** e.g. "Starts in 45 minutes" */
function starts_in(string $startsAt): string
{
    $seconds = (new DateTimeImmutable($startsAt))->getTimestamp() - time();

    if ($seconds <= 0) {
        return 'Starting now';
    }

    $minutes = (int) ceil($seconds / 60);

    if ($minutes < 60) {
        return "Starts in $minutes minute" . ($minutes === 1 ? '' : 's');
    }

    $hours = intdiv($minutes, 60);

    if ($hours < 24) {
        return "Starts in $hours hour" . ($hours === 1 ? '' : 's');
    }

    return 'Starts ' . format_date($startsAt);
}

function excerpt(string $text, int $length = 160): string
{
    return mb_strimwidth($text, 0, $length, '…');
}

/** A nav link that marks itself as the current page. */
function nav_link(string $href, string $label, string $extra = ''): string
{
    $isCurrent = basename($_SERVER['SCRIPT_NAME'] ?? '') === $href;

    return '<a href="' . e($href) . '"'
        . ($isCurrent ? ' aria-current="page"' : '')
        . '>' . e($label) . $extra . '</a>';
}
