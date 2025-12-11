<?php
function registerdefault(){
    session_start();

    // BASE PATH Maker
    require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
    // Database Related
    require_once(BASE_PATH . 'includes/dbchecker.php');
    require_once(BASE_PATH . 'includes/idgenerator.php');
    // HTML Related - TOP
    require_once(BASE_PATH . 'includes/customer/head.php');
    require_once(BASE_PATH . 'includes/customer/header.php');
    require_once(BASE_PATH . 'includes/customer/navigation.php');
    // HTML Related - BOTTOM
    require_once(BASE_PATH . 'includes/customer/footer.php');


    mysqli_close($conn);
}
function customerviewdefault(){
    // BASE PATH Maker
    require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
    // Database Related
    require_once(BASE_PATH . 'includes/dbchecker.php');
    // HTML Related - TOP
    require_once(BASE_PATH . 'includes/customer/head.php');
    require_once(BASE_PATH . 'includes/customer/header.php');
    require_once(BASE_PATH . 'includes/customer/navigation.php');
    // HTML Related - BOTTOM
    require_once(BASE_PATH . 'includes/customer/footer.php');
}
function menuviewdefault(){
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
}
function adminviewdefault(){
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
}
function htmldefault(){
/*

<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Title -->
        <title>Home | JBOY MAKI and BENTI Food House</title>
    </head>
    <body>
        <main>

        </main>
        <script>

        </script>
    </body>
</html>

<?php
    $conn->close();
?>

*/
}
?>