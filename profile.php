<?php
ob_start(); // Start output buffering at the very beginning
session_start();
include 'proof_creation.php';
require_once 'db_config.php';
require_once 'functions.php';

// Redirect if not logged in
ensureLoggedIn();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];


$profile_id = $_GET['id'] ?? $_SESSION['user_id']; // Get profile ID from URL or use logged-in user's ID
$user_id = $_SESSION['user_id']; // ID of the logged-in user
$is_own_profile = ($user_id == $profile_id);

// Fetch user data
$user_query = "SELECT username, profile_picture FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $profile_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$userData = $user_result->fetch_assoc();

// Fetch profile settings data
$settings_query = "SELECT * FROM profile_settings WHERE user_id = ?";
$settings_stmt = $conn->prepare($settings_query);
$settings_stmt->bind_param("i", $profile_id);
$settings_stmt->execute();
$settings_result = $settings_stmt->get_result();
$profileSettings = $settings_result->fetch_assoc();





if ($settings_result->num_rows == 0 && $is_own_profile) {
    $insert_query = "INSERT INTO profile_settings (user_id, background_color, navbar_color, btn_color, text_color, link_color, font, theme) VALUES (?, '#FFFFFF', '#000000', '#FFFFFF', '#000000', '#0000FF', 'Arial', 'default')";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("i", $user_id);
    $insert_stmt->execute();

    // Re-fetch settings after insertion
    $settings_stmt->execute();
    $settings_result = $settings_stmt->get_result();
    $profileSettings = $settings_result->fetch_assoc();
}


// Handle profile update requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_own_profile) {
    // Function to sanitize color input
    function sanitize_color($color) {
        if (preg_match('/^#[a-f0-9]{6}$/i', $color)) {
            return $color;
        } else {
            return null; // Or a default color value
        }
    }

    // Sanitize and validate data from POST request
    $background_color = isset($_POST['background_color']) ? sanitize_color($_POST['background_color']) : $profileSettings['background_color'];
    $text_color = isset($_POST['text_color']) ? sanitize_color($_POST['text_color']) : $profileSettings['text_color'];
    $link_color = isset($_POST['link_color']) ? sanitize_color($_POST['link_color']) : $profileSettings['link_color'];
    $navbar_color = isset($_POST['navbar_color']) ? sanitize_color($_POST['navbar_color']) : $profileSettings['navbar_color'];
    $btn_color = isset($_POST['btn_color']) ? sanitize_color($_POST['btn_color']) : $profileSettings['btn_color'];

    // Sanitize other inputs (e.g., font, theme)
    $font = htmlspecialchars($_POST['font'] ?? $profileSettings['font']);
    $theme = htmlspecialchars($_POST['theme'] ?? $profileSettings['theme']);

    // Check if any color values are null (invalid)
    if ($background_color === null || $text_color === null || $link_color === null || $navbar_color === null || $btn_color === null) {
        // Handle invalid input (e.g., show an error message)
        // Do not proceed with updating the database
    } else {
        // Update profile settings
        $update_query = "UPDATE profile_settings SET background_color = ?, text_color = ?, link_color = ?, font = ?, theme = ?, navbar_color = ?, btn_color = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssssssi", $background_color, $text_color, $link_color, $font, $theme, $navbar_color, $btn_color, $user_id);
        $update_stmt->execute();

        // Refresh the page to show updated settings
        header("Location: profile.php?id=".$profile_id);
        exit;
    }
}


$is_friend = false;

if (!$is_own_profile) {
    // Check if the users are friends
    $check_friendship_stmt = $conn->prepare("SELECT * FROM friends WHERE (user_id = ? AND friend_id = ? AND status = 'accepted') OR (user_id = ? AND friend_id = ? AND status = 'accepted')");
    $check_friendship_stmt->bind_param("iiii", $user_id, $profile_id, $profile_id, $user_id);
    $check_friendship_stmt->execute();
    $friendship_result = $check_friendship_stmt->get_result();

    if ($friendship_result->num_rows > 0) {
        $is_friend = true;
    }
}

// Ensure this part comes before the proxy check to fetch NFTs
$wallet_query = "SELECT blockchain_wallet_address FROM users WHERE user_id = ?";
$wallet_stmt = $conn->prepare($wallet_query);
$wallet_stmt->bind_param("i", $profile_id);
$wallet_stmt->execute();
$wallet_result = $wallet_stmt->get_result();
if ($wallet_row = $wallet_result->fetch_assoc()) {
    $blockchain_wallet_address = $wallet_row['blockchain_wallet_address'];
} else {
    $blockchain_wallet_address = ''; // Default to an empty string if not found
}



// Fetch user posts
$posts_query = "SELECT * FROM posts WHERE user_id = ?"; // Replace 'posts' with your actual posts table name
$posts_stmt = $conn->prepare($posts_query);
$posts_stmt->bind_param("i", $profile_id);
$posts_stmt->execute();
$posts_result = $posts_stmt->get_result();

$aurProofData = getAurProofData($conn, $profile_id);

// Check if the profile belongs to the logged-in user
$isCurrentUser = ($user_id == $profile_id);
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($userData['username']); ?>'s Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/web3@1.5.2/dist/web3.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@solana/web3.js@1.10.4/dist/web3.min.js"></script>
	<link href="Aureus.css" rel="stylesheet">
<script>
    var loggedInUserId = <?php echo json_encode($_SESSION['user_id']); ?>;
</script>
<style>
.username-title {
    color: #fff; /* Adjust based on banner image */
    text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    /* Additional styling for the title */
}

