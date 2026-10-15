<?php
require __DIR__ . '/includes/bootstrap.php';

$id = query_id();

if ($id === null) {
    not_found();
}

$event = find_event($id, current_student_id());
$isOwner = $event !== null
    && current_organiser_id() === (int) $event['organiser_id'];

// Cancelled events are only visible to the organiser who created them.
if ($event === null || ($event['status'] !== 'active' && !$isOwner)) {
    not_found('This event does not exist or has been cancelled.');
}

$isUpcoming = is_upcoming($event);
$attendees = $isOwner ? find_event_attendees($id) : [];

$pageTitle = $event['title'];
require __DIR__ . '/partials/header.php';
?>

<p><a href="index.php">← Back to events</a></p>

<article class="event-details">
    <p class="event-category"><?= e($event['category']) ?></p>
    <h1><?= e($event['title']) ?></h1>

    <?php if ($event['status'] === 'cancelled'): ?>
        <p class="status status-cancelled">This event has been cancelled.</p>
    <?php elseif (!$isUpcoming): ?>
        <p class="status status-ended">This event has already taken place.</p>
    <?php endif; ?>

    <dl class="event-meta">
        <dt>Date</dt>
        <dd>
            <time datetime="<?= e(format_iso($event['starts_at'])) ?>">
                <?= e(format_date($event['starts_at'])) ?>
            </time>
        </dd>

        <dt>Time</dt>
        <dd><?= e(format_time($event['starts_at'])) ?></dd>

        <dt>Venue</dt>
        <dd><?= e($event['venue']) ?></dd>

        <dt>Organiser</dt>
        <dd><?= e($event['organiser_name']) ?></dd>

        <dt>Going</dt>
        <dd><?= (int) $event['attendee_count'] ?></dd>
    </dl>

    <?php require __DIR__ . '/partials/registration-button.php'; ?>

    <h2>About this event</h2>

    <?php if (!empty($event['description'])): ?>
        <p class="event-description"><?= nl2br(e($event['description'])) ?></p>
    <?php else: ?>
        <p>No description has been added yet.</p>
    <?php endif; ?>

    <?php if ($isOwner): ?>
        <section class="organiser-panel">
            <h2>Manage event</h2>

            <?php if ($isUpcoming): ?>
                <p class="actions">
                    <a class="button" href="edit-event.php?id=<?= (int) $event['id'] ?>">Edit event</a>
                    <a class="button button-danger" href="cancel-event.php?id=<?= (int) $event['id'] ?>">Cancel event</a>
                </p>
            <?php endif; ?>

            <h3>Registered students (<?= count($attendees) ?>)</h3>

            <?php if (!$attendees): ?>
                <p>No students have registered yet.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Registration no.</th>
                                <th scope="col">Email</th>
                                <th scope="col">Registered</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendees as $attendee): ?>
                                <tr>
                                    <td><?= e($attendee['full_name']) ?></td>
                                    <td><?= e($attendee['registration_number']) ?></td>
                                    <td><?= e($attendee['email']) ?></td>
                                    <td><?= e(format_date($attendee['registered_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</article>

<?php require __DIR__ . '/partials/footer.php'; ?>
