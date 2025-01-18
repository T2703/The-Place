<?php
    include("../database.php");
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to like a post.";
        exit;
    }

    $userId = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['like'])) {
        // Get the tweet ID from the form
        $tweetId = filter_input(INPUT_POST, 'commenet_like_id', FILTER_SANITIZE_NUMBER_INT);
        echo $tweetId;

        if (!empty($tweetId)) {
            // Check if the user has liked or disliked the post
            $checkSql = "
                SELECT 
                    (SELECT COUNT(*) FROM comment_likes WHERE user_id = ? AND comment_id = ?) AS liked,
                    (SELECT COUNT(*) FROM comment_dislikes WHERE user_id = ? AND comment_id = ?) AS disliked
            ";
            $checkStmt = mysqli_prepare($connection, $checkSql);
            mysqli_stmt_bind_param($checkStmt, "iiii", $userId, $tweetId, $userId, $tweetId);
            mysqli_stmt_execute($checkStmt);
            $result = mysqli_stmt_get_result($checkStmt);
            $row = mysqli_fetch_assoc($result);
            $isLiked = $row['liked'] > 0;
            $isDisliked = $row['disliked'] > 0;

            // Begin database transaction
            mysqli_begin_transaction($connection);

            try {
                if ($isLiked) {
                    // Remove the like
                    $deleteLikeSql = "DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?";
                    $deleteLikeStmt = mysqli_prepare($connection, $deleteLikeSql);
                    mysqli_stmt_bind_param($deleteLikeStmt, "ii", $userId, $tweetId);
                    mysqli_stmt_execute($deleteLikeStmt);

                    // Decrement likes count
                    $updateLikesSql = "UPDATE comments SET likes = likes - 1 WHERE id = ?";
                    $updateLikesStmt = mysqli_prepare($connection, $updateLikesSql);
                    mysqli_stmt_bind_param($updateLikesStmt, "i", $tweetId);
                    mysqli_stmt_execute($updateLikesStmt);

                    echo "Like removed!";
                } else {
                    // Add the like
                    $insertLikeSql = "INSERT INTO comment_likes (user_id, comment_id) VALUES (?, ?)";
                    $insertLikeStmt = mysqli_prepare($connection, $insertLikeSql);
                    mysqli_stmt_bind_param($insertLikeStmt, "ii", $userId, $tweetId);
                    mysqli_stmt_execute($insertLikeStmt);

                    // Increment likes count
                    $updateLikesSql = "UPDATE comments SET likes = likes + 1 WHERE id = ?";
                    $updateLikesStmt = mysqli_prepare($connection, $updateLikesSql);
                    mysqli_stmt_bind_param($updateLikesStmt, "i", $tweetId);
                    mysqli_stmt_execute($updateLikesStmt);

                    echo "You have liked this post!";

                    // If the post is disliked, remove the dislike
                    if ($isDisliked) {
                        $deleteDislikeSql = "DELETE FROM comment_dislikes WHERE user_id = ? AND comment_id = ?";
                        $deleteDislikeStmt = mysqli_prepare($connection, $deleteDislikeSql);
                        mysqli_stmt_bind_param($deleteDislikeStmt, "ii", $userId, $tweetId);
                        mysqli_stmt_execute($deleteDislikeStmt);

                        // Decrement dislikes count
                        $updateDislikesSql = "UPDATE comments SET dislikes = dislikes - 1 WHERE id = ?";
                        $updateDislikesStmt = mysqli_prepare($connection, $updateDislikesSql);
                        mysqli_stmt_bind_param($updateDislikesStmt, "i", $tweetId);
                        mysqli_stmt_execute($updateDislikesStmt);

                        echo "Dislike removed!";
                    }
                }

                // Commit transaction
                mysqli_commit($connection);
            } catch (mysqli_sql_exception $e) {
                // Rollback transaction on error
                mysqli_rollback($connection);
                echo "Error processing your request. <br>";
                echo $e;
            }
        } else {
            echo "Invalid post ID.";
            exit;
        }
    }

    // Close the database connection
    mysqli_close($connection);

    // Redirect back to the profile page
    //header("Location: ../home.php");
    exit;
?>
