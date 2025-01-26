<?php
    include("../database.php");

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to follow a user.";
        exit;
    }

    $loggedInUserId = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $followingId = filter_input(INPUT_POST, 'following_id', FILTER_SANITIZE_NUMBER_INT);

        // Follow the user.
        if (isset($_POST['follow'])) {
            // Insert the follow relationship into the 'follows' table
            $sql = "INSERT INTO follows (follower_id, following_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $loggedInUserId, $followingId);

            if (mysqli_stmt_execute($stmt)) {
                // Increment the 'follows' count for the logged-in user
                $incrementFollowsSql = "UPDATE users SET following = following + 1 WHERE id = ?";
                $incrementFollowsStmt = mysqli_prepare($connection, $incrementFollowsSql);
                mysqli_stmt_bind_param($incrementFollowsStmt, "i", $loggedInUserId);
                mysqli_stmt_execute($incrementFollowsStmt);
                mysqli_stmt_close($incrementFollowsStmt);

                // Increment the 'followers' count for the followed user
                $incrementFollowersSql = "UPDATE users SET followers = followers + 1 WHERE id = ?";
                $incrementFollowersStmt = mysqli_prepare($connection, $incrementFollowersSql);
                mysqli_stmt_bind_param($incrementFollowersStmt, "i", $followingId);
                mysqli_stmt_execute($incrementFollowersStmt);
                mysqli_stmt_close($incrementFollowersStmt);

                echo "You are now following this user.";
            } else {
                echo "Failed to follow the user.";
            }

            mysqli_stmt_close($stmt);
        }
        else if (isset($_POST['unfollow'])) {
            $deleteFollowsSql = "DELETE FROM follows WHERE follower_id = ? AND following_id = ?";
            $deleteFollowsStmt = mysqli_prepare($connection, $deleteFollowsSql);
            mysqli_stmt_bind_param($deleteFollowsStmt, "ii", $loggedInUserId, $followingId);
            mysqli_stmt_execute($deleteFollowsStmt);
            mysqli_stmt_close($deleteFollowsStmt);

            // Decrement the 'follows' count for the logged-in user
            $incrementFollowsSql = "UPDATE users SET following = following - 1 WHERE id = ?";
            $incrementFollowsStmt = mysqli_prepare($connection, $incrementFollowsSql);
            mysqli_stmt_bind_param($incrementFollowsStmt, "i", $loggedInUserId);
            mysqli_stmt_execute($incrementFollowsStmt);
            mysqli_stmt_close($incrementFollowsStmt);

            // Decrement the 'followers' count for the followed user
            $incrementFollowersSql = "UPDATE users SET followers = followers - 1 WHERE id = ?";
            $incrementFollowersStmt = mysqli_prepare($connection, $incrementFollowersSql);
            mysqli_stmt_bind_param($incrementFollowersStmt, "i", $followingId);
            mysqli_stmt_execute($incrementFollowersStmt);
            mysqli_stmt_close($incrementFollowersStmt);
        }
    }

    mysqli_close($connection);
    
    // Redirect back to the profile page
    header("Location: ../profile.php?user_id=$followingId");
?>