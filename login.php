<?php
// Organiser login.
require __DIR__ . '/includes/bootstrap.php';

if (current_organiser_id() !== null) {
    redirect('organiser-dashboard.php');
}

$email = '';
$error = '';

if (is_post()) {
    require_valid_post();

    $email = post_string('email');
    $result = attempt_login('organiser', $email, post_raw('password'));

    if (is_array($result)) {
        login_user('organiser', $result['id'], $result['name']);
        flash('success', 'Welcome back, ' . $result['name'] . '.');
        redirect('organiser-dashboard.php');
    }

    $error = $result;
}

$pageTitle = 'Organiser login';
$asideTitle = 'Fill the room, every time.';
$asideText = 'Publish events in minutes. Students register in one tap and get reminded automatically.';
require __DIR__ . '/partials/header.php';
?>

<div class="auth-layout">
    <?php require __DIR__ . '/partials/auth-aside.php'; ?>

    <section class="auth-panel">
        <div class="auth-card">
            <p class="eyebrow">For organisers</p>
            <h1 class="display-sm">Welcome back</h1>
            <p class="muted">Log in to publish and manage your events.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error" role="alert"><?= icon('alert', 'alert-icon') ?><p><?= e($error) ?></p></div>
            <?php endif; ?>

            <form class="form" method="post" action="login.php" data-submit-once>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" required autofocus
                           autocomplete="email" placeholder="you@kabarak.ac.ke" value="<?= e($email) ?>">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           autocomplete="current-password">
                </div>

                <button type="submit" class="button button-lg button-block">Log in <?= icon('arrow-right') ?></button>
            </form>

            <p class="auth-switch">Are you a student? <a href="student-login.php">Log in here</a></p>
        </div>
    </section>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
