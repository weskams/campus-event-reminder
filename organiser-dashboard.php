<?php
require __DIR__ . '/includes/bootstrap.php';

$organiserId = require_organiser();

$upcomingEvents = find_organiser_events($organiserId, true);
$pastEvents = find_organiser_events($organiserId, false);

$pageTitle = 'My events';
require __DIR__ . '/partials/header.php';
?>

<section class="page-intro">
    <h1>My events</h1>
    <p class="actions">
        <a class="button" href="add-event.php">Add an event</a>
        <a href="create-organiser.php">Add another organiser</a>
    </p>
</section>

<?php
$sections = [
    'Upcoming' => $upcomingEvents,
    'Past and cancelled' => $pastEvents,
];
?>

<?php foreach ($sections as $heading => $events): ?>
    <section class="dashboard-section">
        <h2><?= e($heading) ?></h2>

        <?php if (!$events): ?>
            <p class="empty-state">Nothing here yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Event</th>
                            <th scope="col">When</th>
                            <th scope="col">Venue</th>
                            <th scope="col">Going</th>
                            <th scope="col">Status</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <?php $isUpcoming = is_upcoming($event); ?>
                            <tr>
                                <td>
                                    <a href="event-details.php?id=<?= (int) $event['id'] ?>">
                                        <?= e($event['title']) ?>
                                    </a>
                                </td>
                                <td>
                                    <?= e(format_date($event['starts_at'])) ?>,
                                    <?= e(format_time($event['starts_at'])) ?>
                                </td>
                                <td><?= e($event['venue']) ?></td>
                                <td><?= (int) $event['attendee_count'] ?></td>
                                <td>
                                    <?php if ($event['status'] === 'cancelled'): ?>
                                        <span class="status status-cancelled">Cancelled</span>
                                    <?php elseif ($isUpcoming): ?>
                                        <span class="status status-active">Upcoming</span>
                                    <?php else: ?>
                                        <span class="status status-ended">Ended</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <?php if ($isUpcoming): ?>
                                        <a href="edit-event.php?id=<?= (int) $event['id'] ?>">Edit</a>
                                        <a href="cancel-event.php?id=<?= (int) $event['id'] ?>">Cancel</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
<?php endforeach; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>
