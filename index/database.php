<?php
    $servername = "localhost";
    $dbusername = "root";
    $dbpassword = "";
    $dbname = "JboyDB";
    $conn = "";

    try{
    $conn = mysqli_connect($servername,
                            $dbusername,
                            $dbpassword,
                            $dbname);  
    }
    catch(mysqli_sql_exception){
        echo "You are not connected to the databse, therefore some functions won't work. Please contact website owner or developer if problem persists.";
    }
?>