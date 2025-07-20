<?php
session_start();
session_destroy(); // Destroy the session
header("Location: signin.php"); // Redirect to sign-in page
exit();
?>
