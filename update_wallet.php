<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "User not authenticated"]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Extract the wallet address from the AJAX request
$wallet_address = $_POST['wallet_address'];

// Determine the blockchain type based on the wallet address or network
function determineBlockchainType($address) {
    // Check if the wallet address belongs to Ethereum (Metamask)
    if (preg_match("/^0x[0-9a-fA-F]{40}$/", $address)) {
        return 'ETH'; // Ethereum address
    }

    // Check if the wallet address belongs to Solana
    if (preg_match("/^[A-Za-z0-9+\/=]{43}$/", $address)) {
        return 'SOL'; // Solana address
    }

    return 'UNKNOWN'; // Unknown blockchain type
}


// Create a database connection
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// Determine blockchain type
$blockchainType = determineBlockchainType($wallet_address);

// Update the user's wallet address and blockchain type in the database
$update_query = "UPDATE users SET blockchain_wallet_address = ?, blockchain_type = ? WHERE user_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ssi", $wallet_address, $blockchainType, $user_id);

if ($stmt->execute()) {
    http_response_code(200); // OK
    echo json_encode(["message" => "Wallet successfully connected"]);
} else {
    http_response_code(500); // Internal Server Error
    echo json_encode(["error" => "Failed to update wallet information"]);
}

$stmt->close();
$conn->close();
?>

