<?php
    include("../database.php");
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to dislike a comment.";
        exit;
    }

    $userId = $_SESSION['user_id'];
    $privacyLikes = isset($_POST['privacy_likes']) ? 1 : 0;
    $privacyFollows = isset($_POST['privacy_follows']) ? 1 : 0;
    $privacyFollowers = isset($_POST['privacy_followers']) ? 1 : 0;

    // SQL for inserting private settings into table
    $sql = "INSERT INTO user_privacy (user_id, privacy_type, is_private)
            VALUES (?, 'likes', ?), (?, 'follows', ?), (?, 'followers', ?)
            ON DUPLICATE KEY UPDATE is_private = VALUES(is_private)";

    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "iiiiii", $userId, $privacyLikes, $userId, $privacyFollows, $userId, $privacyFollowers);
    
    try {
        mysqli_stmt_execute($stmt);
        echo "You private thing";
        //exit();
    }
    catch (mysqli_sql_exception $e) {
        echo "Error privating: ", $e;
    }

    // Delete SQL for settings that are turned off.
    $sqlDelete = "DELETE FROM user_privacy 
                  WHERE user_id = ? AND 
                  ((privacy_type = 'likes' AND ? = 0) OR 
                  (privacy_type = 'follows' AND ? = 0) OR 
                  (privacy_type = 'followers' AND ? = 0))
                ";

    $stmtDelete = mysqli_prepare($connection, $sqlDelete);
    mysqli_stmt_bind_param($stmtDelete, "iiii", $userId, $privacyLikes, $privacyFollows, $privacyFollowers);

    try {
        mysqli_stmt_execute($stmtDelete);
        echo "You private delete";
    }
    catch (mysqli_sql_exception $e) {
        echo "Error privating deleting: ", $e;
    }

    mysqli_close($connection);
?>