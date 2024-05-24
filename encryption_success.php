<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // If not logged in, redirect to the login page
    header("Location: login.php");
    exit;
}

// Optionally, check if the user has indeed opted in for encryption
// This step requires fetching the user's opt-in status from the database
// Assuming a function or a flag from the session indicates this status
// This example will skip this check for brevity

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Encryption Enabled</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</head>
<body>
<div class="container mt-5">
    <div class="jumbotron">
        <h1 class="display-4">Encryption Enabled!</h1>
        <p class="lead">You have successfully enabled end-to-end encryption for your messages.</p>
        <hr class="my-4">
        <p>Your messages will now be encrypted, enhancing your privacy and security.</p>
        <a class="btn btn-primary btn-lg" href="messages.php" role="button">Go to messages</a>
        <a class="btn btn-secondary btn-lg" href="logout.php" role="button">Log Out</a>
    </div>
</div>
</body>
</html>
