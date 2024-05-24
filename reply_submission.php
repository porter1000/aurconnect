<?php
session_start();
include 'db_config.php'; // Ensure this points to your actual database config file

// Check if the form was submitted and the necessary POST data exists
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_content'], $_POST['post_id'])) {
    $user_id = $_SESSION['user_id']; // Assuming the session contains the logged-in user's ID
    $post_id = $_POST['post_id']; // ID of the post being replied to
    $parent_reply_id = isset($_POST['parent_reply_id']) ? $_POST['parent_reply_id'] : null; // Parent reply ID for nested replies, null for direct post replies
    $reply_content = trim($_POST['reply_content']); // Trim the reply content to remove accidental whitespace

    // Security check: Ensure the reply content is not empty
    if (empty($reply_content)) {
        echo "Reply content cannot be empty.";
        exit;
    }

    // Prepare and bind parameters to prevent SQL injection
    $query = "INSERT INTO replies (post_id, user_id, parent_reply_id, content) VALUES (?, ?, ?, ?)";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("iiis", $post_id, $user_id, $parent_reply_id, $reply_content);

        if ($stmt->execute()) {
            // Redirect to the post page to see the new reply
            // Consider including a success message or redirecting back to the post
            header("Location: post.php?post_id=" . $post_id);
        } else {
            // Handle execution error
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        // Handle preparation error
        echo "Error preparing statement: " . $conn->error;
    }
} else {
    // If not a POST request or required data missing, redirect or show an error
    echo "Invalid request.";
}

$conn->close();
?>
