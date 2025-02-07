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
    <title>Document</title>
</head>
<body>
    What's on your mind today? <br>
    <form method="post" action="login.php">
        <input type="submit" name="logout" value="logout">
    </form>
    <input type="text" id="search" placeholder="Search posts or users..." style="padding: 5px; width: 300px;">
    <div id="searchResults" style="border: 1px solid #ccc; display: none; position: absolute; background: white; width: 300px;"></div>

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
                                div.innerHTML = `<strong>User:</strong> ${item.username}`;
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
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
        echo "<p><a href='profile.php?user_id={$row['user_id']}' style='color: blue; text-decoration: none;'>{$row['username']}</a></p>";
        echo "<p><strong>Title:</strong> {$row['title']}</p>";
        echo "<p>{$row['content']}</p>";
        echo "<p><em>Posted on {$row['created_at']}</em></p>";
        echo "<p><strong>Likes:</strong> {$row['like_count']} | <strong>Dislikes:</strong> {$row['dislike_count']}</p> <strong>Comments:</strong> {$row['comments_count']}</p>";
        echo "</div>";

        // Profile pic
        if (!empty($row['pfp'])) {
            echo '<img src="Handlers/displayPFPHandler.php?user_id=' . $row['user_id'] . '" width="150" height="150" style="border-radius: 100%;">';
        }
        else {
            echo "<p>No profile picture uploaded.</p>";
        }
        
        // Like button 
        echo "<form method='post' action='Handlers/likeHandler.php' style='margin-top: 10px;'>";
        echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
        echo "<button type='submit' name='like' style='color: white; background-color: green; border: none; padding: 5px 10px; cursor: pointer;'>Like</button>";
        echo "</form>";
    
        // Dislike button 
        echo "<form method='post' action='Handlers/dislikeHandler.php' style='margin-top: 10px;'>";
        echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
        echo "<button type='submit' name='dislike' style='color: white; background-color: red; border: none; padding: 5px 10px; cursor: pointer;'>Dislike</button>";
        echo "</form>";

        // Comment button 
        echo "<form method='get' action='comment.php' style='margin-top: 10px;'>";
        echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>";
        echo "<button type='submit' name='comment' style='color: white; background-color: blue; border: none; padding: 5px 10px; cursor: pointer;'>Comment</button>";
        echo "</form>";
    }

    // This is a place holder for testing out
    if (isset($_POST["logout"])) {
        session_destroy();
        header("Location: login.php");
    }

    mysqli_close($connection);
?>