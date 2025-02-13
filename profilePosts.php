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
    Profile <br>
    <input type="text" id="search" placeholder="Search posts or users..." style="padding: 5px; width: 300px;">
    <div id="searchResults" style="border: 1px solid #ccc; display: none; position: absolute; background: white; width: 300px;"></div>

    <script>
    document.getElementById("search").addEventListener("input", function () {
        let query = this.value.trim();
        let resultsDiv = document.getElementById("searchResults");
        let profileUserId = new URLSearchParams(window.location.search).get('user_id'); 

        if (query.length > 0) {
            fetch(`Handlers/searchHandlerProfilePosts.php?q=${encodeURIComponent(query)}&user_id=${profileUserId}`)
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
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to post";
        exit;
    }

    $userId = $_SESSION['user_id'];

    if (isset($_GET['user_id'])) {
        $profileUserId = intval($_GET['user_id']);
    }

    $search = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Get the tweets from the logged in user. 
    $sql = "SELECT tweets.id, tweets.title, tweets.content, tweets.created_at, users.username 
    FROM tweets 
    JOIN users ON tweets.user_id = users.id
    WHERE tweets.user_id = ?
    ORDER BY tweets.created_at DESC";
    
    
    // Needed for filtering the data (i is for injection we to prevent that)
    $stmt = mysqli_prepare($connection, $sql);

    if (!empty($search)) {
        $searchParam = '%' . $search . '%';
        mysqli_stmt_bind_param($stmt, "isss", $profileUserId, $searchParam, $searchParam, $searchParam);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $profileUserId);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if there is any tweets
    if (mysqli_num_rows($result) > 0) {
        echo "Your posts";

        // Fetching each tweet from the database. 
        while ($row = mysqli_fetch_assoc($result)) {
            // Tweet information 
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
            echo "<p><strong>{$row['username']}</strong></p>";
            echo "<p><strong>Title:</strong> {$row['title']}</p>";
            echo "<p>{$row['content']}</p>";
            echo "<p><em>Posted on {$row['created_at']}</em></p>";
            echo "</div>";

            // Update button 
            echo "<form method='get' action='updateTweet.php' style='margin-top: 10px;'>";
            echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>"; // Pass the tweet ID into the script
            echo "<button type='submit' name='update' style='color: white; background-color: green; border: none; padding: 5px 10px; cursor: pointer;'>Update</button>";
            echo "</form>";

            // Delete button 
            echo "<form method='post' action='Handlers/deleteTweetHandler.php' style='margin-top: 10px;'>";
            echo "<input type='hidden' name='tweet_id' value='{$row['id']}'>"; // Pass the tweet ID into the script
            echo "<button type='submit' name='delete' style='color: white; background-color: red; border: none; padding: 5px 10px; cursor: pointer;'>Delete</button>";
            echo "</form>";
        }
    }
    else {
        echo "<p>You haven't posted anything yet.</p>";
    }

    // Close the database connection
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>