<?php
session_start();

unset(
    $_SESSION["student_id"],
    $_SESSION["student_name"]
);
session_regenerate_id(true);

header("Location: student-login.php");
exit;