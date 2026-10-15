<?php
require __DIR__ . '/includes/bootstrap.php';

$studentId = require_student();
require_valid_post();

$returnTo = safe_return_path(post_string('return_to'), 'student-dashboard.php');
$eventId = post_id('event_id');

if ($eventId !== null && unregister_student_from_event($studentId, $eventId)) {
    flash('success', 'Your registration has been cancelled.');
} else {
    flash('error', 'You are not registered for that event, or it has already started.');
}

redirect($returnTo);
