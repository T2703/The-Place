<?php
    include("../database.php");

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_pic"])) {
        
        // Check if the user is logged in
        if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
            die("You need to log in to upload a profile picture.");
        }

        $userId = $_SESSION['user_id'];

        // Get image data
        $imageTmp = $_FILES["profile_pic"]["tmp_name"];
        $imageType = $_FILES["profile_pic"]["type"];

        // Ensure a file was uploaded
        if (!$imageTmp) {
            die("No image uploaded.");
        }

        // Read file contents
        $imgData = file_get_contents($imageTmp);

        // Debugging check
        if (!$imgData) {
            die("Error reading image file.");
        }

        // Prepare SQL statement
        $sql = "UPDATE users SET pfp = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $sql);
        
        if (!$stmt) {
            die("SQL prepare error: " . mysqli_error($connection));
        }

        // Bind BLOB data
        mysqli_stmt_bind_param($stmt, "si", $imgData, $userId);
        
        if (!mysqli_stmt_execute($stmt)) {
            die("SQL execution error: " . mysqli_error($connection));
        }

        echo "Profile picture updated successfully!";
        header("Location: ../profile.php?user_id=$userId"); // Redirect to profile
        exit();

        mysqli_stmt_close($stmt);
        mysqli_close($connection);
    }
?>
