<?php
require __DIR__ . '/includes/bootstrap.php';

$organiserId = require_organiser();

$upcomingEvents = find_organiser_events($organiserId, true);
$pastEvents = find_organiser_events($organiserId, false);

$upcomingAttendees = array_sum(array_map(
    fn(array $event): int => (int) $event['attendee_count'],
    $upcomingEvents
));
$firstName = first_name(current_user()['name']);

$pageTitle = 'My events';
require __DIR__ . '/partials/header.php';
?>

<section class="page-hero page-hero-compact">
    <div class="container dashboard-head">
        <div>
            <p class="eyebrow">Organiser dashboard</p>
            <h1 class="display-sm"><?= e(greeting()) ?>, <em><?= e($firstName) ?></em>.</h1>
            <p class="actions">
                <a class="button" href="add-event.php"><?= icon('plus') ?> New event</a>
                <a class="button button-ghost" href="create-organiser.php"><?= icon('users') ?> Add organiser</a>
            </p>
        </div>

        <dl class="stat-row">
            <div class="stat">
                <dt><?= icon('calendar') ?> Upcoming</dt>
                <dd><?= count($upcomingEvents) ?></dd>
            </div>
            <div class="stat">
                <dt><?= icon('users') ?> Registered</dt>
                <dd><?= $upcomingAttendees ?></dd>
            </div>
            <div class="stat">
                <dt><?= icon('clock') ?> Past</dt>
                <dd><?= count($pastEvents) ?></dd>
            </div>
        </dl>
    </div>
</section>

<div class="container">
    <?php foreach (['Upcoming' => $upcomingEvents, 'Past and cancelled' => $pastEvents] as $heading => $events): ?>
        <section class="section">
            <div class="section-head">
                <h2><?= e($heading) ?></h2>
            </div>

            <?php if (!$events): ?>
                <div class="empty-state empty-state-inline">
                    <span class="empty-state-icon"><?= icon('calendar') ?></span>
                    <div>
                        <h3>Nothing here yet</h3>
                        <?php if ($heading === 'Upcoming'): ?>
                            <p><a href="add-event.php">Publish your first event</a> and it will show up here.</p>
                        <?php else: ?>
                            <p>Events move here once they've happened or been cancelled.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card card-flush">
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Event</th>
                                    <th scope="col">When</th>
                                    <th scope="col">Going</th>
                                    <th scope="col">Status</th>
                                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $event): ?>
                                    <?php
                                    $isUpcoming = is_upcoming($event);
                                    $rowDate = date_parts($event['starts_at']);
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="event-cell cat-<?= e(category_slug($event['category'])) ?>">
                                                <span class="mini-date" aria-hidden="true">
                                                    <span><?= e($rowDate['month']) ?></span><?= e($rowDate['day']) ?>
                                                </span>
                                                <span>
                                                    <a class="event-cell-title" href="event-details.php?id=<?= (int) $event['id'] ?>"><?= e($event['title']) ?></a>
                                                    <span class="event-cell-sub"><?= e($event['venue']) ?></span>
                                                </span>
                                            </span>
                                        </td>
                                        <td class="nowrap">
                                            <?= e(format_date($event['starts_at'])) ?>
                                            <span class="muted">· <?= e(format_time($event['starts_at'])) ?></span>
                                        </td>
                                        <td><span class="count-pill"><?= (int) $event['attendee_count'] ?></span></td>
                                        <td>
                                            <?php if ($event['status'] === 'cancelled'): ?>
                                                <span class="status status-cancelled">Cancelled</span>
                                            <?php elseif ($isUpcoming): ?>
                                                <span class="status status-active">Upcoming</span>
                                            <?php else: ?>
                                                <span class="status status-ended">Ended</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="row-actions">
                                            <?php if ($isUpcoming): ?>
                                                <a class="icon-button" href="edit-event.php?id=<?= (int) $event['id'] ?>" title="Edit">
                                                    <?= icon('edit') ?><span class="visually-hidden">Edit <?= e($event['title']) ?></span>
                                                </a>
                                                <a class="icon-button icon-button-danger" href="cancel-event.php?id=<?= (int) $event['id'] ?>" title="Cancel">
                                                    <?= icon('ban') ?><span class="visually-hidden">Cancel <?= e($event['title']) ?></span>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
