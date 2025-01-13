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
    Update <br>
</body>
</html>

<?php
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to post";
        exit;
    }
    else {
        $userId = $_SESSION['user_id'];
    }

    // Get the tweet id from the query parameter
    $tweetId = filter_input(INPUT_GET, 'tweet_id', FILTER_SANITIZE_NUMBER_INT);

    // Exit if no id.
    if (empty($tweetId)) {
        echo "Invalid tweet ID.";
        exit;
    }

    // Fetch the tweet data
    $sql = "SELECT * FROM tweets WHERE id = ? AND user_id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $tweetId, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        // Tweet information 
        echo "<form method='post' action='updateTweetHandler.php'>";
        echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>"; // Hidden input for tweet ID
        echo "<label for='title'>Title:</label><br>";
        echo "<input type='text' id='title' name='title' value='" . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . "' required><br><br>";
        echo "<label for='content'>Content:</label><br>";
        echo "<textarea id='content' name='content' rows='5' cols='50' required>" . htmlspecialchars($row['content'], ENT_QUOTES, 'UTF-8') . "</textarea><br><br>";
        echo "<button type='submit'>Update</button>";
        echo "</form>";
    }
    else {
        echo "Post not found";
    }
    mysqli_close($connection);
?>