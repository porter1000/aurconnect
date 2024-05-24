<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}



// Create database connection
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get post_id from request and user_id from session
$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];

// Function to toggle like
function toggleLike($user_id, $post_id, $conn) {
    $checkQuery = "SELECT * FROM likes WHERE user_id = ? AND post_id = ?";
    if ($stmt = $conn->prepare($checkQuery)) {
        $stmt->bind_param("ii", $user_id, $post_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $deleteQuery = "DELETE FROM likes WHERE user_id = ? AND post_id = ?";
            if ($deleteStmt = $conn->prepare($deleteQuery)) {
                $deleteStmt->bind_param("ii", $user_id, $post_id);
                $deleteStmt->execute();
            }
        } else {
            $insertQuery = "INSERT INTO likes (user_id, post_id) VALUES (?, ?)";
            if ($insertStmt = $conn->prepare($insertQuery)) {
                $insertStmt->bind_param("ii", $user_id, $post_id);
                $insertStmt->execute();
            }
        }
    }
}

// Function to count likes
function countLikes($post_id, $conn) {
    $countQuery = "SELECT COUNT(*) AS like_count FROM likes WHERE post_id = ?";
    if ($countStmt = $conn->prepare($countQuery)) {
        $countStmt->bind_param("i", $post_id);
        $countStmt->execute();
        $result = $countStmt->get_result();
        $row = $result->fetch_assoc();
        return $row['like_count'];
    }
    return 0;
}

// Toggle the like
toggleLike($user_id, $post_id, $conn);

// Get the new like count
$likeCount = countLikes($post_id, $conn);

// Return the new like count as JSON for AJAX
echo json_encode(['likeCount' => $likeCount]);

// Close the database connection
$conn->close();
?>

