<?php
require __DIR__ . '/includes/bootstrap.php';

$studentId = require_student();

$reminders = find_due_reminders($studentId);
$myEvents = find_student_events($studentId);

$pageTitle = 'My dashboard';
require __DIR__ . '/partials/header.php';
?>

<section class="page-intro">
    <h1>Welcome, <?= e(current_user()['name']) ?></h1>
    <p>Your reminders and the events you've registered for. <a href="index.php">Browse more events</a>.</p>
</section>

<section class="dashboard-section">
    <h2>Reminders</h2>

    <?php if (!$reminders): ?>
        <p class="empty-state">No reminders right now. You'll see one here an hour before each event you've registered for.</p>
    <?php else: ?>
        <ul class="reminder-list">
            <?php foreach ($reminders as $reminder): ?>
                <li class="reminder">
                    <div>
                        <p class="reminder-when"><?= e(starts_in($reminder['starts_at'])) ?></p>
                        <h3>
                            <a href="event-details.php?id=<?= (int) $reminder['event_id'] ?>">
                                <?= e($reminder['title']) ?>
                            </a>
                        </h3>
                        <p>
                            <?= e(format_time($reminder['starts_at'])) ?>
                            at <?= e($reminder['venue']) ?>
                        </p>
                    </div>

                    <form method="post" action="mark-reminder-read.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="reminder_id" value="<?= (int) $reminder['reminder_id'] ?>">
                        <button type="submit" class="button-secondary">Dismiss</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<section class="dashboard-section">
    <h2>My upcoming events</h2>

    <?php if (!$myEvents): ?>
        <p class="empty-state">
            You haven't registered for any upcoming events.
            <a href="index.php">Find something to attend</a>.
        </p>
    <?php else: ?>
        <div class="event-grid">
            <?php foreach ($myEvents as $event): ?>
                <?php require __DIR__ . '/partials/event-card.php'; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
