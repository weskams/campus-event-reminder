# Campus Event Reminder

A web app for Kabarak University. Organisers publish campus events, and students
register for them and get reminded an hour before each one starts.

Built with plain PHP 8.1+ and MySQL 8 / MariaDB 10.4+, with no framework or
build step.

## Setup (XAMPP)

1. Copy this folder into `C:\xampp\htdocs\` (Windows) or
   `/Applications/XAMPP/htdocs/` (macOS).
2. Start **Apache** and **MySQL** from the XAMPP control panel.
3. Open phpMyAdmin → **Import** → choose `database/schema.sql` → **Go**.
   This creates the `campus_event_reminder` database. It drops existing tables.
4. If your database login isn't `root` with no password, copy
   `config/config.local.example.php` to `config/config.local.php` and edit it.
   Set `'env' => 'development'` there to see error details while coding.
5. Visit `http://localhost/campus-event-reminder/create-organiser.php` and create
   the first organiser account. **Do this straight away on any server you deploy
   to**: until the first organiser exists, that page is open to anyone.
6. Optional: import `database/demo-events.sql` to load sample events.

## How it works

| Who | Can |
|---|---|
| Anyone | Browse, search and filter upcoming events; see the next 24 hours |
| Students | Sign up, register/unregister for events, see due reminders on their dashboard |
| Organisers | Create, edit and cancel **their own** events, see who registered, add other organisers |

When a student registers, a reminder is scheduled for one hour before the event.
If an organiser changes the start time, reminders are rescheduled. If the event
is cancelled, its reminders are withdrawn.

## Project layout

```
*.php                  Pages (one file per URL)
includes/bootstrap.php Loaded first by every page: config, errors, session, headers
includes/helpers.php   Escaping, input, redirects, flash messages, formatting
includes/db.php        PDO connection and transactions
includes/auth.php      Login, sessions, CSRF, throttling, accounts
includes/events.php    All event / registration / reminder queries
partials/              Shared HTML: header, footer, event card, forms, pagination
config/                Settings (real credentials go in config.local.php, git-ignored)
database/              Schema and demo data
assets/css/style.css   Stylesheet
```

Every page follows the same pattern:

```php
require __DIR__ . '/includes/bootstrap.php';
// 1. check access (require_student() / require_organiser())
// 2. handle POST (require_valid_post(), validate, save, flash, redirect)
// 3. load data
$pageTitle = '...';
require __DIR__ . '/partials/header.php';
// 4. HTML, escaping every value with e()
require __DIR__ . '/partials/footer.php';
```

## Security notes

- All SQL uses prepared statements, and all output is escaped with `e()`.
- Every POST form includes `<?= csrf_field() ?>` and is checked with
  `require_valid_post()`.
- Sessions use HttpOnly + SameSite cookies, a fresh ID on login, and a 2-hour
  idle timeout.
- Failed logins are throttled: 5 per email or 30 per IP address in 15 minutes.
- Organisers can only change events they created.
- `config/`, `includes/`, `partials/` and `database/` are blocked from the web
  by `.htaccess`. On Nginx, add equivalent `deny` rules.
- Pages send a Content-Security-Policy that allows only same-origin scripts and
  styles (plus Google Fonts). Don't use inline `style=""` or `<script>` blocks.
  Put them in `assets/` instead.
