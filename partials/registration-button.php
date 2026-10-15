<?php
/**
 * Register / unregister control for $event, depending on who is viewing.
 * Set $registrationLarge = true for the big call-to-action version.
 */

defined('APP_ROOT') || exit;

if (!is_upcoming($event)) {
    return;
}

$registrationSize = !empty($registrationLarge) ? 'button-lg button-block' : 'button-sm';
?>
<?php if (current_student_id() !== null): ?>

    <?php if ((int) $event['is_registered'] === 1): ?>
        <form class="registration-form is-going" method="post" action="unregister-event.php">
            <?= csrf_field() ?>
            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
            <input type="hidden" name="return_to" value="<?= e(current_url_with()) ?>">
            <span class="going-pill"><?= icon('check') ?> You're going</span>
            <button type="submit" class="button-link">Cancel registration</button>
        </form>
    <?php else: ?>
        <form class="registration-form" method="post" action="register-event.php">
            <?= csrf_field() ?>
            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
            <input type="hidden" name="return_to" value="<?= e(current_url_with()) ?>">
            <button type="submit" class="button <?= $registrationSize ?>">
                Register <?= icon('arrow-right') ?>
            </button>
        </form>
    <?php endif; ?>

<?php elseif (current_user() === null): ?>
    <a class="button button-secondary <?= $registrationSize ?> registration-form" href="student-login.php">
        Log in to register
    </a>
<?php endif; ?>
