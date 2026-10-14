<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION["student_id"])) {
    header("Location: student-login.php");
    exit;
}