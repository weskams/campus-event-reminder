<?php
declare(strict_types=1);

/**
 * Sessions, CSRF protection, login throttling and accounts.
 *
 * A session holds at most one signed-in user in $_SESSION['user']:
 * ['role' => 'student'|'organiser', 'id' => int, 'name' => string].
 */

const PASSWORD_MIN_LENGTH = 10;
const PASSWORD_MAX_BYTES = 72; // bcrypt ignores anything longer

const LOGIN_WINDOW_MINUTES = 15;
const LOGIN_MAX_FAILURES_PER_EMAIL = 5;
const LOGIN_MAX_FAILURES_PER_IP = 30;

/** Database table and name column for each kind of account. */
const ACCOUNT_TABLES = [
    'organiser' => ['table' => 'organisers', 'name' => 'name'],
    'student' => ['table' => 'students', 'name' => 'full_name'],
];

// ---------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------

function start_secure_session(): void
{
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_name('campus_events');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    $idleLimit = (int) config('app.session_idle_minutes') * 60;
    $lastActivity = $_SESSION['last_activity'] ?? null;

    if (
        isset($_SESSION['user'])
        && is_int($lastActivity)
        && time() - $lastActivity > $idleLimit
    ) {
        logout_user();
        flash('info', 'Your session expired. Please log in again.');
    }

    $_SESSION['last_activity'] = time();
}

function current_user(): ?array
{
    $user = $_SESSION['user'] ?? null;

    return is_array($user) ? $user : null;
}

function current_student_id(): ?int
{
    $user = current_user();

    return ($user && $user['role'] === 'student') ? (int) $user['id'] : null;
}

function current_organiser_id(): ?int
{
    $user = current_user();

    return ($user && $user['role'] === 'organiser') ? (int) $user['id'] : null;
}

/** Redirect to the student login unless a student is signed in. */
function require_student(): int
{
    $studentId = current_student_id();

    if ($studentId === null) {
        flash('info', 'Please log in with your student account to continue.');
        redirect('student-login.php');
    }

    return $studentId;
}

/** Redirect to the organiser login unless an organiser is signed in. */
function require_organiser(): int
{
    $organiserId = current_organiser_id();

    if ($organiserId === null) {
        flash('info', 'Please log in with your organiser account to continue.');
        redirect('login.php');
    }

    return $organiserId;
}

function login_user(string $role, int $id, string $name): void
{
    // New session ID prevents session fixation; signing in as one role
    // always replaces any other role in this browser.
    session_regenerate_id(true);

    $_SESSION['user'] = ['role' => $role, 'id' => $id, 'name' => $name];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

// ---------------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------------

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

/** Stop unless this is a POST request with a valid CSRF token. */
function require_valid_post(): void
{
    if (!is_post()) {
        header('Allow: POST');
        render_error(405, 'Method not allowed', 'This action must be submitted from a form.');
    }

    $token = $_POST['csrf_token'] ?? '';

    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        render_error(
            403,
            'Form expired',
            'Your form has expired. Go back, refresh the page and try again.'
        );
    }
}

// ---------------------------------------------------------------------
// Login
// ---------------------------------------------------------------------

/** Lower-cased, validated email address, or null if invalid. */
function normalise_email(string $email): ?string
{
    $email = mb_strtolower(trim($email));

    return strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL)
        ? $email
        : null;
}

/** An error message if the password is unacceptable, otherwise null. */
function validate_password(string $password, string $confirmation): ?string
{
    if (mb_strlen($password) < PASSWORD_MIN_LENGTH) {
        return 'Use a password of at least ' . PASSWORD_MIN_LENGTH . ' characters.';
    }

    if (strlen($password) > PASSWORD_MAX_BYTES) {
        return 'Use a password of at most ' . PASSWORD_MAX_BYTES . ' characters.';
    }

    if ($password !== $confirmation) {
        return 'The passwords do not match.';
    }

    return null;
}

/**
 * Check a student's or organiser's credentials.
 *
 * Returns the account (id, name) on success, or an error message.
 */
function attempt_login(string $role, string $email, string $password): array|string
{
    $invalid = 'Invalid email or password.';
    $email = normalise_email($email);

    if ($email === null || $password === '') {
        return $invalid;
    }

    if (login_is_throttled($email)) {
        return 'Too many failed attempts. Please wait '
            . LOGIN_WINDOW_MINUTES . ' minutes and try again.';
    }

    ['table' => $table, 'name' => $nameColumn] = ACCOUNT_TABLES[$role];

    $stmt = db()->prepare(
        "SELECT id, $nameColumn AS name, password_hash
         FROM $table
         WHERE email = :email"
    );
    $stmt->execute(['email' => $email]);
    $account = $stmt->fetch();

    if (!$account) {
        // Spend the same time as a real check so response times
        // don't reveal which emails have accounts.
        password_hash($password, PASSWORD_DEFAULT);
        record_failed_login($email);

        return $invalid;
    }

    if (!password_verify($password, $account['password_hash'])) {
        record_failed_login($email);

        return $invalid;
    }

    if (password_needs_rehash($account['password_hash'], PASSWORD_DEFAULT)) {
        db()->prepare("UPDATE $table SET password_hash = :hash WHERE id = :id")
            ->execute([
                'hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $account['id'],
            ]);
    }

    clear_failed_logins($email);

    return ['id' => (int) $account['id'], 'name' => $account['name']];
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
}

function login_is_throttled(string $email): bool
{
    $since = 'NOW() - INTERVAL ' . LOGIN_WINDOW_MINUTES . ' MINUTE';

    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE email = :email AND attempted_at > $since"
    );
    $stmt->execute(['email' => $email]);

    if ((int) $stmt->fetchColumn() >= LOGIN_MAX_FAILURES_PER_EMAIL) {
        return true;
    }

    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE ip_address = :ip AND attempted_at > $since"
    );
    $stmt->execute(['ip' => client_ip()]);

    return (int) $stmt->fetchColumn() >= LOGIN_MAX_FAILURES_PER_IP;
}

function record_failed_login(string $email): void
{
    db()->prepare(
        'INSERT INTO login_attempts (email, ip_address) VALUES (:email, :ip)'
    )->execute(['email' => $email, 'ip' => client_ip()]);

    // Occasionally clear out old attempts so the table stays small.
    if (random_int(1, 50) === 1) {
        db()->exec(
            'DELETE FROM login_attempts WHERE attempted_at < NOW() - INTERVAL 1 DAY'
        );
    }
}

function clear_failed_logins(string $email): void
{
    db()->prepare('DELETE FROM login_attempts WHERE email = :email')
        ->execute(['email' => $email]);
}

// ---------------------------------------------------------------------
// Accounts
// ---------------------------------------------------------------------

/** @throws PDOException with a duplicate-key error if already registered */
function create_student(
    string $fullName,
    string $registrationNumber,
    string $email,
    string $password
): int {
    db()->prepare(
        'INSERT INTO students (full_name, registration_number, email, password_hash)
         VALUES (:full_name, :registration_number, :email, :password_hash)'
    )->execute([
        'full_name' => $fullName,
        'registration_number' => $registrationNumber,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    return (int) db()->lastInsertId();
}

/** @throws PDOException with a duplicate-key error if already registered */
function create_organiser(string $name, string $email, string $password): int
{
    db()->prepare(
        'INSERT INTO organisers (name, email, password_hash)
         VALUES (:name, :email, :password_hash)'
    )->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    return (int) db()->lastInsertId();
}

function count_organisers(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM organisers')->fetchColumn();
}
