<?php
    include("../database.php");
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to dislike a comment.";
        exit;
    }

    $userId = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dislike'])) {
        // Get the comment ID from the form
        $commentId = filter_input(INPUT_POST, 'comment_dislike_id', FILTER_SANITIZE_NUMBER_INT);

        if (!empty($commentId)) {
            // Check if the user has liked or disliked the comment
            $checkSql = "
                SELECT 
                    (SELECT COUNT(*) FROM comment_likes WHERE user_id = ? AND comment_id = ?) AS liked,
                    (SELECT COUNT(*) FROM comment_dislikes WHERE user_id = ? AND comment_id = ?) AS disliked
            ";
            $checkStmt = mysqli_prepare($connection, $checkSql);
            mysqli_stmt_bind_param($checkStmt, "iiii", $userId, $commentId, $userId, $commentId);
            mysqli_stmt_execute($checkStmt);
            $result = mysqli_stmt_get_result($checkStmt);
            $row = mysqli_fetch_assoc($result);
            $isLiked = $row['liked'] > 0;
            $isDisliked = $row['disliked'] > 0;

            // Begin database transaction
            mysqli_begin_transaction($connection);

            try {
                if ($isDisliked) {
                    // Remove the dislike
                    $deleteDislikeSql = "DELETE FROM comment_dislikes WHERE user_id = ? AND comment_id = ?";
                    $deleteDislikeStmt = mysqli_prepare($connection, $deleteDislikeSql);
                    mysqli_stmt_bind_param($deleteDislikeStmt, "ii", $userId, $commentId);
                    mysqli_stmt_execute($deleteDislikeStmt);

                    // Decrement dislikes count
                    $updateDislikesSql = "UPDATE comments SET dislikes = dislikes - 1 WHERE id = ?";
                    $updateDislikesStmt = mysqli_prepare($connection, $updateDislikesSql);
                    mysqli_stmt_bind_param($updateDislikesStmt, "i", $commentId);
                    mysqli_stmt_execute($updateDislikesStmt);

                    echo "Dislike removed!";
                } else {
                    // Add the dislike
                    $insertDislikeSql = "INSERT INTO comment_dislikes (user_id, comment_id) VALUES (?, ?)";
                    $insertDislikeStmt = mysqli_prepare($connection, $insertDislikeSql);
                    mysqli_stmt_bind_param($insertDislikeStmt, "ii", $userId, $commentId);
                    mysqli_stmt_execute($insertDislikeStmt);

                    // Increment dislikes count
                    $updateDislikesSql = "UPDATE comments SET dislikes = dislikes + 1 WHERE id = ?";
                    $updateDislikesStmt = mysqli_prepare($connection, $updateDislikesSql);
                    mysqli_stmt_bind_param($updateDislikesStmt, "i", $commentId);
                    mysqli_stmt_execute($updateDislikesStmt);

                    echo "You have disliked this comment!";

                    // If the comment is liked, remove the like
                    if ($isLiked) {
                        $deleteLikeSql = "DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?";
                        $deleteLikeStmt = mysqli_prepare($connection, $deleteLikeSql);
                        mysqli_stmt_bind_param($deleteLikeStmt, "ii", $userId, $commentId);
                        mysqli_stmt_execute($deleteLikeStmt);

                        // Decrement likes count
                        $updateLikesSql = "UPDATE comments SET likes = likes - 1 WHERE id = ?";
                        $updateLikesStmt = mysqli_prepare($connection, $updateLikesSql);
                        mysqli_stmt_bind_param($updateLikesStmt, "i", $commentId);
                        mysqli_stmt_execute($updateLikesStmt);

                        echo "Like removed!";
                    }
                }

                // Commit transaction
                mysqli_commit($connection);
            } catch (mysqli_sql_exception $e) {
                // Rollback transaction on error
                mysqli_rollback($connection);
                echo "Error processing your request.";
                echo $e;
            }
        } else {
            echo "Invalid comment ID.";
            exit;
        }
    }

    // Close the database connection
    mysqli_close($connection);

    // Redirect back to the profile page
    header("Location: ../home.php");
    exit;
?>
