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
function extractHashtags($string) {
    preg_match_all('/#(\w+)/', $string, $matches);
    return array_unique($matches[1]);
}

function processHashtags($post_id, $post_content, $conn) {
    $hashtags = extractHashtags($post_content);
    foreach ($hashtags as $hashtag) {
        // Check if hashtag exists
        $stmt = $conn->prepare("SELECT hashtag_id FROM hashtags WHERE name = ?");
        $stmt->bind_param("s", $hashtag);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 0) {
            // Insert new hashtag
            $stmt = $conn->prepare("INSERT INTO hashtags (name) VALUES (?)");
            $stmt->bind_param("s", $hashtag);
            $stmt->execute();
            $hashtag_id = $conn->insert_id;
        } else {
            $row = $result->fetch_assoc();
            $hashtag_id = $row['hashtag_id'];
        }
        $stmt->close();

        // Link hashtag to post
        $stmt = $conn->prepare("INSERT INTO post_hashtags (post_id, hashtag_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $post_id, $hashtag_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>
