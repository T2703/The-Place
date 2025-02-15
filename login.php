<?php
    include("database.php");
    
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles/login.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
</head>
<body>
    <div class="login-container">
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="login-form">
            <h2>Login</h2>

            <div class="input-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="input-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <button type="submit" name="submit">Login</button>

            <p>Don't have an account? <a href="index.php">Register here.</a></p>
        </form>
    </div>
</body>
</html>

<?php

    // Filter malicious scripts
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);

        // Set hashed password if both fields are filled out.
        if (!empty($email) && !empty($password)) {
            // Query the database to find the user.
            $sqlUser = "SELECT * FROM users WHERE email = ?";
            $stmt = mysqli_prepare($connection, $sqlUser);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            // Check if the user exists 
            if ($row = mysqli_fetch_assoc($result)) {
                if (password_verify($password, $row["password"])) {
                    //session_start();
                    $_SESSION["user_id"] = $row["id"];
                    header("Location: home.php"); 
                    exit();
                }
                else {
                    echo "<p class='error'>Incorrect password!</p>";
                }
            }
            else {
                echo "<p class='error'>No account found with that email</p>";
            }
        }
    }
    mysqli_close($connection);
?>