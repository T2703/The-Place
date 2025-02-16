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
        (SELECT tweets.id, tweets.title, 'post' AS type 
        FROM tweets 
        INNER JOIN tweet_likes on tweets.id = tweet_likes.tweet_id
        WHERE tweet_likes.user_id = ?
        AND (tweets.title LIKE ? OR tweets.content LIKE ?)
        ORDER BY tweets.created_at DESC)
        LIMIT 10
    ";

    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "iss", $loggedInUserId, $searchQuery, $searchQuery);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);


    $searchResults = [];

    // Fetch all items
    while ($row = mysqli_fetch_assoc($result)) {
        $searchResults[] = [
            'id' => $row['id'],
            'title' => htmlspecialchars_decode($row['title'], ENT_QUOTES),
            'type' => $row['type'],
        ];
    }

    echo json_encode($searchResults);


    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>