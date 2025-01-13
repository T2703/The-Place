<?php
    include("database.php");
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to delete a post.";
        exit;
    }
    else {
        $userId = $_SESSION['user_id'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
        // Get the tweet ID from the form
        $tweetId = filter_input(INPUT_POST, 'tweet_id', FILTER_SANITIZE_NUMBER_INT);

        if (!empty($tweetId)) {
            // Prepare the delete query
            $sql = "DELETE FROM tweets WHERE id = ? AND user_id = ?";

            $stmt = mysqli_prepare($connection, $sql);

            if ($stmt) {
                // Bind the parameters (tweet ID and user ID)
                mysqli_stmt_bind_param($stmt, "ii", $tweetId, $_SESSION['user_id']);

                // Execute the statement
                if (mysqli_stmt_execute($stmt)) {
                    echo "Tweet deleted successfully.";
                } else {
                    echo "Failed to delete tweet. Please try again.";
                }

                mysqli_stmt_close($stmt);
            } else {
                echo "Error preparing query: " . mysqli_error($connection);
            }
        } else {
            echo "Invalid tweet ID.";
        }
    }

    // Close the database connection
    mysqli_close($connection);

    // Redirect back to the profile page
    header("Location: profile.php");
    exit;
?>
