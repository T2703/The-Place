<?php
    include("../database.php");
    session_start();

    // Filter malicious scripts
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
        $userId = filter_input(INPUT_POST, "user_id", FILTER_SANITIZE_NUMBER_INT);
        $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);

        // Set hashed password if both fields are filled out.
        if (!empty($email) && !empty($password)) {
            // Query the database to find the user.
            $sql = "SELECT password FROM users WHERE id = ? AND email = ?";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "is", $userId, $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            // Check if the user exists 
            if ($row = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $row["password"])) {
                    // DELETE PROFILE
                    $deleteSql = "DELETE FROM users WHERE id = ?";
                    $deleteStmt = mysqli_prepare($connection, $deleteSql);
                    mysqli_stmt_bind_param($deleteStmt, "i", $userId);
                    try {
                        mysqli_stmt_execute($deleteStmt);
                        session_destroy();
                        header("Location: ../index.php");
                        exit();
                    }
                    catch (mysqli_sql_exception) {
                        echo "Error deleting account";
                    }
                }
                else {
                    echo "Error deleting account!";
                }
            }
            else {
                echo "No account found with that email";
            }

        }
    }
    mysqli_close($connection);
?>
