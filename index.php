<?php
require __DIR__ . '/includes/bootstrap.php';

$search = query_string('search');
$category = query_string('category', 30);

if (!in_array($category, EVENT_CATEGORIES, true)) {
    $category = '';
}

$filters = ['search' => $search, 'category' => $category];
$isFiltered = $search !== '' || $category !== '';
$studentId = current_student_id();

$pagination = paginate(count_upcoming_events($filters), current_page(), EVENTS_PER_PAGE);
$events = find_upcoming_events($filters, EVENTS_PER_PAGE, $pagination['offset'], $studentId);

// Headline numbers ignore the filters; the "next up" spotlight is only
// shown while browsing everything.
$upcomingTotal = $isFiltered ? count_upcoming_events([]) : $pagination['total'];
$next24Total = count_upcoming_events(['within_hours' => 24]);
$nextUp = match (true) {
    $isFiltered => null,
    $pagination['page'] === 1 => $events[0] ?? null,
    default => find_upcoming_events([], 1, 0, $studentId)[0] ?? null,
};

/** Link to a category chip, keeping the current search. */
$chipUrl = fn(string $value): string => 'index.php' . (
    ($query = http_build_query(array_filter(['search' => $search, 'category' => $value])))
        ? '?' . $query
        : ''
);

$pageTitle = 'Discover campus events';
require __DIR__ . '/partials/header.php';
?>

<section class="hero">
    <div class="hero-backdrop" aria-hidden="true"></div>

    <div class="container hero-inner">
        <div class="hero-copy">
            <p class="eyebrow"><?= icon('sparkle') ?> Kabarak University · Campus life</p>
            <h1 class="display">Never miss a <em>moment</em> on campus.</h1>
            <p class="lead">
                Talks, matches, socials and workshops, all in one place.
                Register in one tap and we'll remind you an hour before it starts.
            </p>

            <form class="search-bar" method="get" action="index.php" role="search">
                <?= icon('search', 'search-bar-icon') ?>
                <label for="search" class="visually-hidden">Search events</label>
                <input type="search" id="search" name="search" maxlength="100"
                       placeholder="Search by event or venue…" value="<?= e($search) ?>">
                <?php if ($category !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($category) ?>">
                <?php endif; ?>
                <button type="submit" class="button">Search</button>
            </form>

            <dl class="hero-stats">
                <div>
                    <dt>Upcoming</dt>
                    <dd><?= $upcomingTotal ?></dd>
                </div>
                <div>
                    <dt>Next 24 hours</dt>
                    <dd><a href="upcoming-soon.php"><?= $next24Total ?></a></dd>
                </div>
                <div>
                    <dt>Categories</dt>
                    <dd><?= count(EVENT_CATEGORIES) ?></dd>
                </div>
            </dl>
        </div>

        <?php if ($nextUp !== null): ?>
            <?php $nextDate = date_parts($nextUp['starts_at']); ?>
            <a class="spotlight cat-<?= e(category_slug($nextUp['category'])) ?>" href="event-details.php?id=<?= (int) $nextUp['id'] ?>">
                <span class="spotlight-label"><span class="pulse" aria-hidden="true"></span> Next up</span>
                <span class="poster-glyph" aria-hidden="true"><?= e(mb_substr($nextUp['category'], 0, 1)) ?></span>

                <span class="spotlight-body">
                    <span class="chip chip-glass"><?= e($nextUp['category']) ?></span>
                    <span class="spotlight-title"><?= e($nextUp['title']) ?></span>
                    <span class="spotlight-meta">
                        <span><?= icon('calendar') ?> <?= e($nextDate['weekday'] . ', ' . $nextDate['day'] . ' ' . $nextDate['month']) ?> · <?= e(format_time($nextUp['starts_at'])) ?></span>
                        <span><?= icon('pin') ?> <?= e($nextUp['venue']) ?></span>
                    </span>
                    <span class="spotlight-footer">
                        <span class="spotlight-countdown" data-countdown="<?= e(format_iso($nextUp['starts_at'])) ?>">
                            <?= e(starts_in($nextUp['starts_at'])) ?>
                        </span>
                        <span class="spotlight-cta">View event <?= icon('arrow-right') ?></span>
                    </span>
                </span>
            </a>
        <?php endif; ?>
    </div>
</section>

<section class="container section" id="events">
    <div class="section-head">
        <div>
            <p class="eyebrow">What's on</p>
            <h2 class="result-count">
                <?= $pagination['total'] ?> upcoming event<?= $pagination['total'] === 1 ? '' : 's' ?>
                <?php if ($search !== ''): ?>
                    <span class="muted">for “<?= e($search) ?>”</span>
                <?php endif; ?>
            </h2>
        </div>

        <nav class="chips" aria-label="Filter by category">
            <a class="chip-filter" href="<?= e($chipUrl('')) ?>" <?= $category === '' ? 'aria-current="true"' : '' ?>>All</a>
            <?php foreach (EVENT_CATEGORIES as $option): ?>
                <a class="chip-filter cat-<?= e(category_slug($option)) ?>" href="<?= e($chipUrl($option)) ?>"
                   <?= $category === $option ? 'aria-current="true"' : '' ?>>
                    <span class="chip-dot" aria-hidden="true"></span><?= e($option) ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <?php if (!$events): ?>
        <div class="empty-state">
            <span class="empty-state-icon"><?= icon($isFiltered ? 'search' : 'calendar') ?></span>
            <h3><?= $isFiltered ? 'Nothing matches… yet' : 'The calendar is clear' ?></h3>
            <p>
                <?= $isFiltered
                    ? 'No upcoming events match your filters.'
                    : 'There are no upcoming events right now. Check back soon!' ?>
            </p>
            <?php if ($isFiltered): ?>
                <a class="button button-secondary" href="index.php">Clear filters</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="event-grid">
            <?php foreach ($events as $event): ?>
                <?php require __DIR__ . '/partials/event-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php require __DIR__ . '/partials/pagination.php'; ?>
    <?php endif; ?>
</section>

<?php if (current_user() === null): ?>
    <section class="container section">
        <div class="cta-band">
            <div>
                <h2 class="display-sm">Be there for everything that matters.</h2>
                <p>Create a free student account to register for events and get reminders before they start.</p>
            </div>
            <div class="cta-band-actions">
                <a class="button button-light button-lg" href="student-register.php">Create your account <?= icon('arrow-right') ?></a>
                <a class="button-link button-link-light" href="login.php">Hosting an event? Organiser login</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
