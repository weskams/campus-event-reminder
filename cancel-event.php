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

$pageTitle = 'Cancel ' . $event['title'];
require __DIR__ . '/partials/header.php';
?>

<section class="confirm-card">
    <h1>Cancel this event?</h1>

    <h2><?= e($event['title']) ?></h2>

    <dl class="event-meta">
        <dt>When</dt>
        <dd>
            <?= e(format_date($event['starts_at'])) ?>,
            <?= e(format_time($event['starts_at'])) ?>
        </dd>

        <dt>Where</dt>
        <dd><?= e($event['venue']) ?></dd>
    </dl>

    <?php if ((int) $event['attendee_count'] > 0): ?>
        <p class="alert alert-warning">
            <?= (int) $event['attendee_count'] ?> student(s) have registered.
            Their reminders will be withdrawn.
        </p>
    <?php endif; ?>

    <p>This cannot be undone.</p>

    <form method="post" action="cancel-event.php?id=<?= (int) $id ?>" class="actions">
        <?= csrf_field() ?>
        <button type="submit" class="button-danger">Yes, cancel event</button>
        <a href="event-details.php?id=<?= (int) $id ?>">No, keep it</a>
    </form>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
