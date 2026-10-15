<?php
/**
 * Page header and navigation. Set $pageTitle before including.
 */

defined('APP_ROOT') || exit;

$user = current_user();
$dueReminderCount = 0;

if ($user !== null && $user['role'] === 'student') {
    try {
        $dueReminderCount = count_due_reminders((int) $user['id']);
    } catch (Throwable $e) {
        // The badge is optional; never let it break the page.
        error_log((string) $e);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(isset($pageTitle) ? $pageTitle . ' · Campus Event Reminder' : 'Campus Event Reminder') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header">
        <a class="brand" href="index.php">Campus Event Reminder</a>

        <nav class="site-nav" aria-label="Main">
            <ul>
                <li><?= nav_link('index.php', 'Events') ?></li>
                <li><?= nav_link('upcoming-soon.php', 'Next 24 hours') ?></li>

                <?php if ($user === null): ?>
                    <li><?= nav_link('student-login.php', 'Student login') ?></li>
                    <li><?= nav_link('student-register.php', 'Sign up') ?></li>
                    <li><?= nav_link('login.php', 'Organisers') ?></li>

                <?php elseif ($user['role'] === 'student'): ?>
                    <li>
                        <?= nav_link(
                            'student-dashboard.php',
                            'My dashboard',
                            $dueReminderCount > 0
                                ? ' <span class="badge" aria-label="' . $dueReminderCount . ' reminders">' . $dueReminderCount . '</span>'
                                : ''
                        ) ?>
                    </li>

                <?php else: ?>
                    <li><?= nav_link('organiser-dashboard.php', 'My events') ?></li>
                    <li><?= nav_link('add-event.php', 'Add event') ?></li>
                <?php endif; ?>

                <?php if ($user !== null): ?>
                    <li>
                        <form class="logout-form" method="post" action="logout.php">
                            <?= csrf_field() ?>
                            <button type="submit">Log out (<?= e($user['name']) ?>)</button>
                        </form>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main id="main" class="container">
        <?php foreach (take_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>" role="status">
                <?= e($flash['message']) ?>
            </div>
        <?php endforeach; ?>
