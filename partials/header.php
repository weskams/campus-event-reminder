<?php
/**
 * Page header and navigation. Set $pageTitle before including.
 * Pages wrap their own content in .container so heroes can go full-width.
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

$flashIcons = ['success' => 'check', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(isset($pageTitle) ? $pageTitle . ' · Kabarak Campus Events' : 'Kabarak Campus Events') ?></title>
    <meta name="description" content="Discover campus events at Kabarak University, register in one tap and get reminded before they start.">
    <meta name="theme-color" content="#7a1f32" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#141110" media="(prefers-color-scheme: dark)">
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..600&family=Inter:wght@400..700&display=swap">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header" data-header>
        <div class="container header-inner">
            <a class="brand" href="index.php">
                <?= brand_mark() ?>
                <span class="brand-text">
                    <span class="brand-name">Kabarak</span>
                    <span class="brand-sub">Campus Events</span>
                </span>
            </a>

            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" data-nav-toggle>
                <?= icon('menu') ?>
                <span class="visually-hidden">Menu</span>
            </button>

            <nav id="site-nav" class="site-nav" aria-label="Main">
                <ul class="nav-links">
                    <li><?= nav_link('index.php', 'Explore') ?></li>
                    <li><?= nav_link('upcoming-soon.php', 'Next 24 hours') ?></li>

                    <?php if ($user !== null && $user['role'] === 'student'): ?>
                        <li>
                            <?= nav_link(
                                'student-dashboard.php',
                                'My dashboard',
                                $dueReminderCount > 0
                                    ? ' <span class="badge" aria-label="' . $dueReminderCount . ' reminders">' . $dueReminderCount . '</span>'
                                    : ''
                            ) ?>
                        </li>
                    <?php elseif ($user !== null): ?>
                        <li><?= nav_link('organiser-dashboard.php', 'My events') ?></li>
                    <?php endif; ?>
                </ul>

                <div class="nav-account">
                    <?php if ($user === null): ?>
                        <a class="nav-quiet" href="login.php">For organisers</a>
                        <a class="button button-ghost button-sm" href="student-login.php">Log in</a>
                        <a class="button button-sm" href="student-register.php">Sign up</a>
                    <?php else: ?>
                        <?php if ($user['role'] === 'organiser'): ?>
                            <a class="button button-sm" href="add-event.php"><?= icon('plus') ?> New event</a>
                        <?php endif; ?>

                        <span class="user-chip">
                            <span class="avatar" aria-hidden="true"><?= e(initials($user['name'])) ?></span>
                            <span class="user-chip-text">
                                <span class="user-chip-name"><?= e($user['name']) ?></span>
                                <span class="user-chip-role"><?= $user['role'] === 'student' ? 'Student' : 'Organiser' ?></span>
                            </span>
                        </span>

                        <form class="logout-form" method="post" action="logout.php">
                            <?= csrf_field() ?>
                            <button type="submit" class="button button-ghost button-sm button-icon" title="Log out">
                                <?= icon('logout') ?>
                                <span class="logout-label">Log out</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <div class="toasts" aria-live="polite">
        <?php foreach (take_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> toast" role="status" data-toast>
                <?= icon($flashIcons[$flash['type']] ?? 'info', 'alert-icon') ?>
                <p><?= e($flash['message']) ?></p>
                <button type="button" class="toast-close" data-toast-close>
                    <?= icon('x') ?>
                    <span class="visually-hidden">Dismiss</span>
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <main id="main" class="site-main">
