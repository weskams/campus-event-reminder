<?php
/**
 * Creates organiser accounts.
 *
 * While no organisers exist this page is open so the first account can
 * be set up. After that, only signed-in organisers can add more.
 */
require __DIR__ . '/includes/bootstrap.php';

$isFirstSetup = count_organisers() === 0;

if (!$isFirstSetup) {
    require_organiser();
}

$form = ['name' => '', 'email' => ''];
$errors = [];

if (is_post()) {
    require_valid_post();

    $form = ['name' => post_string('name'), 'email' => post_string('email')];
    $email = normalise_email($form['email']);
    $password = post_raw('password');

    if ($form['name'] === '' || mb_strlen($form['name']) > 100) {
        $errors[] = 'Enter a name of up to 100 characters.';
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
            $organiserId = create_organiser($form['name'], $email, $password);

            if ($isFirstSetup) {
                login_user('organiser', $organiserId, $form['name']);
                flash('success', 'Your organiser account is ready.');
            } else {
                flash('success', 'Organiser account created for ' . $form['name'] . '.');
            }

            redirect('organiser-dashboard.php');
        } catch (PDOException $e) {
            if (!is_duplicate_key($e)) {
                throw $e;
            }

            $errors[] = 'That email address is already registered.';
        }
    }
}

$pageTitle = 'Create organiser account';
$asideTitle = $isFirstSetup ? "Let's get your campus started." : 'Grow your organising team.';
$asideText = 'Organisers can publish events, see who has registered and keep everyone informed.';
require __DIR__ . '/partials/header.php';
?>

<div class="auth-layout">
    <?php require __DIR__ . '/partials/auth-aside.php'; ?>

    <section class="auth-panel">
        <div class="auth-card">
            <p class="eyebrow"><?= $isFirstSetup ? 'First-time setup' : 'Organisers' ?></p>
            <h1 class="display-sm"><?= $isFirstSetup ? 'Set up the first organiser' : 'Add an organiser' ?></h1>

            <?php if ($isFirstSetup): ?>
                <p class="muted">No organiser accounts exist yet. The account you create here will be able to add others.</p>
            <?php else: ?>
                <p class="muted">They'll be able to publish and manage their own events.</p>
            <?php endif; ?>

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

            <form class="form" method="post" action="create-organiser.php" data-submit-once>
                <?= csrf_field() ?>

                <div class="field">
                    <label for="name">Full name</label>
                    <input type="text" id="name" name="name" maxlength="100" required
                           value="<?= e($form['name']) ?>">
                </div>

                <div class="field">
                    <label for="email">Email address</label>
                    <input type="email" id="email" name="email" required
                           placeholder="name@kabarak.ac.ke" value="<?= e($form['email']) ?>">
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
        </div>
    </section>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
