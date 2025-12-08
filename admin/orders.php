<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
require_once(BASE_PATH . 'includes/dbchecker.php');
require_once(BASE_PATH . 'includes/admin_auth.php');
require_once(BASE_PATH . 'includes/idgenerator.php');
include_once(BASE_PATH . 'includes/admin-head.php');
include_once(BASE_PATH . 'includes/admin-header.php');
include_once(BASE_PATH . 'includes/admin-navigation.php');
include_once(BASE_PATH . 'includes/admin-footer.php');

$message = '';

// -------------------- Handle Create/Edit Order --------------------
if (isset($_POST['save_order'])) {
    $isEdit = !empty($_POST['orderID']);
    $orderID = $isEdit ? $_POST['orderID'] : generateOrderID($conn);
    $userID = $_POST['userID'];
    $deliveryFee = floatval($_POST['deliveryFee']);
    $deliveryAddress = trim($_POST['deliveryAddress']);
    $totalAmount = 0;

    $items = $_POST['items']; // array of ['itemID'=>..,'qty'=>..,'price'=>..]

    foreach ($items as $it) {
        $totalAmount += $it['qty'] * $it['price'];
    }
    $totalAmount += $deliveryFee;

    if ($isEdit) {
        // Update main order
        $stmt = $conn->prepare("UPDATE Orders SET userID=?, totalAmount=?, deliveryFee=?, deliveryAddress=? WHERE orderID=?");
        $stmt->bind_param("sddss", $userID, $totalAmount, $deliveryFee, $deliveryAddress, $orderID);
        $stmt->execute();
        // Clear old items
        $stmtDel = $conn->prepare("DELETE FROM OrderItem WHERE orderID=?");
        $stmtDel->bind_param("s",$orderID);
        $stmtDel->execute();
    } else {
        // Create new order
        $stmt = $conn->prepare("INSERT INTO Orders(orderID,userID,totalAmount,deliveryFee,deliveryAddress) VALUES(?,?,?,?,?)");
        $stmt->bind_param("sddds",$orderID,$userID,$totalAmount,$deliveryFee,$deliveryAddress);
        $stmt->execute();
    }

    // Insert order items
    $stmtItem = $conn->prepare("INSERT INTO OrderItem(orderItemID, orderID, itemID, quantity, itemPriceAtOrder) VALUES(?,?,?,?,?)");
    foreach ($items as $it) {
        $stmtItem->bind_param("sssid", generateOrderID($conn), $orderID, $it['itemID'], $it['qty'], $it['price']);
        $stmtItem->execute();
    }

    $message = $isEdit ? "✅ Order $orderID updated." : "✅ Order $orderID created.";
}

// -------------------- Fetch Data --------------------
$users = $conn->query("SELECT * FROM Users ORDER BY firstName");
$categories = $conn->query("SELECT * FROM Category ORDER BY categoryName");
$itemsData = $conn->query("SELECT * FROM Items ORDER BY itemName");

