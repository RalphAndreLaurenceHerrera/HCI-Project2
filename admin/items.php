<?php
// BASE PATH Maker
require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
// Database - Functions Related
require_once(BASE_PATH . 'includes/dbchecker.php');
require_once(BASE_PATH . 'includes/admin_auth.php');
require_once(BASE_PATH . 'includes/idgenerator.php');
// HTML Related - TOP/BOTTOM
include_once(BASE_PATH . 'includes/admin-head.php');
include_once(BASE_PATH . 'includes/admin-header.php');
include_once(BASE_PATH . 'includes/admin-navigation.php');
include_once(BASE_PATH . 'includes/admin-footer.php');

$message = '';
$editingItem = false;
$editItem = null;
$editingSub = false;
$editSub = null;
$editingCat = false;
$editCat = null;

// ---------- HANDLE ADD SUBCATEGORY ----------
if (isset($_POST['add_subcategory'])) {
    $subName = trim($_POST['subCategoryName']);
    $subDesc = trim($_POST['subCategoryDesc'] ?? '');

    if ($subName === '') {
        $message = "❌ Subcategory name required.";
    } else {
        $chk = $conn->prepare("SELECT 1 FROM SubCategory WHERE subCategoryName = ?");
        $chk->bind_param("s", $subName);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $message = "❌ Subcategory already exists.";
        } else {
            $subID = generateSubCategoryID($conn);
            $ins = $conn->prepare("INSERT INTO SubCategory (subCategoryID, subCategoryName, subCategoryDesc) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $subID, $subName, $subDesc);
            $ins->execute();
            $message = "✅ Subcategory added ($subID).";
        }
    }
}

// ---------- HANDLE UPDATE SUBCATEGORY ----------
if (isset($_POST['update_subcategory'])) {
    $subID = $_POST['subCategoryID'];
    $subName = trim($_POST['subCategoryName']);
    $subDesc = trim($_POST['subCategoryDesc'] ?? '');

    if ($subName === '') {
        $message = "❌ Subcategory name required.";
    } else {
        // ensure not duplicate name for other id
        $chk = $conn->prepare("SELECT subCategoryID FROM SubCategory WHERE subCategoryName = ? AND subCategoryID != ?");
        $chk->bind_param("ss", $subName, $subID);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $message = "❌ Another subcategory uses that name.";
        } else {
            $upd = $conn->prepare("UPDATE SubCategory SET subCategoryName = ?, subCategoryDesc = ? WHERE subCategoryID = ?");
            $upd->bind_param("sss", $subName, $subDesc, $subID);
            $upd->execute();
            $message = "✅ Subcategory updated.";
        }
    }
}

// ---------- HANDLE DELETE SUBCATEGORY ----------
if (isset($_GET['delete_sub'])) {
    $delSub = $_GET['delete_sub'];
    // prevent deletion if categories exist under this subcategory
    $chk = $conn->prepare("SELECT 1 FROM Category WHERE subCategoryID = ? LIMIT 1");
    $chk->bind_param("s", $delSub);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $message = "❌ Cannot delete subcategory while categories exist under it. Delete or move those categories first.";
    } else {
        $del = $conn->prepare("DELETE FROM SubCategory WHERE subCategoryID = ?");
        $del->bind_param("s", $delSub);
        $del->execute();
        $message = "✅ Subcategory deleted.";
    }
}

// ---------- HANDLE ADD CATEGORY ----------
if (isset($_POST['add_category'])) {
    $catName = trim($_POST['categoryName']);
    $catDesc = trim($_POST['categoryDesc'] ?? '');
    $subCategoryID = $_POST['subCategoryID_for_cat'] ?? null;

    if ($catName === '') {
        $message = "❌ Category name required.";
    } else {
        $chk = $conn->prepare("SELECT 1 FROM Category WHERE categoryName = ?");
        $chk->bind_param("s", $catName);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $message = "❌ Category already exists.";
        } else {
            $categoryID = generateCategoryID($conn);
            $ins = $conn->prepare("INSERT INTO Category (categoryID, subCategoryID, categoryName, categoryDesc) VALUES (?, ?, ?, ?)");
            $ins->bind_param("ssss", $categoryID, $subCategoryID, $catName, $catDesc);
            $ins->execute();
            $message = "✅ Category added ($categoryID).";
        }
    }
}

