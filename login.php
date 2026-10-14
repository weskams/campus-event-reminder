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
            "SELECT id, name, email, password_hash
             FROM organisers
             WHERE email = :email"
        );

        $stmt->execute(["email" => $email]);
        $organiser = $stmt->fetch();

        if (
            $organiser &&
            password_verify($password, $organiser["password_hash"])
        ) {
            session_regenerate_id(true);

            $_SESSION["organiser_id"] = $organiser["id"];
            $_SESSION["organiser_name"] = $organiser["name"];

            header("Location: index.php");
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
    <title>Organiser Login</title>
</head>
<body>
    <h1>Organiser Login</h1>

    <?php if ($error !== ""): ?>
        <p><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></p>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email"
               placeholder="Email address" required>

        <input type="password" name="password"
               placeholder="Password" required>

        <button type="submit">Log In</button>
    </form>
</body>
</html>