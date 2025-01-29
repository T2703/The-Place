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
    Your blocks <br>
</body>
</html>

<?php
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to post";
        exit;
    }

    $userId = $_SESSION['user_id'];


    // Get the block users from the logged in user. 
    $sql = "
    SELECT 
        blocks.id AS block_id,
        users.username AS blocked_username
    FROM blocks
    JOIN users ON users.id = blocks.blocked_id
    WHERE blocks.blocker_id = ?
    UNION
    SELECT 
        blocks.id AS block_id,
        users.username AS blocker_username
    FROM blocks
    JOIN users ON users.id = blocks.blocker_id
    WHERE blocks.blocked_id = ?
    ";
    
    // Needed for filtering the data (i is for injection we to prevent that)
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $userId, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if there are blocked users
    if (mysqli_num_rows($result) > 0) {
        echo "<p>The following users are blocked or have blocked you:</p>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
            echo "<p><strong>Username:</strong> {$row['blocked_username']}</p>";
            echo "</div>";

            // Unblock
            echo "<form method='post' action='Handlers/blockHandler.php'>";
            echo "<input type='hidden' name='block_id' value='{$userId}'>";
            echo "<button type='submit' name='unblock' style='background-color: red; color: white;'>Unfollow</button>";
            echo "</form>";
        }
    } else {
        echo "<p>No blocked users found.</p>";
    }

    // Close the database connection
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>