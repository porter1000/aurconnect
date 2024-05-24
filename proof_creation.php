<?php
session_start();


// Create database connection
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page or handle as per your application's flow
    exit('User not logged in');
}

$userId = $_SESSION['user_id'];


// Function to get AurProof data
function getAurProofData($conn, $userId) {
    $stmt = $conn->prepare("SELECT * FROM aurproof WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to add or update AurProof data
function addOrUpdateAurProofData($conn, $userId, $date, $activity, $importance) {
    $stmt = $conn->prepare("INSERT INTO aurproof (user_id, date, activity, importance) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE activity = ?, importance = ?");
    $stmt->bind_param("isssss", $userId, $date, $activity, $importance, $activity, $importance);
    $stmt->execute();
}

// Handling POST request to add or update data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $date = $_POST['date'] ?? '';
    $activity = $_POST['activity'] ?? '';
    $importance = $_POST['importance'] ?? 'low';

    // Validation and sanitization of inputs should be added here

    addOrUpdateAurProofData($conn, $userId, $date, $activity, $importance);
}

// Handling GET request to fetch data
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    $data = getAurProofData($conn, $userId);
}

$conn->close();
?>
