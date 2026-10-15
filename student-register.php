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
require __DIR__ . '/partials/header.php';
?>

<section class="auth-card">
    <h1>Create a student account</h1>

    <?php if ($errors): ?>
        <div class="alert alert-error" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form class="form" method="post" action="student-register.php">
        <?= csrf_field() ?>

        <div class="field">
            <label for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" maxlength="100" required
                   autocomplete="name" value="<?= e($form['full_name']) ?>">
        </div>

        <div class="field">
            <label for="registration_number">Registration number</label>
            <input type="text" id="registration_number" name="registration_number"
                   maxlength="30" required value="<?= e($form['registration_number']) ?>">
        </div>

        <div class="field">
            <label for="email">Email address</label>
            <input type="email" id="email" name="email" required
                   autocomplete="email" value="<?= e($form['email']) ?>">
        </div>

        <div class="field">
            <label for="password">Password <span class="hint">(at least <?= PASSWORD_MIN_LENGTH ?> characters)</span></label>
            <input type="password" id="password" name="password" required
                   minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password">
        </div>

        <div class="field">
            <label for="confirm_password">Confirm password</label>
            <input type="password" id="confirm_password" name="confirm_password" required
                   minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="new-password">
        </div>

        <button type="submit">Create account</button>
    </form>

    <p>Already registered? <a href="student-login.php">Log in</a>.</p>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>
