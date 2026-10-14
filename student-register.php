<?php
session_start();
require_once "database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullName = trim($_POST["full_name"] ?? "");
    $regNumber = trim($_POST["registration_number"] ?? "");
    $email = filter_var(
        trim($_POST["email"] ?? ""),
        FILTER_VALIDATE_EMAIL
    );
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    if (
        $fullName === "" ||
        $regNumber === "" ||
        !$email ||
        !is_string($password) ||
        strlen($password) < 10
    ) {
        $message = "Enter all details and use a password of at least 10 characters.";
    } elseif ($password !== $confirmPassword) {
        $message = "The passwords do not match.";
    } else {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO students
                    (full_name, registration_number, email, password_hash)
                 VALUES
                    (:full_name, :reg_number, :email, :password_hash)"
            );

            $stmt->execute([
                "full_name" => $fullName,
                "reg_number" => $regNumber,
                "email" => $email,
                "password_hash" => $hash
            ]);

            $message = "Registration successful! You can now log in.";
        } catch (PDOException $e) {
            if ($e->getCode() === "23000") {
                $message = "That email or registration number is already registered.";
            } else {
                error_log($e->getMessage());
                $message = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
</head>
<body>
    <h1>Create Student Account</h1>

    <p><?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?></p>

    <form method="POST">
        <label>Full Name</label>
        <input type="text" name="full_name" required>

        <label>Registration Number</label>
        <input type="text" name="registration_number" required>

        <label>Email Address</label>
        <input type="email" name="email" required>

        <label>Password (minimum 10 characters)</label>
        <input type="password" name="password" minlength="10" required>

        <label>Confirm Password</label>
        <input type="password" name="confirm_password" minlength="10" required>

        <button type="submit">Register</button>
    </form>

    <p><a href="student-login.php">Already registered? Log in</a></p>
</body>
</html>
