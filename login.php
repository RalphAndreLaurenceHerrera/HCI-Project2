<?php
session_start();
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
$message = '';

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check if user exists
    $stmt = $conn->prepare("SELECT * FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['hashPass'])) {
            // Login successful
            $_SESSION['userID']   = $user['userID'];
            $_SESSION['userrole'] = $user['userrole'];
            $_SESSION['firstName'] = $user['firstName'];

            // Redirect based on role
            if ($user['userrole'] === 'admin') {
                header("Location: /jboymakiandbento/admin/admin-dashboard.php");
                exit();
            } else {
                header("Location: /home.php");
                exit();
            }
        } else {
            $message = "Incorrect password.";
        }
    } else {
        $message = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Title -->
        <title>Login | JBOY MAKI and BENTI Food House</title>
    </head>
    <body>
        <main>
            <div class="login-registration-container">
                <h1>Login</h1>
                <?php if($message) echo "<p style='color:red;'>$message</p>"; ?>
                <form method="post">
                    <input type="email" name="email" placeholder="Email" required><br><br>
                    <input type="password" name="password" placeholder="Password" required><br><br>
                    <button type="submit" name="login">Login</button>
                </form>
                <p><a href="/jboymakiandbento/register.php">Register as Customer</a></p>
            </div>
        </main>
        <script>

        </script>
    </body>
<html>

<?php
    mysqli_close($conn);
?>