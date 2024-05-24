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

if (isset($_GET['q'])) {
    $input = $_GET['q'];

    // Database query to search for users
    $stmt = $conn->prepare("SELECT username FROM users WHERE username LIKE CONCAT('%', ?, '%')");
    $stmt->bind_param("s", $input);
    $stmt->execute();
    $result = $stmt->get_result();

    $suggestions = [];
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row['username'];
    }

    echo json_encode($suggestions);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['friend_username'])) {
    $friend_username = $_POST['friend_username'];

    // First, get the friend's user ID from their username
    $user_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $user_stmt->bind_param("s", $friend_username);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows > 0) {
        $friend_row = $user_result->fetch_assoc();
        $friend_id = $friend_row['user_id'];

        if ($friend_id == $user_id) {
            $message = "Error: You cannot send a friend request to yourself.";
        } else {
            // Check if the friend request already exists
            $stmt = $conn->prepare("SELECT * FROM friends WHERE user_id = ? AND friend_id = ?");
            $stmt->bind_param("ii", $user_id, $friend_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 0) {
                // No existing friend request, so insert a new one
                $insert_stmt = $conn->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')");
                $insert_stmt->bind_param("ii", $user_id, $friend_id);
                if ($insert_stmt->execute()) {
                    $message = "Friend request sent successfully.";
                } else {
                    $message = "Error sending friend request.";
                }
                $insert_stmt->close();
            } else {
                $message = "Friend request already sent.";
            }
            $stmt->close();
        }
    } else {
        $message = "Error: No user found with that username.";
    }
    $user_stmt->close();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accept_request_id'])) {
    $accept_request_id = $_POST['accept_request_id'];

    // Accept the friend request
    $update_stmt = $conn->prepare("UPDATE friends SET status = 'accepted' WHERE id = ?");
    $update_stmt->bind_param("i", $accept_request_id);
    if ($update_stmt->execute()) {
        $message = "Friend request accepted successfully.";
    } else {
        $message = "Error accepting friend request.";
    }
    $update_stmt->close();
}

$friends_stmt = $conn->prepare("
    SELECT u.username, u.user_id AS friend_user_id, f.status 
    FROM friends f 
    JOIN users u ON (f.friend_id = u.user_id AND f.user_id = ?) OR (f.user_id = u.user_id AND f.friend_id = ?)
    WHERE f.status = 'accepted'
    GROUP BY u.username
");
$friends_stmt->bind_param("ii", $user_id, $user_id);
$friends_stmt->execute();
$friendsResult = $friends_stmt->get_result();


$pending_stmt = $conn->prepare("
    SELECT u.username, f.id 
    FROM friends f 
    JOIN users u ON f.user_id = u.user_id 
    WHERE f.friend_id = ? AND f.status = 'pending'
");
$pending_stmt->bind_param("i", $user_id);
$pending_stmt->execute();
$pendingResult = $pending_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AurConnect - Friends</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="Aureus.css" rel="stylesheet">
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

    <div class="container mt-4">
        <?php if($message): ?>
            <div class="alert alert-info">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <h2>Your Friends</h2>
        <div class="list-group">
    <?php while($row = $friendsResult->fetch_assoc()): ?>
        <a href="https://aurconnect.com/profile.php?id=<?= htmlspecialchars($row['friend_user_id']); ?>" class="list-group-item list-group-item-action">
            <?= htmlspecialchars($row['username']); ?>
        </a>
    <?php endwhile; ?>
</div> 
        <h2 class="mt-4">Pending Friend Requests</h2>
        <div class="list-group">
            <?php while($row = $pendingResult->fetch_assoc()): ?>
                <div class="list-group-item">
                    <?= htmlspecialchars($row['username']); ?>
                    <form method="post" class="d-inline">
                        <input type="hidden" name="accept_request_id" value="<?= $row['id']; ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Accept</button>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>

        <h2 class="mt-4">Send a Friend Request</h2>
      <form method="post">
          <div class="input-group mb-3">
              <input type="text" id="friend_username" name="friend_username" placeholder="Enter friend's username" onkeyup="fetchUsernames()">
              <button type="submit">Send Friend Request</button>
              
          </div>
        <div id="usernameList" class="autocomplete-items"></div>
      </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function fetchUsernames() {
        var input = document.getElementById('friend_username').value;
        var list = document.getElementById('usernameList');

        if (input.length > 0) {
            fetch('friends.php?q=' + input)
                .then(response => response.json())
                .then(data => {
                    list.innerHTML = '';
                    data.forEach(function(username) {
                        list.innerHTML += '<div onclick="setUsername(\'' + username + '\')">' + username + '</div>';
                    });
                });
        } else {
            list.innerHTML = '';
        }
    }

    function setUsername(username) {
        document.getElementById('friend_username').value = username;
        document.getElementById('usernameList').innerHTML = '';
    }
    </script>
</body>
</html>
