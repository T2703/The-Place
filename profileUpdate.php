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
            <h3>Enter your password to change your password</h3>
            <form method="post" action="changePassword.php">
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
        echo "You need to log in to post";
        exit;
    }
    $userId = $_SESSION['user_id'];

    // Fetch the tweet data
    $sql = "SELECT id, username, email FROM users WHERE id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Private data fetching
    $privacySql = "SELECT privacy_type, is_private FROM user_privacy WHERE user_id = ?";
    $privacyStmt = mysqli_prepare($connection, $privacySql);
    mysqli_stmt_bind_param($privacyStmt, "i", $userId);
    mysqli_stmt_execute($privacyStmt);
    $privacyResult = mysqli_stmt_get_result($privacyStmt);

    $privacySettings = [];
    while ($privacyRow = mysqli_fetch_assoc($privacyResult)) {
        $privacySettings[$privacyRow['privacy_type']] = $privacyRow['is_private'];
    }

    // Default values if privacy settings don't exist yet
    $privacyLikes = $privacySettings['likes'] ?? 0;
    $privacyFollows = $privacySettings['follows'] ?? 0;
    $privacyFollowers = $privacySettings['followers'] ?? 0;
    
    // Show the profile update stuff
    if ($row = mysqli_fetch_assoc($result)) {
        // Profile info 
        echo "<h1>Update Profile</h1>";
        echo "<form method='post' action='Handlers/profileUpdateHandler.php'>";
        echo "<label>Username:</label>";
        echo "<input type='text' name='username' value='{$row['username']}' required><br>";
        echo "<label>Email:</label>";
        echo "<input type='email' name='email' value='{$row['email']}' required><br>";
        echo "<button type='submit' style='margin-top: 10px; background-color: blue; color: white; padding: 10px 20px; border: none;'>Save</button>";
        echo "</form>";

        // Change password
        echo "<button onclick='openModal(\"{$row['id']}\", \"{$row['email']}\")' style='color: white; background-color: blue; border: none; padding: 5px 10px;'>Change Password</button>";

        // Profile Upload
        echo "<form action='Handlers/uploadPFPHandler.php' method='post' enctype='multipart/form-data'>";
        echo "<label for='profile_pic'>Upload Profile Picture:</label>";
        echo "<input type='file' name='profile_pic' accept='image/jpeg' required>";
        echo "<button type='submit' name='upload'>Upload</button>";
        echo "</form>";

        // Private Boxes
        echo "<form action='Handlers/updatePrivacyHandler.php' method='post' enctype='multipart/form-data'>";
        echo "<h3>Privacy Settings:</h3>";

        $privacyTypes = ['likes' => 'Private Likes', 'follows' => 'Private Follows', 'followers' => 'Private Followers'];
       
        foreach ($privacyTypes as $type => $label) {
            $isChecked = !empty($privacySettings[$type]) && $privacySettings[$type] ? 'checked' : '';
            echo "<label>";
            echo "<input type='checkbox' name='privacy_$type' value='1' $isChecked> $label";
            echo "</label><br>";
        }

        echo "<button type='submit' name='upload'>Upload</button>";
        echo "</form>";

    }
    else {
        echo "Unable to fetch profile details.";
    }
    mysqli_stmt_close($privacyStmt);
    mysqli_close($connection);
?>