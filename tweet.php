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
    <title>Create Post</title>
    <link rel="stylesheet" href="styles/tweet.css"> 
</head>
<body>
<div class="post-container">
    <h2>Create a New Post</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype='multipart/form-data'>
        
        <label for="title">Title:</label>
        <input type="text" id="title" name="title" placeholder="Enter post title..." maxlength="255" required>

        <label for="tweet">Post:</label>
        <textarea id="tweet" name="tweet" placeholder="Write something..." required></textarea>

        <button type="submit" name="submit" class="post-btn">Tweet</button>
    </form>
</div>
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
        $imageDataArray = [];
 
        if (!empty($title) && !empty($tweet)) {
            // Insert post first
            $sqlTweet = "INSERT INTO tweets (user_id, title, content) VALUES (?, ?, ?)";
            $stmtTweet = mysqli_prepare($connection, $sqlTweet);
    
            if ($stmtTweet) {
                mysqli_stmt_bind_param($stmtTweet, "iss", $userId, $title, $tweet);
                try {
                    mysqli_stmt_execute($stmtTweet);
                    $postId = mysqli_insert_id($connection); // Get the ID of the inserted post
                    
                    // Insert images
                    if (!empty($imageDataArray)) {
                        $sqlImage = "INSERT INTO tweet_images (tweet_id, image_data) VALUES (?, ?)";
                        $stmtImage = mysqli_prepare($connection, $sqlImage);
    
                        foreach ($imageDataArray as $imageData) {
                            mysqli_stmt_bind_param($stmtImage, "ib", $postId, $imageData);
                            mysqli_stmt_send_long_data($stmtImage, 1, $imageData);
                            mysqli_stmt_execute($stmtImage);
                        }
                        mysqli_stmt_close($stmtImage);
                    }
    
                    echo "<p class='success-message'>Your post has been published!</p>";
                } catch (mysqli_sql_exception $e) {
                    echo "<p class='error-message'>Error: " . $e->getMessage() . "</p>";
                }
                mysqli_stmt_close($stmtTweet);
            } else {
                echo "<p class='error-message'>Failed to prepare the statement.</p>";
            }
        }
    }
    mysqli_close($connection);
?>