// Fetch orders
$orders = $conn->query("
    SELECT o.*, u.firstName, u.lastName
    FROM Orders o
    LEFT JOIN Users u ON o.userID = u.userID
    ORDER BY o.orderedTime DESC
");

// Fetch payments
$payments = $conn->query("
    SELECT p.*, u.firstName, u.lastName, o.orderID 
    FROM Payments p 
    LEFT JOIN Orders o ON p.orderID = o.orderID 
    LEFT JOIN Users u ON o.userID = u.userID
    ORDER BY p.paymentID DESC
");

// Fetch reviews
$reviews = $conn->query("
    SELECT r.*, u.firstName, u.lastName
    FROM Reviews r
    LEFT JOIN Users u ON r.userID = u.userID
    ORDER BY r.reviewID DESC
");

// Helper function
function fetchOrderItems($conn, $orderID){
    $stmt = $conn->prepare("SELECT oi.*, i.itemName FROM OrderItem oi LEFT JOIN Items i ON oi.itemID=i.itemID WHERE oi.orderID=?");
    $stmt->bind_param("s",$orderID);
    $stmt->execute();
    return $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin — Orders Management</title>
<style>
.container{display:flex;gap:16px;align-items:flex-start;max-width:1200px;margin:18px auto;padding:12px;}
.left{flex:1.6}.right{flex:1;display:flex;flex-direction:column;gap:16px;}
.card{background:#fff;border:1px solid #eee;border-radius:6px;padding:12px;box-shadow:0 1px 3px rgba(0,0,0,.03);}
table{width:100%;border-collapse:collapse;font-size:0.95rem;}
th,td{border:1px solid #eee;padding:6px;text-align:left;vertical-align:top;}
th{background:#fafafa;}
.inline-form{display:inline-block;margin:0;}
.small{font-size:0.85rem;color:#666;}
.toggle-btn{cursor:pointer;color:#0073e6;text-decoration:underline;background:none;border:none;padding:0;font-size:0.95rem;}
.order-items{background:#f8f8f9;padding:8px;border-radius:4px;margin-top:8px;}
.add-item-row{margin:4px 0;display:flex;gap:4px;}
.add-item-row input, .add-item-row select{flex:1;padding:2px;}
</style>
</head>
<body>
<main class="container">

<div class="left card">
<h2>Create / Edit Order</h2>
<?php if($message) echo "<p style='color:green;'>".htmlspecialchars($message)."</p>"; ?>
<form method="post" id="orderForm">
<input type="hidden" name="orderID" id="orderID">
<label>User:</label>
<select name="userID" required>
<option value="">Select User</option>
<?php while($u=$users->fetch_assoc()): ?>
<option value="<?= $u['userID'] ?>"><?= htmlspecialchars($u['firstName'].' '.$u['lastName']) ?></option>
<?php endwhile; ?>
</select>
<br><label>Delivery Fee:</label>
<input type="number" step="0.01" name="deliveryFee" value="0" required>
<br><label>Delivery Address:</label>
<textarea name="deliveryAddress" required></textarea>
<br><label>Items:</label>
<div id="itemsContainer"></div>
<button type="button" id="addItemBtn">Add Item</button>
<br><br>
<button type="submit" name="save_order">Save Order</button>
</form>

<h2>Existing Orders</h2>
<table>
<tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Address</th><th>Items</th></tr>
<?php while($o=$orders->fetch_assoc()):
$orderID = $o['orderID'];
$itemsRes = fetchOrderItems($conn,$orderID);
?>
<tr>
<td><?= htmlspecialchars($orderID) ?></td>
<td><?= htmlspecialchars($o['firstName'].' '.$o['lastName']) ?></td>
<td>₱<?= number_format($o['totalAmount'],2) ?></td>
<td>
<form method="post" class="inline-form">
<input type="hidden" name="orderID" value="<?= htmlspecialchars($orderID) ?>">
<select name="orderStatus">
<?php
$statuses=['placed','confirmed','preparing','out-for-delivery','delivered','cancelled'];
foreach($statuses as $s){
$sel=($o['orderStatus']==$s)?'selected':'';
echo "<option value=\"$s\" $sel>".htmlspecialchars($s)."</option>";
}
?>
</select>
<button type="submit" name="update_order_status">Save</button>
</form>
</td>
<td><?= nl2br(htmlspecialchars($o['deliveryAddress'])) ?></td>
<td><button class="toggle-btn" data-target="#items-<?= htmlspecialchars($orderID) ?>">Show</button></td>
</tr>
<tr id="items-<?= htmlspecialchars($orderID) ?>" style="display:none;">
<td colspan="6">
<table>
<tr><th>Item</th><th>Qty</th><th>Price</th></tr>
<?php while($it=$itemsRes->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($it['itemName']) ?></td>
<td><?= (int)$it['quantity'] ?></td>
<td>₱<?= number_format($it['itemPriceAtOrder'],2) ?></td>
</tr>
<?php endwhile; ?>
</table>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>

<div class="right">
<div class="card" style="max-height:48vh; overflow:auto;">
<h3>Payments</h3>
<table>
<tr><th>ID</th><th>Order</th><th>User</th><th>Method</th><th>Ref</th><th>Status</th><th>Action</th></tr>
<?php while($p=$payments->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($p['paymentID']) ?></td>
<td><?= htmlspecialchars($p['orderID']) ?></td>
<td><?= htmlspecialchars($p['firstName'].' '.$p['lastName']) ?></td>
<td><?= htmlspecialchars($p['paymentMethod']) ?></td>
<td><?= htmlspecialchars($p['transactionReference'] ?? '—') ?></td>
<td><?= htmlspecialchars($p['paymentStatus'] ?? '—') ?></td>
<td>
<form method="post">
<input type="hidden" name="paymentID" value="<?= htmlspecialchars($p['paymentID']) ?>">
<select name="paymentStatus">
<?php $pst=['pending','success','failed']; foreach($pst as $s){$sel=($p['paymentStatus']==$s)?'selected':''; echo "<option value=\"$s\" $sel>".htmlspecialchars($s)."</option>";} ?>
</select>
<button type="submit" name="update_payment_status">Save</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
</div>

<div class="card" style="max-height:48vh; overflow:auto;">
<h3>Reviews</h3>
<table>
<tr><th>ID</th><th>Order</th><th>User</th><th>Rating</th><th>Comment</th></tr>
<?php while($r=$reviews->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($r['reviewID']) ?></td>
<td><?= htmlspecialchars($r['orderID']) ?></td>
<td><?= htmlspecialchars($r['firstName'].' '.$r['lastName']) ?></td>
<td><?= (int)$r['rating'] ?>/5</td>
<td><?= nl2br(htmlspecialchars($r['comment'] ?? '—')) ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>

</main>
<script>
document.querySelectorAll('.toggle-btn').forEach(btn=>{
btn.addEventListener('click',function(){
const id=this.getAttribute('data-target');
const el=document.querySelector(id);
if(!el)return;
el.style.display=(el.style.display==='none' || el.style.display==='')?'table-row':'none';
this.textContent=(el.style.display==='table-row')?'Hide':'Show';
});
});

const itemsContainer = document.getElementById('itemsContainer');
const itemsData = <?php
$arr=[];
while($it=$itemsData->fetch_assoc()){
$arr[]=$it;
}
echo json_encode($arr);
?>;

document.getElementById('addItemBtn').addEventListener('click',function(){
const row = document.createElement('div'); row.className='add-item-row';
const sel = document.createElement('select'); sel.name='items[][itemID]'; sel.required=true;
itemsData.forEach(it=>{const o=document.createElement('option');o.value=it.itemID;o.textContent=it.itemName+' ₱'+parseFloat(it.itemPrice).toFixed(2); sel.appendChild(o);});
const qty = document.createElement('input'); qty.type='number'; qty.name='items[][qty]'; qty.value=1; qty.min=1; qty.required=true;
const price = document.createElement('input'); price.type='number'; price.name='items[][price]'; price.step='0.01'; price.value=0; price.required=true;
const rm = document.createElement('button'); rm.type='button'; rm.textContent='Remove'; rm.addEventListener('click',()=>row.remove());
row.appendChild(sel); row.appendChild(qty); row.appendChild(price); row.appendChild(rm);
itemsContainer.appendChild(row);
});
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
