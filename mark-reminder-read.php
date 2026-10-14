<?php
require_once "student-auth.php";
require_once "database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit("Method not allowed.");
}

$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    empty($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Invalid request. Refresh the dashboard and try again.");
}

$reminderId = filter_input(
    INPUT_POST,
    "reminder_id",
    FILTER_VALIDATE_INT
);

if (!$reminderId || $reminderId < 1) {
    http_response_code(400);
    exit("Invalid reminder ID.");
}

$stmt = $pdo->prepare(
    "UPDATE reminders
     SET status = 'read'
     WHERE id = :id
       AND recipient_id = :student_id
       AND status = 'pending'"
);

$stmt->execute([
    "id" => $reminderId,
    "student_id" => (int) $_SESSION["student_id"]
]);

header("Location: student-dashboard.php");
exit;