<?php
session_start();

// Check if the user is logged in
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

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['to_user_id'])) {
    $to_user_id = $_POST['to_user_id'];

    if ($to_user_id == $user_id) {
        $message = "Error: You cannot send a friend request to yourself.";
    } else {
        // Check if the friend request already exists
        $stmt = $conn->prepare("SELECT * FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $stmt->bind_param("iiii", $user_id, $to_user_id, $to_user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            // No existing friend request, so insert a new one
            $insert_stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
            $insert_stmt->bind_param("ii", $user_id, $to_user_id);
            if ($insert_stmt->execute()) {
                $message = "Friend request sent successfully.";
            } else {
                $message = "Error sending friend request.";
            }
            $insert_stmt->close();
        } else {
            $message = "You have already sent a friend request to this user.";
        }
        $stmt->close();
    }

    // Redirect back to the profile page from where the request was sent
    $referrer = $_SERVER['HTTP_REFERER'] ?? 'profile.php?id=' . $user_id; // Fallback to user's own profile if HTTP_REFERER is not set
    header("Location: $referrer&message=" . urlencode($message));
    exit();
}

// Redirect to a default page if the script is accessed without a POST request
header("Location: home.php");
exit();
?>