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
$message = '';

// ---------------- ORDERS CRUD ----------------

// ---------- HANDLE ADD ORDER ----------
if(isset($_POST['add_order'])){
    $userID = $_POST['userID'];
    $deliveryAddress = trim($_POST['deliveryAddress']);
    $deliveryFee = floatval($_POST['deliveryFee']);
    
    // items & quantities
    $items = $_POST['itemID'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    
    if(empty($userID) || empty($items)){
        $message = "❌ Please select a user and at least one item.";
    } else {
        // Calculate total amount
        $total = 0;
        $orderItems = [];
        for($i=0;$i<count($items);$i++){
            $itemID = $items[$i];
            $qty = max(1,intval($quantities[$i]));
            // Fetch item price
            $stmt = $conn->prepare("SELECT itemPrice FROM Items WHERE itemID=?");
            $stmt->bind_param("s",$itemID);
            $stmt->execute();
            $price = $stmt->get_result()->fetch_assoc()['itemPrice'];
            $total += $price * $qty;
            $orderItems[] = ['itemID'=>$itemID,'qty'=>$qty,'price'=>$price];
        }
        $total += $deliveryFee;

        // Generate orderID
        $orderID = generateOrderID($conn);

        // Insert into Orders
        $stmt = $conn->prepare("INSERT INTO Orders (orderID,userID,totalAmount,deliveryFee,deliveryAddress) VALUES (?,?,?,?,?)");
        $stmt->bind_param("ssdds",$orderID,$userID,$total,$deliveryFee,$deliveryAddress);
        $stmt->execute();

        // Insert each item into OrderItem
        foreach($orderItems as $oi){
            $orderItemID = generateOrderItemID($conn);
            $stmt2 = $conn->prepare("INSERT INTO OrderItem (orderItemID,orderID,itemID,quantity,itemPriceAtOrder) VALUES (?,?,?,?,?)");
            $stmt2->bind_param("sssdd",$orderItemID,$orderID,$oi['itemID'],$oi['qty'],$oi['price']);
            $stmt2->execute();
        }

        $message = "✅ Order added successfully: ".$orderID;
    }
}

// Fetch users for dropdown
$usersList = $conn->query("SELECT userID, firstName, lastName FROM Users ORDER BY firstName ASC");

// Fetch items for dropdown
$itemsList = $conn->query("SELECT itemID, itemName, itemPrice FROM Items ORDER BY itemName ASC");


// Delete Order
if(isset($_GET['delete_order'])){
    $delID = $_GET['delete_order'];
    $stmt = $conn->prepare("DELETE FROM Orders WHERE orderID=?");
    $stmt->bind_param("s", $delID);
    $stmt->execute();
    $message = "✅ Order deleted successfully.";
}

// Update Order Status
if(isset($_POST['update_order'])){
    $orderID = $_POST['orderID'];
    $status = $_POST['orderStatus'];
    $stmt = $conn->prepare("UPDATE Orders SET orderStatus=? WHERE orderID=?");
    $stmt->bind_param("ss", $status, $orderID);
    $stmt->execute();
    $message = "✅ Order status updated successfully.";
}

// Fetch orders with user info
$orders = $conn->query("
    SELECT 
        o.orderID,
        o.userID,
        u.firstName,
        u.lastName,
        o.totalAmount,
        o.deliveryFee,
        o.orderStatus,
        o.deliveryAddress
    FROM Orders o
    LEFT JOIN Users u ON u.userID = o.userID
    ORDER BY o.orderedTime DESC
");

// Fetch reviews mapped by orderID
$reviewsResult = $conn->query("SELECT * FROM Reviews ORDER BY reviewID ASC");
$reviewsByOrder = [];
while($r = $reviewsResult->fetch_assoc()){
    $reviewsByOrder[$r['orderID']][] = $r;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin - Orders & Reviews</title>
<style>
.container { padding:20px; max-width:1200px; margin:auto; display:flex; gap:20px; }
.orders, .reviews { flex:1; }
table { width:100%; border-collapse:collapse; }
th, td { border:1px solid #ddd; padding:8px; text-align:left; }
.msg { margin:10px 0; color:green; }
form.inline { display:inline; }
select { padding:4px; }
</style>
</head>
<body>
<main class="container">

<div class="orders">
    <?php if($message) echo "<p class='msg'>".htmlspecialchars($message)."</p>"; ?>
<section>
<h2>Add New Order</h2>
<form method="post" style="margin-bottom:20px;">
    <div class="form-row">
        <select name="userID" required>
            <option value="">Select User</option>
            <?php while($u=$usersList->fetch_assoc()): ?>
                <option value="<?= $u['userID'] ?>"><?= htmlspecialchars($u['firstName'].' '.$u['lastName']) ?></option>
            <?php endwhile; ?>
        </select>
        <input type="text" name="deliveryAddress" placeholder="Delivery Address" required>
        <input type="number" name="deliveryFee" placeholder="Delivery Fee" step="0.01" value="0" required>
    </div>
    <div id="itemsContainer">
        <div class="form-row">
            <select name="itemID[]" required>
                <option value="">Select Item</option>
                <?php
                mysqli_data_seek($itemsList,0); // reset pointer
                while($it=$itemsList->fetch_assoc()): ?>
                    <option value="<?= $it['itemID'] ?>"><?= htmlspecialchars($it['itemName']).' - '.$it['itemPrice'] ?></option>
                <?php endwhile; ?>
            </select>
            <input type="number" name="quantity[]" value="1" min="1" required>
        </div>
    </div>
    <button type="button" onclick="addItemRow()">+ Add Another Item</button>
    <div style="margin-top:8px;">
        <button type="submit" name="add_order">Add Order</button>
    </div>
</form>
</section>

<script>
function addItemRow(){
    const container = document.getElementById('itemsContainer');
    const row = container.children[0].cloneNode(true);
    row.querySelectorAll('input').forEach(i=>i.value=1);
    row.querySelector('select').selectedIndex = 0;
    container.appendChild(row);
}
</script>
    <h2>All Orders</h2>
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>User</th>
                <th>Total Amount</th>
                <th>Delivery Fee</th>
                <th>Status</th>
                <th>Delivery Address</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($o = $orders->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($o['orderID']) ?></td>
                <td><?= htmlspecialchars($o['firstName'].' '.$o['lastName']) ?></td>
                <td><?= htmlspecialchars($o['totalAmount']) ?></td>
                <td><?= htmlspecialchars($o['deliveryFee']) ?></td>
                <td>
                    <form method="post" class="inline">
                        <input type="hidden" name="orderID" value="<?= htmlspecialchars($o['orderID']) ?>">
                        <select name="orderStatus">
                            <?php
                            $statuses = ['placed','confirmed','preparing','out-for-delivery','delivered','cancelled'];
                            foreach($statuses as $s){
                                $sel = $o['orderStatus']==$s ? 'selected' : '';
                                echo "<option value='$s' $sel>".ucfirst($s)."</option>";
                            }
                            ?>
                        </select>
                        <button type="submit" name="update_order">Save</button>
                    </form>
                </td>
                <td><?= htmlspecialchars($o['deliveryAddress']) ?></td>
                <td>
                    <a href="?delete_order=<?= $o['orderID'] ?>" onclick="return confirm('Delete this order?')">Delete</a> |
                    <a href="#reviews_<?= $o['orderID'] ?>">View Reviews</a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<div class="reviews">
    <h2>Order Reviews / Feedback</h2>
    <?php foreach($reviewsByOrder as $orderID => $revList): ?>
        <div id="reviews_<?= $orderID ?>" style="border:1px solid #ccc; padding:10px; margin-bottom:12px;">
            <h4>Order: <?= htmlspecialchars($orderID) ?></h4>
            <?php foreach($revList as $rev): ?>
                <p>
                    <strong>User:</strong> <?= htmlspecialchars($rev['userID']) ?><br>
                    <strong>Rating:</strong> <?= htmlspecialchars($rev['rating']) ?>/5<br>
                    <strong>Comment:</strong> <?= htmlspecialchars($rev['comment']) ?>
                </p>
                <hr>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
</div>

</main>
</body>
</html>

<?php mysqli_close($conn); ?>
