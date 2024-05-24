<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database connection settings
$servername = "";
$dbUsername = ""; // Database username
$dbPassword = ""; // Database password
$dbname = "";

// Create database connection
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user profile data along with profile picture
$userProfileQuery = $conn->query("SELECT username, email, profile_picture FROM users WHERE user_id = $user_id");
$userProfile = $userProfileQuery->fetch_assoc();

function styleHashtags($content) {
    return preg_replace('/#(\w+)/', '<span class="hashtag">#$1</span>', $content);
}

function fetchNestedReplies($post_id, $parent_reply_id = NULL) {
    global $conn;
    $replies = [];
    $sql = "SELECT r.*, u.username AS reply_username, u.profile_picture AS reply_profile_picture
            FROM replies r
            JOIN users u ON r.user_id = u.user_id 
            WHERE r.post_id = ? AND " . ($parent_reply_id ? "r.parent_reply_id = ?" : "r.parent_reply_id IS NULL") . "
            ORDER BY r.created_at ASC";
    $stmt = $conn->prepare($sql);

    if ($parent_reply_id) {
        $stmt->bind_param("ii", $post_id, $parent_reply_id);
    } else {
        $stmt->bind_param("i", $post_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    while ($reply = $result->fetch_assoc()) {
        $reply['nested_replies'] = fetchNestedReplies($post_id, $reply['reply_id']);
        $replies[] = $reply;
    }
    return $replies;
}

// Fetch posts, their images, and their replies from the database
$postsQuery = $conn->query("
    SELECT p.*, u.username as post_username, u.profile_picture as post_profile_picture 
    FROM posts p 
    JOIN users u ON p.user_id = u.user_id 
    ORDER BY p.created_at DESC
");

$posts = [];
while ($post = $postsQuery->fetch_assoc()) {
    $post['replies'] = fetchNestedReplies($post['post_id']);
    $posts[] = $post;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AurConnect - Home</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="Aureus.css" rel="stylesheet">
  <script>
    var loggedInUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
</script>
<style>
.hashtag {
    color: #19C61B; /* Example: blue color for hashtags */
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
}
.hashtag:hover {
    text-decoration: underline;
}
.post-link {
    text-decoration: none; /* Removes underline */
    color: inherit; /* Inherits the color from the parent element */
}

.post-link:hover, .post-link:focus {
    text-decoration: none; /* Optional: Removes underline on hover/focus */
    color: inherit; /* Optional: Change color on hover/focus if desired */
}

</style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark md-3">
    <div class="container-fluid">
            <a class="navbar-brand" href="#">AurConnect</a>
            <button class="navbar-toggler collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="navbar-collapse collapse" id="navbarNav" style="">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">Profile</a>
                    </li>
                  <li class="nav-item">
                        <a class="nav-link" href="messages.php">Messages</a>
                    </li>
                  	<li class="nav-item">
                        <a class="nav-link" href="friends.php">Friends</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4">
        <!-- User Profile Summary -->
        <!-- User Profile Summary -->
        <div class="user-profile-summary">
            <?php if (!empty($userProfile['profile_picture'])): ?>
                <img src="<?php echo htmlspecialchars($userProfile['profile_picture']); ?>" alt="User Profile Picture" class="img-fluid user-profile-picture-summary" style="max-width: 100px; height: auto;">
            <?php else: ?>
                <img src="default.png" alt="Default Profile Picture" class="img-fluid user-profile-picture-summary" >
            <?php endif; ?>
            <div class="user-info">
                <h2><?php echo htmlspecialchars($userProfile['username']); ?></h2>
            </div>
        </div>


        <!-- Post Creation Area -->
        <div class="post-creation mb-4">
    <form method="post" action="post_submission.php" enctype="multipart/form-data">
        <textarea class="form-control" name="post_content" placeholder="What's on your mind?"></textarea>
        <input type="file" class="form-control mt-2" name="post_image" accept="image/*">

        <button type="submit" class="btn btn-primary mt-2">Post</button>
    </form>
</div>


<!-- Display profile picture for each post and nested replies -->
<div class="user-feed">
    <h4>Recent Posts</h4>
    <?php foreach ($posts as $post): ?>
        <div class="card mb-3">
            <div class="card-body">
                <a href="https://aurconnect.com/post.php?post_id=<?php echo $post['post_id']; ?>" class="post-link">
                <div class="user-post" style="display: grid; grid-template-columns: auto 1fr; gap: 10px;">
    <div class="user-profile">
        <!-- User Profile Image -->
        <?php if (!empty($post['post_profile_picture'])): ?>
            <img src="<?php echo htmlspecialchars($post['post_profile_picture']); ?>">
        <?php else: ?>
            <img src="default.png" alt="Default Profile Picture" class="img-fluid user-profile-picture" style="max-width: 75px; height: auto;">
        <?php endif; ?>
    </div>

    <div class="post-content">
        <!-- Post Details -->
        <p class="card-text"><small class="text-muted">Post by <?php echo htmlspecialchars($post['post_username']); ?> on <?php echo $post['created_at']; ?></small></p>
        <p class="card-text"><?php echo styleHashtags(htmlspecialchars($post['content'])); ?></p>
        <img src="<?php echo ($post['image_path']); ?>" style="max-width: 200px; height: auto;">
        
        <!-- Like and Reply Section -->
        <?php
        // Check if the current user has liked this post
        $likeCheckQuery = "SELECT * FROM likes WHERE user_id = ? AND post_id = ?";
        $likeCheckStmt = $conn->prepare($likeCheckQuery);
        $likeCheckStmt->bind_param("ii", $_SESSION['user_id'], $post['post_id']);
        $likeCheckStmt->execute();
        $likeResult = $likeCheckStmt->get_result();
        $userLikedPost = $likeResult->num_rows > 0;

        // Get the total like count for the post
        $likeCountQuery = "SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?";
        $likeCountStmt = $conn->prepare($likeCountQuery);
        $likeCountStmt->bind_param("i", $post['post_id']);
        $likeCountStmt->execute();
        $likeCountResult = $likeCountStmt->get_result()->fetch_assoc();
        $likeCount = $likeCountResult['like_count'];
        ?>
        <div class="interaction-buttons" style="display: flex; gap: 10px;">
            <button class="like-button btn btn-sm btn-outline-primary" data-post-id="<?php echo $post['post_id']; ?>">
                <?php echo $userLikedPost ? 'Unlike' : 'Like'; ?>
            </button>
            <button class="reply-button btn btn-sm btn-outline-secondary" data-post-id="<?php echo $post['post_id']; ?>">
                Reply
            </button>
            <span class="like-count" id="like-count-<?php echo $post['post_id']; ?>">
                <?php echo $likeCount; ?> Likes
            </span>
        </div>

        <!-- Reply Form (Initially Hidden) -->
        <form action="reply_submission.php" method="post" class="reply-form" id="reply-form-<?php echo $post['post_id']; ?>" style="display: none;">
            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
            <textarea name="reply_content" class="form-control" placeholder="Write a reply..."></textarea>
            <button type="submit" class="btn btn-sm btn-primary mt-2">Post Reply</button>
        </form>
    </div>
</div>
              
<!-- Nested Replies -->
                <!-- Nested Replies -->
<?php if (!empty($post['replies'])): ?>
    <div class="replies">
        <h6 class="mt-3">Replies:</h6>
        <?php foreach ($post['replies'] as $reply): ?>
            <div class="user-reply" style="display: grid; grid-template-columns: auto 1fr; gap: 10px;">
                <div class="user-profile">
                    <!-- User Reply Profile Picture -->
                    <?php if (!empty($reply['reply_profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($reply['reply_profile_picture']); ?>" alt="User Profile Picture" class="img-fluid user-profile-picture" style="max-width: 75px; height: auto;">
                    <?php else: ?>
                        <img src="default.png" alt="Default Profile Picture" class="img-fluid user-profile-picture" style="max-width: 75px; height: auto;">
                    <?php endif; ?>
                </div>
                <div class="reply-content" style="margin-left: 10px;">
                    <!-- Reply Content and Details -->
                    <p class="card-text"><?php echo htmlspecialchars($reply['content']); ?></p>
                    <p class="card-text">
                        <small class="text-muted">
                            Reply from <?php echo htmlspecialchars($reply['reply_username']); ?>
                            on <?php echo $reply['created_at']; ?>
                        </small>
                    </p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

            </div>
        </div>
    <?php endforeach; ?>
</div>

    </div>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('.like-button').click(function() {
            var postId = $(this).data('post-id');
            var button = $(this);

            $.ajax({
                url: 'like_toggle.php',
                type: 'POST',
                data: {post_id: postId, user_id: loggedInUserId},
                success: function(response) {
                    var likeCount = JSON.parse(response).likeCount;
                    $('#like-count-' + postId).text(likeCount + ' Likes');
                    
                    // Optionally, change button appearance if liked
                    if(button.text() === 'Like') {
                        button.text('Unlike');
                    } else {
                        button.text('Like');
                    }
                }
            });
        });
    });
  document.addEventListener('DOMContentLoaded', (event) => {
        // Add click event listener to all reply buttons
        document.querySelectorAll('.reply-button').forEach(button => {
            button.addEventListener('click', function() {
                var postId = this.getAttribute('data-post-id');
                var replyForm = document.getElementById('reply-form-' + postId);
                replyForm.style.display = (replyForm.style.display === 'none' ? 'block' : 'none');
            });
        });
    });
</script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
