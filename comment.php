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
    <title>Comment on Post</title>
    <link rel="stylesheet" href="styles/comment.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
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

        // Not good naming but this is for the edit/update comment
        function openModal2(commentId, commentContent, tweetId) {
            // Show the modal
            document.getElementById("commentEditModal").style.display = "block";

            // Set the tweet_id in the hidden input field
            document.getElementById("comment_id").value = commentId;

            // Set the comment content in the hidden input field
            document.getElementById("edit_comment_content").value = commentContent;

            // Set the tweet_id in the hidden input field
            document.getElementById("edit_tweet_id").value = tweetId;
        }

        function closeModal2() {
            // Hide the modal
            document.getElementById("commentEditModal").style.display = "none";
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
    <div id="commentEditModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal2()">&times;</span>
            <h3>Edit Your Comment</h3>
            <form method="post" action="commentEditHandler.php">
                <input type="hidden" name="comment_id" id="comment_id">
                <input type="hidden" name="tweet_id" id="edit_tweet_id">
                <textarea name="comment_content" id="edit_comment_content" rows="4" style="width: 100%; padding: 10px;" placeholder="Write your comment here..." required></textarea>
                <br>
                <button type="submit" name="submit_edit" style="margin-top: 10px; background-color: green; color: white; padding: 10px 20px; border: none;">Edit comment</button>
            </form>
        </div>
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

    // Get the tweet ID from the form
    $tweetId = filter_input(INPUT_GET, 'tweet_id', FILTER_SANITIZE_NUMBER_INT);

    // Exit if no id.
    if (empty($tweetId)) {
        echo "Invalid tweet ID.";
        exit;
    }

    // Get the tweets likes from the logged in user. 
    $sql = "SELECT 
        tweets.id AS tweet_id,
        tweets.title AS tweet_title,
        tweets.content AS tweet_content,
        tweets.created_at AS tweet_created_at,
        tweets.user_id AS tweet_owner_id,
        users.username AS tweet_author,
        comments.id AS comment_id,
        comments.content AS comment_content,
        comments.created_at AS comment_created_at,
        comments.user_id AS comment_owner_id 
    FROM tweets
    LEFT JOIN comments ON tweets.id = comments.tweet_id
    JOIN users ON tweets.user_id = users.id
    WHERE tweets.id = ? AND (comments.parent_comment_id IS NULL OR comments.parent_comment_id = 0)
    ORDER BY comments.created_at DESC";
    
    // Needed for filtering the data (i is for injection we to prevent that)
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $tweetId);

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
                    echo "<div class='container'>";
                    echo "<div class='post'>";
                    echo "<p><strong>Post by: {$row['tweet_author']}</strong></p>";
                    echo "<p><strong>Title:</strong> {$row['tweet_title']}</p>";
                    echo "<p>{$row['tweet_content']}</p>";
                    echo "<p><em>Posted on {$row['tweet_created_at']}</em></p>";

                    echo "<div class='buttons'>";
                    // Show delete button for post owner
                    if ($userId == $row['tweet_owner_id']) {
                        echo "<form method='post' action='Handlers/deleteTweetHandler.php' style='display:inline;'>";
                        echo "<input type='hidden' name='id' value='{$row['tweet_id']}'>";
                        echo "<button type='submit' class='btn-red'>Delete Post</button>";
                        echo "</form>";
                    }

                    // Comment button 
                    echo "<button onclick='openModal({$row['tweet_id']})' class='btn-blue'>Comment</button>";
                    echo "</div>";  // Close buttons div
                    echo "</div>";  // Close post div
                    $postShown = true; 
                }

                // Display comments, if any exist
                if (!empty($row['comment_content'])) {
                    echo "<div class='comment'>";
                    echo "<p><strong>Comments:</strong></p>";
                    echo "<p>{$row['comment_content']}</p>";
                    echo "<p><em>Commented on {$row['comment_created_at']}</em></p>";

                    // Display the delete button if it's the owner of the tweet or the owner of the comment
                    if ($userId == $row['comment_owner_id'] || $userId == $row['tweet_owner_id']) {
                        echo "<form method='post' action='deleteCommentHandler.php' style='display:inline;'>";
                        echo "<input type='hidden' name='comment_id' value='{$row['comment_id']}'>";
                        echo "<button type='submit' class='btn-red'>Delete Comment</button>";
                        echo "</form>";
                    }

                    // Update only for owners of the comments
                    if ($userId == $row['comment_owner_id']) {
                        $escapedCommentContent = htmlspecialchars($row['comment_content'], ENT_QUOTES, 'UTF-8');
                        echo "<button onclick='openModal2({$row['comment_id']}, \"" . addslashes($escapedCommentContent) . "\", {$row['tweet_id']})' style='color: white; background-color: orange; border: none; padding: 5px 10px;'>Edit</button>";
                    }

                    // Reply button
                    echo "<form method='get' action='replies.php' style='display:inline;'>";
                    echo "<input type='hidden' name='comment_id' value='{$row['comment_id']}'>";
                    echo "<button type='submit' class='btn-green'>Reply</button>";
                    echo "</form>";
                                        // Like button 
                                        echo "<form method='post' action='Handlers/commentLikeHandler.php' style='margin-top: 10px;'>";
                                        echo "<input type='hidden' name='commenet_like_id' value='{$row['comment_id']}'>";
                                        echo "<button type='submit' name='like' style='color: white; background-color: green; border: none; padding: 5px 10px; cursor: pointer;'>Like</button>";
                                        echo "</form>";
                    
                                        // Dislike button 
                                        echo "<form method='post' action='Handlers/commentDislikeHandler.php' style='margin-top: 10px;'>";
                                        echo "<input type='hidden' name='comment_dislike_id' value='{$row['comment_id']}'>";
                                        echo "<button type='submit' name='dislike' style='color: white; background-color: red; border: none; padding: 5px 10px; cursor: pointer;'>Dislike</button>";
                                        echo "</form>";
                    echo "</div>"; // Close buttons div
                    echo "</div>"; // Close comment div

                    // Like button 
                    echo "<form method='post' action='Handlers/commentLikeHandler.php' style='margin-top: 10px;'>";
                    echo "<input type='hidden' name='commenet_like_id' value='{$row['comment_id']}'>";
                    echo "<button type='submit' name='like' style='color: white; background-color: green; border: none; padding: 5px 10px; cursor: pointer;'>Like</button>";
                    echo "</form>";

                    // Dislike button 
                    echo "<form method='post' action='Handlers/commentDislikeHandler.php' style='margin-top: 10px;'>";
                    echo "<input type='hidden' name='comment_dislike_id' value='{$row['comment_id']}'>";
                    echo "<button type='submit' name='dislike' style='color: white; background-color: red; border: none; padding: 5px 10px; cursor: pointer;'>Dislike</button>";
                    echo "</form>";
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
    

    // Close the database connection
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>