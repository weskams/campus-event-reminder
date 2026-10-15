<?php
require __DIR__ . '/includes/bootstrap.php';

$organiserId = require_organiser();
$id = query_id();

if ($id === null) {
    not_found();
}

$event = load_owned_event($id, $organiserId);

$form = event_form_from_row($event);
$errors = [];

if (is_post()) {
    require_valid_post();

    $form = read_event_form();
    [$errors, $startsAt] = validate_event($form);

    if (!$errors) {
        $timeChanged = update_event($event, $form, $startsAt);

        flash(
            'success',
            $timeChanged
                ? 'Event updated. Registered students will be reminded at the new time.'
                : 'Event updated.'
        );
        redirect('event-details.php?id=' . $id);
    }
}

$pageTitle = 'Edit ' . $event['title'];
require __DIR__ . '/partials/header.php';
?>

<section class="page-hero page-hero-compact">
    <div class="container">
        <a class="back-link" href="event-details.php?id=<?= (int) $id ?>"><?= icon('arrow-left') ?> Back to event</a>
        <h1 class="display-sm">Edit <em><?= e($event['title']) ?></em></h1>
        <p class="lead">
            <?= (int) $event['attendee_count'] ?> registered.
            Changing the time automatically reschedules their reminders.
        </p>
    </div>
</section>

<div class="container section">
    <?php
    $formAction = 'edit-event.php?id=' . $id;
    $submitLabel = 'Save changes';
    require __DIR__ . '/partials/event-form.php';
    ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
