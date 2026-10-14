<?php
require_once "database.php";

$search = trim($_GET["search"] ?? "");

$sql = "
    SELECT id, title, description, event_date,
           event_time, venue, category
    FROM events
    WHERE status = 'active'
      AND TIMESTAMP(event_date, event_time) >= NOW()
";

$params = [];

if ($search !== "") {
    $sql .= " AND title LIKE :search";
    $params["search"] = "%" . $search . "%";
}

$sql .= " ORDER BY event_date ASC, event_time ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

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
    <title>Campus Event Reminder</title>
</head>
<body>

    <h1>Campus Event Reminder</h1>

    <form method="GET" action="index.php">
    <label for="category">Filter by category:</label>

    <select id="category" name="category">
        <option value="All">All Categories</option>

        <option value="General"
            <?= $category === "General" ? "selected" : "" ?>>
            General
        </option>

        <option value="Academic"
            <?= $category === "Academic" ? "selected" : "" ?>>
            Academic
        </option>

        <option value="Sports"
            <?= $category === "Sports" ? "selected" : "" ?>>
            Sports
        </option>

        <option value="Social"
            <?= $category === "Social" ? "selected" : "" ?>>
            Social
        </option>
    </select>
        <button type="submit">Filter</button>
    </form>


    <?php if (isset($_GET["cancelled"])): ?>
        <p style="color: green;">
            Event cancelled successfully.
        </p>
    <?php endif; ?>
    <p>Discover upcoming events at Kabarak University.</p>

    <p>
        <a href="add-event.php">Add a Campus Event</a>
    </p>

    <h2>Upcoming Events</h2>

    <p>
    <a href="upcoming-soon.php">Events Happening Soon (Next 24 Hours)</a>
    </p>

    <?php if (empty($events)): ?>

        <p>No upcoming events are available at the moment.</p>

    <?php else: ?>

        <?php foreach ($events as $event): ?>

            <article>
                
                <h3>
                    <a href="event-details.php?id=<?= (int) $event["id"] ?>">
                        <?= escapeHtml($event["title"]) ?>
                    </a>
                </h3>

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

                <?php if (!empty($event["description"])): ?>
                    <p>
                        <?= nl2br(escapeHtml($event["description"])) ?>
                    </p>
                <?php endif; ?>

                <hr>
            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</body>
</html>