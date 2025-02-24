<?php
    include("../database.php");

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        exit;
    }

    $loggedInUserId = $_SESSION['user_id'];

    if (isset($_GET['user_id'])) {
        $profileUserId = intval($_GET['user_id']);
    }

    header('Content-Type: application/json');
    
    $searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

    if (empty($searchQuery)) {
        echo json_encode([]);
        exit;
    }

    $searchQuery = "%" . $searchQuery . "%";

    // SQL query to fetch only posts
    $sql = "
            SELECT users.id, users.username, 'title' AS type
            FROM follows
            JOIN users ON follows.follower_id = users.id
            WHERE follows.following_id = ?
            ORDER BY users.username ASC
            LIMIT 10
    ";

    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "iss", $profileUserId, $searchQuery);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);


    $searchResults = [];

    // Fetch all items
    while ($row = mysqli_fetch_assoc($result)) {
        $searchResults[] = [
            'id' => $row['id'],
            'title' => htmlspecialchars_decode($row['title'], ENT_QUOTES),
            'type' => $row['type'],
            'username' => isset($row['username']) ? htmlspecialchars_decode($row['username'], ENT_QUOTES) : null,
        ];
    }

    echo json_encode($searchResults);


    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>