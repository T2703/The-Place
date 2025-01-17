<?php
    include("../database.php");
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to delete a comment.";
        exit;
    }

    $userId = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {

        // Validate the reply/comment ID
        $commentId = filter_input(INPUT_POST, 'comment_id', FILTER_VALIDATE_INT);
        $commentId2 = filter_input(INPUT_POST, 'parent_comment_id2', FILTER_VALIDATE_INT);
        if (!$commentId) {
            echo "Invalid comment ID.";
            exit;
        }

        // Check if the comment exists and belongs to the user or if the user is the tweet owner
        $sqlCheck = "
            SELECT c.id 
            FROM comments AS c
            LEFT JOIN tweets AS t ON c.tweet_id = t.id
            WHERE c.id = ? 
            AND (c.user_id = ? OR t.user_id = ?)";
        $stmtCheck = mysqli_prepare($connection, $sqlCheck);

        if ($stmtCheck) {
            mysqli_stmt_bind_param($stmtCheck, "iii", $commentId, $userId, $userId);
            mysqli_stmt_execute($stmtCheck);
            mysqli_stmt_store_result($stmtCheck);

            if (mysqli_stmt_num_rows($stmtCheck) === 0) {
                echo "You do not have permission to delete this comment.";
                mysqli_stmt_close($stmtCheck);
                exit;
            }
            mysqli_stmt_close($stmtCheck);
        } else {
            echo "Error preparing query: " . mysqli_error($connection);
            exit;
        }

        // Delete the comment
        $sqlDelete = "DELETE FROM comments WHERE id = ?";
        $stmtDelete = mysqli_prepare($connection, $sqlDelete);

        if ($stmtDelete) {
            mysqli_stmt_bind_param($stmtDelete, "i", $commentId);
            if (mysqli_stmt_execute($stmtDelete)) {
                // Redirect back to the replies page
                header("Location: ../replies.php?comment_id=$commentId2");
                exit;
            } else {
                echo "Error: Could not delete the comment.";
            }
            mysqli_stmt_close($stmtDelete);
        } else {
            echo "Error preparing query: " . mysqli_error($connection);
        }
    } else {
        echo "Invalid request.";
    }

    // Close the database connection
    mysqli_close($connection);
exit;
?>
