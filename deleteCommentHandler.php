<?php
    include("database.php");
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to delete a comment.";
        exit;
    }

    $userId = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {

        // Validate tweet ID
        $tweetId = filter_input(INPUT_POST, 'tweet_id', FILTER_VALIDATE_INT);
        $tweetIdGoBack = filter_input(INPUT_POST, 'tweet_id2', FILTER_VALIDATE_INT); // Voodoo sorry
        if (!$tweetId) {
            echo "Invalid tweet ID.";
            exit;
        }

        // Check if the comment exists and belongs to the user or post owner
        $sqlCheck = "SELECT id FROM comments WHERE id = ? AND (user_id = ? OR ? IN (SELECT user_id FROM tweets WHERE id = tweet_id))";
        $stmtCheck = mysqli_prepare($connection, $sqlCheck);

        if ($stmtCheck) {
            mysqli_stmt_bind_param($stmtCheck, "iii", $tweetId, $userId, $userId);
            mysqli_stmt_execute($stmtCheck);
            mysqli_stmt_store_result($stmtCheck);

            if (mysqli_stmt_num_rows($stmtCheck) === 0) {
                echo "You do not have permission to delete this comment.";
                mysqli_stmt_close($stmtCheck);
                exit;
            }
            mysqli_stmt_close($stmtCheck);
        }

        // Delete the comment from the table
        $sqlDelete = [
            "DELETE FROM comments WHERE id = ?",
            "DELETE FROM comment_likes WHERE comment_id = ?",
            "DELETE FROM comment_dislikes WHERE comment_id = ?"
        ];

        // Execute each delete query in the array
        foreach ($sqlDelete as $query) {
            $stmtDelete = mysqli_prepare($connection, $query);
            if ($stmtDelete) {
                mysqli_stmt_bind_param($stmtDelete, "i", $tweetId);
                if (!mysqli_stmt_execute($stmtDelete)) {
                    echo "Error executing query: " . mysqli_error($connection);
                    mysqli_stmt_close($stmtDelete);
                    exit;
                }
                mysqli_stmt_close($stmtDelete);
            } else {
                echo "Error preparing query: " . mysqli_error($connection);
                exit;
            }
        }

        // Redirect back to the comment page
        //header("Location: comment.php?tweet_id=$tweetIdGoBack");
        //exit;
    } else {
        echo "Invalid request.";
    }

    // Close the database connection
    mysqli_close($connection);
    exit;
?>
