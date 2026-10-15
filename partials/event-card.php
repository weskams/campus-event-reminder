<?php
/**
 * One event in a list. Expects $event from an events.php query.
 */

defined('APP_ROOT') || exit;
?>
<article class="event-card">
    <p class="event-category"><?= e($event['category']) ?></p>

    <h3 class="event-title">
        <a href="event-details.php?id=<?= (int) $event['id'] ?>">
            <?= e($event['title']) ?>
        </a>
    </h3>

    <dl class="event-meta">
        <dt>When</dt>
        <dd>
            <time datetime="<?= e(format_iso($event['starts_at'])) ?>">
                <?= e(format_date($event['starts_at'])) ?>,
                <?= e(format_time($event['starts_at'])) ?>
            </time>
        </dd>

        <dt>Where</dt>
        <dd><?= e($event['venue']) ?></dd>
    </dl>

    <?php if (!empty($event['description'])): ?>
        <p class="event-summary"><?= e(excerpt($event['description'])) ?></p>
    <?php endif; ?>

    <p class="event-attendees"><?= (int) $event['attendee_count'] ?> going</p>

    <?php require __DIR__ . '/registration-button.php'; ?>
</article>
