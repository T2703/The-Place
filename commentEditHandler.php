<?php
    include("database.php");
    session_start();
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_edit'])) {
        $userId = $_SESSION['user_id'];
        $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_SANITIZE_NUMBER_INT);
        $tweetId = filter_input(INPUT_POST, 'tweet_id', FILTER_SANITIZE_NUMBER_INT);
        $commentContent = filter_input(INPUT_POST, 'comment_content', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!empty($commentId) && !empty($commentContent)) {
            // Update the comment into the database
            $sql = "UPDATE comments SET content = ? WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "sii", $commentContent, $commentId, $userId);
            if (mysqli_stmt_execute($stmt)) {
                echo "Comment edited successfully!";
                header("Location: comment.php?tweet_id=$tweetId");
                exit;
            } else {
                echo "Error: Could not edit the comment.";
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "Invalid input.";
        }
    }
    mysqli_close($connection);
?>