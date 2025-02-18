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
    <title>View Likes</title>
    <link rel="stylesheet" href="styles/home.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
</head>
<body>
    Your likes <br>

    Profile <br>
    <input type="text" id="search" placeholder="Search your liked posts..." style="padding: 5px; width: 300px;">
    <div id="searchResults" style="border: 1px solid #ccc; display: none; position: absolute; background: white; width: 300px;"></div>

    <script>
    document.getElementById("search").addEventListener("input", function () {
        let query = this.value.trim();
        let resultsDiv = document.getElementById("searchResults");
        let profileUserId = new URLSearchParams(window.location.search).get('user_id'); 

        if (query.length > 0) {
            fetch(`Handlers/searchHandlerLikes.php?q=${encodeURIComponent(query)}&user_id=${profileUserId}`)
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
        let profileUserId = new URLSearchParams(window.location.search).get('user_id'); 
        if (event.key === "Enter") {
            event.preventDefault(); // Prevent form submission (if inside a form)
            let query = this.value.trim();
            if (query.length > 0) {
                window.location.href = `searchResultsLikes.php?q=${encodeURIComponent(query)}&user_id=${profileUserId}`;
            }
        }
    });
</script>
</body>
</html>

<?php
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to post";
        exit;
    }
    else {
        $loggedinUserId = $_SESSION['user_id'];
    }

    if (isset($_GET['user_id'])) {
        $userId = intval($_GET['user_id']);
    }

    // Get the tweets likes from the logged in user. 
    $sql = "SELECT tweets.id, tweets.content, tweets.title, tweets.created_at, users.username, users.pfp, users.id as user_id,
            (SELECT COUNT(*) FROM tweet_likes WHERE tweet_likes.tweet_id = tweets.id) AS like_count,
            (SELECT COUNT(*) FROM tweet_dislikes WHERE tweet_dislikes.tweet_id = tweets.id) AS dislike_count,
            (SELECT COUNT(*) FROM comments WHERE comments.tweet_id = tweets.id) AS comments_count
            FROM tweet_likes
            JOIN tweets ON tweet_likes.tweet_id = tweets.id
            JOIN users ON tweets.user_id = users.id
            WHERE tweet_likes.user_id = ?
            ORDER BY tweet_likes.created_at DESC";
    
    // Needed for filtering the data (i is for injection we to prevent that)
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if there is any tweets
    if (mysqli_num_rows($result) > 0) {
        // Fetching each tweet from the database. 
        while ($row = mysqli_fetch_assoc($result)) {
            // Tweet information 
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
    }
    else {
        echo "<p>You haven't liked anything yet.</p>";
    }

    // Close the database connection
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>