.post-card {
    background-color: rgba(255, 255, 255, 0.1); /* Adjust opacity as needed */
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.post-wrapper {
        margin-bottom: 20px; /* Add desired margin between posts */
    }
@font-face {
    font-family: 'Matrix Sans';
    src: url('/fonts/matrix-sans.otf') format('opentype');
}

/* Style for the specific form */
#friendRequestForm {
    background: transparent !important; /* Use !important to ensure it overrides other styles */
}


/* Responsive adjustments as needed for different screen sizes */


</style>
  
  </head>
    
<body style="background-color: <?php echo $profileSettings['background_color']; ?>; color: <?php echo $profileSettings['text_color']; ?>; font-family: <?php echo $profileSettings['font']; ?>;">
  <nav class="navbar navbar-expand-lg md-3" style="background-color: <?php echo htmlspecialchars($navbarBackgroundColor); ?>;">
    <div class="container-fluid">
             <a class="navbar-brand" href="#" style="color: <?php echo $profileSettings['text_color']; ?>;">AurConnect</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav" >
                <ul class="navbar-nav" >
                    <li class="nav-item">
                        <a class="nav-link" href="home.php" style="color: <?php echo $profileSettings['text_color']; ?>;">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php" style="color: <?php echo $profileSettings['text_color']; ?>;">Profile</a>
                    </li>
                  <li class="nav-item">
                        <a class="nav-link" href="messages.php" style="color: <?php echo $profileSettings['text_color']; ?>;">Messages</a>
                    </li>
                  	<li class="nav-item">
                        <a class="nav-link" href="friends.php" style="color: <?php echo $profileSettings['text_color']; ?>;">Friends</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php" style="color: <?php echo $profileSettings['text_color']; ?>;">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

<div class="container mt-3">
    <div class="row">
        <!-- Sidebar: User Info and Additional Options -->
        <div class="col-md-4">
            <div class="user-info">
                <img src="<?php echo htmlspecialchars($userData['profile_picture']); ?>" alt="Profile Picture" class="img-thumbnail">
                <h2><?php echo htmlspecialchars($userData['username']); ?></h2>
                <!-- Additional User Info -->
            </div>
            <?php if ($is_own_profile): ?>
            <div class="edit-button mt-3">
                <a href="edit_profile.php?id=<?php echo $profile_id; ?>" class="btn btn-primary">Edit Profile</a>
            </div>

            
            <?php endif; ?>
            
            <?php if (!$is_own_profile): ?>
            <?php if ($is_friend): ?>
                <p>You are friends with this user.</p>
            <?php else: ?>
                <button type="button" class="btn btn-primary" onclick="submitFriendRequest()">Send Friend Request</button>
            <?php endif; ?>
            <?php endif; ?>

            <div id="aurproof-container" class="mt-3">
                <h3>AurProof Activity</h3>
                <?php foreach ($aurProofData as $entry): ?>
                    <div class="aurproof-entry">
                        <p>Date: <?php echo htmlspecialchars($entry['date']); ?></p>
                        <p>Activity: <?php echo htmlspecialchars($entry['activity']); ?></p>
                        <p>Importance: <?php echo htmlspecialchars($entry['importance']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

<!-- Display NFTs Owned by User -->
<div class="container mt-3">
    <div class="row">
        <div class="col-md-4">
            <!-- Empty div to separate content -->
        </div>
        <div class="col-md-8">
            <div id="nft-container">
                <!-- NFTs display here -->
            </div>
        </div>
    </div>
</div>

        </div>

        <!-- Main Content: User Posts -->
        <div class="col-md-8">
            <div class="user-posts">
                <?php while ($post = $posts_result->fetch_assoc()): ?>
                    <div class="post">
                        <div class="card post-card mb-3">
                            <div class="card-body">
                                <p><?php echo htmlspecialchars($post['content']); ?></p>
                                <p class="card-text"><small class="text-muted">Post by <?php echo htmlspecialchars($post['post_username']); ?> on <?php echo $post['created_at']; ?></small></p>
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

                        <button class="like-button btn btn-sm btn-outline-primary" data-post-id="<?php echo $post['post_id']; ?>">
                            <?php echo $userLikedPost ? 'Unlike' : 'Like'; ?>
                        </button>
                        <span class="like-count" id="like-count-<?php echo $post['post_id']; ?>">
                            <?php echo $likeCount; ?> Likes
                        </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>



                        
  
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // AurProof Editing
    $('#edit-aurproof').on('click', function() {
        $('#aurproofEditModal').modal('show');
    });

    $('#aurproofEditForm').on('submit', function(e) {
        e.preventDefault();

        var formData = {
            date: $('#aurproofDate').val(),
            activity: $('#aurproofActivity').val(),
            importance: $('#aurproofImportance').val()
        };

        $.ajax({
            type: 'POST',
            url: 'proof_creation.php', // Change to your PHP script URL
            data: formData,
            success: function(response) {
                $('#aurproofEditModal').modal('hide');
                // Reload or refresh AurProof data
            },
            error: function() {
                // Handle error
            }
        });
    });

    // Like button functionality
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
                button.text(button.text() === 'Like' ? 'Unlike' : 'Like');
            }
        });
    });

    // Reply button toggle
    $('.reply-button').click(function() {
        var postId = $(this).attr('data-post-id');
        var replyForm = $('#reply-form-' + postId);
        replyForm.toggle();
    });

    // Function to submit friend request
    window.submitFriendRequest = function() {
        var form = $('<form></form>').attr('action', 'send_friend_request.php').attr('method', 'post');
        form.append($('<input>').attr('type', 'hidden').attr('name', 'to_user_id').val('<?php echo $profile_id; ?>'));
        $('body').append(form);
        form.submit();
    };
});
</script>


</body>
</html>