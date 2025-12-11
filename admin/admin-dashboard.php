<?php
    // BASE PATH Maker
    require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
    // Database - Functions Related
    require_once(BASE_PATH . 'includes/dbchecker.php');
    require_once(BASE_PATH . 'includes/admin_auth.php');
    require_once(BASE_PATH . 'includes/idgenerator.php');
    // HTML Related - TOP
    require_once(BASE_PATH . 'includes/admin/head.php');
    require_once(BASE_PATH . 'includes/admin/header.php');
    require_once(BASE_PATH . 'includes/admin/navigation.php');
    // HTML Related - BOTTOM
    require_once(BASE_PATH . 'includes/admin/footer.php');
    
// Basic Analytics
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM Users")->fetch_assoc()['total'];
$totalItems = $conn->query("SELECT COUNT(*) AS total FROM Items")->fetch_assoc()['total'];
$totalOrders = $conn->query("SELECT COUNT(*) AS total FROM Orders")->fetch_assoc()['total'];
$totalSales = $conn->query("SELECT IFNULL(SUM(totalAmount + deliveryFee),0) AS total FROM Orders WHERE orderStatus='delivered'")->fetch_assoc()['total'];

// Search functionality 
$searchType = $_GET['searchType'] ?? '';
$searchQuery = $_GET['q'] ?? '';
$results = [];

if ($searchQuery) {
    $query = "";
    $param = "%$searchQuery%";

    switch ($searchType) {
        case 'users':
            $stmt = $conn->prepare("SELECT * FROM Users WHERE firstName LIKE ? OR lastName LIKE ? OR email LIKE ? ORDER BY creationDate DESC");
            $stmt->bind_param("sss", $param, $param, $param);
            break;
        case 'items':
            $stmt = $conn->prepare("SELECT Items.*, SubCategory.subCategoryName FROM Items LEFT JOIN SubCategory ON Items.subCategoryID=SubCategory.subCategoryID WHERE Items.itemName LIKE ? ORDER BY Items.itemName");
            $stmt->bind_param("s", $param);
            break;
        case 'categories':
            $stmt = $conn->prepare("SELECT * FROM Category WHERE categoryName LIKE ? ORDER BY categoryName");
            $stmt->bind_param("s", $param);
            break;
        case 'orders':
            $stmt = $conn->prepare("SELECT Orders.*, Users.firstName, Users.lastName FROM Orders LEFT JOIN Users ON Orders.userID=Users.userID WHERE Orders.orderID LIKE ? OR Users.firstName LIKE ? OR Users.lastName LIKE ? ORDER BY Orders.orderedTime DESC");
            $stmt->bind_param("sss", $param, $param, $param);
            break;
    }

    if (isset($stmt)) {
        $stmt->execute();
        $results = $stmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Title -->
        <title>Admin Dashboard</title>
    </head>
    <body>
        <main>
            <h2>Analytics</h2>
            <ul>
                <li>Total Users: <?= $totalUsers ?></li>
                <li>Total Items: <?= $totalItems ?></li>
                <li>Total Orders: <?= $totalOrders ?></li>
                <li>Total Sales: ₱<?= number_format($totalSales,2) ?></li>
            </ul>

            <h2>Search</h2>
            <form method="get">
                <select name="searchType">
                    <option value="">Select Type</option>
                    <option value="users" <?= $searchType=='users'?'selected':'' ?>>Users</option>
                    <option value="items" <?= $searchType=='items'?'selected':'' ?>>Items</option>
                    <option value="categories" <?= $searchType=='categories'?'selected':'' ?>>Categories</option>
                    <option value="orders" <?= $searchType=='orders'?'selected':'' ?>>Orders</option>
                </select>
                <input type="text" name="q" placeholder="Search..." value="<?= htmlspecialchars($searchQuery) ?>">
                <button type="submit">Search</button>
            </form>

            <?php if($results && $results->num_rows > 0): ?>
                <h3>Search Results:</h3>
                <table border="1" cellpadding="5">
                    <tr>
                        <?php
                        $fields = $results->fetch_fields();
                        foreach($fields as $field) echo "<th>{$field->name}</th>";
                        ?>
                    </tr>
                    <?php
                    $results->data_seek(0);
                    while($row = $results->fetch_assoc()):
                        echo "<tr>";
                        foreach($row as $value) echo "<td>$value</td>";
                        echo "</tr>";
                    endwhile;
                    ?>
                </table>
            <?php elseif($searchQuery): ?>
                <p>No results found for '<?= htmlspecialchars($searchQuery) ?>'.</p>
            <?php endif; ?>
        </main>
        <script>

        </script>
    </body>
<html>

<?php
    mysqli_close($conn);
?>