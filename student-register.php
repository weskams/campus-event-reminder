<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_student_id() !== null) {
    redirect('student-dashboard.php');
}

$form = ['full_name' => '', 'registration_number' => '', 'email' => ''];
$errors = [];

if (is_post()) {
    require_valid_post();

    $form = [
        'full_name' => post_string('full_name'),
        'registration_number' => mb_strtoupper(post_string('registration_number')),
        'email' => post_string('email'),
    ];
    $email = normalise_email($form['email']);
    $password = post_raw('password');

    if ($form['full_name'] === '' || mb_strlen($form['full_name']) > 100) {
        $errors[] = 'Enter your full name (up to 100 characters).';
    }

    if (!preg_match('~^[A-Z0-9/-]{3,30}$~', $form['registration_number'])) {
        $errors[] = 'Enter a valid registration number (letters, numbers, "/" and "-" only).';
    }

    if ($email === null) {
        $errors[] = 'Enter a valid email address.';
    }

    $passwordError = validate_password($password, post_raw('confirm_password'));

    if ($passwordError !== null) {
        $errors[] = $passwordError;
    }

    if (!$errors) {
        try {
            $studentId = create_student(
                $form['full_name'],
                $form['registration_number'],
                $email,
                $password
            );

            login_user('student', $studentId, $form['full_name']);
            flash('success', 'Your account is ready. Find an event to register for!');
            redirect('index.php');
        } catch (PDOException $e) {
            if (!is_duplicate_key($e)) {
                throw $e;
            }

            $errors[] = 'An account with that email or registration number already exists.';
        }
    }
}

$pageTitle = 'Create a student account';
$asideTitle = 'Join campus life in thirty seconds.';
require __DIR__ . '/partials/header.php';
?>

<div class="auth-layout">
    <?php require __DIR__ . '/partials/auth-aside.php'; ?>

    <section class="auth-panel">
        <div class="auth-card">
            <p class="eyebrow">Students</p>
            <h1 class="display-sm">Create your account</h1>
            <p class="muted">Free for every Kabarak student.</p>

            <?php if ($errors): ?>
                <div class="alert alert-error" role="alert">
                    <?= icon('alert', 'alert-icon') ?>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form class="form" method="post" action="student-register.php" data-submit-once>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="full_name">Full name</label>
                    <input type="text" id="full_name" name="full_name" maxlength="100" required
                           autocomplete="name" placeholder="Jane Wanjiru" value="<?= e($form['full_name']) ?>">
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="registration_number">Registration no.</label>
                        <input type="text" id="registration_number" name="registration_number"
                               maxlength="30" required placeholder="BIT/0001/22"
                               value="<?= e($form['registration_number']) ?>">
                    </div>

                    <div class="field">
                        <label for="email">Email address</label>
                        <input type="email" id="email" name="email" required
                               autocomplete="email" placeholder="you@students.kabarak.ac.ke" value="<?= e($form['email']) ?>">
                    </div>
                </div>

                <div class="field-row">
                    <div class="field">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required
                               minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password">
                        <p class="hint">At least <?= PASSWORD_MIN_LENGTH ?> characters</p>
                    </div>

                    <div class="field">
                        <label for="confirm_password">Confirm password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required
                               minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="button button-lg button-block">Create account <?= icon('arrow-right') ?></button>
            </form>

            <p class="auth-switch">Already registered? <a href="student-login.php">Log in</a></p>
        </div>
    </section>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
