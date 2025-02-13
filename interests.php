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
</head>
<body>
    Your interests (who you follow) <br>
    <form method="post" action="login.php">
        <input type="submit" name="logout" value="logout">
    </form>
</body>
</html>

<?php
    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to like a post.";
        exit;
    }

    $userId = $_SESSION['user_id'];

    $filterUserId  = isset($_GET['filter_user_id']) ? trim($_GET['filter_user_id']) : '';

    // Get the tweets from the followed users. 
    $sql = "
    SELECT 
        tweets.id, 
        tweets.title, 
        tweets.content, 
        tweets.created_at, 
        users.id as user_id,
        users.username,
        users.pfp,
        (SELECT COUNT(*) FROM tweet_likes WHERE tweet_likes.tweet_id = tweets.id) AS like_count,
        (SELECT COUNT(*) FROM tweet_dislikes WHERE tweet_dislikes.tweet_id = tweets.id) AS dislike_count,
        (SELECT COUNT(*) FROM comments WHERE comments.tweet_id = tweets.id) AS comments_count
    FROM tweets 
    JOIN users ON tweets.user_id = users.id
    JOIN follows ON follows.following_id = users.id
    WHERE follows.follower_id = ?
        AND users.id NOT IN (
            SELECT blocked_id FROM blocks WHERE blocker_id = ?
        ) 
        AND users.id NOT IN (
            SELECT blocker_id FROM blocks WHERE blocked_id = ?
        )
";
                    
    // Add the filter condition for `filter_user_id` if it's set
    if ($filterUserId) {
        $sql .= " AND tweets.user_id = ?";
    }

    $sql .= " ORDER BY tweets.created_at DESC";

    $stmt = mysqli_prepare($connection, $sql);
    
    // Bind the appropriate parameters
    if ($filterUserId) {
        mysqli_stmt_bind_param($stmt, "iiii", $userId, $userId, $userId, $filterUserId);
    } else {
        mysqli_stmt_bind_param($stmt, "iii", $userId, $userId, $userId);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Fetching each tweet from the database. 
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<div style='display: flex; align-items: flex-start; margin-bottom: 20px;'>";

        // Profile pic for selecting filter
        if (!empty($row['pfp'])) {
            echo '<a href="?filter_user_id=' . $row['user_id'] . '">';
            echo '<img src="Handlers/displayPFPHandler.php?user_id=' . $row['user_id'] . '" width="150" height="150" style="border-radius: 100%;">';
            echo '</a>';
        }
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
        echo "<p><a href='profile.php?user_id={$row['user_id']}' style='color: blue; text-decoration: none;'>{$row['username']}</a></p>";
        echo "<p><strong>Title:</strong> {$row['title']}</p>";
        echo "<p>{$row['content']}</p>";
        echo "<p><em>Posted on {$row['created_at']}</em></p>";
        echo "<p><strong>Likes:</strong> {$row['like_count']} | <strong>Dislikes:</strong> {$row['dislike_count']}</p> <strong>Comments:</strong> {$row['comments_count']}</p>";
        echo "</div>";

        // Profile pic
        if (!empty($row['pfp'])) {
            echo '<img src="Handlers/displayPFPHandler.php?user_id=' . $row['user_id'] . '" width="150" height="150" style="border-radius: 100%;">';
        }
        else {
            echo "<p>No profile picture uploaded.</p>";
        }
        
        // Like button 
        echo "<form method='post' action='Handlers/likeHandler.php' style='margin-top: 10px;'>";
        echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
        echo "<button type='submit' name='like' style='color: white; background-color: green; border: none; padding: 5px 10px; cursor: pointer;'>Like</button>";
        echo "</form>";
    
        // Dislike button 
        echo "<form method='post' action='Handlers/dislikeHandler.php' style='margin-top: 10px;'>";
        echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
        echo "<button type='submit' name='dislike' style='color: white; background-color: red; border: none; padding: 5px 10px; cursor: pointer;'>Dislike</button>";
        echo "</form>";

        // Comment button 
        echo "<form method='get' action='comment.php' style='margin-top: 10px;'>";
        echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
        echo "<button type='submit' name='comment' style='color: white; background-color: blue; border: none; padding: 5px 10px; cursor: pointer;'>Comment</button>";
        echo "</form>";
        
    }

    // This is a place holder for testing out
    if (isset($_POST["logout"])) {
        session_destroy();
        header("Location: login.php");
    }

    mysqli_close($connection);
?>