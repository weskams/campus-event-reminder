<?php
require __DIR__ . '/includes/bootstrap.php';

$studentId = require_student();
require_valid_post();

$reminderId = post_id('reminder_id');

if ($reminderId !== null) {
    mark_reminder_read($reminderId, $studentId);
}

redirect('student-dashboard.php');
