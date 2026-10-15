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
    <section class="container error-page">
        <p class="error-code"><?= $status ?></p>
        <h1 class="display-sm"><?= e($title) ?></h1>
        <p class="lead"><?= e($message) ?></p>
        <p><a class="button" href="index.php"><?= icon('arrow-left') ?> Back to events</a></p>
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

    $days = intdiv($hours, 24);

    return $days === 1 ? 'Starts tomorrow' : "Starts in $days days";
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

// ---------------------------------------------------------------------
// Presentation
// ---------------------------------------------------------------------

/** CSS-safe category name, e.g. "Academic" -> "academic". */
function category_slug(string $category): string
{
    return preg_replace('/[^a-z0-9]+/', '-', strtolower($category)) ?? 'general';
}

/** ['weekday' => 'Wed', 'day' => '14', 'month' => 'Oct'] */
function date_parts(string $dateTime): array
{
    $date = new DateTimeImmutable($dateTime);

    return [
        'weekday' => $date->format('D'),
        'day' => $date->format('j'),
        'month' => $date->format('M'),
    ];
}

/** True if the event starts within the next $hours hours. */
function starts_within(string $startsAt, int $hours): bool
{
    $seconds = (new DateTimeImmutable($startsAt))->getTimestamp() - time();

    return $seconds > 0 && $seconds <= $hours * 3600;
}

/** Up to two initials for an avatar, e.g. "Jane Wanjiru" -> "JW". */
function initials(string $name): string
{
    $initials = '';

    foreach (preg_split('/\s+/', trim($name)) ?: [] as $word) {
        if ($word !== '' && mb_strlen($initials) < 2) {
            $initials .= mb_strtoupper(mb_substr($word, 0, 1));
        }
    }

    return $initials !== '' ? $initials : '?';
}

/** First name for greetings, skipping titles: "Dr. Grace Chebet" -> "Grace". */
function first_name(string $name): string
{
    $words = preg_split('/\s+/', trim($name)) ?: [];

    foreach ($words as $word) {
        if (!preg_match('/^(dr|prof|mr|mrs|ms|miss|rev|eng|hon|sir)\.?$/i', $word)) {
            return $word;
        }
    }

    return $words[0] ?? $name;
}

/** Time-of-day greeting for dashboards. */
function greeting(): string
{
    $hour = (int) date('G');

    return match (true) {
        $hour < 12 => 'Good morning',
        $hour < 17 => 'Good afternoon',
        default => 'Good evening',
    };
}

/** An inline SVG icon (decorative, hidden from screen readers). */
function icon(string $name, string $class = ''): string
{
    static $paths = [
        'calendar' => '<rect x="3" y="4.5" width="18" height="16" rx="2.5"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'users' => '<path d="M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20"/><circle cx="10" cy="8" r="3.5"/><path d="M20 20v-1.5a3.5 3.5 0 0 0-2.5-3.35M15.5 4.6a3.5 3.5 0 0 1 0 6.8"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'bell' => '<path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'arrow-right' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5M11 18l-6-6 6-6"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'logout' => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3"/>',
        'sparkle' => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z"/><path d="M19 15.5l.7 1.8 1.8.7-1.8.7-.7 1.8-.7-1.8-1.8-.7 1.8-.7z"/>',
        'alert' => '<path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"/><path d="M12 9v4M12 17h.01"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'ban' => '<circle cx="12" cy="12" r="9"/><path d="m5.7 5.7 12.6 12.6"/>',
        'shield' => '<path d="M12 21s8-3.5 8-10V5l-8-3-8 3v6c0 6.5 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'zap' => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
    ];

    return '<svg class="icon' . ($class !== '' ? ' ' . e($class) : '') . '" viewBox="0 0 24 24"'
        . ' fill="none" stroke="currentColor" stroke-width="1.75"'
        . ' stroke-linecap="round" stroke-linejoin="round"'
        . ' aria-hidden="true" focusable="false">'
        . ($paths[$name] ?? '') . '</svg>';
}

/** The app's logo mark. */
function brand_mark(): string
{
    return '<svg class="brand-mark" viewBox="0 0 40 40" aria-hidden="true" focusable="false">'
        . '<rect width="40" height="40" rx="11" class="brand-mark-bg"/>'
        . '<path d="M12 14.5h16M12 14.5v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-12a2 2 0 0 0-2-2H14a2 2 0 0 0-2 2zM16 10.5v4M24 10.5v4"'
        . ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>'
        . '<path d="M20 18.5l1.1 2.6 2.6 1.1-2.6 1.1L20 26l-1.1-2.7-2.6-1.1 2.6-1.1z" class="brand-mark-spark"/>'
        . '</svg>';
}
