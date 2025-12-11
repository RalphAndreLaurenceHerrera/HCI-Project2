<?php
    // BASE PATH Maker
    require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
    // Database - Functions Related
    require_once(BASE_PATH . 'includes/dbchecker.php');
    require_once(BASE_PATH . 'includes/admin_auth.php');
    require_once(BASE_PATH . 'includes/idgenerator.php');
    // HTML Related - TOP
    include_once(BASE_PATH . 'includes/admin-head.php');
    include_once(BASE_PATH . 'includes/admin-header.php');
    include_once(BASE_PATH . 'includes/admin-navigation.php');
    // HTML Related - BOTTOM
    include_once(BASE_PATH . 'includes/admin-footer.php');

// Database Dropper
/*Usage:
    dropDatabase($conn, 'databasename');
*/

function dropDatabase($conn, $dbname) {
    try {
        // Confirm before dropping
        if (!isset($_POST['confirm_drop'])) {
            echo "<h2>WARNING: You are about to drop the database: $dbname</h2>";
            echo "<form method='post'>
                    <button type='submit' name='confirm_drop'>Yes, Drop Database</button>
                  </form>";
            exit();
        }

        $conn->query("DROP DATABASE IF EXISTS $dbname");
        echo "<p>Database '$dbname' has been dropped successfully.</p>";

    } catch (mysqli_sql_exception $e) {
        die("<b>Error:</b> " . $e->getMessage());
    }
}

// Drop JBoyDB safely
dropDatabase($conn, 'JBoyDB');
?>