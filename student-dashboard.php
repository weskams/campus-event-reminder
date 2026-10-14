<?php
require_once "student-auth.php";
require_once "database.php";

$studentId = (int) $_SESSION["student_id"];

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

// Search for upcoming active events.
$search = trim($_GET["search"] ?? "");

$availableEventsSql = "
    SELECT
        e.id,
        e.title,
        e.description,
        e.event_date,
        e.event_time,
        e.venue,
        e.category,
        er.id AS registration_id
    FROM events e
    LEFT JOIN event_registrations er
        ON er.event_id = e.id
        AND er.student_id = :student_id
    WHERE e.status = 'active'
      AND TIMESTAMP(e.event_date, e.event_time) >= NOW()
";

$params = ["student_id" => $studentId];

if ($search !== "") {
    $availableEventsSql .= " AND e.title LIKE :search";
    $params["search"] = "%" . $search . "%";
}

$availableEventsSql .= "
    ORDER BY e.event_date ASC, e.event_time ASC
";

$stmt = $pdo->prepare($availableEventsSql);
$stmt->execute($params);
$availableEvents = $stmt->fetchAll();

// Fetch this student's registered upcoming events.
$stmt = $pdo->prepare(
    "SELECT e.id, e.title, e.event_date, e.event_time,
            e.venue, e.category
     FROM event_registrations er
     JOIN events e ON e.id = er.event_id
     WHERE er.student_id = :student_id
       AND e.status = 'active'
       AND TIMESTAMP(e.event_date, e.event_time) > NOW()
     ORDER BY e.event_date, e.event_time"
);

$stmt->execute(["student_id" => $studentId]);
$events = $stmt->fetchAll();

// Fetch this student's reminders that are due.
$stmt = $pdo->prepare(
    "SELECT r.id AS reminder_id, r.scheduled_for,
            e.title, e.event_date, e.event_time, e.venue
     FROM reminders r
     JOIN events e ON e.id = r.event_id
     WHERE r.recipient_id = :student_id
       AND r.status = 'pending'
       AND r.scheduled_for <= NOW()
       AND e.status = 'active'
       AND TIMESTAMP(e.event_date, e.event_time) > NOW()
     ORDER BY r.scheduled_for"
);

$stmt->execute(["student_id" => $studentId]);
$reminders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
</head>
<body>

    <h1>
        Welcome,
        <?= escapeHtml($_SESSION["student_name"] ?? "Student") ?>
    </h1>

    <p>
        View campus events, register for events, and manage your reminders here.
    </p>

    <h2>Find Campus Events</h2>

    <form method="GET" action="student-dashboard.php">
        <label for="search">Search events by name:</label>

        <input
            type="search"
            id="search"
            name="search"
            placeholder="e.g. Tech Conference"
            value="<?= escapeHtml($search) ?>"
        >

        <button type="submit">Search</button>

        <?php if ($search !== ""): ?>
            <a href="student-dashboard.php">Clear Search</a>
        <?php endif; ?>
    </form>

    <h2>
        <?= $search !== ""
            ? "Search Results"
            : "Upcoming Campus Events" ?>
    </h2>

    <?php if (empty($availableEvents)): ?>

        <?php if ($search !== ""): ?>
            <p>No upcoming events found matching your search.</p>
        <?php else: ?>
            <p>There are currently no upcoming events.</p>
        <?php endif; ?>

    <?php else: ?>

        <?php foreach ($availableEvents as $event): ?>
            <div>
                <h3><?= escapeHtml($event["title"]) ?></h3>

                <p>
                    <?= escapeHtml($event["description"] ?? "") ?>
                </p>

                <p>
                    <strong>Date:</strong>
                    <?= escapeHtml($event["event_date"]) ?>
                </p>

                <p>
                    <strong>Time:</strong>
                    <?= escapeHtml($event["event_time"]) ?>
                </p>

                <p>
                    <strong>Venue:</strong>
                    <?= escapeHtml($event["venue"]) ?>
                </p>

                <p>
                    <strong>Category:</strong>
                    <?= escapeHtml($event["category"]) ?>
                </p>

                <?php if ($event["registration_id"] !== null): ?>

                    <p>You are already registered for this event.</p>

                <?php else: ?>

                    <form method="POST" action="register-event.php">
                        <input
                            type="hidden"
                            name="event_id"
                            value="<?= (int) $event["id"] ?>"
                        >

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= escapeHtml($_SESSION["csrf_token"]) ?>"
                        >

                        <button type="submit">Register for Event</button>
                    </form>

                <?php endif; ?>

            </div>
            <hr>
        <?php endforeach; ?>

    <?php endif; ?>

    <h2>My Reminders</h2>

    <?php if (empty($reminders)): ?>

        <p>You have no reminders due right now.</p>

    <?php else: ?>

        <?php foreach ($reminders as $reminder): ?>
            <div>
                <h3><?= escapeHtml($reminder["title"]) ?></h3>

                <p>Date: <?= escapeHtml($reminder["event_date"]) ?></p>
                <p>Time: <?= escapeHtml($reminder["event_time"]) ?></p>
                <p>Venue: <?= escapeHtml($reminder["venue"]) ?></p>

                <form method="POST" action="mark-reminder-read.php">
                    <input
                        type="hidden"
                        name="reminder_id"
                        value="<?= (int) $reminder["reminder_id"] ?>"
                    >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= escapeHtml($_SESSION["csrf_token"]) ?>"
                    >

                    <button type="submit">Mark as Read</button>
                </form>
            </div>
            <hr>
        <?php endforeach; ?>

    <?php endif; ?>

    <h2>My Registered Events</h2>

    <?php if (empty($events)): ?>

        <p>You have not registered for any upcoming events.</p>

    <?php else: ?>

        <?php foreach ($events as $event): ?>
            <div>
                <h3><?= escapeHtml($event["title"]) ?></h3>

                <p>Date: <?= escapeHtml($event["event_date"]) ?></p>
                <p>Time: <?= escapeHtml($event["event_time"]) ?></p>
                <p>Venue: <?= escapeHtml($event["venue"]) ?></p>
            </div>
            <hr>
        <?php endforeach; ?>

    <?php endif; ?>

    <p>
        <a href="student-logout.php">Log Out</a>
    </p>

</body>
</html>