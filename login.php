<?php
    include("database.php");
    session_start();
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
        <label>email:</label><br>
        <input type="email" name="email" required> <br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br>

        <input type="submit" name="submit" value="login"><br> 
    </form>
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
                    echo "Incorrect password!";
                }
            }
            else {
                echo "No account found with that email";
            }

        }
    }
    mysqli_close($connection);
?>