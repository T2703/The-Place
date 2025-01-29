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

            echo "You have been blocked";
        }
        // Or unblock
        else if (isset($_POST['unblock'])) {
            $sql = "DELETE FROM blocks WHERE blocker_id = ? AND blocker_id = ?";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "ii", $loggedInUserId, $blockId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        else {
            echo "Block or unblock failed";
        }
        

        mysqli_stmt_close($stmt);
    }
?>