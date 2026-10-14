<?php
require_once "student-auth.php";
require_once "database.php";

// Accept POST requests only.
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: student-dashboard.php");
    exit;
}

// Verify CSRF token.
$submittedToken = $_POST["csrf_token"] ?? "";
$sessionToken = $_SESSION["csrf_token"] ?? "";

if (
    !is_string($submittedToken) ||
    !is_string($sessionToken) ||
    $sessionToken === "" ||
    !hash_equals($sessionToken, $submittedToken)
) {
    http_response_code(403);
    exit("Invalid security token. Please return to your dashboard and try again.");
}

// Validate event ID.
$eventId = filter_input(
    INPUT_POST,
    "event_id",
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1]]
);

if (!$eventId) {
    header("Location: student-dashboard.php?error=invalid_event");
    exit;
}

$studentId = (int) $_SESSION["student_id"];

try {
    // Confirm the event exists and is still upcoming.
    $stmt = $pdo->prepare(
        "SELECT id
         FROM events
         WHERE id = :event_id
           AND status = 'active'
           AND TIMESTAMP(event_date, event_time) > NOW()
         LIMIT 1"
    );

    $stmt->execute(["event_id" => $eventId]);

    if (!$stmt->fetch()) {
        header("Location: student-dashboard.php?error=event_unavailable");
        exit;
    }

    // Save the registration and reminder together.
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO event_registrations (student_id, event_id)
         VALUES (:student_id, :event_id)"
    );

    $stmt->execute([
        "student_id" => $studentId,
        "event_id" => $eventId
    ]);

    // Automatically create the student's reminder.
    // If the event starts within one hour, make the reminder due now.
    $stmt = $pdo->prepare(
        "INSERT INTO reminders
            (recipient_id, event_id, reminder_type, scheduled_for)
         SELECT
            :student_id,
            id,
            'one_hour_before',
            GREATEST(
                NOW(),
                DATE_SUB(
                    TIMESTAMP(event_date, event_time),
                    INTERVAL 1 HOUR
                )
            )
         FROM events
         WHERE id = :event_id
           AND status = 'active'"
    );

    $stmt->execute([
        "student_id" => $studentId,
        "event_id" => $eventId
    ]);

    $pdo->commit();

    // Return to the dashboard.
    header("Location: student-dashboard.php?registered=1");
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // MySQL duplicate-key error.
    if (
        $e->getCode() === "23000" &&
        isset($e->errorInfo[1]) &&
        (int) $e->errorInfo[1] === 1062
    ) {
        header("Location: student-dashboard.php?error=already_registered");
        exit;
    }

    error_log($e->getMessage());
    http_response_code(500);
    exit("Registration failed. Please try again later.");
}