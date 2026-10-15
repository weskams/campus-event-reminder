<?php
/**
 * Decorative brand panel beside the login and sign-up forms.
 * Optional: $asideTitle, $asideText.
 */

defined('APP_ROOT') || exit;
?>
<aside class="auth-aside" aria-hidden="true">
    <div class="auth-aside-inner">
        <a class="brand brand-inverse" href="index.php" tabindex="-1">
            <?= brand_mark() ?>
            <span class="brand-text">
                <span class="brand-name">Kabarak</span>
                <span class="brand-sub">Campus Events</span>
            </span>
        </a>

        <div>
            <p class="auth-aside-title"><?= e($asideTitle ?? 'Your campus, perfectly on time.') ?></p>
            <p class="auth-aside-text"><?= e($asideText ?? 'Talks, matches, socials and workshops, all in one place, with a reminder before every one.') ?></p>
        </div>

        <ul class="auth-aside-points">
            <li><?= icon('zap') ?> One-tap registration</li>
            <li><?= icon('bell') ?> Reminders an hour before</li>
            <li><?= icon('shield') ?> Secure student accounts</li>
        </ul>
    </div>
</aside>
