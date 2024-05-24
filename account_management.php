<?php
session_start();

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $new_email, $new_password, $user_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "Account updated successfully";
    } else {
        echo "Error updating account: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
