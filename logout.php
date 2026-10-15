<?php
// Logs out students and organisers. POST-only so other sites can't log
// people out with a link or image.
require __DIR__ . '/includes/bootstrap.php';

if (!is_post() || current_user() === null) {
    redirect('index.php');
}

require_valid_post();

logout_user();
flash('success', 'You have been logged out.');
redirect('index.php');
