<?php
// Root Finder
require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');

// ID Generator
require_once(BASE_PATH . 'includes/idgenerator.php');

// Enable MySQLi exceptions
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "JBoyDB";

try {
    // Connect to MySQL (no DB selected yet)
    $conn = new mysqli($host, $user, $pass);
    $conn->set_charset("utf8mb4");

    // ---------------------------
    // CREATE DATABASE
    // ---------------------------
    $conn->query("CREATE DATABASE IF NOT EXISTS $db");
    $conn->select_db($db);

    // ---------------------------
    // TABLE CREATION
    // ---------------------------
    $queries = [
        // SubCategory Table
        "CREATE TABLE IF NOT EXISTS SubCategory(
            subCategoryID VARCHAR(50) PRIMARY KEY,
            subCategoryName VARCHAR(50) NOT NULL UNIQUE,
            subCategoryDesc TEXT
        )",

        // Category Table
        "CREATE TABLE IF NOT EXISTS Category(
            categoryID VARCHAR(50) PRIMARY KEY,
            categoryName VARCHAR(50) NOT NULL UNIQUE,
            categoryDesc TEXT
        )",

        // Items Table
        "CREATE TABLE IF NOT EXISTS Items(
            itemID VARCHAR(50) PRIMARY KEY,
            categoryID VARCHAR(50),
            subCategoryID VARCHAR(50),
            itemName VARCHAR(100) NOT NULL UNIQUE,
            itemPrice DECIMAL(10,2) NOT NULL,
            itemDesc TEXT,
            itemAvail TINYINT(1) DEFAULT 1 NOT NULL,
            itemImageLocation VARCHAR(255),
            FOREIGN KEY (categoryID) REFERENCES Category(categoryID) ON DELETE SET NULL,
            FOREIGN KEY (subCategoryID) REFERENCES SubCategory(subCategoryID) ON DELETE SET NULL
        )",

        // Junction Table: ItemCategorySubCategory
        "CREATE TABLE IF NOT EXISTS ItemCategorySubCategory(
            itemID VARCHAR(50) NOT NULL,
            categoryID VARCHAR(50) NOT NULL,
            subCategoryID VARCHAR(50) NOT NULL,
            PRIMARY KEY (itemID, categoryID, subCategoryID),
            FOREIGN KEY (itemID) REFERENCES Items(itemID) ON DELETE CASCADE,
            FOREIGN KEY (categoryID) REFERENCES Category(categoryID) ON DELETE CASCADE,
            FOREIGN KEY (subCategoryID) REFERENCES SubCategory(subCategoryID) ON DELETE CASCADE
        )",

        // Users Table
        "CREATE TABLE IF NOT EXISTS Users(
            userID VARCHAR(50) PRIMARY KEY,
            firstName VARCHAR(100) NOT NULL,
            lastName VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            hashPass VARCHAR(255) NOT NULL,
            contactNo VARCHAR(20),
            gender ENUM('M','F') NOT NULL,
            userrole ENUM('customer','admin') NOT NULL DEFAULT 'customer',
            creationDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        // UserAddress Table
        "CREATE TABLE IF NOT EXISTS UserAddress(
            addressID VARCHAR(50) PRIMARY KEY,
            userID VARCHAR(50) NOT NULL,
            addressLine VARCHAR(100),
            city VARCHAR(100),
            FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
        )",

        // Orders Table
        "CREATE TABLE IF NOT EXISTS Orders(
            orderID VARCHAR(50) PRIMARY KEY,
            userID VARCHAR(50) NOT NULL,
            totalAmount DECIMAL(10,2) NOT NULL,
            deliveryFee DECIMAL(10,2) NOT NULL,
            orderStatus ENUM('placed','confirmed','preparing','out-for-delivery','delivered','cancelled') NOT NULL DEFAULT 'placed',
            orderedTime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            deliveredTime TIMESTAMP NULL,
            deliveryAddress TEXT,
            FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
        )",

        // OrderItem Table
        "CREATE TABLE IF NOT EXISTS OrderItem(
            orderItemID VARCHAR(50) PRIMARY KEY,
            orderID VARCHAR(50) NOT NULL,
            itemID VARCHAR(50) NOT NULL,
            quantity INT UNSIGNED NOT NULL,
            itemPriceAtOrder DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (orderID) REFERENCES Orders(orderID) ON DELETE CASCADE,
            FOREIGN KEY (itemID) REFERENCES Items(itemID) ON DELETE CASCADE
        )",

        // Payments Table
        "CREATE TABLE IF NOT EXISTS Payments(
            paymentID VARCHAR(50) PRIMARY KEY,
            orderID VARCHAR(50) NOT NULL,
            paymentMethod ENUM('GCash','Cash-on-Delivery') NOT NULL,
            transactionReference VARCHAR(150) UNIQUE,
            paymentStatus ENUM('pending','success','failed'),
            FOREIGN KEY (orderID) REFERENCES Orders(orderID) ON DELETE CASCADE
        )",

        // Reviews Table
        "CREATE TABLE IF NOT EXISTS Reviews(
            reviewID VARCHAR(50) PRIMARY KEY,
            orderID VARCHAR(50) NOT NULL,
            userID VARCHAR(50) NOT NULL,
            rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
            comment TEXT,
            FOREIGN KEY (orderID) REFERENCES Orders(orderID) ON DELETE CASCADE,
            FOREIGN KEY (userID) REFERENCES Users(userID) ON DELETE CASCADE
        )",

        // Notices Table
        "CREATE TABLE IF NOT EXISTS Notices(
            noticeID VARCHAR(50) PRIMARY KEY,
            noticeTitle VARCHAR(150) NOT NULL UNIQUE,
            noticeSummary VARCHAR(255),
            noticeBody TEXT NOT NULL,
            noticeImageLocation VARCHAR(255),
            noticeActive TINYINT(1) DEFAULT 1 NOT NULL,
            noticeCreated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            noticeUpdated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            noticeLinkRelated TEXT
        )",

        // Indexes
        "CREATE INDEX IF NOT EXISTS idx_items_name ON Items(itemName)",
        "CREATE INDEX IF NOT EXISTS idx_items_category ON Items(categoryID)",
        "CREATE INDEX IF NOT EXISTS idx_orders_user ON Orders(userID)",
        "CREATE INDEX IF NOT EXISTS idx_orderitem_order ON OrderItem(orderID)"
    ];

    foreach ($queries as $q) {
        $conn->query($q);
    }

    // ---------------------------
    // NEW SYSTEM DETECTION
    // ---------------------------
    $check = $conn->query("SELECT COUNT(*) AS total FROM Users");
    $row = $check->fetch_assoc();
    $isNewSystem = ($row['total'] == 0);

    // ---------------------------
    // ADMIN SETUP FORM
    // ---------------------------
    if ($isNewSystem && !isset($_POST['setup_admin'])) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head><meta charset="utf-8"><title>Initial Setup</title></head>
        <body>
            <h2>🆕 New System Detected</h2>
            <p>Create the first admin account:</p>
            <form method="post">
                <input type="text" name="fname" placeholder="First Name" required><br><br>
                <input type="text" name="lname" placeholder="Last Name" required><br><br>
                <input type="email" name="email" placeholder="Email" required><br><br>
                <input type="password" name="password" placeholder="Password" required><br><br>
                <select name="gender" required>
                    <option value="">Select Gender</option>
                    <option value="M">Male</option>
                    <option value="F">Female</option>
                </select><br><br>
                <button type="submit" name="setup_admin">Create Admin</button>
            </form>
        </body>
        </html>
        <?php
        exit();
    }

    // ---------------------------
    // PROCESS ADMIN CREATION
    // ---------------------------
    if ($isNewSystem && isset($_POST['setup_admin'])) {
        $fname  = trim($_POST['fname']);
        $lname  = trim($_POST['lname']);
        $email  = trim($_POST['email']);
        $gender = $_POST['gender'];
        $hash   = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $role   = 'admin';
        $userID = generateUserID($conn, $role); // auto-generated

        $stmt = $conn->prepare("
            INSERT INTO Users
            (userID, firstName, lastName, email, hashPass, gender, userrole)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssssss", $userID, $fname, $lname, $email, $hash, $gender, $role);
        $stmt->execute();

        echo "<h3>✅ Admin account created successfully!</h3>";
        echo "<p>User ID: <b>" . htmlspecialchars($userID) . "</b></p>";
        echo '<p><a href="/jboymakiandbento/login.php">Go to Login</a></p>';
        exit();
    } else {
        echo "<h3>System is already initialized.</h3>";
    }

} catch (mysqli_sql_exception $e) {
    die("<b>Error:</b> " . $e->getMessage());
} finally {
    if (isset($conn)) $conn->close();
}
?>
