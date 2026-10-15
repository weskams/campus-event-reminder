<?php
/**
 * Register / unregister control for $event, depending on who is viewing.
 */

defined('APP_ROOT') || exit;

if (!is_upcoming($event)) {
    return;
}
?>
<?php if (current_student_id() !== null): ?>

    <?php if ((int) $event['is_registered'] === 1): ?>
        <form class="registration-form" method="post" action="unregister-event.php">
            <?= csrf_field() ?>
            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
            <input type="hidden" name="return_to" value="<?= e(current_url_with()) ?>">
            <p class="registered-note">You're going</p>
            <button type="submit" class="button-secondary">Cancel registration</button>
        </form>
    <?php else: ?>
        <form class="registration-form" method="post" action="register-event.php">
            <?= csrf_field() ?>
            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
            <input type="hidden" name="return_to" value="<?= e(current_url_with()) ?>">
            <button type="submit">Register</button>
        </form>
    <?php endif; ?>

<?php elseif (current_user() === null): ?>
    <p><a href="student-login.php">Log in to register</a></p>
<?php endif; ?>
