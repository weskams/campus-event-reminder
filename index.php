<?php
require __DIR__ . '/includes/bootstrap.php';

$search = query_string('search');
$category = query_string('category', 30);

if (!in_array($category, EVENT_CATEGORIES, true)) {
    $category = '';
}

$filters = ['search' => $search, 'category' => $category];
$isFiltered = $search !== '' || $category !== '';

$pagination = paginate(count_upcoming_events($filters), current_page(), EVENTS_PER_PAGE);
$events = find_upcoming_events(
    $filters,
    EVENTS_PER_PAGE,
    $pagination['offset'],
    current_student_id()
);

$pageTitle = 'Upcoming events';
require __DIR__ . '/partials/header.php';
?>

<section class="page-intro">
    <h1>Upcoming campus events</h1>
    <p>Discover what's happening at Kabarak University and get reminded before it starts.</p>
</section>

<form class="event-filters" method="get" action="index.php" role="search">
    <div class="field">
        <label for="search">Search</label>
        <input type="search" id="search" name="search" maxlength="100"
               placeholder="Event name or venue" value="<?= e($search) ?>">
    </div>

    <div class="field">
        <label for="category">Category</label>
        <select id="category" name="category">
            <option value="">All categories</option>
            <?php foreach (EVENT_CATEGORIES as $option): ?>
                <option value="<?= e($option) ?>" <?= $option === $category ? 'selected' : '' ?>>
                    <?= e($option) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit">Filter</button>

    <?php if ($isFiltered): ?>
        <a href="index.php">Clear filters</a>
    <?php endif; ?>
</form>

<?php if (!$events): ?>
    <p class="empty-state">
        <?= $isFiltered
            ? 'No upcoming events match your filters.'
            : 'There are no upcoming events right now. Check back soon!' ?>
    </p>
<?php else: ?>
    <h2 class="result-count">
        <?= $pagination['total'] ?> upcoming event<?= $pagination['total'] === 1 ? '' : 's' ?>
    </h2>

    <div class="event-grid">
        <?php foreach ($events as $event): ?>
            <?php require __DIR__ . '/partials/event-card.php'; ?>
        <?php endforeach; ?>
    </div>

    <?php require __DIR__ . '/partials/pagination.php'; ?>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
