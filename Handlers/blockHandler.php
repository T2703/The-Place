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
        $blockId = filter_input(INPUT_POST, 'block_id', FILTER_SANITIZE_NUMBER_INT);
        // Block the user.
        if (isset($_POST['block'])) {
            $sql = "INSERT INTO blocks (blocker_id, blocked_id) VALUES (?, ?)";

            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $loggedInUserId, $blockId);
            mysqli_stmt_execute($stmt);

            // Delete relations next (cause block duh)
            $deleteRelations = [
                "DELETE FROM tweet_likes WHERE user_id = ?", 
                "DELETE FROM tweet_dislikes WHERE user_id = ?", 
                "DELETE FROM follows WHERE follower_id = ?", 
            ];

            foreach ($deleteRelations as $query) {
                $stmtDelete = mysqli_prepare($connection, $query);
                mysqli_stmt_bind_param($stmtDelete, "i", $blockId);
                mysqli_stmt_execute($stmtDelete);
                mysqli_stmt_close($stmtDelete);
            }

            echo "You have been blocked";
        }
        // Or unblock
        else if (isset($_POST['unblock'])) {
            $sqlUnblock = "DELETE FROM blocks WHERE blocker_id = ? AND blocked_id = ?"; 
            $stmtUnblock = mysqli_prepare($connection, $sqlUnblock);
            mysqli_stmt_bind_param($stmtUnblock, "ii", $loggedInUserId, $blockId);
            mysqli_stmt_execute($stmtUnblock);
            mysqli_stmt_close($stmtUnblock);
        }
        else {
            echo "Block or unblock failed";
        }
        
    }
?>