<?php
    include("../database.php");

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
        $userId = filter_input(INPUT_POST, "user_id", FILTER_SANITIZE_NUMBER_INT);
        $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);

        if (!empty($email) && !empty($password)) {
            // Query the database to verify the user
            $sql = "SELECT password FROM users WHERE id = ? AND email = ?";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "is", $userId, $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $row["password"])) {
                    
                    // Start a transaction
                    mysqli_begin_transaction($connection);

                    try {
                        // Delete records with foreign key constraints first
                        $deleteRelations = [
                            "DELETE FROM blocks WHERE blocked_id = ?", 
                            "DELETE FROM blocks WHERE blocker_id = ?",
                            "DELETE FROM tweet_likes WHERE user_id = ?", 
                            "DELETE FROM tweet_dislikes WHERE user_id = ?", 
                            "DELETE FROM comments WHERE user_id = ?", 
                            "DELETE FROM follows WHERE follower_id = ?", 
                            "DELETE FROM tweets WHERE user_id = ?"
                        ];

                        foreach ($deleteRelations as $query) {
                            $stmtDelete = mysqli_prepare($connection, $query);
                            mysqli_stmt_bind_param($stmtDelete, "i", $userId);
                            mysqli_stmt_execute($stmtDelete);
                            mysqli_stmt_close($stmtDelete);
                        }

                        // Now delete the user
                        $deleteUserSql = "DELETE FROM users WHERE id = ?";
                        $stmtDeleteUser = mysqli_prepare($connection, $deleteUserSql);
                        mysqli_stmt_bind_param($stmtDeleteUser, "i", $userId);
                        mysqli_stmt_execute($stmtDeleteUser);
                        mysqli_stmt_close($stmtDeleteUser);

                        // Commit transaction
                        mysqli_commit($connection);

                        // Destroy session and redirect
                        session_destroy();
                        header("Location: ../index.php");
                        exit();

                    } catch (mysqli_sql_exception $e) {
                        // Rollback transaction if error occurs
                        mysqli_rollback($connection);
                        echo "Error deleting account: " . $e->getMessage();
                    }
                } else {
                    echo "Error deleting account: Incorrect password!";
                }
            } else {
                echo "No account found with that email.";
            }
        }
    }
    mysqli_close($connection);
?>
