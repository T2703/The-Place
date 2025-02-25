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
    <title>Home</title>
    <link rel="stylesheet" href="styles/home.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
</head>
<body>
    <div class="search-container">
        <input type="text" id="search" placeholder="Search posts or users...">
        <div id="searchResults" class="search-results"></div>
    </div>

    <script>
    document.getElementById("search").addEventListener("input", function () {
        let query = this.value.trim();
        let resultsDiv = document.getElementById("searchResults");

        if (query.length > 0) {
            fetch("Handlers/searchHandler.php?q=" + encodeURIComponent(query))
                .then(response => response.json())
                
                .then(data => {
                    resultsDiv.innerHTML = "";
                    resultsDiv.style.display = "block"; 

                    if (data.length === 0) {
                        resultsDiv.innerHTML = "<p>No results found</p>";
                    } else {
                        data.forEach(item => {
                            let div = document.createElement("div");
                            div.style.padding = "10px";
                            div.style.cursor = "pointer";
                            div.style.borderBottom = "1px solid #ccc";

                            if (item.type === "post") {
                                div.innerHTML = `<strong>Post:</strong> ${item.title}`;
                                div.onclick = () => window.location.href = `comment.php?tweet_id=${item.id}`;
                            } else if (item.type === "user") {
                                div.innerHTML = `<strong>User:</strong> ${item.title}`;
                                div.onclick = () => window.location.href = `profile.php?user_id=${item.id}`;
                            }

                            resultsDiv.appendChild(div);
                        });
                    }
                })
                .catch(error => console.error("Error fetching search results:", error));
        } else {
            resultsDiv.style.display = "none";
        }
    });

    // Handle Enter key to go to search results page
    document.getElementById("search").addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault(); // Prevent form submission (if inside a form)
            let query = this.value.trim();
            if (query.length > 0) {
                window.location.href = "searchResults.php?q=" + encodeURIComponent(query);
            }
        }
    });
</script>
</body>
</html>

<?php
    // Check if the user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to like a post.";
        exit;
    }

    $userId = $_SESSION['user_id'];

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Get the tweets from the specified user. 
    $sql = "SELECT 
            tweets.id, 
            tweets.title, 
            tweets.content, 
            tweets.created_at, 
            users.id as user_id,
            users.username,
            users.pfp,
            (SELECT COUNT(*) FROM tweet_likes WHERE tweet_likes.tweet_id = tweets.id) AS like_count,
            (SELECT COUNT(*) FROM tweet_dislikes WHERE tweet_dislikes.tweet_id = tweets.id) AS dislike_count,
            (SELECT COUNT(*) FROM comments WHERE comments.tweet_id = tweets.id) AS comments_count
            FROM tweets 
            JOIN users ON tweets.user_id = users.id
            WHERE
                users.id NOT IN (
                    SELECT blocked_id FROM blocks WHERE blocker_id = ?
                ) 
                AND users.id NOT IN (
                    SELECT blocker_id FROM blocks WHERE blocked_id = ?
                )
            ";   
                    
    
    // Append search condition if a search query is provided
    // I don't want to remove this (it's useless yes but let's not break anything)
    if (!empty($search)) {
        $sql .= " AND tweets.title LIKE ?";
    }

    $sql .= " ORDER BY (tweets.dislikes - tweets.likes) ASC";

    $stmt = mysqli_prepare($connection, $sql);
    
    // Bind the search parameter if applicable
    if (!empty($search)) {
        $searchParam = '%' . $search . '%';
        mysqli_stmt_bind_param($stmt, "iis", $userId, $userId, $searchParam);
    }
    else {
        mysqli_stmt_bind_param($stmt, "ii", $userId, $userId);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Fetching each tweet from the database. 
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<div class='post'>";
        echo "<div style='display: flex; align-items: center;'>";
        
        if (!empty($row['pfp'])) {
            echo "<img src='Handlers/displayPFPHandler.php?user_id={$row['user_id']}' class='pfp'>";
        }

        echo "<a href='profile.php?user_id={$row['user_id']}' class='username'>{$row['username']}</a>";
        echo "</div>";

        echo "<p class='title'>{$row['title']}</p>";
        echo "<p class='content'>{$row['content']}</p>";
        echo "<p class='meta'>Posted on " . date("F d, Y", strtotime($row['created_at'])) . "</p>";
        echo "<p class='meta'><strong>Likes:</strong> {$row['like_count']} | <strong>Dislikes:</strong> {$row['dislike_count']} | <strong>Comments:</strong> {$row['comments_count']}</p>";

        echo "<div class='button-group'>";
        echo "<form method='post' action='Handlers/likeHandler.php'>";
        echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
        echo "<button type='submit' name='like' class='like-btn'>Like</button>";
        echo "</form>";

        echo "<form method='post' action='Handlers/dislikeHandler.php'>";
        echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
        echo "<button type='submit' name='dislike' class='dislike-btn'>Dislike</button>";
        echo "</form>";

        echo "<form method='get' action='comment.php'>";
        echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
        echo "<button type='submit' name='comment' class='comment-btn'>Comment</button>";
        echo "</form>";
        
        echo "</div>"; // Close button group
        echo "</div>"; // Close post
    }

    // This is a place holder for testing out
    if (isset($_POST["logout"])) {
        session_destroy();
        header("Location: login.php");
    }

    mysqli_close($connection);
?>