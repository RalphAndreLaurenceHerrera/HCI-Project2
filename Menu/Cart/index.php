<?php
    session_start();
    // BASE PATH Maker
    require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
    // Database Related
    $conn = include_once(BASE_PATH . 'includes/dbchecker.php');
    require_once(BASE_PATH . 'includes/idgenerator.php');
    // HTML Related - TOP
    include_once(BASE_PATH . 'includes/customer-head.php');
    include_once(BASE_PATH . 'includes/customer-header.php');
    require_once(BASE_PATH . 'includes/customer-navigation.php');
    // HTML Related - BOTTOM
    include_once(BASE_PATH . 'includes/customer-footer.php');
$cart = $_SESSION['cart'] ?? [];

// Handle remove item
if(isset($_GET['remove'])){
    $removeID = $_GET['remove'];
    unset($_SESSION['cart'][$removeID]);
    header("Location: /jboymakiandbento/Menu/Cart/index.php");
    exit;
}

// Fetch item details for items in cart
$cartItems = [];
if($cart){
    $ids = implode("','", array_keys($cart));
    $res = $conn->query("SELECT itemID, itemName, itemPrice, itemImageLocation FROM Items WHERE itemID IN ('$ids')");
    while($row = $res->fetch_assoc()){
        $row['qty'] = $cart[$row['itemID']];
        $row['subtotal'] = $row['qty'] * $row['itemPrice'];
        $cartItems[] = $row;
    }
}

$total = array_sum(array_column($cartItems, 'subtotal'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cart</title>
<style>
table { width:100%; border-collapse:collapse; }
th, td { border:1px solid #ddd; padding:8px; text-align:left; }
img { width:80px; height:60px; object-fit:cover; }
</style>
</head>
<body>
<h2>Your Cart</h2>
<?php if($cartItems): ?>
<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Image</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($cartItems as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['itemName']) ?></td>
            <td><img src="<?= $item['itemImageLocation'] ?>" alt="<?= htmlspecialchars($item['itemName']) ?>"></td>
            <td>₱<?= number_format($item['itemPrice'],2) ?></td>
            <td><?= $item['qty'] ?></td>
            <td>₱<?= number_format($item['subtotal'],2) ?></td>
            <td><a href="?remove=<?= $item['itemID'] ?>" onclick="return confirm('Remove this item?')">Remove</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<h3>Total: ₱<?= number_format($total,2) ?></h3>
<?php else: ?>
<p>Your cart is empty.</p>
<?php endif; ?>
</body>
</html>