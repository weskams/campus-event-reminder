
<?php
require_once "database.php";

// Find active events taking place in the next 24 hours.
$sql = "SELECT id, title, description, event_date,
               event_time, venue, category
        FROM events
        WHERE status = 'active'
          AND TIMESTAMP(event_date, event_time) >= NOW()
          AND TIMESTAMP(event_date, event_time)
              <= DATE_ADD(NOW(), INTERVAL 24 HOUR)
        ORDER BY event_date ASC, event_time ASC";

$stmt = $pdo->query($sql);
$events = $stmt->fetchAll();

function escapeHtml($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        "UTF-8"
    );
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Happening Soon</title>
</head>
<body>

    <h1>Events Happening Soon</h1>

    <p>These are events taking place within the next 24 hours.</p>

    <p>
        <a href="index.php">All Upcoming Events</a>
    </p>

    <?php if (count($events) === 0): ?>

        <p>No events are happening in the next 24 hours.</p>

    <?php else: ?>

        <?php foreach ($events as $event): ?>

            <div>
                <h2><?= escapeHtml($event["title"]) ?></h2>

                <p>
                    Date: <?= escapeHtml($event["event_date"]) ?>
                </p>

                <p>
                    Time: <?= escapeHtml($event["event_time"]) ?>
                </p>

                <p>
                    Venue: <?= escapeHtml($event["venue"]) ?>
                </p>

                <p>
                    Category: <?= escapeHtml($event["category"]) ?>
                </p>

                <p>
                    <a href="event-details.php?id=<?= (int) $event["id"] ?>">
                        View Event Details
                    </a>
                </p>
            </div>

            <hr>

        <?php endforeach; ?>

    <?php endif; ?>

</body>
</html>