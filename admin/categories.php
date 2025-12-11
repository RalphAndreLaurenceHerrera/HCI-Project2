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

// Message
$message = '';

// ---------------- CATEGORIES CRUD ----------------

// Add Category
if(isset($_POST['add_category'])){
    $name = trim($_POST['categoryName']);
    $desc = trim($_POST['categoryDesc']);
    if($name===''){
        $message = "❌ Category name cannot be empty.";
    } else {
        $catID = generateCategoryID($conn);
        $stmt = $conn->prepare("INSERT INTO Category (categoryID, categoryName, categoryDesc) VALUES (?,?,?)");
        $stmt->bind_param("sss", $catID, $name, $desc);
        $stmt->execute();
        $message = "✅ Category added successfully.";
    }
}

// Delete Category
if(isset($_GET['delete_category'])){
    $delID = $_GET['delete_category'];
    $stmt = $conn->prepare("DELETE FROM Category WHERE categoryID=?");
    $stmt->bind_param("s", $delID);
    $stmt->execute();
    $message = "✅ Category deleted successfully.";
}

// Edit Category
$editingCat = false;
$editCategory = null;
if(isset($_GET['edit_category'])){
    $editingCat = true;
    $editID = $_GET['edit_category'];
    $stmt = $conn->prepare("SELECT * FROM Category WHERE categoryID=?");
    $stmt->bind_param("s", $editID);
    $stmt->execute();
    $editCategory = $stmt->get_result()->fetch_assoc();
}

// Update Category
if(isset($_POST['update_category'])){
    $catID = $_POST['categoryID'];
    $name = trim($_POST['categoryName']);
    $desc = trim($_POST['categoryDesc']);
    $stmt = $conn->prepare("UPDATE Category SET categoryName=?, categoryDesc=? WHERE categoryID=?");
    $stmt->bind_param("sss", $name, $desc, $catID);
    $stmt->execute();
    $message = "✅ Category updated successfully.";
}

// ---------------- SUBCATEGORIES CRUD ----------------

// Add Subcategory
if(isset($_POST['add_subcategory'])){
    $name = trim($_POST['subCategoryName']);
    $desc = trim($_POST['subCategoryDesc']);
    if($name===''){
        $message = "❌ Subcategory name cannot be empty.";
    } else {
        $subID = generateSubCategoryID($conn);
        $stmt = $conn->prepare("INSERT INTO SubCategory (subCategoryID, subCategoryName, subCategoryDesc) VALUES (?,?,?)");
        $stmt->bind_param("sss", $subID, $name, $desc);
        $stmt->execute();
        $message = "✅ Subcategory added successfully.";
    }
}

// Delete Subcategory
if(isset($_GET['delete_subcategory'])){
    $delID = $_GET['delete_subcategory'];
    $stmt = $conn->prepare("DELETE FROM SubCategory WHERE subCategoryID=?");
    $stmt->bind_param("s", $delID);
    $stmt->execute();
    $message = "✅ Subcategory deleted successfully.";
}

// Edit Subcategory
$editingSub = false;
$editSub = null;
if(isset($_GET['edit_subcategory'])){
    $editingSub = true;
    $editID = $_GET['edit_subcategory'];
    $stmt = $conn->prepare("SELECT * FROM SubCategory WHERE subCategoryID=?");
    $stmt->bind_param("s", $editID);
    $stmt->execute();
    $editSub = $stmt->get_result()->fetch_assoc();
}

// Update Subcategory
if(isset($_POST['update_subcategory'])){
    $subID = $_POST['subCategoryID'];
    $name = trim($_POST['subCategoryName']);
    $desc = trim($_POST['subCategoryDesc']);
    $stmt = $conn->prepare("UPDATE SubCategory SET subCategoryName=?, subCategoryDesc=? WHERE subCategoryID=?");
    $stmt->bind_param("sss", $name, $desc, $subID);
    $stmt->execute();
    $message = "✅ Subcategory updated successfully.";
}

