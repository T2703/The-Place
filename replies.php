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
        function openModal(tweetId, commentId) {
            // Show the modal
            document.getElementById("replyModal").style.display = "block";

            // Set the tweet_id and comment_id in the hidden input field
            document.getElementById("tweet_id").value = tweetId;
            document.getElementById("comment_id").value = commentId;
        }

        function closeModal() {
            // Hide the modal
            document.getElementById("commentModal").style.display = "none";
        }

        // Not good naming but this is for the edit/update comment
        function openModal2(replyId, replyContent, tweetId) {
            // Show the modal
            document.getElementById("replyEditModal").style.display = "block";

            // Set the tweet_id in the hidden input field
            document.getElementById("edit_reply_id").value = replyId;

            // Set the comment content in the hidden input field
            document.getElementById("edit_reply_content").value = replyContent;

            // Set the tweet_id in the hidden input field
            document.getElementById("edit_tweet_id").value = tweetId;
        }

        function closeModal2() {
            // Hide the modal
            document.getElementById("replyEditModal").style.display = "none";
        }
    </script>
    
    <div id="replyModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Reply</h3>
            <form method="post" action="commentHandler.php">
                <input type="hidden" name="tweet_id" id="tweet_id">
                <input type="hidden" name="comment_id" id="comment_id">
                <textarea name="comment_content" rows="4" style="width: 100%; padding: 10px;" placeholder="Write your comment here..." required></textarea>
                <br>
                <button type="submit" name="submit" style="margin-top: 10px; background-color: green; color: white; padding: 10px 20px; border: none;">Submit Comment</button>
            </form>
        </div>
    </div>
    <div id="replyEditModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal2()">&times;</span>
            <h3>Edit Your Comment</h3>
            <form method="post" action="Handlers/replyEditHandler.php">
                <input type="hidden" name="comment_id" id="edit_reply_id">
                <input type="hidden" name="tweet_id" id="edit_tweet_id">
                <textarea name="comment_content" id="edit_reply_content" rows="4" style="width: 100%; padding: 10px;" placeholder="Write your comment here..." required></textarea>
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
        echo "You need to log in to view this page.";
        exit;
    }

    $userId = $_SESSION['user_id'];

    // Get the comment_id from the form submission
    $commentId = filter_input(INPUT_GET, 'comment_id', FILTER_SANITIZE_NUMBER_INT);

    if (empty($commentId)) {
        echo "Invalid comment ID.";
        exit;
    }

    // SQL query to get the original tweet, the selected comment, and its replies
    $sql = "
        SELECT 
            tweets.id AS tweet_id,
            tweets.title AS tweet_title,
            tweets.content AS tweet_content,
            tweets.created_at AS tweet_created_at,
            tweets.user_id AS tweet_owner_id,
            users.username AS tweet_author,
            parent.id AS parent_comment_id,
            parent.content AS parent_comment_content,
            parent.created_at AS parent_comment_created_at,
            parent.user_id AS parent_comment_owner_id,
            comment_users.username AS parent_comment_author,
            replies.id AS reply_id,
            replies.content AS reply_content,
            replies.created_at AS reply_created_at,
            replies.user_id AS reply_owner_id,
            reply_users.username AS reply_author
        FROM tweets
        JOIN comments AS parent ON tweets.id = parent.tweet_id
        LEFT JOIN comments AS replies ON parent.id = replies.parent_comment_id
        JOIN users ON tweets.user_id = users.id
        JOIN users AS comment_users ON parent.user_id = comment_users.id
        LEFT JOIN users AS reply_users ON replies.user_id = reply_users.id
        WHERE parent.id = ?
        ORDER BY replies.created_at ASC;
    ";

    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $commentId);

    try {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $tweetShown = false;
            $parentCommentShown = false;

            // Fetch each row
            while ($row = mysqli_fetch_assoc($result)) {
                // Show the tweet content only once
                if (!$tweetShown) {
                    echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;'>";
                    echo "<p><strong>Tweet by: {$row['tweet_author']}</strong></p>";
                    echo "<p><strong>Title:</strong> {$row['tweet_title']}</p>";
                    echo "<p>{$row['tweet_content']}</p>";
                    echo "<p><em>Posted on: {$row['tweet_created_at']}</em></p>";
                    echo "</div>";
                    $tweetShown = true;
                }

                // Show the selected parent comment
                if (!$parentCommentShown) {
                    // Reply button and form
                    echo "<form method='post' action='Handlers/replyHandler.php' style='margin-top: 10px;'>";
                    echo "<input type='hidden' name='tweet_id' value='{$row['tweet_id']}'>";
                    echo "<input type='hidden' name='parent_comment_id' value='{$row['parent_comment_id']}'>";
                    echo "<textarea name='comment_content' rows='4' style='width: 100%; padding: 10px;' placeholder='Write your reply here...' required></textarea>";
                    echo "<br>";
                    echo "<button type='submit' name='submit' style='margin-top: 10px; background-color: green; color: white; padding: 10px 20px; border: none;'>Submit Reply</button>";
                    echo "</form>";
                    
                    echo "<div style='border: 1px solid #ccc; padding: 10px; margin-top: 10px;'>";
                    echo "<p><strong>Comment by: {$row['parent_comment_author']}</strong></p>";
                    echo "<p>{$row['parent_comment_content']}</p>";
                    echo "<p><em>Commented on: {$row['parent_comment_created_at']}</em></p>";
                    echo "</div>";

                    $parentCommentShown = true;
                }

                // Show replies to the selected comment
                if (!empty($row['reply_content'])) {
                    echo "<div style='margin-left: 20px; margin-top: 10px; padding: 10px; border-top: 1px solid #ddd;'>";
                    echo "<p><strong>Reply by: {$row['reply_author']}</strong></p>";
                    echo "<p>{$row['reply_content']}</p>";
                    echo "<p><em>Replied on: {$row['reply_created_at']}</em></p>";

                    // Like button 
                    echo "<form method='post' action='Handlers/commentLikeHandler.php' style='margin-top: 10px;'>";
                    echo "<input type='hidden' name='commenet_like_id' value='{$row['reply_id']}'>";
                    echo "<button type='submit' name='like' style='color: white; background-color: green; border: none; padding: 5px 10px; cursor: pointer;'>Like</button>";
                    echo "</form>";

                    // Dislike button 
                    echo "<form method='post' action='Handlers/commentDislikeHandler.php' style='margin-top: 10px;'>";
                    echo "<input type='hidden' name='comment_dislike_id' value='{$row['reply_id']}'>";
                    echo "<button type='submit' name='dislike' style='color: white; background-color: red; border: none; padding: 5px 10px; cursor: pointer;'>Dislike</button>";
                    echo "</form>";

                    // Show delete button for replies if owned by the logged-in user
                    if ($userId == $row['reply_owner_id']) {
                        echo "<form method='post' action='Handlers/replyDeleteHandler.php' style='display:inline;'>";
                        echo "<input type='hidden' name='comment_id' value='{$row['reply_id']}'>";
                        echo "<input type='hidden' name='parent_comment_id2' value='{$row['parent_comment_id']}'>";
                        echo "<button type='submit' name='delete' style='color: white; background-color: red; border: none; padding: 5px 10px;'>Delete Reply</button>";
                        echo "</form>";

                        $escapedCommentContent = htmlspecialchars($row['reply_content'], ENT_QUOTES, 'UTF-8');
                        echo "<button onclick='openModal2({$row['reply_id']}, \"" . addslashes($escapedCommentContent) . "\", {$row['tweet_id']})' style='color: white; background-color: orange; border: none; padding: 5px 10px;'>Edit</button>";
                    }
                    echo "</div>";
                }
            }
        } else {
            echo "<p>No replies to this comment yet.</p>";
        }
    } catch (mysqli_sql_exception $e) {
        echo "An error occurred: " . $e->getMessage();
    }

    // Close the database connection
    mysqli_stmt_close($stmt);
    mysqli_close($connection);
?>