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

<section class="auth-card">
    <h1>Student login</h1>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form class="form" method="post" action="student-login.php">
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

    <p>New here? <a href="student-register.php">Create a student account</a>.</p>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
