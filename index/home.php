<?php
    include_once($_SERVER['DOCUMENT_ROOT'] . '/website/config.php');

    include_once(BASE_PATH . '/database.php');
    include_once(BASE_PATH . '/header.html');
    include_once(BASE_PATH . '/nav.html');

    $menu_ramen = ["Item 1", "Item 2", "Item 3", "Item 4"];
    $sql = "INSERT INTO ";
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Title -->
        <title>JBOY MAKI and BENTI Food House - HOME</title>
        <!-- Icon -->
        <link rel="shortcut icon" type="image/x-icon" href="../img/default-images/logo.png" />
        <!-- Stylesheet -->
        <link rel="stylesheet" href="style.css">
        <!-- Font Giver -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Itim&display=swap" rel="stylesheet">
    </head>
    <body>

        <main>
            <section class="class_greeting">
                <img id="greet" src="greeting-images/Time-Morning.png" alt="Greetings" width="950" height="475" style="align-items: center;">
            </section>

            <div class="section-divider"></div>

            <section class="section-title">
                <h2>Foods</h2>
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
                Image_greet.src = "greeting-images/Time-Evening.png";
            } 
            else if (time >= 13) {
                Image_greet.src = "greeting-images/Time-Afternoon.png";
            } 
            else {
                Image_greet.src = "greeting-images/Time-Morning.png";
            }
        </script>
    </body>
</html>

<?php
    mysqli_close($conn);
?>