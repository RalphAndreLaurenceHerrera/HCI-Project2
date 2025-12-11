<?php
    session_start();
    
    // BASE PATH Maker
    require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
    // Database Related
    require_once(BASE_PATH . 'includes/dbchecker.php');
    // HTML Related - TOP
    require_once(BASE_PATH . 'includes/menu/head.php');
    require_once(BASE_PATH . 'includes/menu/header.php');
    require_once(BASE_PATH . 'includes/menu/navigation.php');
    // HTML Related - BOTTOM
    require_once(BASE_PATH . 'includes/menu/footer.php');

    // Get category from the current page or URL
    $categoryName = basename($_SERVER['PHP_SELF'], ".php"); // e.g., Bento-Rice-Meals
    $categoryName = str_replace("-", " ", $categoryName); // optional: replace dash with space

    // Initialize cart if not exists
    if(!isset($_SESSION['cart'])){
        $_SESSION['cart'] = [];
    }

    // Handle adding item via POST
    if(isset($_POST['add_to_cart'])){
        $itemID = $_POST['itemID'];
        $qty = max(1, intval($_POST['qty']));

        // If item exists, increase quantity
        if(isset($_SESSION['cart'][$itemID])){
            $_SESSION['cart'][$itemID] += $qty;
        } else {
            $_SESSION['cart'][$itemID] = $qty;
        }

        $message = "✅ Added $qty item(s) to cart.";
    }

// Fetch items for this category
$sql = "
    SELECT i.*, s.subCategoryName, c.categoryName 
    FROM Items i
    LEFT JOIN SubCategory s ON i.subCategoryID = s.subCategoryID
    LEFT JOIN ItemCategory ic ON i.itemID = ic.itemID
    LEFT JOIN Category c ON ic.categoryID = c.categoryID
    WHERE c.categoryName = ?
    ORDER BY s.subCategoryName, i.itemName
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $categoryName);
$stmt->execute();
$itemsRes = $stmt->get_result();
$items = [];
while($row = $itemsRes->fetch_assoc()){
    $items[$row['subCategoryName']][] = $row; // group by subcategory
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($categoryName) ?> - Menu | JBOY MAKI and BENTI Food House</title>
<link rel="stylesheet" href="menu-style.css"> <!-- your CSS -->
<style>
.container { display:flex; max-width:1200px; margin:auto; }
.sidebar { width:200px; padding:10px; }
.main { flex:1; padding:10px; }
.item-card { display:inline-block; width:180px; margin:10px; cursor:pointer; border:1px solid #ccc; padding:5px; text-align:center; background-color: #d1beb0;}
.item-card img { width:160px; height:120px; object-fit:cover; }
.item-detail-panel { position:fixed; top:0; right:-400px; width:400px; height:100%; background:#fff; box-shadow:-2px 0 5px rgba(0,0,0,0.3); transition:0.3s; padding:20px; overflow:auto; }
.item-detail-panel.active { right:0; background-color: #d1beb0;}
</style>
</head>
<body>
<?php if(isset($message)) echo "<p style='color:green;'>$message</p>"; ?>
<div class="container">
    <!-- LEFT SIDEBAR: Categories -->
    <div class="sidebar">
        <h3>Categories</h3>
        <ul>
            <li><a href="Bento-Rice-Meals.php">Bento Rice Meals</a></li>
            <li><a href="Maki-Rolls.php">Maki Rolls</a></li>
            <li><a href="Popular.php">Popular</a></li>
            <li><a href="Ramen.php">Ramen</a></li>
            <li><a href="Salad.php">Salad</a></li>
            <li><a href="Sushi-Rolls.php">Sushi Rolls</a></li>
        </ul>
    </div>

    <!-- MAIN CONTENT: Items by subcategory -->
    <div class="main">
        <?php foreach($items as $subcat => $subItems): ?>
            <h3><?= htmlspecialchars($subcat) ?></h3>
            <div class="subcategory-items">
                <?php foreach($subItems as $item): ?>
                    <div class="item-card" data-id="<?= $item['itemID'] ?>" data-name="<?= htmlspecialchars($item['itemName']) ?>" data-price="<?= $item['itemPrice'] ?>" data-desc="<?= htmlspecialchars($item['itemDesc']) ?>" data-img="<?= $item['itemImageLocation'] ?>" data-subcat="<?= htmlspecialchars($item['subCategoryName']) ?>" data-cat="<?= htmlspecialchars($item['categoryName']) ?>">
                        <img src="<?= $item['itemImageLocation'] ?>" alt="<?= htmlspecialchars($item['itemName']) ?>">
                        <p><?= htmlspecialchars($item['itemName']) ?></p>
                        <p>₱<?= number_format($item['itemPrice'],2) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ITEM DETAIL PANEL -->
<div class="item-detail-panel" id="itemDetail">
    <button id="closeDetail">Close</button>
    <h2 id="detailName"></h2>
    <img id="detailImg" src="" alt="" style="width:100%; height:auto;">
    <p id="detailDesc"></p>
    <p>Price: ₱<span id="detailPrice"></span></p>
    <p>Subcategory: <span id="detailSubcat"></span></p>
    <p>Category: <span id="detailCat"></span></p>
    <div>
        <button id="decQty">-</button>
        <input type="text" id="qtyInput" value="1" style="width:30px;text-align:center;">
        <button id="incQty">+</button>
    </div>
    <form method="post">
        <input type="hidden" name="itemID" id="cartItemID">
        <input type="hidden" name="qty" id="cartQty" value="1">
        <button type="submit" name="add_to_cart">Add to Cart</button>
    </form>
</div>

<script>
let currentItemID;
const detailPanel = document.getElementById('itemDetail');
const detailName = document.getElementById('detailName');
const detailImg = document.getElementById('detailImg');
const detailDesc = document.getElementById('detailDesc');
const detailPrice = document.getElementById('detailPrice');
const detailSubcat = document.getElementById('detailSubcat');
const detailCat = document.getElementById('detailCat');
const qtyInput = document.getElementById('qtyInput');
const cartItemID = document.getElementById('cartItemID');
const cartQty = document.getElementById('cartQty');

document.querySelectorAll('.item-card').forEach(card => {
    card.addEventListener('click', ()=>{
        currentItemID = card.dataset.id;
        detailName.textContent = card.dataset.name;
        detailImg.src = card.dataset.img;
        detailDesc.textContent = card.dataset.desc;
        detailPrice.textContent = card.dataset.price;
        detailSubcat.textContent = card.dataset.subcat;
        detailCat.textContent = card.dataset.cat;
        qtyInput.value = 1;
        cartItemID.value = currentItemID;
        cartQty.value = 1;
        detailPanel.classList.add('active');
    });
});

document.getElementById('closeDetail').addEventListener('click', ()=>{
    detailPanel.classList.remove('active');
});

document.getElementById('incQty').addEventListener('click', ()=>{ qtyInput.value = parseInt(qtyInput.value)+1; cartQty.value = qtyInput.value; });
document.getElementById('decQty').addEventListener('click', ()=>{ 
    let v = parseInt(qtyInput.value); 
    if(v>1) qtyInput.value = v-1; 
    cartQty.value = qtyInput.value;
});
qtyInput.addEventListener('change', ()=>{ cartQty.value = qtyInput.value; });
</script>
</body>
</html>