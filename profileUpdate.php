<?php
    include("database.php");
    include("navbar.php");
    session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    Update Profile <br>
</body>
</html>

<?php
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to post";
        exit;
    }
    $userId = $_SESSION['user_id'];

    // Fetch the tweet data
    $sql = "SELECT username, email FROM users WHERE id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Profile info 
        echo "<h1>Update Profile</h1>";
        echo "<form method='post' action='Handlers/profileUpdateHandler.php'>";
        echo "<label>Username:</label>";
        echo "<input type='text' name='username' value='{$row['username']}' required><br>";
        echo "<label>Email:</label>";
        echo "<input type='email' name='email' value='{$row['email']}' required><br>";
        echo "<button type='submit' style='margin-top: 10px; background-color: blue; color: white; padding: 10px 20px; border: none;'>Save</button>";
        echo "</form>";
    }
    else {
        echo "Unable to fetch profile details.";
    }
    mysqli_close($connection);
?>