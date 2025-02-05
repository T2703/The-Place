<?php
    include("database.php");
    include("navbar.php");
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    Your following <br>
</body>
</html>

<?php
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to see followers";
        exit;
    }
    else {
        $userId = $_SESSION['user_id'];
    }

    // Get the followers from the profile
    $sql = "
        SELECT 
            users.id AS following_id, 
            users.username 
        FROM 
            follows
        JOIN 
            users 
        ON 
            follows.following_id = users.id
        WHERE 
            follows.follower_id = ?
        ORDER BY
            users.username ASC
    ";
    
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if there is any tweets
    if (mysqli_num_rows($result) > 0) {
        echo "Your posts";

        // Fetching each tweet from the database. 
        while ($row = mysqli_fetch_assoc($result)) {
            // Tweet information 
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
            echo "<p><strong>{$row['username']}</strong></p>";
            echo "</div>";

        }
    }
    else {
        echo "<p>No followers</p>";
    }

    // Close the database connection
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>