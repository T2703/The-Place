<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
?>

<header>
    <a href="home.php">Home</a>
    <?php 
        // Check if the user is logged in and if not have a login link to replace the profile link
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id']; 
            echo "<a href='profile.php?user_id=$userId'>Profile</a>";
        } else {
            echo "<a href='login.php'>Login</a>"; 
        }
    ?>
    <a href="tweet.php">Post</a>
    <hr>
</header>