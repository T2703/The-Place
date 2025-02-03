<?php
    include("database.php");

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        exit;
    }

    $loggedInUserId = $_SESSION['user_id'];
    
    $searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

    if (empty($searchQuery)) {
        echo json_encode([]);
        exit;
    }

    $searchQuery = "%" . $searchQuery . "%";

    $sql = "SELECT 
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
            WHERE
                users.id NOT IN (
                    SELECT blocked_id FROM blocks WHERE blocker_id = ?
                ) 
                AND users.id NOT IN (
                    SELECT blocker_id FROM blocks WHERE blocked_id = ?
                )
            ";   

    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $searchQuery, $searchQuery);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);


    echo "<h2>Search Results for '" . htmlspecialchars($_GET['q']) . "'</h2>";

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
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
    } else {
        echo "<p>No results found.</p>";
    }


    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>