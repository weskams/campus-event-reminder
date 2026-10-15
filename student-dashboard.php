<?php
require __DIR__ . '/includes/bootstrap.php';

$studentId = require_student();

$reminders = find_due_reminders($studentId);
$myEvents = find_student_events($studentId);
$firstName = first_name(current_user()['name']);

$pageTitle = 'My dashboard';
require __DIR__ . '/partials/header.php';
?>

<section class="page-hero page-hero-compact">
    <div class="container dashboard-head">
        <div>
            <p class="eyebrow"><?= e(date('l, j F')) ?></p>
            <h1 class="display-sm"><?= e(greeting()) ?>, <em><?= e($firstName) ?></em>.</h1>
            <p class="lead">
                <?php if ($reminders): ?>
                    You have <?= count($reminders) ?> event<?= count($reminders) === 1 ? '' : 's' ?> starting soon.
                <?php elseif ($myEvents): ?>
                    You're all set. We'll remind you before each event.
                <?php else: ?>
                    Find something worth showing up for.
                <?php endif; ?>
            </p>
        </div>

        <dl class="stat-row">
            <div class="stat">
                <dt><?= icon('bell') ?> Reminders</dt>
                <dd><?= count($reminders) ?></dd>
            </div>
            <div class="stat">
                <dt><?= icon('calendar') ?> Going</dt>
                <dd><?= count($myEvents) ?></dd>
            </div>
        </dl>
    </div>
</section>

<div class="container">
    <section class="section">
        <div class="section-head">
            <h2>Reminders</h2>
        </div>

        <?php if (!$reminders): ?>
            <div class="empty-state empty-state-inline">
                <span class="empty-state-icon"><?= icon('bell') ?></span>
                <div>
                    <h3>No reminders right now</h3>
                    <p>You'll see one here an hour before each event you've registered for.</p>
                </div>
            </div>
        <?php else: ?>
            <ul class="reminder-list">
                <?php foreach ($reminders as $reminder): ?>
                    <li class="reminder">
                        <span class="reminder-icon"><?= icon('bell') ?></span>

                        <div class="reminder-body">
                            <p class="reminder-when" data-countdown="<?= e(format_iso($reminder['starts_at'])) ?>">
                                <?= e(starts_in($reminder['starts_at'])) ?>
                            </p>
                            <h3>
                                <a href="event-details.php?id=<?= (int) $reminder['event_id'] ?>">
                                    <?= e($reminder['title']) ?>
                                </a>
                            </h3>
                            <p class="facts-inline">
                                <span><?= icon('clock') ?> <?= e(format_time($reminder['starts_at'])) ?></span>
                                <span><?= icon('pin') ?> <?= e($reminder['venue']) ?></span>
                            </p>
                        </div>

                        <form method="post" action="mark-reminder-read.php">
                            <?= csrf_field() ?>
                            <input type="hidden" name="reminder_id" value="<?= (int) $reminder['reminder_id'] ?>">
                            <button type="submit" class="button button-ghost button-sm"><?= icon('check') ?> Dismiss</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="section">
        <div class="section-head">
            <h2>My upcoming events</h2>
            <a class="button-link" href="index.php">Browse events <?= icon('arrow-right') ?></a>
        </div>

        <?php if (!$myEvents): ?>
            <div class="empty-state">
                <span class="empty-state-icon"><?= icon('calendar') ?></span>
                <h3>Your calendar is wide open</h3>
                <p>You haven't registered for any upcoming events.</p>
                <a class="button" href="index.php">Find something to attend <?= icon('arrow-right') ?></a>
            </div>
        <?php else: ?>
            <div class="event-grid">
                <?php foreach ($myEvents as $event): ?>
                    <?php require __DIR__ . '/partials/event-card.php'; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
