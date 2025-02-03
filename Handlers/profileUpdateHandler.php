<?php
    include("../database.php");
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
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!empty($username) && !empty($email)) {
            // Update the query
            $sql = "UPDATE users SET username = ?, email = ? WHERE id = ?";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "ssi", $username, $email, $userId);

            try {
                mysqli_stmt_execute($stmt);
                echo "Account updated!";
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
    header("Location: ../profileUpdate.php");
    exit;
?>