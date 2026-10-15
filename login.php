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
require __DIR__ . '/partials/header.php';
?>

<section class="auth-card">
    <h1>Organiser login</h1>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="form" method="post" action="login.php">
        <?= csrf_field() ?>

        <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" required
                   autocomplete="email" value="<?= e($email) ?>">
        </div>

        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required
                   autocomplete="current-password">
        </div>

        <button type="submit">Log in</button>
    </form>

    <p>Are you a student? <a href="student-login.php">Log in here</a>.</p>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
