<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/home.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <title>Search Results</title>
</head>
<body>

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

    if (isset($_GET['user_id'])) {
        $profileUserId = intval($_GET['user_id']);
    }
    
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
            JOIN tweet_likes  ON tweets.id = tweet_likes.tweet_id
            WHERE
                tweet_likes.user_id = ? AND  
                (
                    tweets.title LIKE ? OR
                    users.username LIKE ?
                ) AND
                users.id NOT IN (
                    SELECT blocked_id FROM blocks WHERE blocker_id = ?
                ) 
                AND users.id NOT IN (
                    SELECT blocker_id FROM blocks WHERE blocked_id = ?
                )
            ";   
            
    $sql .= " ORDER BY (tweets.dislikes - tweets.likes) ASC";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "ssiii", $profileUserId, $searchQuery, $searchQuery, $loggedInUserId, $loggedInUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);


    echo "<h2>Search Results for '" . htmlspecialchars($_GET['q']) . "'</h2>";

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<div class='post'>";
            echo "<div style='display: flex; align-items: center;'>";

            if (!empty($row['pfp'])) {
                echo "<img src='Handlers/displayPFPHandler.php?user_id={$row['user_id']}' class='pfp'>";
            }

            echo "<a href='profile.php?user_id={$row['user_id']}' class='username'>{$row['username']}</a>";
            echo "</div>";

            echo "<p class='title'>{$row['title']}</p>";
            echo "<p class='content'>{$row['content']}</p>";
            echo "<p class='meta'>Posted on " . date("F d, Y", strtotime($row['created_at'])) . "</p>";
            echo "<p class='meta'><strong>Likes:</strong> {$row['like_count']} | <strong>Dislikes:</strong> {$row['dislike_count']} | <strong>Comments:</strong> {$row['comments_count']}</p>";
            
            // Like button 
            echo "<div class='button-group'>";
            echo "<form method='post' action='Handlers/likeHandler.php'>";
            echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
            echo "<button type='submit' name='like' class='like-btn'>Like</button>";
            echo "</form>";
        
            // Dislike button 
            echo "<form method='post' action='Handlers/dislikeHandler.php'>";
            echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
            echo "<button type='submit' name='dislike' class='dislike-btn'>Dislike</button>";
            echo "</form>";
    
            // Comment button 
            echo "<form method='get' action='comment.php'>";
            echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
            echo "<button type='submit' name='comment' class='comment-btn'>Comment</button>";
            echo "</form>";

            echo "</div>"; // Close button group
            echo "</div>"; // Close post
        }
    } else {
        echo "<p>No results found.</p>";
    }


    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>