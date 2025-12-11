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

if(!isset($_GET['id'])){
    die("Notice ID not provided.");
}

$noticeID = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM Notices WHERE noticeID = ? AND noticeActive = 1");
$stmt->bind_param("s", $noticeID);
$stmt->execute();
$notice = $stmt->get_result()->fetch_assoc();

if(!$notice){
    die("Notice not found or inactive.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($notice['noticeTitle']) ?></title>
    <style>
        body {font-family: Arial, sans-serif; padding:20px; max-width:900px; margin:auto;}
        img {max-width:100%; height:auto; margin-bottom:15px;}
        h1 {margin-bottom:10px;}
        p {margin-bottom:10px;}
        a.back {display:inline-block; margin-top:15px; color:blue; text-decoration:none;}
        a.back:hover {text-decoration:underline;}
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($notice['noticeTitle']) ?></h1>
    <?php if($notice['noticeImageLocation']): ?>
        <img src="<?= htmlspecialchars($notice['noticeImageLocation']) ?>" alt="Notice Image">
    <?php endif; ?>
    <?php if($notice['noticeSummary']): ?>
        <p><strong>Summary:</strong> <?= htmlspecialchars($notice['noticeSummary']) ?></p>
    <?php endif; ?>
    <p><?= nl2br(htmlspecialchars($notice['noticeBody'])) ?></p>
    <?php if($notice['noticeLinkRelated']): ?>
        <p>Related link: <a href="<?= htmlspecialchars($notice['noticeLinkRelated']) ?>" target="_blank"><?= htmlspecialchars($notice['noticeLinkRelated']) ?></a></p>
    <?php endif; ?>
    <a href="notices-list.php" class="back">← Back to Notices</a>
</body>
</html>
<?php $conn->close(); ?>
