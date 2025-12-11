<?php
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

    $menu_ramen = ["Item 1", "Item 2", "Item 3", "Item 4"];
    $sql = "INSERT INTO ";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Title -->
        <title>Home | JBOY MAKI and BENTI Food House</title>
    </head>
    <body>
        <main>
            <section class="class_greeting">
                <img id="greet" src="assets/images/Time-Morning.png" alt="Greetings" width="950" height="475" style="align-items: center;">
            </section>

            <div class="section-divider"></div>

            <section class="section-title">
                <h2>Our Foods</h2>
            </section>

            <section class="food-grid">
                <div class="food-card"></div>
                <div class="food-card"></div>
                <div class="food-card"></div>
                <div class="food-card"></div>
                <?php foreach ($menu_ramen as $ramen): ?>
                    <h1> <?php echo $ramen; ?> </h1>
                <?php endforeach; ?>
                <div class="food-card"></div>
                <div class="food-card"></div>
                <div class="food-card"></div>
                <div class="food-card"></div>
            </section>
        </main>

        <script>
            const time = new Date().getHours();
            const Image_greet = document.getElementById("greet");

            if (time >= 18) {
                Image_greet.src = "/jboymakiandbento/assets/images/Time-Evening.png";
            } 
            else if (time >= 13) {
                Image_greet.src = "/jboymakiandbento/assets/images/Time-Afternoon.png";
            } 
            else {
                Image_greet.src = "/jboymakiandbento/assets/images/Time-Morning.png";
            }
        </script>
    </body>
</html>

<?php
    mysqli_close($conn);
?>