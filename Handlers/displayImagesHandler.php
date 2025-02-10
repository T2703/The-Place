<?php
    include("../database.php");
    
    if (isset($_GET['tweet_id'])) {
        $tweetId = intval($_GET['tweet_id']);

        $sql = "SELECT image_data FROM tweet_images WHERE tweet_id = ?";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, "i", $tweetId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            while ($row = mysqli_fetch_assoc($result)) {
                header("Content-Type: image/png");
                echo $row['image_data'];
            }
            echo "lol";
        } else {
            echo "No image found.";
        }
    
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
    }
?>