// ---------- HANDLE UPDATE CATEGORY ----------
if (isset($_POST['update_category'])) {
    $categoryID = $_POST['categoryID'];
    $catName = trim($_POST['categoryName']);
    $catDesc = trim($_POST['categoryDesc'] ?? '');
    $subCategoryID = $_POST['subCategoryID_for_cat'] ?? null;

    if ($catName === '') {
        $message = "❌ Category name required.";
    } else {
        $chk = $conn->prepare("SELECT categoryID FROM Category WHERE categoryName = ? AND categoryID != ?");
        $chk->bind_param("ss", $catName, $categoryID);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $message = "❌ Another category uses that name.";
        } else {
            $upd = $conn->prepare("UPDATE Category SET categoryName = ?, categoryDesc = ?, subCategoryID = ? WHERE categoryID = ?");
            $upd->bind_param("ssss", $catName, $catDesc, $subCategoryID, $categoryID);
            $upd->execute();
            $message = "✅ Category updated.";
        }
    }
}

// ---------- HANDLE DELETE CATEGORY ----------
if (isset($_GET['delete_cat'])) {
    $delCat = $_GET['delete_cat'];
    // prevent deletion if items exist under this category
    $chk = $conn->prepare("SELECT 1 FROM Items WHERE categoryID = ? LIMIT 1");
    $chk->bind_param("s", $delCat);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        $message = "❌ Cannot delete category while items exist under it. Reassign or delete those items first.";
    } else {
        $del = $conn->prepare("DELETE FROM Category WHERE categoryID = ?");
        $del->bind_param("s", $delCat);
        $del->execute();
        $message = "✅ Category deleted.";
    }
}

// ---------- ADD ITEM ----------
if (isset($_POST['add_item'])) {
    $itemName = trim($_POST['itemName']);
    $categoryIDs = $_POST['categoryIDs'] ?? [];
    $itemPrice = $_POST['itemPrice'];
    $itemDesc = trim($_POST['itemDesc'] ?? '');
    $itemAvail = isset($_POST['itemAvail']) ? 1 : 0;

    if ($itemName === '' || empty($categoryIDs) || $itemPrice === '') {
        $message = "❌ Fill required fields.";
    } else {
        $itemID = generateItemID($conn);

        // handle image
        $imageLocation = null;
        if (!empty($_FILES['itemImage']['name'])) {
            $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/assets/images/items/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
            $fileName = $itemID . "_" . basename($_FILES['itemImage']['name']);
            $targetFile = $targetDir . $fileName;
            if (move_uploaded_file($_FILES['itemImage']['tmp_name'], $targetFile)) {
                $imageLocation = "/assets/images/items/" . $fileName;
            }
        }

        // insert item
        $stmt = $conn->prepare("INSERT INTO Items (itemID, itemName, itemPrice, itemDesc, itemAvail, itemImageLocation) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdiss", $itemID, $itemName, $itemPrice, $itemDesc, $itemAvail, $imageLocation);
        $stmt->execute();

        // insert item-category links
        foreach ($categoryIDs as $catID) {
            $link = $conn->prepare("INSERT INTO ItemCategory (itemID, categoryID) VALUES (?, ?)");
            $link->bind_param("ss", $itemID, $catID);
            $link->execute();
        }

        $message = "✅ Item added. ID: $itemID";
    }
}

