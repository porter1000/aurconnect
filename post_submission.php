<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_content']) && isset($_FILES['post_image'])) {
    $user_id = $_SESSION['user_id'];
    $post_content = sanitize($_POST['post_content']);
    $post_image = $_FILES['post_image'];
    $maxContentLength = 250;
    $target_dir = "uploads/";
    
    if (strlen($post_content) > $maxContentLength) {
        $_SESSION['error_message'] = "Post content exceeds maximum length.";
        header("Location: home.php");
        exit;
    }

    // Image upload handling
    $target_file = $target_dir . uniqid() . '.' . strtolower(pathinfo($post_image["name"], PATHINFO_EXTENSION));
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check file size and type
    if ($post_image["size"] > 5000000) {
        $_SESSION['error_message'] = "Sorry, your file is too large.";
    } elseif (!in_array($imageFileType, ["jpg", "png", "jpeg", "gif"])) {
        $_SESSION['error_message'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    } elseif (move_uploaded_file($post_image["tmp_name"], $target_file)) {
        // Image upload successful
        $query = "INSERT INTO posts (user_id, content, image_path) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iss", $user_id, $post_content, $target_file);
        if ($stmt->execute()) {
            $post_id = $conn->insert_id;
            require 'hashtag_processor.php';
            processHashtags($post_id, $post_content, $conn);
            $_SESSION['success_message'] = "Your post has been successfully created.";
        } else {
            $_SESSION['error_message'] = "There was an error creating your post.";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Sorry, there was an error uploading your file.";
    }

    header("Location: home.php");
    exit;
}

$conn->close();
?>
