<?php
require_once "auth.php";
require_once "database.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Invalid event ID.");
}

// Load the existing event
$stmt = $pdo->prepare(
    "SELECT * FROM events WHERE id = :id AND status = 'active'"
);
$stmt->execute([":id" => $id]);
$event = $stmt->fetch();

if (!$event) {
    exit("Event not found or already cancelled.");
}

$errors = [];
$success = isset($_GET["success"]);

$title = $event["title"];
$description = $event["description"] ?? "";
$eventDate = $event["event_date"];
$eventTime = substr($event["event_time"], 0, 5);
$venue = $event["venue"];
$category = $event["category"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $eventDate = $_POST["event_date"] ?? "";
    $eventTime = $_POST["event_time"] ?? "";
    $venue = trim($_POST["venue"] ?? "");
    $category = $_POST["category"] ?? "";

    if ($title === "" || strlen($title) > 150) {
        $errors[] = "Enter a title of up to 150 characters.";
    }

    if ($venue === "" || strlen($venue) > 150) {
        $errors[] = "Enter a venue of up to 150 characters.";
    }

    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $eventDate
    );

    $time = DateTimeImmutable::createFromFormat(
        '!H:i',
        $eventTime
    );

    if (!$date || $date->format("Y-m-d") !== $eventDate) {
        $errors[] = "Enter a valid event date.";
    }

    if (!$time || $time->format("H:i") !== $eventTime) {
        $errors[] = "Enter a valid event time.";
    }

    $allowedCategories = [
        "General", "Academic", "Sports", "Social"
    ];

    if (!in_array($category, $allowedCategories, true)) {
        $errors[] = "Select a valid category.";
    }

    if ($date && $time) {
        $eventDateTime = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $eventDate . ' ' . $eventTime
        );

        if (
            $eventDateTime &&
            $eventDateTime <= new DateTimeImmutable()
        ) {
            $errors[] = "The event must be scheduled in the future.";
        }
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE events
                    SET title = :title,
                        description = :description,
                        event_date = :event_date,
                        event_time = :event_time,
                        venue = :venue,
                        category = :category
                    WHERE id = :id
                      AND status = 'active'";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ":title" => $title,
                ":description" => $description,
                ":event_date" => $eventDate,
                ":event_time" => $eventTime,
                ":venue" => $venue,
                ":category" => $category,
                ":id" => $id
            ]);

            header("Location: edit-event.php?id=$id&success=1");
            exit;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = "Could not update the event. Please try again.";
        }
    }
}

function escapeHtml($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Campus Event</title>
</head>
<body>

    <p><a href="index.php">Back to Upcoming Events</a></p>
    <h1>Edit Campus Event</h1>

    <?php if ($success): ?>
        <p style="color: green;">Event updated successfully!</p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <p style="color: red;">
            <?= escapeHtml($error) ?>
        </p>
    <?php endforeach; ?>

    <form method="POST"
          action="edit-event.php?id=<?= (int) $id ?>">

        <label for="title">Event title:</label><br>
        <input type="text" id="title" name="title"
               maxlength="150" required
               value="<?= escapeHtml($title) ?>"><br><br>

        <label for="description">Description:</label><br>
        <textarea id="description" name="description"><?= escapeHtml($description) ?></textarea><br><br>

        <label for="event_date">Date:</label><br>
        <input type="date" id="event_date" name="event_date"
               required value="<?= escapeHtml($eventDate) ?>"><br><br>

        <label for="event_time">Time:</label><br>
        <input type="time" id="event_time" name="event_time"
               required value="<?= escapeHtml($eventTime) ?>"><br><br>

        <label for="venue">Venue:</label><br>
        <input type="text" id="venue" name="venue"
               maxlength="150" required
               value="<?= escapeHtml($venue) ?>"><br><br>

        <label for="category">Category:</label><br>
        <select id="category" name="category" required>
            <?php foreach (
                ["General", "Academic", "Sports", "Social"] as $option
            ): ?>
                <option value="<?= escapeHtml($option) ?>"
                    <?= $category === $option ? "selected" : "" ?>>
                    <?= escapeHtml($option) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Save Changes</button>
    </form>

</body>
</html>