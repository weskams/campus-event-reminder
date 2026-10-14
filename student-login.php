<?php
session_start();
require_once "database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_var(
        trim($_POST["email"] ?? ""),
        FILTER_VALIDATE_EMAIL
    );
    $password = $_POST["password"] ?? "";

    if ($email && is_string($password)) {
        $stmt = $pdo->prepare(
            "SELECT id, full_name, email, password_hash
             FROM students
             WHERE email = :email"
        );

        $stmt->execute(["email" => $email]);
        $student = $stmt->fetch();

        if (
            $student &&
            password_verify($password, $student["password_hash"])
        ) {
            session_regenerate_id(true);

            // Keep student identity separate from organiser identity.
            unset(
                $_SESSION["organiser_id"],
                $_SESSION["organiser_name"]
            );

            $_SESSION["student_id"] = $student["id"];
            $_SESSION["student_name"] = $student["full_name"];

            header("Location: student-dashboard.php");
            exit;
        }
    }

    $error = "Invalid email or password.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
</head>
<body>
    <h1>Student Login</h1>

    <?php if ($error !== ""): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Email Address</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Log In</button>
    </form>

    <p><a href="student-register.php">Create a student account</a></p>
</body>
</html>