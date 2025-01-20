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
    <title>Profile</title>
</head>
<body>
    <h1>Profile</h1>
</body>
</html>

<?php
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to view your profile.";
        exit;
    } else {
        $userId = $_SESSION['user_id'];
    }

    // Get the user's information
    $sql = "SELECT username, email, reg_date FROM users WHERE id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if the user exists and fetch the data
    if ($row = mysqli_fetch_assoc($result)) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 20px;'>";
        echo "<p><strong>Username:</strong> {$row['username']}</p>";
        echo "<p><strong>Email:</strong> {$row['email']}</p>";
        echo "<p><strong>Member Since:</strong> " . date("F d, Y", strtotime($row['reg_date'])) . "</p>";
        echo "</div>";
    } else {
        echo "<p>Unable to fetch your profile details.</p>";
    }

    // Close the statement and database connection
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>
