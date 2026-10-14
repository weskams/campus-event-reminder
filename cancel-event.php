<?php
session_start();
require_once "auth.php";
require_once "database.php";

// Create a CSRF token to protect the cancellation form.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function escapeHtml($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Invalid event ID.");
}

// Find the event.
$stmt = $pdo->prepare(
    "SELECT id, title, event_date, event_time, venue
     FROM events
     WHERE id = :id AND status = 'active'"
);

$stmt->execute(["id" => $id]);
$event = $stmt->fetch();

if (!$event) {
    exit("Event not found or already cancelled.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_token"], $token)
    ) {
        $error = "Invalid request. Please refresh and try again.";
    } else {
        $update = $pdo->prepare(
            "UPDATE events
             SET status = 'cancelled'
             WHERE id = :id AND status = 'active'"
        );

        $update->execute(["id" => $id]);

        // Generate a new token after processing.
        $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

        header("Location: index.php?cancelled=1");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancel Event</title>
</head>
<body>

    <h1>Cancel Event</h1>

    <?php if ($error !== ""): ?>
        <p style="color: red;">
            <?= escapeHtml($error) ?>
        </p>
    <?php endif; ?>

    <p>Are you sure you want to cancel this event?</p>

    <h2><?= escapeHtml($event["title"]) ?></h2>

    <p>
        Date:
        <?= escapeHtml($event["event_date"]) ?>
    </p>

    <p>
        Time:
        <?= escapeHtml($event["event_time"]) ?>
    </p>

    <p>
        Venue:
        <?= escapeHtml($event["venue"]) ?>
    </p>

    <form method="POST">
        <input
            type="hidden"
            name="csrf_token"
            value="<?= escapeHtml($_SESSION["csrf_token"]) ?>"
        >

        <button type="submit">Yes, Cancel Event</button>
    </form>

    <p>
        <a href="event-details.php?id=<?= (int) $event["id"] ?>">
            No, Go Back
        </a>
    </p>

</body>
</html>