// ---------- DELETE ITEM ----------
if (isset($_GET['delete'])) {
    $delID = $_GET['delete'];

    $imgQ = $conn->prepare("SELECT itemImageLocation FROM Items WHERE itemID = ?");
    $imgQ->bind_param("s", $delID);
    $imgQ->execute();
    $imgR = $imgQ->get_result()->fetch_assoc();
    if ($imgR && !empty($imgR['itemImageLocation'])) {
        $imgPath = $_SERVER['DOCUMENT_ROOT'] . $imgR['itemImageLocation'];
        if (file_exists($imgPath)) unlink($imgPath);
    }

    // delete item-category links
    $conn->query("DELETE FROM ItemCategory WHERE itemID='$delID'");

    $del = $conn->prepare("DELETE FROM Items WHERE itemID = ?");
    $del->bind_param("s", $delID);
    $del->execute();
    $message = "✅ Item deleted.";
}

// ---------- EDIT ITEM (show form) ----------
if (isset($_GET['edit'])) {
    $editingItem = true;
    $editID = $_GET['edit'];
    $s = $conn->prepare("SELECT * FROM Items WHERE itemID = ?");
    $s->bind_param("s", $editID);
    $s->execute();
    $editItem = $s->get_result()->fetch_assoc();

    // fetch linked categories
    $catRes = $conn->query("SELECT categoryID FROM ItemCategory WHERE itemID='$editID'");
    $editItemCategories = [];
    while ($row = $catRes->fetch_assoc()) {
        $editItemCategories[] = $row['categoryID'];
    }
}

// ---------- UPDATE ITEM ----------
if (isset($_POST['update_item'])) {
    $itemID = $_POST['itemID'];
    $itemName = trim($_POST['itemName']);
    $categoryIDs = $_POST['categoryIDs'] ?? [];
    $itemPrice = $_POST['itemPrice'];
    $itemDesc = trim($_POST['itemDesc'] ?? '');
    $itemAvail = isset($_POST['itemAvail']) ? 1 : 0;

    // handle new image
    $imageLocation = null;
    if (!empty($_FILES['itemImage']['name'])) {
        $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/assets/images/items/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);

        // delete old image
        $oldQ = $conn->prepare("SELECT itemImageLocation FROM Items WHERE itemID = ?");
        $oldQ->bind_param("s", $itemID);
        $oldQ->execute();
        $oldR = $oldQ->get_result()->fetch_assoc();
        if ($oldR && !empty($oldR['itemImageLocation'])) {
            $oldPath = $_SERVER['DOCUMENT_ROOT'] . $oldR['itemImageLocation'];
            if (file_exists($oldPath)) unlink($oldPath);
        }

        $fileName = $itemID . "_" . basename($_FILES['itemImage']['name']);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES['itemImage']['tmp_name'], $targetFile)) {
            $imageLocation = "/assets/images/items/" . $fileName;
        }
    }

    if ($imageLocation !== null) {
        $stmt = $conn->prepare("UPDATE Items SET itemName=?, itemPrice=?, itemDesc=?, itemAvail=?, itemImageLocation=? WHERE itemID=?");
        $stmt->bind_param("sdisss", $itemName, $itemPrice, $itemDesc, $itemAvail, $imageLocation, $itemID);
    } else {
        $stmt = $conn->prepare("UPDATE Items SET itemName=?, itemPrice=?, itemDesc=?, itemAvail=? WHERE itemID=?");
        $stmt->bind_param("sdiss", $itemName, $itemPrice, $itemDesc, $itemAvail, $itemID);
    }
    $stmt->execute();

    // update item-category links
    $conn->query("DELETE FROM ItemCategory WHERE itemID='$itemID'");
    foreach ($categoryIDs as $catID) {
        $link = $conn->prepare("INSERT INTO ItemCategory (itemID, categoryID) VALUES (?, ?)");
        $link->bind_param("ss", $itemID, $catID);
        $link->execute();
    }

    $message = "✅ Item updated.";
    header("Location: items.php?edit=" . urlencode($itemID));
    exit;
}

