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
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype='multipart/form-data'>
        <label for="title">Title:</label><br>
        <input type="text" id="title" name="title" placeholder="Title here..." maxlength="255" required> <br>

        <label for="tweet">Post:</label><br>
        <textarea id="tweet" name="tweet" placeholder="Write something..." style="height:200px" required></textarea><br>
        
        <label for='image'>Upload Image (4 max):</label>
        <input type='file' name='image[]' accept='image/jpeg' multiple>
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
        $imageDataArray = [];

        // Get image data
        if (!empty($_FILES["image"]["tmp_name"][0])) {
            // Count the files
            if (count($_FILES["image"]["tmp_name"]) > 4) {
                echo "You can only upload up to 4 images.";
                exit;
            }
            foreach ($_FILES["image"]["tmp_name"] as $imageTmp) {
                if (!empty($imageTmp)) {
                    $imageDataArray[] = file_get_contents($imageTmp); // Read binary data
                }
            }
        }
 
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
    
                    echo "You have posted!";
                } catch (mysqli_sql_exception $e) {
                    echo "Uh oh bad request: " . $e->getMessage();
                }
                mysqli_stmt_close($stmtTweet);
            } else {
                echo "Failed to prepare the statement.";
            }
        }
    }
    mysqli_close($connection);
?>