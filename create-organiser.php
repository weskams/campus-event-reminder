<?php
require_once "database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = filter_var(
        trim($_POST["email"] ?? ""),
        FILTER_VALIDATE_EMAIL
    );
    $password = $_POST["password"] ?? "";

    if ($name === "" || !$email || strlen($password) < 10) {
        $message = "Enter a name, valid email, and password of at least 10 characters.";
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO organisers (name, email, password_hash)
                 VALUES (:name, :email, :password_hash)"
            );

            $stmt->execute([
                "name" => $name,
                "email" => $email,
                "password_hash" => $hash
            ]);

            $message = "Organiser account created successfully.";
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $message = "That email address is already registered.";
            } else {
                error_log($e->getMessage());
                $message = "Account creation failed.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Organiser</title>
</head>
<body>
    <h1>Create Organiser Account</h1>

    <p><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>

    <form method="POST">
        <input name="name" placeholder="Full name" required>
        <input name="email" type="email" placeholder="Email" required>
        <input name="password" type="password"
               minlength="10" placeholder="Password" required>
        <button type="submit">Create Account</button>
    </form>
</body>
</html>
