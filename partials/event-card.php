<?php
/**
 * One event in a grid. Expects $event from an events.php query.
 */

defined('APP_ROOT') || exit;

$cardDate = date_parts($event['starts_at']);
$cardSoon = is_upcoming($event) && starts_within($event['starts_at'], 24);
?>
<article class="event-card cat-<?= e(category_slug($event['category'])) ?>">
    <div class="poster">
        <span class="poster-glyph" aria-hidden="true"><?= e(mb_substr($event['category'], 0, 1)) ?></span>

        <div class="poster-top">
            <span class="chip chip-glass"><?= e($event['category']) ?></span>
            <?php if ($cardSoon): ?>
                <span class="chip chip-live" data-countdown="<?= e(format_iso($event['starts_at'])) ?>">
                    <?= e(starts_in($event['starts_at'])) ?>
                </span>
            <?php endif; ?>
        </div>

        <time class="date-tile" datetime="<?= e(format_iso($event['starts_at'])) ?>">
            <span class="date-tile-month"><?= e($cardDate['month']) ?></span>
            <span class="date-tile-day"><?= e($cardDate['day']) ?></span>
            <span class="date-tile-weekday"><?= e($cardDate['weekday']) ?></span>
        </time>
    </div>

    <div class="event-card-body">
        <h3 class="event-card-title">
            <a class="stretched-link" href="event-details.php?id=<?= (int) $event['id'] ?>">
                <?= e($event['title']) ?>
            </a>
        </h3>

        <ul class="facts">
            <li><?= icon('clock') ?> <?= e(format_time($event['starts_at'])) ?></li>
            <li><?= icon('pin') ?> <?= e($event['venue']) ?></li>
        </ul>

        <?php if (!empty($event['description'])): ?>
            <p class="event-card-summary"><?= e(excerpt($event['description'], 120)) ?></p>
        <?php endif; ?>
    </div>

    <div class="event-card-footer">
        <span class="attendees">
            <?= icon('users') ?>
            <?= (int) $event['attendee_count'] ?> going
        </span>

        <?php require __DIR__ . '/registration-button.php'; ?>
    </div>
</article>
