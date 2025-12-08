<?php
function customerviewdefault(){
    // BASE PATH Maker
    require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
    // Database Related
    include_once(BASE_PATH . 'includes/dbchecker.php');
    require_once(BASE_PATH . 'includes/idgenerator.php');
    // HTML Related - TOP
    include_once(BASE_PATH . 'includes/customer-head.php');
    include_once(BASE_PATH . 'includes/customer-header.php');
    include_once(BASE_PATH . 'includes/customer-navigation.php');
    // HTML Related - BOTTOM
    include_once(BASE_PATH . 'includes/customer-footer.php');
}
function adminviewdefault(){
    // BASE PATH Maker
    require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
    // Database - Functions Related
    require_once(BASE_PATH . 'includes/dbchecker.php');
    require_once(BASE_PATH . 'includes/admin_auth.php');
    require_once(BASE_PATH . 'includes/idgenerator.php');
    // HTML Related - TOP
    include_once(BASE_PATH . 'includes/admin-head.php');
    include_once(BASE_PATH . 'includes/admin-header.php');
    include_once(BASE_PATH . 'includes/admin-navigation.php');
    // HTML Related - BOTTOM
    include_once(BASE_PATH . 'includes/admin-footer.php');
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
<html>

<?php
    mysqli_close($conn);
?>
*/
}
?>