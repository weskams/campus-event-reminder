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
$date = date_parts($event['starts_at']);

$pageTitle = $event['title'];
require __DIR__ . '/partials/header.php';
?>

<section class="event-hero cat-<?= e(category_slug($event['category'])) ?>">
    <span class="poster-glyph" aria-hidden="true"><?= e(mb_substr($event['category'], 0, 1)) ?></span>

    <div class="container event-hero-inner">
        <a class="back-link" href="index.php"><?= icon('arrow-left') ?> All events</a>

        <div class="event-hero-tags">
            <span class="chip chip-glass"><?= e($event['category']) ?></span>
            <?php if ($event['status'] === 'cancelled'): ?>
                <span class="chip chip-danger"><?= icon('ban') ?> Cancelled</span>
            <?php elseif (!$isUpcoming): ?>
                <span class="chip chip-glass">Ended</span>
            <?php elseif (starts_within($event['starts_at'], 24)): ?>
                <span class="chip chip-live" data-countdown="<?= e(format_iso($event['starts_at'])) ?>"><?= e(starts_in($event['starts_at'])) ?></span>
            <?php endif; ?>
        </div>

        <h1 class="display event-hero-title"><?= e($event['title']) ?></h1>

        <p class="event-hero-sub">
            <span><?= icon('calendar') ?> <?= e(format_date($event['starts_at'])) ?></span>
            <span><?= icon('clock') ?> <?= e(format_time($event['starts_at'])) ?></span>
            <span><?= icon('pin') ?> <?= e($event['venue']) ?></span>
        </p>
    </div>
</section>

<div class="container event-layout">
    <article class="event-main">
        <?php if ($event['status'] === 'cancelled'): ?>
            <div class="alert alert-error">
                <?= icon('ban', 'alert-icon') ?>
                <p>This event has been cancelled. Students' reminders were withdrawn.</p>
            </div>
        <?php elseif (!$isUpcoming): ?>
            <div class="alert alert-info">
                <?= icon('info', 'alert-icon') ?>
                <p>This event has already taken place.</p>
            </div>
        <?php endif; ?>

        <section class="prose">
            <h2>About this event</h2>

            <?php if (!empty($event['description'])): ?>
                <p><?= nl2br(e($event['description'])) ?></p>
            <?php else: ?>
                <p class="muted">The organiser hasn't added a description yet.</p>
            <?php endif; ?>
        </section>

        <?php if ($isOwner): ?>
            <section class="card organiser-panel">
                <div class="card-head">
                    <div>
                        <p class="eyebrow">Organiser tools</p>
                        <h2>Manage event</h2>
                    </div>

                    <?php if ($isUpcoming): ?>
                        <div class="actions">
                            <a class="button button-secondary button-sm" href="edit-event.php?id=<?= (int) $event['id'] ?>"><?= icon('edit') ?> Edit</a>
                            <a class="button button-danger-ghost button-sm" href="cancel-event.php?id=<?= (int) $event['id'] ?>"><?= icon('ban') ?> Cancel event</a>
                        </div>
                    <?php endif; ?>
                </div>

                <h3 class="table-title">Registered students <span class="count-pill"><?= count($attendees) ?></span></h3>

                <?php if (!$attendees): ?>
                    <p class="muted">No students have registered yet. Share the event link to get the word out.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Student</th>
                                    <th scope="col">Registration no.</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Registered</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($attendees as $attendee): ?>
                                    <tr>
                                        <td>
                                            <span class="person">
                                                <span class="avatar avatar-sm" aria-hidden="true"><?= e(initials($attendee['full_name'])) ?></span>
                                                <?= e($attendee['full_name']) ?>
                                            </span>
                                        </td>
                                        <td class="mono"><?= e($attendee['registration_number']) ?></td>
                                        <td><a href="mailto:<?= e($attendee['email']) ?>"><?= e($attendee['email']) ?></a></td>
                                        <td class="muted"><?= e(format_date($attendee['registered_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </article>

    <aside class="event-aside">
        <div class="ticket">
            <div class="ticket-top">
                <time class="date-tile date-tile-lg" datetime="<?= e(format_iso($event['starts_at'])) ?>">
                    <span class="date-tile-month"><?= e($date['month']) ?></span>
                    <span class="date-tile-day"><?= e($date['day']) ?></span>
                    <span class="date-tile-weekday"><?= e($date['weekday']) ?></span>
                </time>
                <div>
                    <p class="ticket-time"><?= e(format_time($event['starts_at'])) ?></p>
                    <?php if ($isUpcoming): ?>
                        <p class="ticket-countdown" data-countdown="<?= e(format_iso($event['starts_at'])) ?>"><?= e(starts_in($event['starts_at'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <dl class="ticket-facts">
                <div>
                    <dt><?= icon('pin') ?> Venue</dt>
                    <dd><?= e($event['venue']) ?></dd>
                </div>
                <div>
                    <dt><?= icon('user') ?> Organiser</dt>
                    <dd><?= e($event['organiser_name']) ?></dd>
                </div>
                <div>
                    <dt><?= icon('users') ?> Going</dt>
                    <dd><?= (int) $event['attendee_count'] ?> student<?= (int) $event['attendee_count'] === 1 ? '' : 's' ?></dd>
                </div>
            </dl>

            <?php if ($isUpcoming): ?>
                <div class="ticket-action">
                    <?php $registrationLarge = true; ?>
                    <?php require __DIR__ . '/partials/registration-button.php'; ?>
                    <?php if (current_student_id() !== null && (int) $event['is_registered'] !== 1): ?>
                        <p class="ticket-note"><?= icon('bell') ?> We'll remind you an hour before.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
