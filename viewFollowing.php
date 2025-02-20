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
    <title>Following</title>
</head>
<body>
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
            users.id AS following_id, 
            users.username 
        FROM 
            follows
        JOIN 
            users 
        ON 
            follows.following_id = users.id
        LEFT JOIN blocks AS b1 ON (b1.blocker_id = ? AND b1.blocked_id = users.id) 
        LEFT JOIN blocks AS b2 ON (b2.blocker_id = users.id AND b2.blocked_id = ?)  
        WHERE 
            follows.follower_id = ?
        AND b1.blocked_id IS NULL  
        AND b2.blocked_id IS NULL  
        ORDER BY
            users.username ASC
    ";
    
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $loggedInUserId, $loggedInUserId, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Follow operations & code
    $sqlCheckFollow = "SELECT * FROM follows WHERE follower_id = ? AND following_id = ?";
    $followStmt = mysqli_prepare($connection, $sqlCheckFollow);
    mysqli_stmt_bind_param($followStmt, "ii", $loggedInUserId, $userId);
    mysqli_stmt_execute($followStmt);
    $followResult = mysqli_stmt_get_result($followStmt);
    $isFollowing = mysqli_num_rows($followResult) > 0;
    mysqli_stmt_close($followStmt);

    // Block operations & code
    $sqlCheckBlock = "SELECT * FROM blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?)";
    $blockStmt = mysqli_prepare($connection, $sqlCheckBlock);
    mysqli_stmt_bind_param($blockStmt, "iiii", $loggedInUserId, $userId, $userId, $loggedInUserId);
    mysqli_stmt_execute($blockStmt);
    $blockResult = mysqli_stmt_get_result($blockStmt);
    if (mysqli_num_rows($blockResult) === 0) {
        echo "No blocked users found!";
        echo $loggedInUserId;
        echo $userId;
    } else {
        echo "Blocked users exist!";
    }
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
    $privacyFollowing = $privacySettings['following'] ?? 0;

    // First checked if they are blocked
    if ($isBlocked) {
        echo "You are blocked from this user";
        exit;
    }

    // or if it is private
    if ($privacyFollowing == 1 && $loggedInUserId != $userId) {
        echo "Private Following";
        exit;
    }
    

    // Check if there is any tweets
    if (mysqli_num_rows($result) > 0) {
        // Fetching each tweet from the database. 
        while ($row = mysqli_fetch_assoc($result)) {
            // Tweet information 
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
            echo "<p><strong>{$row['username']}</strong></p>";
            echo "</div>";

        }
    }
    else {
        echo "<p>No followers</p>";
    }

    // Close the database connection
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>