<?php
    // BASE PATH Maker
    require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
    // Database - Functions Related
    require_once(BASE_PATH . 'includes/dbchecker.php');
    // require_once(BASE_PATH . 'includes/admin_auth.php');
    require_once(BASE_PATH . 'includes/idgenerator.php');
    // HTML Related - TOP
    require_once(BASE_PATH . 'includes/admin/head.php');
    require_once(BASE_PATH . 'includes/admin/header.php');
    require_once(BASE_PATH . 'includes/admin/navigation.php');
    // HTML Related - BOTTOM
    require_once(BASE_PATH . 'includes/admin/footer.php');

$message = '';

// ---------- HANDLE ADD USER ----------
if (isset($_POST['add_user'])) {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $contact = trim($_POST['contact']);
    $gender = $_POST['gender'];
    $role = $_POST['role'];

    // Optional address fields
    $addressLine = isset($_POST['addressLine']) ? trim($_POST['addressLine']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';

    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Please enter a valid email address.";
    } elseif (strlen($password) < 6) {
        $message = "❌ Password must be at least 6 characters.";
    } else {
        // Check email uniqueness
        $chk = $conn->prepare("SELECT userID FROM Users WHERE email = ?");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $message = "❌ Email already registered.";
        } else {
            // Generate userID
            $userID = generateUserID($conn, $role);
            $hashPass = password_hash($password, PASSWORD_DEFAULT);

            // Insert user
            $stmt = $conn->prepare("
                INSERT INTO Users
                (userID, firstName, lastName, email, hashPass, contactNo, gender, userrole)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssssssss", $userID, $fname, $lname, $email, $hashPass, $contact, $gender, $role);
            $stmt->execute();

            // If address provided, insert into UserAddress
            if ($addressLine !== '' || $city !== '') {
                $addressID = generateAddressID($conn);
                $stmtAddr = $conn->prepare("
                    INSERT INTO UserAddress
                    (addressID, userID, addressLine, city)
                    VALUES (?, ?, ?, ?)
                ");
                $stmtAddr->bind_param("ssss", $addressID, $userID, $addressLine, $city);
                $stmtAddr->execute();
            }

            $message = "✅ User added successfully. ID: " . htmlspecialchars($userID);
        }
    }
}

// ---------- HANDLE DELETE USER ----------
if (isset($_GET['delete'])) {
    $delID = $_GET['delete'];

    // Prevent deleting currently logged-in admin
    if (isset($_SESSION['userID']) && $_SESSION['userID'] === $delID) {
        $message = "❌ You cannot delete the currently logged-in user.";
    } else {
        $delStmt = $conn->prepare("DELETE FROM Users WHERE userID = ?");
        $delStmt->bind_param("s", $delID);
        $delStmt->execute();
        $message = "✅ User deleted successfully.";
    }
}

// ---------- HANDLE EDIT USER (showing form and processing) ----------
$editing = false;
$editUser = null;
$editAddress = null;

// When we click Edit (show form)
if (isset($_GET['edit'])) {
    $editing = true;
    $editID = $_GET['edit'];

    // Fetch user details
    $stmt = $conn->prepare("SELECT * FROM Users WHERE userID = ?");
    $stmt->bind_param("s", $editID);
    $stmt->execute();
    $res = $stmt->get_result();
    $editUser = $res->fetch_assoc();

    // Fetch a primary/first address for that user (if any)
    $stmtA = $conn->prepare("SELECT * FROM UserAddress WHERE userID = ? LIMIT 1");
    $stmtA->bind_param("s", $editID);
    $stmtA->execute();
    $resA = $stmtA->get_result();
    $editAddress = $resA->fetch_assoc();
}

// When edit form is submitted
if (isset($_POST['update_user'])) {
    $userID = $_POST['userID'];
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $email = trim($_POST['email']);
    $newPassword = $_POST['password']; // optional: empty means don't change
    $contact = trim($_POST['contact']);
    $gender = $_POST['gender'];
    $role = $_POST['role'];

    // Address fields
    $addressID = isset($_POST['addressID']) ? $_POST['addressID'] : '';
    $addressLine = isset($_POST['addressLine']) ? trim($_POST['addressLine']) : '';
    $city = isset($_POST['city']) ? trim($_POST['city']) : '';

    // Basic validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Please enter a valid email address.";
    } else {
        // Check if email belongs to another user
        $chk = $conn->prepare("SELECT userID FROM Users WHERE email = ? AND userID != ?");
        $chk->bind_param("ss", $email, $userID);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $message = "❌ Email already used by another account.";
        } else {
            // Build update query for Users
            if (!empty($newPassword)) {
                if (strlen($newPassword) < 6) {
                    $message = "❌ New password must be at least 6 characters.";
                } else {
                    $hashPass = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("
                        UPDATE Users
                        SET firstName=?, lastName=?, email=?, hashPass=?, contactNo=?, gender=?, userrole=?
                        WHERE userID=?
                    ");
                    $stmt->bind_param("ssssssss", $fname, $lname, $email, $hashPass, $contact, $gender, $role, $userID);
                }
            } else {
                $stmt = $conn->prepare("
                    UPDATE Users
                    SET firstName=?, lastName=?, email=?, contactNo=?, gender=?, userrole=?
                    WHERE userID=?
                ");
                $stmt->bind_param("sssssss", $fname, $lname, $email, $contact, $gender, $role, $userID);
            }
            $stmt->execute();

            // Handle address: if addressID provided -> UPDATE; else if address fields provided -> INSERT
            if (!empty($addressID)) {
                // Update existing address
                $stmtAddr = $conn->prepare("
                    UPDATE UserAddress
                    SET addressLine = ?, city = ?
                    WHERE addressID = ? AND userID = ?
                ");
                $stmtAddr->bind_param("ssss", $addressLine, $city, $addressID, $userID);
                $stmtAddr->execute();
            } else {
                // If there is address text, insert new address
                if ($addressLine !== '' || $city !== '') {
                    $newAddressID = generateAddressID($conn);
                    $stmtAddr = $conn->prepare("
                        INSERT INTO UserAddress (addressID, userID, addressLine, city)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmtAddr->bind_param("ssss", $newAddressID, $userID, $addressLine, $city);
                    $stmtAddr->execute();
                }
            }

            $message = "✅ User updated successfully.";
            // After update, fetch fresh user for display in edit mode if needed
            $stmt = $conn->prepare("SELECT * FROM Users WHERE userID = ?");
            $stmt->bind_param("s", $userID);
            $stmt->execute();
            $editUser = $stmt->get_result()->fetch_assoc();

            $stmtA = $conn->prepare("SELECT * FROM UserAddress WHERE userID = ? LIMIT 1");
            $stmtA->bind_param("s", $userID);
            $stmtA->execute();
            $editAddress = $stmtA->get_result()->fetch_assoc();

            // stay in edit mode to show updated data (optional: redirect to users list)
            $editing = true;
        }
    }
}

// ---------- FETCH USERS LIST (with addresses) ----------
$users = $conn->query("
    SELECT 
        u.userID,
        u.firstName,
        u.lastName,
        u.email,
        u.contactNo,
        u.gender,
        u.userrole,
        u.creationDate,
        IFNULL(GROUP_CONCAT(CONCAT(ua.addressID,'||',ua.addressLine,'||',ua.city) SEPARATOR '||ROW||'), '') AS addresses
    FROM Users u
    LEFT JOIN UserAddress ua ON ua.userID = u.userID
    GROUP BY u.userID
    ORDER BY u.creationDate DESC
");
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Title -->
        <title>Users | Admin Dashboard</title>
    </head>
    <body>
        <main>
            <?php if($message) echo "<p class='msg'>" . htmlspecialchars($message) . "</p>"; ?>

            <!-- ADD USER -->
            <?php if (!$editing): ?>
            <section>
                <h2>Add New User</h2>
                <form method="post" style="margin-bottom:20px;">
                    <div class="form-row">
                        <input type="text" name="fname" placeholder="First Name" required>
                        <input type="text" name="lname" placeholder="Last Name" required>
                    </div>
                    <div class="form-row">
                        <input type="email" name="email" placeholder="Email" required>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <div class="form-row">
                        <input type="text" name="contact" placeholder="Contact No">
                        <select name="gender" required>
                            <option value="">Gender</option>
                            <option value="M">Male</option>
                            <option value="F">Female</option>
                        </select>
                        <select name="role" required>
                            <option value="customer">Customer</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>

                    <fieldset style="margin-top:8px;">
                        <legend>Address (optional)</legend>
                        <div class="form-row">
                            <input type="text" name="addressLine" placeholder="Street / Barangay / Building">
                            <input type="text" name="city" placeholder="City / Municipality">
                        </div>
                    </fieldset>

                    <div style="margin-top:8px;">
                        <button type="submit" name="add_user">Add User</button>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <!-- EDIT USER -->
            <?php if ($editing && $editUser): ?>
            <section>
                <h2>Edit User: <?= htmlspecialchars($editUser['userID']) ?></h2>
                <form method="post" style="margin-bottom:20px;">
                    <input type="hidden" name="userID" value="<?= htmlspecialchars($editUser['userID']) ?>">
                    <div class="form-row">
                        <input type="text" name="fname" value="<?= htmlspecialchars($editUser['firstName']) ?>" required>
                        <input type="text" name="lname" value="<?= htmlspecialchars($editUser['lastName']) ?>" required>
                    </div>
                    <div class="form-row">
                        <input type="email" name="email" value="<?= htmlspecialchars($editUser['email']) ?>" required>
                        <input type="password" name="password" placeholder="New password (leave blank to keep)">
                    </div>
                    <div class="form-row">
                        <input type="text" name="contact" placeholder="Contact No." value="<?= htmlspecialchars($editUser['contactNo']) ?>">
                        <select name="gender" required>
                            <option value="M" <?= $editUser['gender']==='M' ? 'selected' : '' ?>>Male</option>
                            <option value="F" <?= $editUser['gender']==='F' ? 'selected' : '' ?>>Female</option>
                        </select>
                        <select name="role" required>
                            <option value="customer" <?= $editUser['userrole']==='customer' ? 'selected' : '' ?>>Customer</option>
                            <option value="admin" <?= $editUser['userrole']==='admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <fieldset style="margin-top:8px;">
                        <legend>Primary Address</legend>
                        <?php if ($editAddress): ?>
                            <input type="hidden" name="addressID" value="<?= htmlspecialchars($editAddress['addressID']) ?>">
                        <?php endif; ?>
                        <div class="form-row">
                            <input type="text" name="addressLine" value="<?= htmlspecialchars($editAddress['addressLine'] ?? '') ?>" placeholder="Street / Barangay / Building">
                            <input type="text" name="city" value="<?= htmlspecialchars($editAddress['city'] ?? '') ?>" placeholder="City / Municipality">
                        </div>
                    </fieldset>

                    <div style="margin-top:8px;">
                        <button type="submit" name="update_user">Save Changes</button>
                        <a href="users.php" style="margin-left:10px;">Cancel</a>
                    </div>
                </form>
            </section>
            <?php endif; ?>

            <!-- USERS TABLE -->
            <section>
                <h2 style="margin-top:20px;">All Users</h2>
                <table>
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact No.</th>
                            <th>Gender</th>
                            <th>Role</th>
                            <th>Addresses</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['userID']) ?></td>
                            <td><?= htmlspecialchars($user['firstName'] . ' ' . $user['lastName']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['contactNo']) ?></td>
                            <td><?= htmlspecialchars($user['gender']) ?></td>
                            <td><?= htmlspecialchars($user['userrole']) ?></td>
                            <td>
                                <?php
                                    if ($user['addresses']) {
                                        // addresses are encoded as addressID||line||city||ROW||...
                                        $rows = explode('||ROW||', $user['addresses']);
                                        foreach ($rows as $r) {
                                            if (trim($r)==='') continue;
                                            list($aid, $aline, $acity) = explode('||', $r);
                                            echo "<div><small>" . htmlspecialchars($aline) . " — " . htmlspecialchars($acity) . "</small></div>";
                                        }
                                    } else {
                                        echo '<em>—</em>';
                                    }
                                ?>
                            </td>
                            <td>
                                <a href="?edit=<?= urlencode($user['userID']) ?>">Edit</a> |
                                <a href="?delete=<?= urlencode($user['userID']) ?>" onclick="return confirm('Delete this user?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>
        </main>
        <script>

        </script>
    </body>
</html>
        <style>
            .container { padding:20px; max-width:1100px; margin:auto; }
            .form-row { display:flex; gap:8px; margin-bottom:8px; }
            input[type="text"], input[type="email"], input[type="password"], select { padding:8px; width:100%; }
            table { width:100%; border-collapse:collapse; }
            th, td { border:1px solid #ddd; padding:8px; text-align:left; }
            .msg { margin:10px 0; color:green; }
            .error { color:red; }
        </style>
        
<?php
    mysqli_close($conn);
?>