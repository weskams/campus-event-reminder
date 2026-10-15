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

<section class="page-hero">
    <div class="container">
        <p class="eyebrow"><span class="pulse" aria-hidden="true"></span> Live countdown</p>
        <h1 class="display-sm">Happening in the <em>next 24 hours</em></h1>
        <p class="lead">Last chance to register. These events start soon.</p>
    </div>
</section>

<section class="container section">
    <?php if (!$events): ?>
        <div class="empty-state">
            <span class="empty-state-icon"><?= icon('clock') ?></span>
            <h3>A quiet day on campus</h3>
            <p>No events are happening in the next 24 hours.</p>
            <a class="button button-secondary" href="index.php">See all upcoming events</a>
        </div>
    <?php else: ?>
        <div class="section-head">
            <h2 class="result-count">
                <?= $pagination['total'] ?> event<?= $pagination['total'] === 1 ? '' : 's' ?> coming up
            </h2>
            <a class="button-link" href="index.php">All upcoming events <?= icon('arrow-right') ?></a>
        </div>

        <div class="event-grid">
            <?php foreach ($events as $event): ?>
                <?php require __DIR__ . '/partials/event-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php require __DIR__ . '/partials/pagination.php'; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
