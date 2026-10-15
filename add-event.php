<?php
require __DIR__ . '/includes/bootstrap.php';

$organiserId = require_organiser();

$form = empty_event_form();
$errors = [];

if (is_post()) {
    require_valid_post();

    $form = read_event_form();
    [$errors, $startsAt] = validate_event($form);

    if (!$errors) {
        $eventId = create_event($organiserId, $form, $startsAt);
        flash('success', 'Your event has been published.');
        redirect('event-details.php?id=' . $eventId);
    }
}

$pageTitle = 'Add an event';
require __DIR__ . '/partials/header.php';
?>

<p><a href="organiser-dashboard.php">← Back to my events</a></p>
<h1>Add a campus event</h1>

<?php
$formAction = 'add-event.php';
$submitLabel = 'Publish event';
require __DIR__ . '/partials/event-form.php';
?>

<?php require __DIR__ . '/partials/footer.php'; ?>
