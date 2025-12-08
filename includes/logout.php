<?php
session_start();
session_destroy();
header("Location: /jboymakiandbento/login.php");
exit();
?>