<?php
session_start();
$user_id = $_SESSION['user_id'] ?? null;

// Create database connection
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$userInterests = [];
$interestsQuery = "SELECT interest FROM user_interests WHERE user_id = ?";
$interestsStmt = $conn->prepare($interestsQuery);
$interestsStmt->bind_param("i", $user_id);
$interestsStmt->execute();
$result = $interestsStmt->get_result();

while ($row = $result->fetch_assoc()) {
    $userInterests[] = $row['interest'];
}
$interestsStmt->close();

$posts = [];
foreach ($userInterests as $interest) {
    $query = "SELECT p.*, 
                     (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS PopularityScore,
                     DATEDIFF(CURRENT_DATE, p.created_at) AS RecencyScore,
                     1 AS RelevanceScore, 
                     1 AS UserEngagementScore
              FROM posts p
              INNER JOIN post_hashtags ph ON p.post_id = ph.post_id
              INNER JOIN hashtags h ON ph.hashtag_id = h.hashtag_id
              WHERE h.name = ?
              ORDER BY PopularityScore DESC, RecencyScore ASC, RelevanceScore DESC, UserEngagementScore DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $interest);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $posts[] = $row;
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Posts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/css/bootstrap.min.css" rel="stylesheet">
	<link href="Aureus.css" rel="stylesheet">
    <style>
        .post {
            border: 1px solid #ddd;
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 8px;
        }
        .post-img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
        }
        .post-title {
            font-size: 24px;
            font-weight: bold;
            margin-top: 15px;
        }
        .post-content {
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2>Explore Posts</h2>
        <?php
        foreach ($posts as $post) {
            echo '<div class="post">';
            if (!empty($post['image_url'])) {
                echo '<img src="' . htmlspecialchars($post['image_url']) . '" class="post-img" alt="Post Image">';
            }
            echo '<div class="post-title">' . htmlspecialchars($post['title']) . '</div>';
            echo '<div class="post-content">' . htmlspecialchars($post['content']) . '</div>';
            echo '</div>';
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
