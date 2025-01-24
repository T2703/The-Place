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
    Create post <br>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <label for="title">Title:</label><br>
        <input type="text" id="title" name="title" placeholder="Title here..." maxlength="255" required> <br>

        <label for="tweet">Post:</label><br>
        <textarea id="tweet" name="tweet" placeholder="Write something..." style="height:200px" required></textarea><br>

        <input type="submit" name="submit" value="Tweet"><br> 
    </form>
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

    // Filter malicious scripts
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $title = filter_input(INPUT_POST, "title", FILTER_SANITIZE_SPECIAL_CHARS);
        $tweet = filter_input(INPUT_POST, "tweet", FILTER_SANITIZE_SPECIAL_CHARS);

        if (!empty($title) && !empty($tweet)) {
            $sqlTweet = "INSERT INTO tweets (user_id, title, content)
                         VALUES ('$userId', '$title', '$tweet')";

            try {
                mysqli_query($connection, $sqlTweet);
                echo "You have posted!";
            }
            catch (mysqli_sql_exception) {
                echo "Uh oh bad request";
            }
        }
    }
    mysqli_close($connection);
?>