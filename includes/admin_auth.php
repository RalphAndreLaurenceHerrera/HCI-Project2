<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['userID']) || $_SESSION['userrole'] !== 'admin') {
    header("Location: /jboymakiandbento/index.php"); // redirect non-admins to homepage
    exit();
}
?>