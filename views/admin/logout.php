<?php
session_start();
session_unset(); // optional: clears all session variables
session_destroy(); // destroy the session

header("Location: login.php"); // redirect to login page (change if your login is in another path)
exit();
?>