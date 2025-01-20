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
        
    $userId = $_SESSION['user_id'];


    // Get the user's information
    $sql = "SELECT id, username, email, reg_date FROM users WHERE id = ?";
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
        echo "<button onclick='openModal(\"{$row['id']}\", \"{$row['email']}\")' style='color: white; background-color: blue; border: none; padding: 5px 10px;'>Delete</button>";
    } else {
        echo "<p>Unable to fetch your profile details.</p>";
    }

    // Close the statement and database connection
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>
