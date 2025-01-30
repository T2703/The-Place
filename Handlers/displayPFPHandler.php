<?php
    include("../database.php");
    
    if (isset($_GET['user_id'])) {
        $userId = intval($_GET['user_id']);
    
        $sql = "SELECT pfp FROM users WHERE id = ?";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    
        if ($row = mysqli_fetch_assoc($result)) {
            header("Content-Type: image/png");
            echo $row['pfp'];
        } else {
            echo "No image found.";
        }
    
        mysqli_stmt_close($stmt);
        mysqli_close($connection);
    }
?>
