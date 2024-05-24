<?php
session_start();
include 'db_config.php'; // Ensure this is the path to your database config file.

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
if ($post_id <= 0) {
    echo "Invalid post ID.";
    exit;
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
function styleHashtags($content) {
    return preg_replace('/#(\w+)/', '<span class="hashtag">#$1</span>', $content);
}

// Fetch the individual post
$postQuery = $conn->prepare("SELECT p.*, u.username, u.profile_picture FROM posts p JOIN users u ON p.user_id = u.user_id WHERE p.post_id = ?");
$postQuery->bind_param("i", $post_id);
$postQuery->execute();
$postResult = $postQuery->get_result();
$post = $postResult->fetch_assoc();

if (!$post) {
    echo "Post not found.";
    exit;
}

$replies = fetchNestedReplies($post_id); // Assuming fetchNestedReplies function is defined similarly to your snippet.

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['content']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

	<link href="Aureus.css" rel="stylesheet">
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
<div class="container py-5">
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex align-items-start">
                <!-- Profile Picture -->
                <img src="<?= htmlspecialchars($post['profile_picture']) ?: 'default.png'; ?>" alt="Profile Picture" class="img-fluid user-profile-picture me-3" style="max-width: 100px; height: auto;">
                
                <!-- Post Content -->
                <div>
                    <p class="text-muted">Post by <?= htmlspecialchars($post['post_username']); ?> on <?= htmlspecialchars($post['created_at']); ?></p>
                    <p><?= styleHashtags(htmlspecialchars($post['content'])); ?></p>
                    <?php if (!empty($post['image_path'])): ?>
                        <img src="<?= htmlspecialchars($post['image_path']); ?>" class="img-fluid" style="max-width: 200px; height: auto;">
                    <?php endif; ?>

                    <!-- Like and Reply Section -->
                    <div class="interaction-buttons d-flex gap-2">
                        <?php
                        $likeCheckStmt = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND post_id = ?");
                        $likeCheckStmt->bind_param("ii", $_SESSION['user_id'], $post['post_id']);
                        $likeCheckStmt->execute();
                        $likeResult = $likeCheckStmt->get_result();
                        $userLikedPost = $likeResult->num_rows > 0;

                        $likeCountStmt = $conn->prepare("SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?");
                        $likeCountStmt->bind_param("i", $post['post_id']);
                        $likeCountStmt->execute();
                        $likeCountResult = $likeCountStmt->get_result()->fetch_assoc();
                        ?>
                        <button class="like-button btn btn-sm btn-outline-primary" data-post-id="<?= $post['post_id']; ?>">
                            <?= $userLikedPost ? 'Unlike' : 'Like'; ?>
                        </button>
                        <button class="reply-button btn btn-sm btn-outline-secondary" data-post-id="<?= $post['post_id']; ?>" onclick="toggleReplyForm('reply-form-<?= $post['post_id']; ?>')">Reply</button>
                        <span class="like-count">
                            <?= $likeCountResult['like_count']; ?> Likes
                        </span>
                    </div>

                    <!-- Reply Form (Initially Hidden) -->
                    <form action="reply_submission.php" method="post" class="reply-form mt-2" id="reply-form-<?= $post['post_id']; ?>" style="display: none;">
                        <input type="hidden" name="post_id" value="<?= $post['post_id']; ?>">
                        <textarea name="reply_content" class="form-control" placeholder="Write a reply..."></textarea>
                        <button type="submit" class="btn btn-sm btn-primary mt-2">Post Reply</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="replies mt-3">
    <?php function displayReplies($replies, $parent_id = null, $level = 0) { ?>
    <?php foreach ($replies as $reply): ?>
        <div class="card mb-2" style="margin-left: <?= $level * 20 ?>px;">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <img src="<?= htmlspecialchars($reply['reply_profile_picture']) ?: 'default.png'; ?>" alt="Profile Picture" class="img-fluid me-3" style="width: 50px; height: auto; border-radius: 50%;">
                    <div>
                        <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($reply['reply_username']); ?></h6>
                        <p class="card-text"><?= htmlspecialchars($reply['content']); ?></p>
                        <div class="interaction-buttons">
                            <button class="btn btn-sm btn-outline-primary">Like</button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleReplyForm('reply-form-<?= $reply['reply_id']; ?>')">Reply</button>
                        </div>
                        <!-- Reply Form (Initially Hidden) -->
                        <form action="reply_submission.php" method="post" class="reply-form mt-2" id="reply-form-<?= $reply['reply_id']; ?>" style="display: none;">
                            <input type="hidden" name="post_id" value="<?= $reply['post_id']; ?>">
                            <input type="hidden" name="parent_reply_id" value="<?= $reply['reply_id']; ?>">
                            <textarea name="reply_content" class="form-control" placeholder="Write a reply..."></textarea>
                            <button type="submit" class="btn btn-sm btn-primary mt-2">Post Reply</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php if (!empty($reply['nested_replies'])): ?>
                <?php displayReplies($reply['nested_replies'], $reply['reply_id'], $level + 1); // Recursive call for nested replies ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php } ?>

    <?php displayReplies($replies); // Initial call to display replies ?>
</div>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Handle like button clicks
        $('.like-button').click(function() {
            var postId = $(this).data('post-id');
            var button = $(this);

            $.ajax({
                url: 'like_toggle.php',
                type: 'POST',
                data: {post_id: postId},
                success: function(response) {
                    var likeCount = JSON.parse(response).likeCount;
                    $('#like-count-' + postId).text(likeCount + ' Likes');
                    
                    // Change button appearance based on like status
                    if(button.text().trim() === 'Like') {
                        button.text('Unlike').removeClass('btn-outline-primary').addClass('btn-primary');
                    } else {
                        button.text('Like').removeClass('btn-primary').addClass('btn-outline-primary');
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error occurred: " + error);
                }
            });
        });
    });

    // Handle reply button clicks
    document.addEventListener('DOMContentLoaded', (event) => {
        document.querySelectorAll('.reply-button').forEach(button => {
            button.addEventListener('click', function() {
                var postId = this.getAttribute('data-post-id');
                var replyForm = document.getElementById('reply-form-' + postId);
                replyForm.style.display = (replyForm.style.display === 'none' ? 'block' : 'none');
            });
        });
    });
function toggleReplyForm(formId) {
    // Hide all reply forms
    document.querySelectorAll('.reply-form').forEach(form => {
        if (form.id !== formId) {
            form.style.display = 'none';
        }
    });

    // Toggle the display of the target reply form
    var form = document.getElementById(formId);
    form.style.display = (form.style.display === 'none' ? 'block' : 'none');
    if (form.style.display === 'block') {
        form.querySelector('textarea').focus(); // Automatically focus the textarea
    }
}

</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>