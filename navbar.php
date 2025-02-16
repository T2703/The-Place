<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
?>

<header class="navbar">
<link rel="stylesheet" href="styles/navbar.css"> 
<link rel="preconnect" href="https://fonts.googleapis.com">
    <a href="home.php">Home</a>
    <a href="interests.php">Interests</a>
    <a href="tweet.php">Post</a>
    <?php 
        // Check if the user is logged in and if not have a login link to replace the profile link
        if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
            $userId = $_SESSION['user_id']; 
            echo "<a href='profile.php?user_id=$userId'>Profile</a>";

            // Logout form
            echo '<form method="post" action="navbar.php" style="display: inline;">
                    <button type="submit" name="logout" style="background: none; border: none; cursor: pointer; font-size: 16px;">Logout</button>
                    </form>';
        } else {
            echo "<a href='login.php'>Login</a>"; 
        }
    ?>
    <hr>
</header>

<?php
    if (isset($_POST["logout"])) {
        session_destroy();
        header("Location: login.php");
        exit;
    }
?>