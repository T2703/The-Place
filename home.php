<?php
    include("database.php");
    include("navbar.php");
    session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    What's on your mind today? <br>
    <form action="<?php htmlspecialchars($_SERVER["PHP_SELF"])?>" method="post">
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
    // Get the tweets from the specified user. 
    $sql = "SELECT 
            tweets.id, 
            tweets.title, 
            tweets.content, 
            tweets.created_at, 
            users.username,
            (SELECT COUNT(*) FROM tweet_likes WHERE tweet_likes.tweet_id = tweets.id) AS like_count,
            (SELECT COUNT(*) FROM tweet_dislikes WHERE tweet_dislikes.tweet_id = tweets.id) AS dislike_count
            FROM tweets 
            JOIN users ON tweets.user_id = users.id
            ORDER BY tweets.created_at DESC";

    $result = mysqli_query($connection, $sql);

    // Fetching each tweet from the database. 
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
        echo "<p><strong>{$row['username']}</strong></p>";
        echo "<p><strong>Title:</strong> {$row['title']}</p>";
        echo "<p>{$row['content']}</p>";
        echo "<p><em>Posted on {$row['created_at']}</em></p>";
        echo "<p><strong>Likes:</strong> {$row['like_count']} | <strong>Dislikes:</strong> {$row['dislike_count']}</p>";
        echo "</div>";
        
        // Like button 
        echo "<form method='post' action='likeHandler.php' style='margin-top: 10px;'>";
        echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
        echo "<button type='submit' name='like' style='color: white; background-color: green; border: none; padding: 5px 10px; cursor: pointer;'>Like</button>";
        echo "</form>";
    
        // Dislike button 
        echo "<form method='post' action='dislikeHandler.php' style='margin-top: 10px;'>";
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