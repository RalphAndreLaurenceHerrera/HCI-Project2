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
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Title -->
        <title>Feedback | JBOY MAKI and BENTI Food House</title>
        <link rel="stylesheet" href="/jboymakiandbento/assets/css/Feedback.css">
    </head>
    <body>
        <main>
            <div class="form-box" style="margin-top: 120px;">
                <h1>Feedback Survey Form</h1>
                <form id="survey-form">

                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>

                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>

                    <label for="age">Age:</label>
                    <input type="number" id="age" name="age" min="1" max="150">

                    <label for="feedback-type">Type of Feedback:</label>
                    <select id="feedback-type" name="feedback-type">
                        <option value="positive">Positive</option>
                        <option value="negative">Negative</option>
                        <option value="suggestion">Suggestion</option>
                    </select>

                    <label for="comments">Additional Comments:</label>
                    <textarea id="comments" name="comments" rows="4"></textarea>

                    <button type="submit">Submit Feedback</button>
                </form>
            </div>
        </main>
        <script>

        </script>
    </body>
<html>