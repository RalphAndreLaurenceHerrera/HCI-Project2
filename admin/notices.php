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

$message = '';

// ---------- HANDLE ADD NOTICE ----------
if(isset($_POST['add_notice'])){
    $title = trim($_POST['noticeTitle']);
    $summary = trim($_POST['noticeSummary']);
    $body = trim($_POST['noticeBody']);
    $image = trim($_POST['noticeImageLocation']);
    $link = trim($_POST['noticeLinkRelated']);
    $active = isset($_POST['noticeActive']) ? 1 : 0;
    $important = isset($_POST['noticeImportant']) ? 1 : 0;

    if($title=='' || $body==''){
        $message = "❌ Title and body are required.";
    } else {
        $noticeID = generateNoticeID($conn);

        $stmt = $conn->prepare("INSERT INTO Notices 
            (noticeID, noticeTitle, noticeSummary, noticeBody, noticeImageLocation, noticeLinkRelated, noticeActive, noticeImportant)
            VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssii",$noticeID,$title,$summary,$body,$image,$link,$active,$important);
        $stmt->execute();

        $message = "✅ Notice added successfully: ".$noticeID;
    }
}

// ---------- HANDLE DELETE NOTICE ----------
if(isset($_GET['delete'])){
    $delID = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM Notices WHERE noticeID=?");
    $stmt->bind_param("s",$delID);
    $stmt->execute();
    $message = "✅ Notice deleted successfully.";
}

// ---------- HANDLE EDIT NOTICE ----------
$editing = false;
$editNotice = null;

if(isset($_GET['edit'])){
    $editing = true;
    $editID = $_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM Notices WHERE noticeID=?");
    $stmt->bind_param("s",$editID);
    $stmt->execute();
    $editNotice = $stmt->get_result()->fetch_assoc();
}

if(isset($_POST['update_notice'])){
    $noticeID = $_POST['noticeID'];
    $title = trim($_POST['noticeTitle']);
    $summary = trim($_POST['noticeSummary']);
    $body = trim($_POST['noticeBody']);
    $image = trim($_POST['noticeImageLocation']);
    $link = trim($_POST['noticeLinkRelated']);
    $active = isset($_POST['noticeActive']) ? 1 : 0;
    $important = isset($_POST['noticeImportant']) ? 1 : 0;

    if($title=='' || $body==''){
        $message = "❌ Title and body are required.";
    } else {
        $stmt = $conn->prepare("UPDATE Notices SET 
            noticeTitle=?, noticeSummary=?, noticeBody=?, noticeImageLocation=?, noticeLinkRelated=?, noticeActive=?, noticeImportant=? 
            WHERE noticeID=?");
        $stmt->bind_param("sssssiis",$title,$summary,$body,$image,$link,$active,$important,$noticeID);
        $stmt->execute();
        $message = "✅ Notice updated successfully.";

        $editing = true;
        $stmt = $conn->prepare("SELECT * FROM Notices WHERE noticeID=?");
        $stmt->bind_param("s",$noticeID);
        $stmt->execute();
        $editNotice = $stmt->get_result()->fetch_assoc();
    }
}

// ---------- FETCH ALL NOTICES ----------
$notices = $conn->query("SELECT * FROM Notices ORDER BY noticeCreated DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin - Notices</title>
    <style>
        .container{padding:20px; max-width:1100px;margin:auto;}
        .form-row{display:flex; gap:8px; margin-bottom:8px;}
        input[type="text"], textarea{padding:8px;width:100%;}
        table{width:100%; border-collapse:collapse;}
        th, td{border:1px solid #ddd; padding:8px;text-align:left;}
        .msg{margin:10px 0; color:green;}
    </style>
</head>
<body>
<main class="container">
    <?php if($message) echo "<p class='msg'>".htmlspecialchars($message)."</p>"; ?>

    <!-- ADD / EDIT NOTICE -->
    <section>
        <h2><?= $editing ? "Edit Notice" : "Add New Notice" ?></h2>
        <form method="post" style="margin-bottom:20px;">
            <?php if($editing): ?>
                <input type="hidden" name="noticeID" value="<?= htmlspecialchars($editNotice['noticeID']) ?>">
            <?php endif; ?>
            <div class="form-row">
                <input type="text" name="noticeTitle" placeholder="Title" required value="<?= $editing ? htmlspecialchars($editNotice['noticeTitle']) : '' ?>">
            </div>
            <div class="form-row">
                <input type="text" name="noticeSummary" placeholder="Summary" value="<?= $editing ? htmlspecialchars($editNotice['noticeSummary']) : '' ?>">
            </div>
            <div class="form-row">
                <textarea name="noticeBody" placeholder="Body" rows="5" required><?= $editing ? htmlspecialchars($editNotice['noticeBody']) : '' ?></textarea>
            </div>
            <div class="form-row">
                <input type="text" name="noticeImageLocation" placeholder="Image URL/Path" value="<?= $editing ? htmlspecialchars($editNotice['noticeImageLocation']) : '' ?>">
            </div>
            <div class="form-row">
                <input type="text" name="noticeLinkRelated" placeholder="Related Link" value="<?= $editing ? htmlspecialchars($editNotice['noticeLinkRelated']) : '' ?>">
            </div>
            <div class="form-row">
                <label><input type="checkbox" name="noticeActive" <?= $editing && $editNotice['noticeActive']==1 ? 'checked' : '' ?>> Active</label>
                <label><input type="checkbox" name="noticeImportant" <?= $editing && $editNotice['noticeImportant']==1 ? 'checked' : '' ?>> Important</label>
            </div>
            <div class="form-row">
                <button type="submit" name="<?= $editing ? 'update_notice' : 'add_notice' ?>"><?= $editing ? 'Update Notice' : 'Add Notice' ?></button>
                <?php if($editing): ?><a href="notices.php" style="margin-left:10px;">Cancel</a><?php endif; ?>
            </div>
        </form>
    </section>

    <!-- PREVIEW SECTION -->
    <?php if($editing && $editNotice): ?>
    <section>
        <h3>Preview:</h3>
        <div style="border:1px solid #ccc;padding:10px;margin-bottom:20px;">
            <?php if($editNotice['noticeImageLocation']): ?>
                <img src="<?= htmlspecialchars($editNotice['noticeImageLocation']) ?>" alt="Notice Image" style="max-width:200px;display:block;margin-bottom:10px;">
            <?php endif; ?>
            <strong><?= htmlspecialchars($editNotice['noticeTitle']) ?></strong>
            <p><?= htmlspecialchars($editNotice['noticeSummary']) ?></p>
            <p><?= nl2br(htmlspecialchars($editNotice['noticeBody'])) ?></p>
            <?php if($editNotice['noticeLinkRelated']): ?>
                <a href="<?= htmlspecialchars($editNotice['noticeLinkRelated']) ?>" target="_blank">Related Link</a>
            <?php endif; ?>
            <p><em>Active: <?= $editNotice['noticeActive'] ? 'Yes' : 'No' ?> | Important: <?= $editNotice['noticeImportant'] ? 'Yes' : 'No' ?></em></p>
        </div>
    </section>
    <?php endif; ?>

    <!-- NOTICES TABLE -->
    <section>
        <h2>All Notices</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Summary</th>
                    <th>Active</th>
                    <th>Important</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($n = $notices->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($n['noticeID']) ?></td>
                    <td><?= htmlspecialchars($n['noticeTitle']) ?></td>
                    <td><?= htmlspecialchars($n['noticeSummary']) ?></td>
                    <td><?= $n['noticeActive'] ? 'Yes' : 'No' ?></td>
                    <td><?= $n['noticeImportant'] ? 'Yes' : 'No' ?></td>
                    <td>
                        <a href="?edit=<?= urlencode($n['noticeID']) ?>">Edit</a> |
                        <a href="?delete=<?= urlencode($n['noticeID']) ?>" onclick="return confirm('Delete this notice?')">Delete</a>
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