<?php
// Database Connection Checker
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "JBoyDB";

    try{
    $conn = mysqli_connect($servername, $username, $password, $dbname);  
    }
    catch(mysqli_sql_exception){
        echo "You are not connected, some functions won't work as intended. Please contact website owner or developer.";
    }
?>