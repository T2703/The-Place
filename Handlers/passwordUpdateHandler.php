<?php
    include("../database.php");
    session_start();

    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to change your password.";
        exit;
    }
    $userId = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
        $newPassword = filter_input(INPUT_POST, "new_password", FILTER_SANITIZE_NUMBER_INT);

        // Hash the new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the query
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hashedPassword, $userId);

        try {
            mysqli_stmt_execute($stmt);
            echo "Password updated!";
        }
        catch (mysqli_sql_exception) {
            echo "Failed to update password";
        }
        mysqli_stmt_close($stmt);
    }
?>