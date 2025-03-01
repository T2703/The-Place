<?php
    include("../database.php");
    session_start();

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        $userId = $_SESSION['user_id'];
        $tweetId = filter_input(INPUT_POST, 'tweet_id', FILTER_SANITIZE_NUMBER_INT);
        $parentCommentId = filter_input(INPUT_POST, 'parent_comment_id', FILTER_SANITIZE_NUMBER_INT); // Parent comment ID
        $commentContent = filter_input(INPUT_POST, 'comment_content', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!empty($tweetId) && !empty($commentContent)) {
            // Insert the reply into the comments table
            $sql = "INSERT INTO comments (user_id, tweet_id, parent_comment_id, content, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "iiis", $userId, $tweetId, $parentCommentId, $commentContent);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: ../replies.php?comment_id=$parentCommentId");
                exit;
            } else {
                echo "Error: Could not add the reply. " . mysqli_error($connection);
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "Invalid input. Please make sure all fields are filled.";
        }
    }

    mysqli_close($connection);
?>
