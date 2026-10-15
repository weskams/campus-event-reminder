<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_student_id() !== null) {
    redirect('student-dashboard.php');
}

$email = '';
$error = '';

if (is_post()) {
    require_valid_post();

    $email = post_string('email');
    $result = attempt_login('student', $email, post_raw('password'));

    if (is_array($result)) {
        login_user('student', $result['id'], $result['name']);
        flash('success', 'Welcome back, ' . $result['name'] . '.');
        redirect('student-dashboard.php');
    }

    $error = $result;
}

$pageTitle = 'Student login';
require __DIR__ . '/partials/header.php';
?>

<div class="auth-layout">
    <?php require __DIR__ . '/partials/auth-aside.php'; ?>

    <section class="auth-panel">
        <div class="auth-card">
            <p class="eyebrow">Students</p>
            <h1 class="display-sm">Welcome back</h1>
            <p class="muted">Log in to see your reminders and registered events.</p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-error" role="alert"><?= icon('alert', 'alert-icon') ?><p><?= e($error) ?></p></div>
            <?php endif; ?>

            <form class="form" method="post" action="student-login.php" data-submit-once>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" required autofocus
                           autocomplete="email" placeholder="you@students.kabarak.ac.ke" value="<?= e($email) ?>">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required
                           autocomplete="current-password">
                </div>

                <button type="submit" class="button button-lg button-block">Log in <?= icon('arrow-right') ?></button>
            </form>

            <p class="auth-switch">New here? <a href="student-register.php">Create a student account</a></p>
        </div>
    </section>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