// Fetch categories & subcategories
$categories = $conn->query("SELECT * FROM Category ORDER BY categoryName ASC");
$subcategories = $conn->query("SELECT * FROM SubCategory ORDER BY subCategoryName ASC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin - Categories & Subcategories</title>
    <style>
        .container { padding:20px; max-width:1100px; margin:auto; }
        .form-row { display:flex; gap:8px; margin-bottom:8px; }
        input[type="text"], textarea { padding:8px; width:100%; }
        table { width:100%; border-collapse:collapse; margin-top:12px; }
        th, td { border:1px solid #ddd; padding:8px; text-align:left; }
        .msg { margin:10px 0; color:green; }
        .error { color:red; }
    </style>
</head>
<body>
<main class="container">
    <?php if($message) echo "<p class='msg'>".htmlspecialchars($message)."</p>"; ?>

    <!-- CATEGORY FORM -->
    <section>
        <h2><?= $editingCat ? "Edit Category" : "Add New Category" ?></h2>
        <form method="post">
            <?php if($editingCat): ?>
                <input type="hidden" name="categoryID" value="<?= htmlspecialchars($editCategory['categoryID']) ?>">
            <?php endif; ?>
            <div class="form-row">
                <input type="text" name="categoryName" placeholder="Category Name" required
                       value="<?= htmlspecialchars($editCategory['categoryName'] ?? '') ?>">
            </div>
            <div class="form-row">
                <textarea name="categoryDesc" placeholder="Description"><?= htmlspecialchars($editCategory['categoryDesc'] ?? '') ?></textarea>
            </div>
            <div>
                <button type="submit" name="<?= $editingCat ? 'update_category' : 'add_category' ?>">
                    <?= $editingCat ? 'Save Changes' : 'Add Category' ?>
                </button>
                <?php if($editingCat): ?>
                    <a href="categories.php" style="margin-left:10px;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- CATEGORY TABLE -->
    <section>
        <h2>All Categories</h2>
        <table>
            <thead>
            <tr><th>ID</th><th>Name</th><th>Description</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while($cat = $categories->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($cat['categoryID']) ?></td>
                    <td><?= htmlspecialchars($cat['categoryName']) ?></td>
                    <td><?= htmlspecialchars($cat['categoryDesc']) ?></td>
                    <td>
                        <a href="?edit_category=<?= $cat['categoryID'] ?>">Edit</a> |
                        <a href="?delete_category=<?= $cat['categoryID'] ?>" onclick="return confirm('Delete?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </section>

    <!-- SUBCATEGORY FORM -->
    <section style="margin-top:30px;">
        <h2><?= $editingSub ? "Edit Subcategory" : "Add New Subcategory" ?></h2>
        <form method="post">
            <?php if($editingSub): ?>
                <input type="hidden" name="subCategoryID" value="<?= htmlspecialchars($editSub['subCategoryID']) ?>">
            <?php endif; ?>
            <div class="form-row">
                <input type="text" name="subCategoryName" placeholder="Subcategory Name" required
                       value="<?= htmlspecialchars($editSub['subCategoryName'] ?? '') ?>">
            </div>
            <div class="form-row">
                <textarea name="subCategoryDesc" placeholder="Description"><?= htmlspecialchars($editSub['subCategoryDesc'] ?? '') ?></textarea>
            </div>
            <div>
                <button type="submit" name="<?= $editingSub ? 'update_subcategory' : 'add_subcategory' ?>">
                    <?= $editingSub ? 'Save Changes' : 'Add Subcategory' ?>
                </button>
                <?php if($editingSub): ?>
                    <a href="categories.php" style="margin-left:10px;">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <!-- SUBCATEGORY TABLE -->
    <section>
        <h2>All Subcategories</h2>
        <table>
            <thead>
            <tr><th>ID</th><th>Name</th><th>Description</th><th>Actions</th></tr>
            </thead>
            <tbody>
            <?php while($sub = $subcategories->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($sub['subCategoryID']) ?></td>
                    <td><?= htmlspecialchars($sub['subCategoryName']) ?></td>
                    <td><?= htmlspecialchars($sub['subCategoryDesc']) ?></td>
                    <td>
                        <a href="?edit_subcategory=<?= $sub['subCategoryID'] ?>">Edit</a> |
                        <a href="?delete_subcategory=<?= $sub['subCategoryID'] ?>" onclick="return confirm('Delete?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </section>

</main>
</body>
</html>

<?php mysqli_close($conn); ?>