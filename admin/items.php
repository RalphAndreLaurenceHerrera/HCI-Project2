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
$editing = false;
$editItem = null;

// ---------- FETCH SUBCATEGORIES AND CATEGORIES ----------
$subcategories = $conn->query("SELECT * FROM SubCategory ORDER BY subCategoryName ASC");
$categories = $conn->query("SELECT * FROM Category ORDER BY categoryName ASC");

// ---------- HANDLE ADD ITEM ----------
if (isset($_POST['add_item'])) {
    $itemName = trim($_POST['itemName']);
    $itemPrice = floatval($_POST['itemPrice']);
    $itemDesc = trim($_POST['itemDesc']);
    $subCategoryID = $_POST['subCategoryID'];
    $categoryIDs = $_POST['categoryIDs'] ?? [];
    $itemAvail = isset($_POST['itemAvail']) ? 1 : 0;
    $itemImage = $_FILES['itemImage'] ?? null;

    if ($itemName === '' || $itemPrice <= 0) {
        $message = "❌ Please provide valid item name and price.";
    } else {
        $itemID = generateItemID($conn);

        // Handle image upload
        $imgPath = '';
        if ($itemImage && $itemImage['tmp_name']) {
            $ext = pathinfo($itemImage['name'], PATHINFO_EXTENSION);
            $imgName = $itemID . '.' . $ext;
            $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/assets/images/item-images/';
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
            $targetFile = $targetDir . $imgName;
            if (move_uploaded_file($itemImage['tmp_name'], $targetFile)) {
                $imgPath = '/jboymakiandbento/assets/images/item-images/' . $imgName;
            }
        }

        // Insert into Items
        $stmt = $conn->prepare("
            INSERT INTO Items (itemID, subCategoryID, itemName, itemPrice, itemDesc, itemAvail, itemImageLocation)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("sssdiss", $itemID, $subCategoryID, $itemName, $itemPrice, $itemDesc, $itemAvail, $imgPath);
        $stmt->execute();

        // Insert into ItemCategory
        foreach ($categoryIDs as $catID) {
            $stmtIC = $conn->prepare("INSERT INTO ItemCategory (itemID, categoryID) VALUES (?, ?)");
            $stmtIC->bind_param("ss", $itemID, $catID);
            $stmtIC->execute();
        }

        $message = "✅ Item added successfully. ID: " . htmlspecialchars($itemID);
    }
}

// ---------- HANDLE DELETE ITEM ----------
if (isset($_GET['delete'])) {
    $delID = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM Items WHERE itemID=?");
    $stmt->bind_param("s", $delID);
    $stmt->execute();

    // Remove associated categories
    $stmtCat = $conn->prepare("DELETE FROM ItemCategory WHERE itemID=?");
    $stmtCat->bind_param("s", $delID);
    $stmtCat->execute();

    $message = "✅ Item deleted successfully.";
}

// ---------- HANDLE EDIT ITEM (show form) ----------
if (isset($_GET['edit'])) {
    $editing = true;
    $editID = $_GET['edit'];

    $stmt = $conn->prepare("SELECT * FROM Items WHERE itemID=?");
    $stmt->bind_param("s", $editID);
    $stmt->execute();
    $editItem = $stmt->get_result()->fetch_assoc();

    // Fetch selected categories
    $catRes = $conn->query("SELECT categoryID FROM ItemCategory WHERE itemID='" . $conn->real_escape_string($editID) . "'");
    $editItem['categories'] = [];
    while ($row = $catRes->fetch_assoc()) $editItem['categories'][] = $row['categoryID'];
}

// ---------- HANDLE UPDATE ITEM ----------
if (isset($_POST['update_item'])) {
    $itemID = $_POST['itemID'];
    $itemName = trim($_POST['itemName']);
    $itemPrice = floatval($_POST['itemPrice']);
    $itemDesc = trim($_POST['itemDesc']);
    $subCategoryID = $_POST['subCategoryID'];
    $categoryIDs = $_POST['categoryIDs'] ?? [];
    $itemAvail = isset($_POST['itemAvail']) ? 1 : 0;
    $itemImage = $_FILES['itemImage'] ?? null;

    if ($itemName === '' || $itemPrice <= 0) {
        $message = "❌ Please provide valid item name and price.";
    } else {
        // Handle new image upload
        $imgPath = $_POST['existingImage'] ?? '';
        if ($itemImage && $itemImage['tmp_name']) {
            $ext = pathinfo($itemImage['name'], PATHINFO_EXTENSION);
            $imgName = $itemID . '.' . $ext;
            $targetDir = $_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/assets/images/item-images/';
            $targetFile = $targetDir . $imgName;
            if (move_uploaded_file($itemImage['tmp_name'], $targetFile)) {
                $imgPath = '/jboymakiandbento/assets/images/item-images/' . $imgName;
            }
        }

        $stmt = $conn->prepare("
            UPDATE Items
            SET subCategoryID=?, itemName=?, itemPrice=?, itemDesc=?, itemAvail=?, itemImageLocation=?
            WHERE itemID=?
        ");
        $stmt->bind_param("ssdsiss", $subCategoryID, $itemName, $itemPrice, $itemDesc, $itemAvail, $imgPath, $itemID);
        $stmt->execute();

        // Update categories
        $conn->query("DELETE FROM ItemCategory WHERE itemID='" . $conn->real_escape_string($itemID) . "'");
        foreach ($categoryIDs as $catID) {
            $stmtIC = $conn->prepare("INSERT INTO ItemCategory (itemID, categoryID) VALUES (?, ?)");
            $stmtIC->bind_param("ss", $itemID, $catID);
            $stmtIC->execute();
        }

        $message = "✅ Item updated successfully.";
        // Refresh editItem
        $stmt = $conn->prepare("SELECT * FROM Items WHERE itemID=?");
        $stmt->bind_param("s", $itemID);
        $stmt->execute();
        $editItem = $stmt->get_result()->fetch_assoc();
        $editing = true;
    }
}

// ---------- FETCH ITEMS LIST ----------
$items = $conn->query("
    SELECT i.*, 
           sc.subCategoryName, 
           GROUP_CONCAT(c.categoryName) AS categoryNames
    FROM Items i
    LEFT JOIN SubCategory sc ON i.subCategoryID = sc.subCategoryID
    LEFT JOIN ItemCategory ic ON i.itemID = ic.itemID
    LEFT JOIN Category c ON ic.categoryID = c.categoryID
    GROUP BY i.itemID
    ORDER BY i.itemID DESC
");
?>
        <style>
            .container { padding:20px; max-width:1100px; margin:auto; }
            .form-row { display:flex; gap:8px; margin-bottom:8px; }
            input[type="text"], input[type="email"], input[type="password"], select { padding:8px; width:100%; }
            table { width:100%; border-collapse:collapse; }
            th, td { border:1px solid #ddd; padding:8px; text-align:left; }
            .msg { margin:10px 0; color:green; }
            .error { color:red; }
        </style>
<main class="container">
    <?php if($message) echo "<p class='msg'>" . htmlspecialchars($message) . "</p>"; ?>

    <!-- ADD ITEM -->
    <?php if (!$editing): ?>
    <section>
        <h2>Add New Item</h2>
        <form method="post" enctype="multipart/form-data" style="margin-bottom:20px;">
            <div class="form-row">
                <input type="text" name="itemName" placeholder="Item Name" required>
                <input type="number" step="0.01" name="itemPrice" placeholder="Price" required>
            </div>
            <div class="form-row">
                <select name="subCategoryID" required>
                    <option value="">Select Subcategory</option>
                    <?php while($sc = $subcategories->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($sc['subCategoryID']) ?>"><?= htmlspecialchars($sc['subCategoryName']) ?></option>
                    <?php endwhile; ?>
                </select>
                <input type="file" name="itemImage">
                <label><input type="checkbox" name="itemAvail" checked> Available</label>
            </div>
            <div class="form-row">
                <textarea name="itemDesc" placeholder="Description"></textarea>
            </div>
            <div class="form-row">
                <label>Categories:</label>
                <?php
                    $categories->data_seek(0);
                    while($cat = $categories->fetch_assoc()): ?>
                        <label>
                            <input type="checkbox" name="categoryIDs[]" value="<?= htmlspecialchars($cat['categoryID']) ?>">
                            <?= htmlspecialchars($cat['categoryName']) ?>
                        </label>
                <?php endwhile; ?>
            </div>
            <div>
                <button type="submit" name="add_item">Add Item</button>
            </div>
        </form>
    </section>
    <?php endif; ?>

    <!-- EDIT ITEM -->
    <?php if ($editing && $editItem): ?>
    <section>
        <h2>Edit Item: <?= htmlspecialchars($editItem['itemID']) ?></h2>
        <form method="post" enctype="multipart/form-data" style="margin-bottom:20px;">
            <input type="hidden" name="itemID" value="<?= htmlspecialchars($editItem['itemID']) ?>">
            <input type="hidden" name="existingImage" value="<?= htmlspecialchars($editItem['itemImageLocation']) ?>">

            <div class="form-row">
                <input type="text" name="itemName" value="<?= htmlspecialchars($editItem['itemName']) ?>" required>
                <input type="number" step="0.01" name="itemPrice" value="<?= htmlspecialchars($editItem['itemPrice']) ?>" required>
            </div>
            <div class="form-row">
                <select name="subCategoryID" required>
                    <option value="">Select Subcategory</option>
                    <?php
                        $subcategories->data_seek(0);
                        while($sc = $subcategories->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($sc['subCategoryID']) ?>" <?= $editItem['subCategoryID']===$sc['subCategoryID']?'selected':'' ?>>
                                <?= htmlspecialchars($sc['subCategoryName']) ?>
                            </option>
                    <?php endwhile; ?>
                </select>
                <input type="file" name="itemImage">
                <label><input type="checkbox" name="itemAvail" <?= $editItem['itemAvail'] ? 'checked' : '' ?>> Available</label>
            </div>
            <div class="form-row">
                <textarea name="itemDesc"><?= htmlspecialchars($editItem['itemDesc']) ?></textarea>
            </div>
            <div class="form-row">
                <label>Categories:</label>
                <?php
                    $categories->data_seek(0);
                    while($cat = $categories->fetch_assoc()): ?>
                        <label>
                            <input type="checkbox" name="categoryIDs[]" value="<?= htmlspecialchars($cat['categoryID']) ?>"
                                <?= in_array($cat['categoryID'], $editItem['categories'] ?? []) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($cat['categoryName']) ?>
                        </label>
                <?php endwhile; ?>
            </div>
            <div>
                <button type="submit" name="update_item">Save Changes</button>
                <a href="items.php" style="margin-left:10px;">Cancel</a>
            </div>
        </form>
    </section>
    <?php endif; ?>

    <!-- ITEMS TABLE -->
    <section>
        <h2 style="margin-top:20px;">All Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Name</th>
                    <th>Subcategory</th>
                    <th>Price</th>
                    <th>Available</th>
                    <th>Image</th>
                    <th>Categories</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = $items->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($item['itemID']) ?></td>
                    <td><?= htmlspecialchars($item['itemName']) ?></td>
                    <td><?= htmlspecialchars($item['subCategoryName']) ?></td>
                    <td><?= htmlspecialchars($item['itemPrice']) ?></td>
                    <td><?= $item['itemAvail'] ? 'Yes' : 'No' ?></td>
                    <td>
                        <?php if($item['itemImageLocation']): ?>
                            <img src="<?= htmlspecialchars($item['itemImageLocation']) ?>" width="50">
                        <?php else: ?>
                            <em>—</em>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($item['categoryNames']) ?></td>
                    <td>
                        <a href="?edit=<?= urlencode($item['itemID']) ?>">Edit</a> |
                        <a href="?delete=<?= urlencode($item['itemID']) ?>" onclick="return confirm('Delete this item?')">Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </section>
</main>

<?php
mysqli_close($conn);
?>
