<?php
    // BASE PATH Maker
    require_once($_SERVER['DOCUMENT_ROOT'] . '/jboymakiandbento/includes/rootfinder.php');
    // Database Related
    require_once(BASE_PATH . 'includes/dbchecker.php');
    
    try {
        $stmt = $conn->prepare("
            SELECT noticeID, noticeTitle, noticeLinkRelated
            FROM Notices
            WHERE noticeActive = 1
            ORDER BY noticeCreated DESC
            LIMIT 3
        ");
        $stmt->execute();
        $res = $stmt->get_result();
    }
    catch (Exception $e) {
        // In case DB isn't available, fallback to no notices
        $res = null;
    }
?>

<nav class="navigation-area">
    <div class="dropdown">
        <button><a href="/jboymakiandbento/Menu/Popular.php">Menu</a></button>
        <div class="dropdown-content">
            <a href="/jboymakiandbento/Menu/Popular.php">Popular</a>
            <a href="/jboymakiandbento/Menu/Bento-Rice-Meals.php">Bento Rice Meals</a>
            <a href="/jboymakiandbento/Menu/Ramen.php">Ramen</a>
            <a href="/jboymakiandbento/Menu/Maki-Rolls.php">Maki Rolls</a>
            <a href="/jboymakiandbento/Menu/Sushi-Rolls.php">Sushi Rolls</a>
            <a href="/jboymakiandbento/Menu/Salad.php">Salad</a>
        </div>
    </div>
    <div class="dropdown">
    <button><a href="/jboymakiandbento/notices-list.php">Notices</a></button>
    <div class="dropdown-content">
        <?php if ($res && $res->num_rows > 0): ?>
            <?php while ($n = $res->fetch_assoc()): 
                // prefer an external/related link if provided, otherwise link to internal detail page
                $href = !empty($n['noticeLinkRelated'])
                        ? $n['noticeLinkRelated']
                        : '/jboymakiandbento/notice-detail.php?id=' . urlencode($n['noticeID']);
            ?>
                <a href="<?= htmlspecialchars($href) ?>"><?= htmlspecialchars($n['noticeTitle']) ?></a>
            <?php endwhile; ?>
            <a href="/jboymakiandbento/notices-list.php">See More →</a>
        <?php else: ?>
            <a href="/jboymakiandbento/notices-list.php">No notices yet</a>
        <?php endif; ?>
    </div>
</div>
    <div class="dropdown">
        <button><a href="/jboymakiandbento/AboutUs.php">About Us</a></button>
    </div>
    <div class="dropdown">
        <button><a href="/jboymakiandbento/ContactUs.php">Contact Us</a></button>
    </div>
    <div class="dropdown">
        <button><a href="/jboymakiandbento/Feedback.php">Feedback</a></button>
    </div>
    <div class="dropdown login-button">
        <button><a href="/jboymakiandbento/login.php">Login</a></button>
    </div>
</nav>