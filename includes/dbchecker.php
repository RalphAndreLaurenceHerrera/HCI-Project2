<?php
// Database Connection Checker

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "JBoyDB";
$conn = "";

try{
    $conn = new mysqli($servername, $username, $password, $dbname);  
    if ($conn->connect_errno) {
        throw new mysqli_sql_exception("Connect error: " . $conn->connect_error);
    }
}
catch(mysqli_sql_exception){
    http_response_code(500);
    echo "Database connection error. Some functions won't work as intended.";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    exit();
}

return $conn;
?>