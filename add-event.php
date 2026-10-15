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

$pageTitle = 'New event';
require __DIR__ . '/partials/header.php';
?>

<section class="page-hero page-hero-compact">
    <div class="container">
        <a class="back-link" href="organiser-dashboard.php"><?= icon('arrow-left') ?> My events</a>
        <h1 class="display-sm">Create a <em>new event</em></h1>
        <p class="lead">It goes live as soon as you publish.</p>
    </div>
</section>

<div class="container section">
    <?php
    $formAction = 'add-event.php';
    $submitLabel = 'Publish event';
    require __DIR__ . '/partials/event-form.php';
    ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
