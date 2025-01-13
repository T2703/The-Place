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
    Your likes <br>
</body>
</html>

<?php
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to post";
        exit;
    }
    else {
        $userId = $_SESSION['user_id'];
    }

    // Get the tweets likes from the logged in user. 
    $sql = "SELECT tweets.id, tweets.content, tweets.title, tweets.created_at, users.username 
            FROM tweet_likes
            JOIN tweets ON tweet_likes.tweet_id = tweets.id
            JOIN users ON tweets.user_id = users.id
            WHERE tweet_likes.user_id = ?
            ORDER BY tweets.created_at DESC";
    
    // Needed for filtering the data (i is for injection we to prevent that)
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if there is any tweets
    if (mysqli_num_rows($result) > 0) {
        echo "Your posts";

        // Fetching each tweet from the database. 
        while ($row = mysqli_fetch_assoc($result)) {
            // Tweet information 
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
            echo "<p><strong>{$row['username']}</strong></p>";
            echo "<p><strong>Title:</strong> {$row['title']}</p>";
            echo "<p>{$row['content']}</p>";
            echo "<p><em>Posted on {$row['created_at']}</em></p>";
            echo "</div>";

        }
    }
    else {
        echo "<p>You haven't liked anything yet.</p>";
    }

    // Close the database connection
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>