<?php
require __DIR__ . '/includes/bootstrap.php';

$organiserId = require_organiser();
$id = query_id();

if ($id === null) {
    not_found();
}

$event = load_owned_event($id, $organiserId);

if (is_post()) {
    require_valid_post();

    cancel_event($id);
    flash('success', '"' . $event['title'] . '" has been cancelled and its reminders withdrawn.');
    redirect('organiser-dashboard.php');
}

$attendeeCount = (int) $event['attendee_count'];

$pageTitle = 'Cancel ' . $event['title'];
require __DIR__ . '/partials/header.php';
?>

<div class="container section">
    <section class="card confirm-card">
        <span class="confirm-icon"><?= icon('alert') ?></span>
        <h1 class="display-sm">Cancel this event?</h1>
        <p class="muted">This cannot be undone.</p>

        <div class="confirm-event cat-<?= e(category_slug($event['category'])) ?>">
            <p class="confirm-event-title"><?= e($event['title']) ?></p>
            <p class="facts-inline">
                <span><?= icon('calendar') ?> <?= e(format_date($event['starts_at'])) ?>, <?= e(format_time($event['starts_at'])) ?></span>
                <span><?= icon('pin') ?> <?= e($event['venue']) ?></span>
            </p>
        </div>

        <?php if ($attendeeCount > 0): ?>
            <div class="alert alert-warning">
                <?= icon('users', 'alert-icon') ?>
                <p>
                    <?= $attendeeCount ?> student<?= $attendeeCount === 1 ? ' has' : 's have' ?> registered.
                    Their reminders will be withdrawn.
                </p>
            </div>
        <?php endif; ?>

        <form method="post" action="cancel-event.php?id=<?= (int) $id ?>" class="confirm-actions" data-submit-once>
            <?= csrf_field() ?>
            <a class="button button-secondary" href="event-details.php?id=<?= (int) $id ?>">No, keep it</a>
            <button type="submit" class="button button-danger">Yes, cancel event</button>
        </form>
    </section>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
