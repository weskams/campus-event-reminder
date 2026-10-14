<?php
require_once "auth.php";
require_once "database.php";

$errors = [];
$success = isset($_GET["success"]);

$title = "";
$description = "";
$eventDate = "";
$eventTime = "";
$venue = "";
$category = "General";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $eventDate = $_POST["event_date"] ?? "";
    $eventTime = $_POST["event_time"] ?? "";
    $venue = trim($_POST["venue"] ?? "");
    $category = $_POST["category"] ?? "General";

    // 1. Validate required fields
    if ($title === "") {
        $errors[] = "Event title is required.";
    }

    if ($venue === "") {
        $errors[] = "Event venue is required.";
    }

    // 2. Validate the date and time format
    $date = DateTimeImmutable::createFromFormat(
        '!Y-m-d',
        $eventDate
    );

    $time = DateTimeImmutable::createFromFormat(
        '!H:i',
        $eventTime
    );

    if (
        !$date ||
        $date->format("Y-m-d") !== $eventDate
    ) {
        $errors[] = "Please enter a valid event date.";
    }

    if (
        !$time ||
        $time->format("H:i") !== $eventTime
    ) {
        $errors[] = "Please enter a valid event time.";
    }

    // 3. Only allow the categories offered by the form
    $allowedCategories = [
        "General",
        "Academic",
        "Sports",
        "Social"
    ];

    if (!in_array($category, $allowedCategories, true)) {
        $errors[] = "Please select a valid category.";
    }

    // 4. Prevent new events from being scheduled in the past
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

    // 5. Save the event if validation succeeds
    if (empty($errors)) {
        try {
            $sql = "INSERT INTO events
                    (title, description, event_date,
                     event_time, venue, category)
                    VALUES
                    (:title, :description, :event_date,
                     :event_time, :venue, :category)";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ":title" => $title,
                ":description" => $description,
                ":event_date" => $eventDate,
                ":event_time" => $eventTime,
                ":venue" => $venue,
                ":category" => $category
            ]);

            // Redirect to prevent accidental resubmission
            header("Location: add-event.php?success=1");
            exit;

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $errors[] = "Unable to save the event. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Campus Event</title>
</head>
<body>

    <h1>Add a Campus Event</h1>

    <?php if ($success): ?>
        <p>Event saved successfully!</p>
        <p><a href="add-event.php">Add another event</a></p>
    <?php endif; ?>

    <?php foreach ($errors as $error): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endforeach; ?>

    <form method="POST" action="add-event.php">
        <label for="title">Event title:</label><br>
        <input
            type="text"
            id="title"
            name="title"
            maxlength="150"
            value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
            required
        ><br><br>

        <label for="description">Description:</label><br>
        <textarea
            id="description"
            name="description"
        ><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></textarea><br><br>

        <label for="event_date">Event date:</label><br>
        <input
            type="date"
            id="event_date"
            name="event_date"
            value="<?= htmlspecialchars($eventDate, ENT_QUOTES, 'UTF-8') ?>"
            required
        ><br><br>

        <label for="event_time">Event time:</label><br>
        <input
            type="time"
            id="event_time"
            name="event_time"
            value="<?= htmlspecialchars($eventTime, ENT_QUOTES, 'UTF-8') ?>"
            required
        ><br><br>

        <label for="venue">Venue:</label><br>
        <input
            type="text"
            id="venue"
            name="venue"
            maxlength="150"
            value="<?= htmlspecialchars($venue, ENT_QUOTES, 'UTF-8') ?>"
            required
        ><br><br>

        <label for="category">Category:</label><br>
        <select id="category" name="category">
            <?php foreach (
                ["General", "Academic", "Sports", "Social"] as $option
            ): ?>
                <option
                    value="<?= $option ?>"
                    <?= $category === $option ? "selected" : "" ?>
                >
                    <?= $option ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <button type="submit">Save Event</button>
    </form>

</body>
</html>