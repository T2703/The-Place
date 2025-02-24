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
                users.id AS follower_id,
                users.username,
                users.pfp
            FROM follows 
            JOIN users ON follows.following_id = users.id
            WHERE
                follows.follower_id = ? 
                AND users.username LIKE ? 
                AND users.id NOT IN (
                    SELECT blocked_id FROM blocks WHERE blocker_id = ?
                ) 
                AND users.id NOT IN (
                    SELECT blocker_id FROM blocks WHERE blocked_id = ?
                )
            ORDER BY users.username ASC";

            
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "isii", $profileUserId, $searchQuery, $loggedInUserId, $loggedInUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    echo "<h2>Search Results for '" . htmlspecialchars($_GET['q']) . "'</h2>";

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $followerId = $row['follower_id']; // Get the follower's user ID
            
            // Check if logged-in user follows this specific follower
            $sqlCheckFollow = "SELECT * FROM follows WHERE follower_id = ? AND following_id = ?";
            $followStmt = mysqli_prepare($connection, $sqlCheckFollow);
            mysqli_stmt_bind_param($followStmt, "ii", $loggedInUserId, $followerId);
            mysqli_stmt_execute($followStmt);
            $followResult = mysqli_stmt_get_result($followStmt);
            $isFollowing = mysqli_num_rows($followResult) > 0; // Check if the user follows them
            mysqli_stmt_close($followStmt);
    
            // Display user info
            echo "<div class='post'>";
            echo "<div style='display: flex; align-items: center;'>";
    
            if (!empty($row['pfp'])) {
                echo "<img src='Handlers/displayPFPHandler.php?user_id={$followerId}' class='pfp'>";
            }
    
            echo "<a href='profile.php?user_id={$followerId}' class='username'>{$row['username']}</a>";
            echo "</div>";
    
            // Display follow/unfollow button
            if ($loggedInUserId != $followerId) {
                if ($isFollowing) {
                    // Unfollow button
                    echo "<form method='post' action='Handlers/followHandler.php'>";
                    echo "<input type='hidden' name='following_id' value='{$followerId}'>";
                    echo "<button type='submit' name='unfollow' style='background-color: red; color: white;'>Unfollow</button>";
                    echo "</form>";
                } else {
                    // Follow button
                    echo "<form method='post' action='Handlers/followHandler.php'>";
                    echo "<input type='hidden' name='following_id' value='{$followerId}'>";
                    echo "<button type='submit' name='follow' style='background-color: green; color: white;'>Follow</button>";
                    echo "</form>";
    
                    // Block button
                    echo "<form method='post' action='Handlers/blockHandler.php'>";
                    echo "<input type='hidden' name='block_id' value='{$followerId}'>";
                    echo "<button type='submit' name='block' style='background-color: green; color: white;'>Block</button>";
                    echo "</form>";
                }
            }
    
            echo "</div>"; // Close post
        }
    } else {
        echo "<p>No results found.</p>";
    }


    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>