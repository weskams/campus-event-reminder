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

<p><a href="event-details.php?id=<?= (int) $id ?>">← Back to event</a></p>
<h1>Edit event</h1>

<?php
$formAction = 'edit-event.php?id=' . $id;
$submitLabel = 'Save changes';
require __DIR__ . '/partials/event-form.php';
?>

<?php require __DIR__ . '/partials/footer.php'; ?>
