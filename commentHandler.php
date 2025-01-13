<?php
    include("database.php");
    session_start();

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        $userId = $_SESSION['user_id'];
        $tweetId = filter_input(INPUT_POST, 'tweet_id', FILTER_SANITIZE_NUMBER_INT);
        $commentContent = filter_input(INPUT_POST, 'comment_content', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!empty($tweetId) && !empty($commentContent)) {
            // Insert the comment into the database
            $sql = "INSERT INTO comments (user_id, tweet_id, content, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "iis", $userId, $tweetId, $commentContent);
            if (mysqli_stmt_execute($stmt)) {
                echo "Comment added successfully!";
                header("Location: comment.php");
                exit;
            } else {
                echo "Error: Could not add the comment.";
            }
            mysqli_stmt_close($stmt);
        } else {
            echo "Invalid input.";
        }
    }
    mysqli_close($connection);
?>