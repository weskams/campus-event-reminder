<?php
require_once "database.php";

// Get the event ID from the URL
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$event = null;

if ($id !== false && $id !== null && $id > 0) {
    $sql = "SELECT id, title, description, event_date,
                   event_time, venue, category
            FROM events
            WHERE id = :id
              AND status = 'active'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([":id" => $id]);

    $event = $stmt->fetch();
}

function escapeHtml($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details</title>
</head>
<body>

    <p><a href="index.php">← Back to Upcoming Events</a></p>

    <?php if (!$event): ?>

        <h1>Event Not Found</h1>
        <p>This event does not exist or is no longer available.</p>

    <?php else: ?>

        <h1><?= escapeHtml($event["title"]) ?></h1>
        
        <p>
            <a href="edit-event.php?id=<?= (int) $event["id"] ?>">
                Edit Event
            </a>
        </p>

        <p>
            <a href="cancel-event.php?id=<?= (int) $event["id"] ?>">
                Cancel Event
            </a>
        </p>

        <p>
            <strong>Date:</strong>
            <?= escapeHtml($event["event_date"]) ?>
        </p>

        <p>
            <strong>Time:</strong>
            <?= escapeHtml(substr($event["event_time"], 0, 5)) ?>
        </p>

        <p>
            <strong>Venue:</strong>
            <?= escapeHtml($event["venue"]) ?>
        </p>

        <p>
            <strong>Category:</strong>
            <?= escapeHtml($event["category"]) ?>
        </p>

        <h2>About This Event</h2>

        <?php if (!empty($event["description"])): ?>
            <p><?= nl2br(escapeHtml($event["description"])) ?></p>
        <?php else: ?>
            <p>No additional description is available.</p>
        <?php endif; ?>

    <?php endif; ?>

</body>
</html>