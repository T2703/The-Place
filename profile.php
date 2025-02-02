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
    <title>Profile</title>
    <style>
        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4); /* Black background with opacity */
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 10px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover, .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        button {
            cursor: pointer;
        }
    </style>
        <script>
        // JavaScript to handle modal functionality (for delete account)
        function openModal(userId, email, password) {
            // Show the modal
            document.getElementById("deleteModal").style.display = "block";

            // Set the user stuff in the hidden input field
            document.getElementById("user_id").value = userId;
            document.getElementById("email").value = email;
            document.getElementById("password").value = password;
        }

        function closeModal() {
            // Hide the modal
            document.getElementById("deleteModal").style.display = "none";
        }
    </script>
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Enter your password to delete account</h3>
            <form method="post" action="Handlers/profileDeleteHandler.php">
                <input type="hidden" name="user_id" id="user_id">
                <input type="hidden" name="email" id="email">
                <input type="password" name="password" id="password" required><br>
                <button type="submit" name="delete" style="margin-top: 10px; background-color: green; color: white; padding: 10px 20px; border: none;">Delete</button>
            </form>
        </div>
    </div>
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
    } 
        
    $loggedInUserId = $_SESSION['user_id'];

    // Get the user id and sanitize.
    if (isset($_GET['user_id'])) {
        $userId = intval($_GET['user_id']);

        // Get the user's information
        $sql = "
        SELECT 
            u.id, 
            u.username, 
            u.email, 
            u.pfp,
            u.reg_date,
            (SELECT COUNT(*) FROM tweets WHERE tweets.user_id = u.id) AS post_count,
            (SELECT COUNT(*) FROM tweet_likes WHERE tweet_likes.user_id = u.id) AS liked_post_count,
            (SELECT COUNT(*) FROM follows WHERE follows.following_id = u.id) AS follower_count,
            (SELECT COUNT(*) FROM follows WHERE follows.follower_id = u.id) AS following_count
        FROM 
            users u 
        WHERE 
            u.id = ?";
            
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Follow operations & code
        $sqlCheckFollow = "SELECT * FROM follows WHERE follower_id = ? AND following_id = ?";
        $followStmt = mysqli_prepare($connection, $sqlCheckFollow);
        mysqli_stmt_bind_param($followStmt, "ii", $loggedInUserId, $userId);
        mysqli_stmt_execute($followStmt);
        $followResult = mysqli_stmt_get_result($followStmt);
        $isFollowing = mysqli_num_rows($followResult) > 0;
        mysqli_stmt_close($followStmt);

        // Block operations & code
        $sqlCheckBlock = "SELECT * FROM blocks WHERE blocker_id = ? AND blocked_id = ?";
        $blockStmt = mysqli_prepare($connection, $sqlCheckBlock);
        mysqli_stmt_bind_param($blockStmt, "ii", $loggedInUserId, $userId);
        mysqli_stmt_execute($blockStmt);
        $blockResult = mysqli_stmt_get_result($blockStmt);
        $isBlocked = mysqli_num_rows($blockResult) > 0;
        mysqli_stmt_close($blockStmt);

        // First checked if they are blocked
        if ($isBlocked) {
            echo "This user is blocked.";
            exit;
        }

        // Check if the user exists and fetch the data
        if ($row = mysqli_fetch_assoc($result)) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 20px;'>";
            echo "<p><strong>Username:</strong> {$row['username']}</p>";
            echo "<p><strong>Email:</strong> {$row['email']}</p>";
            echo "<p><strong>Posts:</strong> <a href='profilePosts.php' style='color: blue; text-decoration: none;'>{$row['post_count']}</a></p>";
            echo "<p><strong>Likes:</strong> <a href='viewlikes.php' style='color: blue; text-decoration: none;'>{$row['liked_post_count']}</a></p>";
            echo "<p><strong>Member Since:</strong> " . date("F d, Y", strtotime($row['reg_date'])) . "</p>";
            echo "</div>";

            // Profile pic
            if (!empty($row['pfp'])) {
                echo '<img src="Handlers/displayPFPHandler.php?user_id=' . $row['id'] . '" width="150" height="150" style="border-radius: 100%;">';
            }
            else {
                echo "<p>No profile picture uploaded.</p>";
            }

            echo "<p>Followers: {$row['follower_count']}</p>";
            echo "<p>Following: {$row['following_count']}</p>";

            // Show buttons if it's their own account
            if ($loggedInUserId == $row['id']) {
                // Delete
                echo "<button onclick='openModal(\"{$row['id']}\", \"{$row['email']}\")' style='color: white; background-color: blue; border: none; padding: 5px 10px;'>Delete</button>";

                // Update 
                echo "<form method='get' action='profileUpdate.php' style='margin-top: 10px;'>";
                echo "<button type='submit' style='color: white; background-color: green; border: none; padding: 5px 10px; cursor: pointer;'>Update</button>";
                echo "</form>";

                echo "<a href='viewBlocks.php'>Banworld</a>"; 
            }
            // Show the follow button if not.
            else {
                // Display the appropriate button
                if ($isFollowing) {
                    // Unfollow
                    echo "<form method='post' action='Handlers/followHandler.php'>";
                    echo "<input type='hidden' name='following_id' value='{$userId}'>";
                    echo "<button type='submit' name='unfollow' style='background-color: red; color: white;'>Unfollow</button>";
                    echo "</form>";
                } 
                else {
                    // Follow
                    echo "<form method='post' action='Handlers/followHandler.php'>";
                    echo "<input type='hidden' name='following_id' value='{$userId}'>";
                    echo "<button type='submit' name='follow' style='background-color: green; color: white;'>Follow</button>";
                    echo "</form>";

                    // Block
                    echo "<form method='post' action='Handlers/blockHandler.php'>";
                    echo "<input type='hidden' name='block_id' value='{$userId}'>";
                    echo "<button type='submit' name='block' style='background-color: green; color: white;'>block</button>";
                    echo "</form>";
                }
            }
            
        } 
        else {
            echo "<p>Unable to fetch your profile details.</p>";
        }
    } 
    else {
        echo "Can't find user.";
        exit;
    }

    // Close the statement and database connection
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>
