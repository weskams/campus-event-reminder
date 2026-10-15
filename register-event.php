<?php
require __DIR__ . '/includes/bootstrap.php';

$studentId = require_student();
require_valid_post();

$returnTo = safe_return_path(post_string('return_to'), 'student-dashboard.php');
$eventId = post_id('event_id');

if ($eventId === null) {
    flash('error', 'That event could not be found.');
    redirect($returnTo);
}

$result = register_student_for_event($studentId, $eventId);

match ($result) {
    'registered' => flash('success', "You're registered! We'll remind you an hour before it starts."),
    'already_registered' => flash('info', "You're already registered for this event."),
    'unavailable' => flash('error', 'This event is no longer open for registration.'),
};

redirect($returnTo);
