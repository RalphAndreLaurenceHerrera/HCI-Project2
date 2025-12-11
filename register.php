<?php
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

$message = '';

if (isset($_POST['register'])) {
    $fname   = trim($_POST['fname']);
    $lname   = trim($_POST['lname']);
    $email   = trim($_POST['email']);
    $password= $_POST['password'];
    $contact = trim($_POST['contact']);
    $gender  = $_POST['gender'];

    // Address fields (optional)
    $addressLine = isset($_POST['addressLine']) ? trim($_POST['addressLine']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';

    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $message = "❌ Password must be at least 6 characters.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT 1 FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "❌ Email already registered.";
        } else {
            // Generate customer ID and hash password
            $role = 'customer';
            $userID = generateUserID($conn, $role);
            $hashPass = password_hash($password, PASSWORD_DEFAULT);

            // Insert into Users
            $insertUser = $conn->prepare("
                INSERT INTO Users
                (userID, firstName, lastName, email, hashPass, contactNo, gender, userrole)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $insertUser->bind_param("ssssssss", $userID, $fname, $lname, $email, $hashPass, $contact, $gender, $role);
            $insertUser->execute();

            // If address provided, insert into UserAddress
            if ($addressLine !== '' || $city !== '') {
                // Generate addressID using generic generator (prefix ADDR)
                $addressID = generateAutoID($conn, 'UserAddress', 'addressID', 'ADDR', 5);

                $insertAddr = $conn->prepare("
                    INSERT INTO UserAddress
                    (addressID, userID, addressLine, city)
                    VALUES (?, ?, ?, ?)
                ");
                $insertAddr->bind_param("ssss", $addressID, $userID, $addressLine, $city);
                $insertAddr->execute();
            }

            $message = "✅ Registration successful! Your User ID: $userID";
            header("Refresh:2; url=/jboymakiandbento/login.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Title -->
        <title>Register | JBOY MAKI and BENTI Food House</title>
    </head>
    <body>
        <main>
            <div class="login-registration-container">
                <h2>Customer Registration</h2>
                <?php if($message) echo "<p style='color:green;'>" . htmlspecialchars($message) . "</p>"; ?>
                <form method="post">
                    <input type="text" name="fname" placeholder="First Name" required><br><br>
                    <input type="text" name="lname" placeholder="Last Name" required><br><br>
                    <input type="email" name="email" placeholder="Email" required><br><br>
                    <input type="password" name="password" placeholder="Password (min 6 chars)" required><br><br>
                    <input type="text" name="contact" placeholder="Contact No"><br><br>

                    <label>Gender</label><br>
                    <select name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="M">Male</option>
                        <option value="F">Female</option>
                    </select><br><br>

                    <h3>Address (optional)</h3>
                    <input type="text" name="addressLine" placeholder="Street / Barangay / Building"><br><br>
                    <input type="text" name="city" placeholder="City / Municipality"><br><br>

                    <button type="submit" name="register">Register</button>
                </form>
                <p>Already have an account? <a href="/jboymakiandbento/login.php">Login here</a></p>
            </div>
        </main>
        <script>

        </script>
    </body>
<html>

<?php
    mysqli_close($conn);
?>