// ---------- EDIT SUBCATEGORY (show form) ----------
if (isset($_GET['edit_sub'])) {
    $editingSub = true;
    $editSubID = $_GET['edit_sub'];
    $s = $conn->prepare("SELECT * FROM SubCategory WHERE subCategoryID = ?");
    $s->bind_param("s", $editSubID);
    $s->execute();
    $editSub = $s->get_result()->fetch_assoc();
}

// ---------- EDIT CATEGORY (show form) ----------
if (isset($_GET['edit_cat'])) {
    $editingCat = true;
    $editCatID = $_GET['edit_cat'];
    $s = $conn->prepare("SELECT * FROM Category WHERE categoryID = ?");
    $s->bind_param("s", $editCatID);
    $s->execute();
    $editCat = $s->get_result()->fetch_assoc();
}

// ---------- FETCH SUBCATEGORIES & CATEGORIES ----------
$subcats = $conn->query("SELECT * FROM SubCategory ORDER BY subCategoryName");
$cats = $conn->query("SELECT * FROM Category ORDER BY categoryName");

// Build structure for JS: subCategoryID => [categories]
$categoryMap = [];
$cats->data_seek(0);
while ($c = $cats->fetch_assoc()) {
    $categoryMap[$c['subCategoryID']][] = $c;
}
// fetch items with category & subcategory names
$items = $conn->query("
    SELECT i.*, GROUP_CONCAT(c.categoryName ORDER BY c.categoryName SEPARATOR ', ') AS categoryNames,
           GROUP_CONCAT(sc.subCategoryName ORDER BY sc.subCategoryName SEPARATOR ', ') AS subCategoryNames
    FROM Items i
    LEFT JOIN ItemCategory ic ON i.itemID = ic.itemID
    LEFT JOIN Category c ON ic.categoryID = c.categoryID
    LEFT JOIN SubCategory sc ON c.subCategoryID = sc.subCategoryID
    GROUP BY i.itemID
    ORDER BY i.itemName
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin - Items & Categories</title>
    <style>
        .container { max-width:1100px; margin:auto; padding:16px; }
        .row { display:flex; gap:8px; margin-bottom:8px; }
        input, select, textarea { padding:8px; width:100%; }
        table { width:100%; border-collapse:collapse; margin-top:16px; }
        th, td { border:1px solid #ddd; padding:8px; text-align:left; }
        img.thumb { width:60px; height:auto; }
        .two-col { display:flex; gap:16px; }
        .box { flex:1; padding:8px; border:1px solid #eee; border-radius:6px; background:#fafafa; }
        .small { font-size:0.9rem; color:#555 }
    </style>
</head>
<body>
<main class="container">
    <?php if ($message) echo "<p style='color:green;'>" . htmlspecialchars($message) . "</p>"; ?>

    <!-- SUBCATEGORY / CATEGORY FORMS -->
    <section class="two-col">
        <div class="box">
            <?php if (!$editingSub): ?>
                <h3>Add Subcategory</h3>
                <form method="post">
                    <input type="text" name="subCategoryName" placeholder="Subcategory name" required>
                    <input type="text" name="subCategoryDesc" placeholder="Description (optional)">
                    <button type="submit" name="add_subcategory">Add Subcategory</button>
                </form>
            <?php else: ?>
                <h3>Edit Subcategory: <?= htmlspecialchars($editSub['subCategoryID']) ?></h3>
                <form method="post">
                    <input type="hidden" name="subCategoryID" value="<?= htmlspecialchars($editSub['subCategoryID']) ?>">
                    <input type="text" name="subCategoryName" value="<?= htmlspecialchars($editSub['subCategoryName']) ?>" required>
                    <input type="text" name="subCategoryDesc" value="<?= htmlspecialchars($editSub['subCategoryDesc']) ?>">
                    <button type="submit" name="update_subcategory">Save Subcategory</button>
                    <a href="items.php" style="margin-left:8px;">Cancel</a>
                </form>
            <?php endif; ?>

            <hr>
            <h4>Existing Subcategories</h4>
            <table>
                <thead><tr><th>ID</th><th>Name</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php $subcats->data_seek(0);
                    while ($s = $subcats->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($s['subCategoryID']) ?></td>
                            <td><?= htmlspecialchars($s['subCategoryName']) ?></td>
                            <td>
                                <a href="?edit_sub=<?= urlencode($s['subCategoryID']) ?>">Edit</a> |
                                <a href="?delete_sub=<?= urlencode($s['subCategoryID']) ?>" onclick="return confirm('Delete this subcategory?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="box">
            <?php if (!$editingCat): ?>
                <h3>Add Category</h3>
                <form method="post">
                    <select name="subCategoryID_for_cat" required>
                        <option value="">Choose Subcategory</option>
                        <?php
                        $subcats->data_seek(0);
                        while ($sc = $subcats->fetch_assoc()) {
                            echo "<option value=\"" . htmlspecialchars($sc['subCategoryID']) . "\">" . htmlspecialchars($sc['subCategoryName']) . "</option>";
                        }
                        ?>
                    </select>
                    <input type="text" name="categoryName" placeholder="Category name" required>
                    <input type="text" name="categoryDesc" placeholder="Description (optional)">
                    <button type="submit" name="add_category">Add Category</button>
                </form>
            <?php else: ?>
                <h3>Edit Category: <?= htmlspecialchars($editCat['categoryID']) ?></h3>
                <form method="post">
                    <input type="hidden" name="categoryID" value="<?= htmlspecialchars($editCat['categoryID']) ?>">
                    <select name="subCategoryID_for_cat" required>
                        <option value="">Choose Subcategory</option>
                        <?php
                        $subcats->data_seek(0);
                        while ($sc = $subcats->fetch_assoc()) {
                            $sel = ($sc['subCategoryID'] === $editCat['subCategoryID']) ? 'selected' : '';
                            echo "<option value=\"" . htmlspecialchars($sc['subCategoryID']) . "\" $sel>" . htmlspecialchars($sc['subCategoryName']) . "</option>";
                        }
                        ?>
                    </select>
                    <input type="text" name="categoryName" value="<?= htmlspecialchars($editCat['categoryName']) ?>" required>
                    <input type="text" name="categoryDesc" value="<?= htmlspecialchars($editCat['categoryDesc']) ?>">
                    <button type="submit" name="update_category">Save Category</button>
                    <a href="items.php" style="margin-left:8px;">Cancel</a>
                </form>
            <?php endif; ?>

            <hr>
            <h4>Existing Categories</h4>
            <table>
                <thead><tr><th>ID</th><th>Subcategory</th><th>Name</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php
                    // show categories with subcategory name
                    $catsList = $conn->query("SELECT c.*, sc.subCategoryName FROM Category c LEFT JOIN SubCategory sc ON c.subCategoryID = sc.subCategoryID ORDER BY sc.subCategoryName, c.categoryName");
                    while ($c = $catsList->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['categoryID']) ?></td>
                            <td class="small"><?= htmlspecialchars($c['subCategoryName'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($c['categoryName']) ?></td>
                            <td>
                                <a href="?edit_cat=<?= urlencode($c['categoryID']) ?>">Edit</a> |
                                <a href="?delete_cat=<?= urlencode($c['categoryID']) ?>" onclick="return confirm('Delete this category?')">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- ADD / EDIT ITEM FORM -->
    <section style="margin-top:20px;">
        <?php if (!$editingItem): ?>
            <h2>Add New Item</h2>
        <?php else: ?>
            <h2>Edit Item: <?= htmlspecialchars($editItem['itemID']) ?></h2>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" style="margin-top:8px;">
            <?php if ($editingItem): ?>
                <input type="hidden" name="itemID" value="<?= htmlspecialchars($editItem['itemID']) ?>">
            <?php endif; ?>

            <div class="row">
                <input type="text" name="itemName" placeholder="Item name" required
                    value="<?= $editingItem ? htmlspecialchars($editItem['itemName']) : '' ?>">
                <input type="number" step="0.01" name="itemPrice" placeholder="Price" required
                    value="<?= $editingItem ? htmlspecialchars($editItem['itemPrice']) : '' ?>">
            </div>

            <div class="row">
                <label>Categories (hold Ctrl/Cmd to select multiple)</label>
                <select name="categoryIDs[]" multiple size="5" required>
                    <?php
                    $cats->data_seek(0);
                    while ($c = $cats->fetch_assoc()):
                        $selected = ($editingItem && in_array($c['categoryID'], $editItemCategories ?? [])) ? 'selected' : '';
                        echo "<option value=\"" . htmlspecialchars($c['categoryID']) . "\" $selected>" .
                            htmlspecialchars($c['categoryName'] . ' (' . $c['subCategoryName'] . ')') .
                            "</option>";
                    endwhile;
                    ?>
                </select>
            </div>

            <div class="row">
                <textarea name="itemDesc" placeholder="Description"><?= $editingItem ? htmlspecialchars($editItem['itemDesc']) : '' ?></textarea>
                <label style="display:flex; align-items:center;">
                    <input type="checkbox" name="itemAvail" <?= (!$editingItem || $editItem['itemAvail']) ? 'checked' : '' ?>> Available
                </label>
            </div>

            <div class="row">
                <input type="file" name="itemImage" accept="image/*">
                <?php if ($editingItem && !empty($editItem['itemImageLocation'])): ?>
                    <div>
                        <p>Current Image:</p>
                        <img src="<?= htmlspecialchars($editItem['itemImageLocation']) ?>" class="thumb" alt="">
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-top:8px;">
                <?php if ($editingItem): ?>
                    <button type="submit" name="update_item">Save Changes</button>
                    <a href="items.php" style="margin-left:8px;">Cancel</a>
                <?php else: ?>
                    <button type="submit" name="add_item">Add Item</button>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- ITEMS TABLE -->
    <section>
        <h2 style="margin-top:24px;">All Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Item ID</th>
                    <th>Name</th>
                    <th>Category (Subcategory)</th>
                    <th>Price</th>
                    <th>Avail</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['itemID']) ?></td>
                        <td><?= htmlspecialchars($item['itemName']) ?></td>
                        <td><?= htmlspecialchars($item['categoryName'] . ' (' . ($item['subCategoryName'] ?: '—') . ')') ?></td>
                        <td><?= number_format($item['itemPrice'],2) ?></td>
                        <td><?= $item['itemAvail'] ? 'Yes' : 'No' ?></td>
                        <td>
                            <?php if (!empty($item['itemImageLocation'])): ?>
                                <img src="<?= htmlspecialchars($item['itemImageLocation']) ?>" class="thumb" alt="">
                            <?php endif; ?>
                        </td>
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

<script>
// Build category map from PHP-encoded data
const categoryMap = <?php
    echo json_encode($categoryMap, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
?>;

// on subcategory change, populate category select
document.getElementById('subcat_select').addEventListener('change', function(){
    const subId = this.value;
    const catSelect = document.getElementById('category_select');
    catSelect.innerHTML = '<option value="">Select Category</option>';
    if (!subId) {
        // populate all categories as fallback
        for (const s in categoryMap) {
            categoryMap[s].forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.categoryID;
                opt.textContent = c.categoryName;
                catSelect.appendChild(opt);
            });
        }
        return;
    }
    const cats = categoryMap[subId] || [];
    cats.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.categoryID;
        opt.textContent = c.categoryName;
        catSelect.appendChild(opt);
    });
});

// Preselect category in edit mode
<?php if ($editingItem && $editItem): ?>
    (function(){
        const catSel = document.getElementById('category_select');
        const editCat = <?= json_encode($editItem['categoryID']) ?>;
        if (editCat) {
            for (let i=0;i<catSel.options.length;i++){
                if (catSel.options[i].value === editCat) {
                    catSel.selectedIndex = i;
                    break;
                }
            }
        }
    })();
<?php endif; ?>
</script>

</body>
</html>

<?php
mysqli_close($conn);
?>