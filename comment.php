<?php
    include("database.php");
    include("navbar.php");
    session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comment on Post</title>
    <style>
        /* Modal styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4); /* Black background with opacity */
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 10px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover, .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
        button {
            cursor: pointer;
        }
    </style>
    <script>
        // JavaScript to handle modal functionality
        function openModal(tweetId) {
            // Show the modal
            document.getElementById("commentModal").style.display = "block";

            // Set the tweet_id in the hidden input field
            document.getElementById("tweet_id").value = tweetId;
        }

        function closeModal() {
            // Hide the modal
            document.getElementById("commentModal").style.display = "none";
        }
    </script>
    
    <div id="commentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Add Your Comment</h3>
            <form method="post" action="commentHandler.php">
                <input type="hidden" name="tweet_id" id="tweet_id">
                <textarea name="comment_content" rows="4" style="width: 100%; padding: 10px;" placeholder="Write your comment here..." required></textarea>
                <br>
                <button type="submit" name="submit" style="margin-top: 10px; background-color: green; color: white; padding: 10px 20px; border: none;">Submit Comment</button>
            </form>
    </div>
</div>
</head>
<body>

<?php
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        echo "You need to log in to post";
        exit;
    }

    $userId = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment'])) {
        // Get the tweet ID from the form
        $tweetId = filter_input(INPUT_POST, 'tweet_id', FILTER_SANITIZE_NUMBER_INT);

        // Get the tweets likes from the logged in user. 
        $sql = "SELECT 
            tweets.id AS tweet_id,
            tweets.title AS tweet_title,
            tweets.content AS tweet_content,
            tweets.created_at AS tweet_created_at,
            users.username AS tweet_author,
            comments.content AS comment_content,
            comments.created_at AS comment_created_at
        FROM tweets
        LEFT JOIN comments ON tweets.id = comments.tweet_id AND comments.user_id = ?
        JOIN users ON tweets.user_id = users.id
        WHERE tweets.id = ?
        ORDER BY comments.created_at DESC";
        
        // Needed for filtering the data (i is for injection we to prevent that)
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $userId, $tweetId);

        try {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            // Check if there is any tweets
            if (mysqli_num_rows($result) > 0) {
                $postShown = false;

                // Fetching each tweet from the database. 
                while ($row = mysqli_fetch_assoc($result)) {
                    // Tweet information 
                    if (!$postShown) {
                        echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
                        echo "<p><strong>Post by: {$row['tweet_author']}</strong></p>";
                        echo "<p><strong>Title:</strong> {$row['tweet_title']}</p>";
                        echo "<p>{$row['tweet_content']}</p>";
                        echo "<p><em>Posted on {$row['tweet_created_at']}</em></p>";
                        echo "</div>";

                        // Comment button 
                        echo "<button onclick='openModal({$row['tweet_id']})' style='color: white; background-color: blue; border: none; padding: 5px 10px;'>Comment</button>";

                        $postShown = true; 
                    }

                    // Display comments, if any exist
                    if (!empty($row['comment_content'])) {
                        echo "<div style='margin-top: 10px; padding: 10px; border-top: 1px solid #ddd;'>";
                        echo "<p><strong>Comments:</strong></p>";
                        echo "<p>{$row['comment_content']}</p>";
                        echo "<p><em>Commented on {$row['comment_created_at']}</em></p>";
                        echo "</div>";
                    }

                    // Inform that there are no comments
                    if ($postShown && mysqli_num_rows($result) == 0) {
                        echo "<p>No comments on this post yet.</p>";
                    }

                }
            }
            else {
                echo "<p>The post does not exist</p>";
            }
        }
        catch (mysqli_sql_exception) {
            echo "Uh oh bad request.";
        }

    }
    else {
        echo "<p>Invalid ID</p>";
    }
    

    // Close the database connection
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>