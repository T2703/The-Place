<?php
    include("database.php");
    include("navbar.php");
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="styles/home.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
</head>
<body>
    Your followers <br>
</body>
</html>

<?php
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to see followers";
        exit;
    }
    else {
        $loggedInUserId = $_SESSION['user_id'];
    }

    if (isset($_GET['user_id'])) {
        $userId = intval($_GET['user_id']);
    }

    // Get the followers from the profile
    $sql = "
        SELECT 
            users.id AS follower_id, 
            users.username, 
            users.pfp
        FROM follows
        JOIN users ON follows.follower_id = users.id
        LEFT JOIN blocks AS b1 ON (b1.blocker_id = ? AND b1.blocked_id = users.id) 
        LEFT JOIN blocks AS b2 ON (b2.blocker_id = users.id AND b2.blocked_id = ?)  
        WHERE follows.following_id = ?
        AND b1.blocked_id IS NULL  
        AND b2.blocked_id IS NULL  
        ORDER BY users.username ASC
    ";

    
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $loggedInUserId, $loggedInUserId, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Follow operations & code
    /*$sqlCheckFollow = "SELECT * FROM follows WHERE follower_id = ? AND following_id = ?";
    $followStmt = mysqli_prepare($connection, $sqlCheckFollow);
    mysqli_stmt_bind_param($followStmt, "ii", $loggedInUserId, $userId);
    mysqli_stmt_execute($followStmt);
    $followResult = mysqli_stmt_get_result($followStmt);
    $rowCount = mysqli_num_rows($followResult);
    $isFollowing = $rowCount > 0;
    mysqli_stmt_close($followStmt);*/
    
    

    // Block operations & code
    $sqlCheckBlock = "SELECT * FROM blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)";
    $blockStmt = mysqli_prepare($connection, $sqlCheckBlock);
    mysqli_stmt_bind_param($blockStmt, "iiii", $loggedInUserId, $userId, $userId, $loggedInUserId);
    mysqli_stmt_execute($blockStmt);
    $blockResult = mysqli_stmt_get_result($blockStmt);
    $isBlocked = mysqli_num_rows($blockResult) > 0;
    mysqli_stmt_close($blockStmt);

    // Private data fetching
    $privacySql = "SELECT privacy_type, is_private FROM user_privacy WHERE user_id = ?";
    $privacyStmt = mysqli_prepare($connection, $privacySql);
    mysqli_stmt_bind_param($privacyStmt, "i", $userId);
    mysqli_stmt_execute($privacyStmt);
    $privacyResult = mysqli_stmt_get_result($privacyStmt);

    $privacySettings = [];
    while ($privacyRow = mysqli_fetch_assoc($privacyResult)) {
        $privacySettings[$privacyRow['privacy_type']] = $privacyRow['is_private'];
    }

    // Default values if privacy settings don't exist yet
    $privacyFollowers = $privacySettings['followers'] ?? 0;

    // First checked if they are blocked
    if ($isBlocked) {
        echo "You are blocked from this user";
        exit;
    }

    // or if it is private
    if ($privacyFollowers == 1 && $loggedInUserId != $userId) {
        echo "Private Followers";
        exit;
    }

    // Check if there is any tweets
    if (mysqli_num_rows($result) > 0) {
        echo "Your posts";

        // Fetching each tweet from the database. 
        while ($row = mysqli_fetch_assoc($result)) {

            // Some stuff
            $sqlCheckFollow = "SELECT * FROM follows WHERE follower_id = ? AND following_id = ?";
            $followStmt = mysqli_prepare($connection, $sqlCheckFollow);
            mysqli_stmt_bind_param($followStmt, "ii", $loggedInUserId, $row['follower_id']);
            mysqli_stmt_execute($followStmt);
            $followResult = mysqli_stmt_get_result($followStmt);
            $rowCount = mysqli_num_rows($followResult);
            $isFollowing = $rowCount > 0;

            // Tweet information 
            echo "<div class='post'>";
            echo "<div style='display: flex; align-items: center;'>";

            if (!empty($row['pfp'])) {
                echo "<img src='Handlers/displayPFPHandler.php?user_id={$row['follower_id']}' class='pfp'>";
            }

            echo "<a href='profile.php?user_id={$row['follower_id']}' class='username'>{$row['username']}</a>";
            echo "</div>";

            // Display the appropriate button
            if ($loggedInUserId != $row['follower_id']) {
                if ($isFollowing) {
                    // Unfollow
                    echo "<form method='post' action='Handlers/followHandler.php'>";
                    echo "<input type='hidden' name='following_id' value='{$row['follower_id']}'>";
                    echo "<button type='submit' name='unfollow' style='background-color: red; color: white;'>Unfollow</button>";
                    echo "</form>";
                } 
                else {
                    // Follow
                    echo "<form method='post' action='Handlers/followHandler.php'>";
                    echo "<input type='hidden' name='following_id' value='{$row['follower_id']}'>";
                    echo "<button type='submit' name='follow' style='background-color: green; color: white;'>Follow</button>";
                    echo "</form>";
    
                    // Block
                    echo "<form method='post' action='Handlers/blockHandler.php'>";
                    echo "<input type='hidden' name='block_id' value='{$userId}'>";
                    echo "<button type='submit' name='block' style='background-color: green; color: white;'>block</button>";
                    echo "</form>";
                }
            }

        }
    }
    else {
        echo "<p>No followers</p>";
    }

    // Close the database connection
    mysqli_stmt_close($stmt);
    mysqli_stmt_close($followStmt);
    mysqli_close($connection);
?>