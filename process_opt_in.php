<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

require_once 'db_config.php'; // Include your database configuration file

$user_id = $_SESSION['user_id'];

// Function to generate a random passphrase
function generateRandomPassphrase() {
    return bin2hex(random_bytes(16)); // 16 bytes will generate a 32-character hex string
}

// Function to generate a user key pair
function generateUserKeyPair() {
    $config = [
        "digest_alg" => "sha512",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];
    $res = openssl_pkey_new($config);
    openssl_pkey_export($res, $privateKey);
    $keyDetails = openssl_pkey_get_details($res);
    $publicKey = $keyDetails['key'];
    openssl_pkey_free($res);

    return ['private_key' => $privateKey, 'public_key' => $publicKey];
}

// Initialize variables
$passphrase = generateRandomPassphrase();
$keyPair = generateUserKeyPair();

// Extract keys
$publicKey = $keyPair['public_key'];
$privateKey = $keyPair['private_key'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['encryption_opt_in']) && $_POST['encryption_opt_in'] == 'yes') {
    // Store the key pair and passphrase securely
    if (storeUserKeys($conn, $user_id, $publicKey, $privateKey, $passphrase)) {
        header("Location: encryption_success.php");
        exit;
    } else {
        echo "Error updating encryption settings. Please try again.";
    }
} else {
    header("Location: optin.php");
    exit;
}

// Function to store user's keys and passphrase in the database
function storeUserKeys($conn, $userId, $publicKey, $privateKey, $passphrase) {
    $hashedPassphrase = password_hash($passphrase, PASSWORD_DEFAULT);

    $encryptedPrivateKey = openssl_encrypt($privateKey, 'aes-256-cbc', $passphrase, 0, substr(hash('sha256', $passphrase, true), 0, 16));

    if ($encryptedPrivateKey === false) {
        echo "Error encrypting the private key.";
        return false;
    }

    $stmt = $conn->prepare("UPDATE users SET public_key = ?, encrypted_private_key = ?, encryption_opt_in = TRUE, passphrase_hash = ? WHERE user_id = ?");

    if (!$stmt) {
        echo "Error preparing statement: " . $conn->error;
        return false;
    }

    $stmt->bind_param("sssi", $publicKey, $encryptedPrivateKey, $hashedPassphrase, $userId);

    if (!$stmt->execute()) {
        echo "Error storing keys: " . $stmt->error;
        $stmt->close();
        return false;
    }

    $stmt->close();
    return true;
}

?>
