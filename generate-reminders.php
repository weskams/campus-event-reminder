<?php
require_once "auth.php";
require_once "database.php";

header("Content-Type: text/plain; charset=UTF-8");

$sql = "
    INSERT IGNORE INTO reminders
        (recipient_id, event_id, reminder_type, scheduled_for)

    SELECT
        er.student_id,
        e.id,
        'one_hour_before',
        GREATEST(
            NOW(),
            DATE_SUB(
                TIMESTAMP(e.event_date, e.event_time),
                INTERVAL 1 HOUR
            )
        )
    FROM event_registrations er
    JOIN events e ON e.id = er.event_id
    WHERE e.status = 'active'
      AND TIMESTAMP(e.event_date, e.event_time) > NOW()
      AND TIMESTAMP(e.event_date, e.event_time)
          <= DATE_ADD(NOW(), INTERVAL 30 DAY)
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute();

    echo "Reminder generation completed.\n";
    echo "New reminders created: " . $stmt->rowCount();
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo "Reminder generation failed.";
}