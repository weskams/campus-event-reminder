<?php
require __DIR__ . '/includes/bootstrap.php';

$filters = ['within_hours' => 24];

$pagination = paginate(count_upcoming_events($filters), current_page(), EVENTS_PER_PAGE);
$events = find_upcoming_events(
    $filters,
    EVENTS_PER_PAGE,
    $pagination['offset'],
    current_student_id()
);

$pageTitle = 'Happening in the next 24 hours';
require __DIR__ . '/partials/header.php';
?>

<section class="page-intro">
    <h1>Happening in the next 24 hours</h1>
    <p><a href="index.php">See all upcoming events</a></p>
</section>

<?php if (!$events): ?>
    <p class="empty-state">No events are happening in the next 24 hours.</p>
<?php else: ?>
    <h2 class="result-count">
        <?= $pagination['total'] ?> event<?= $pagination['total'] === 1 ? '' : 's' ?> coming up
    </h2>

    <div class="event-grid">
        <?php foreach ($events as $event): ?>
            <?php require __DIR__ . '/partials/event-card.php'; ?>
        <?php endforeach; ?>
    </div>

    <?php require __DIR__ . '/partials/pagination.php'; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
