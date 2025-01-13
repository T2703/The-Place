<?php
    include("database.php");
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to update a post.";
        exit;
    }
    else {
        $userId = $_SESSION['user_id'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Get the tweet ID from the form
        $tweetId = filter_input(INPUT_POST, 'tweet_id', FILTER_SANITIZE_NUMBER_INT);
        $title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_SPECIAL_CHARS);
        $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!empty($tweetId) && !empty($title) && !empty($content)) {
            // Update the query
            $sql = "UPDATE tweets SET title = ?, content = ? WHERE id = ? AND user_id = ?";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "ssii", $title, $content, $tweetId, $userId);

            try {
                mysqli_stmt_execute($stmt);
                echo "Post updated!";
            }
            catch (mysqli_sql_exception) {
                echo "Failed to update post";
            }
            mysqli_stmt_close($stmt);
        }
        else {
            echo "All fields are required.";
            exit;
        }
    }

    // Close the database connection
    mysqli_close($connection);

    // Redirect back to the profile page
    header("Location: profile.php");
    exit;
?>
