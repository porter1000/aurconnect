<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);


// Create database connection
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$userOptedIn = checkUserEncryptionOptInStatus($user_id);

if ($userOptedIn) {
    header('Location: messages.php');
    exit;
} else {
}

$selected_friend_id = $_GET['friend_id'] ?? 0;

function generateAndStoreUserKeyPair($userId, $passphrase) {
    global $conn; // Use your database connection

    // Generate key pair
    $keyPair = generateUserKeyPair($passphrase);

    // Store keys in the database
    storeUserKeys($conn, $userId, $keyPair['public_key'], $keyPair['private_key']);
    
    // Mark user as opted-in for encryption
    $stmt = $conn->prepare("UPDATE users SET encryption_opt_in = TRUE WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}
function checkUserEncryptionOptInStatus($userId) {
    global $conn; // Assuming $conn is your database connection variable
    $stmt = $conn->prepare("SELECT encryption_opt_in FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['encryption_opt_in'];
    }
    return false; // Default to not opted-in if user not found or other issues
}

// Handling opt-in and key pair generation
if (isset($_POST['opt_in']) && $_POST['opt_in'] == 'yes') {
    $passphrase = $_POST['passphrase']; // In a real scenario, ensure this is securely handled
    generateAndStoreUserKeyPair($user_id, $passphrase);
}
function generateUserKeyPair($passphrase) {
    $config = [
        "digest_alg" => "sha512",
        "private_key_bits" => 4096,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];
    $res = openssl_pkey_new($config);
    openssl_pkey_export($res, $privKey, $passphrase);
    $keyDetails = openssl_pkey_get_details($res);
    $pubKey = $keyDetails["key"];

    return ['private_key' => $privKey, 'public_key' => $pubKey];
}
function storeUserKeys($conn, $userId, $publicKey, $encryptedPrivateKey) {
    $stmt = $conn->prepare("UPDATE users SET public_key = ?, encrypted_private_key = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $publicKey, $encryptedPrivateKey, $userId);
    if (!$stmt->execute()) {
        // Handle error
        echo "Error storing keys.";
    }
    $stmt->close();
}

function generateRandomPassphrase() {
    return bin2hex(random_bytes(16)); // Adjust length as needed
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Encryption Opt-In</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
    <!-- Include Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
</head>
<body>

<?php if (!$userOptedIn): ?>
    <div class="modal show" id="encryptionOptInModal" tabindex="-1" role="dialog" aria-labelledby="encryptionOptInModalLabel" aria-hidden="true" style="display: block;"> <!-- Added inline style for demonstration -->
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="encryptionOptInModalLabel">Encryption Opt-In</h5>
                    <!-- Close button removed for forcing opt-in -->
                </div>
                <div class="modal-body">
                    <p>Would you like to enable end-to-end encryption for your messages?</p>
                    <form id="encryptionOptInForm" action="process_opt_in.php" method="post">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="encryptionOptIn" name="encryption_opt_in" value="yes" checked>
                            <label class="custom-control-label" for="encryptionOptIn">Yes, enable encryption</label>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
      $(document).ready(function() {
        // Show the modal immediately
        $("#encryptionOptInModal").modal({backdrop: 'static', keyboard: false}); // This makes the modal not dismissible by clicking outside or pressing escape
      });
    </script>
<?php endif; ?>

</body>
</html>