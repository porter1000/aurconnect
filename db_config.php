<?php
// Database connection settings
$servername = "";
$dbUsername = ""; // Database username
$dbPassword = ""; // Database password
$dbname = "";

// Create database connection
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
