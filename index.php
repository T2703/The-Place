<?php
    include("database.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="<?php htmlspecialchars($_SERVER["PHP_SELF"])?>" method="post">
        <h2>Login</h2>
        <label>Username:</label><br>
        <input type="text" name="username" required> <br>
        
        <label>email:</label><br>
        <input type="email" name="email" required> <br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br>

        <input type="submit" name="submit" value="register"><br> 
    </form>
</body>
</html>

<?php

    // Filter malicious scripts
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = filter_input(INPUT_POST, "username", FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST, "password", FILTER_SANITIZE_SPECIAL_CHARS);

        // Set hashed password if both fields are filled out.
        if (!empty($username) && !empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sqlUser = "INSERT INTO users (username, email, password)
                        VALUES ('$username', '$email', '$hash')";
            try {
                mysqli_query($connection, $sqlUser);
                header("Location: login.php"); 
            }
            catch (mysqli_sql_exception) {
                echo "That username or email is taken!";
            }
        }
    }
    mysqli_close($connection);
?>