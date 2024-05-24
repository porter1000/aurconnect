<?php
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];



$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id']; // ID of the logged-in user
$profile_id = $_GET['id'] ?? $user_id; // Get profile ID from URL or use logged-in user's ID
$is_own_profile = ($user_id == $profile_id);

if (!$is_own_profile) {
    // Redirect to profile page or show an error if the user is not viewing their own profile
    header("Location: profile.php?id=" . $user_id);
    exit;
}

// Fetch existing profile data
$user_query = "SELECT username FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$userData = $user_result->fetch_assoc();

$settings_query = "SELECT * FROM profile_settings WHERE user_id = ?";
$settings_stmt = $conn->prepare($settings_query);
$settings_stmt->bind_param("i", $user_id);
$settings_stmt->execute();
$settings_result = $settings_stmt->get_result();
$profileSettings = $settings_result->fetch_assoc();

// Handle profile update requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process form data here
    $username = strtolower($_POST['username']); // Add validation and sanitization
    $background_color = $_POST['background_color'] ?? '#FFFFFF';
    $text_color = $_POST['text_color'] ?? '#000000';
    $link_color = $_POST['link_color'] ?? '#0000FF';
    $font = $_POST['font'] ?? 'Arial';
    $theme = $_POST['theme'] ?? 'default';

    // Add validation and sanitization for all inputs

    // Begin database transaction
    $conn->begin_transaction();

    try {
        // Update user table
        $user_update_query = "UPDATE users SET username = ? WHERE user_id = ?";
        $user_update_stmt = $conn->prepare($user_update_query);
        $user_update_stmt->bind_param("si", $username, $user_id);
        $user_update_stmt->execute();

        // Update profile settings table
        $settings_update_query = "UPDATE profile_settings SET background_color = ?, text_color = ?, link_color = ?, font = ?, theme = ? WHERE user_id = ?";
        $settings_update_stmt = $conn->prepare($settings_update_query);
        $settings_update_stmt->bind_param("sssssi", $background_color, $text_color, $link_color, $font, $theme, $user_id);
        $settings_update_stmt->execute();

        // Commit transaction
        $conn->commit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        // Handle error, e.g., log it and display an error message
    }

    // Redirect back to the profile page
    header("Location: profile.php?id=" . $user_id);
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <!-- Include CSS files and possibly inline styles using PHP for customization -->
    <link href="Aureus.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/web3@1.3.6/dist/web3.min.js"></script>

  <style>
    /* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    color: #333;
}

.container {
    max-width: 800px;
    margin: auto;
    padding: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Typography */
h1 {
    color: #333;
    margin-bottom: 20px;
}

/* Form Elements */
label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
/* General Styles for Color Pickers */
.color-picker-container {
    margin-bottom: 15px;
}

.color-picker-container label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

input[type="color"] {
    width: 100%;
    height: 40px;
    border: none;
    cursor: pointer;
    border-radius: 4px;
    padding: 0;
    background: none;
}

/* Custom Styles for Different Color Pickers */
#background_color {
    background: linear-gradient(to right, #ffffff, #000000);
}

#text_color {
    background: linear-gradient(to right, #ffffff, #000000);
}

#link_color {
    background: linear-gradient(to right, #ffffff, #000000);
}

input[type="color"]::-webkit-color-swatch-wrapper {
    padding: 0;
}

input[type="color"]::-webkit-color-swatch {
    border: none;
}
input[type="text"],
input[type="file"],
select {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

input[type="file"] {
    border: none;
}

#fontPreview {
    padding: 10px;
    margin-bottom: 15px;
    background-color: #eaeaea;
    border: 1px dashed #ccc;
}

/* Buttons */
.btn {
    padding: 10px 20px;
    color: white;
    background-color: #007bff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn:hover {
    background-color: #0056b3;
}

/* Tab Navigation */
.nav-tabs {
    border-bottom: 1px solid #ddd;
    margin-bottom: 20px;
}

.nav-link {
    padding: 10px 15px;
    margin-right: 5px;
    border: 1px solid transparent;
    border-radius: 4px 4px 0 0;
}

.nav-link:hover {
    border-color: #ddd;
}

.nav-link.active {
    color: #fff;
    background-color: #007bff;
    border-color: #007bff #007bff transparent;
}
@font-face {
    font-family: 'Matrix Sans';
    src: url('/fonts/matrix-sans.otf') format('opentype');
}
/* Responsive Design */
@media (max-width: 600px) {
    .container {
        padding: 10px;
    }

    .btn {
        width: 100%;
    }
  input[type="color"] {
        height: 30px;
    }
}


</style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Edit Your Profile</h1>

        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link" href="#generalSettings" data-bs-toggle="tab">General</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="#appearance" data-bs-toggle="tab">Appearance</a>
            </li>
            <button onclick="connectWallet()" class="btn btn-primary">Connect Wallet</button>
        </ul>

        <form action="edit_profile.php?id=<?php echo $user_id; ?>" method="post" class="tab-content mt-3">
            <!-- Other tabs content -->

            <div class="tab-pane fade show active" id="appearance">
                <!-- Banner Image Upload -->
                <div class="mb-3">
                    <label for="bannerImage" class="form-label">Banner Image:</label>
                    <input type="file" class="form-control" name="banner_image" id="bannerImage">
                    <div class="form-text">Upload a banner image for your profile.</div>
                </div>
    <div>
        <label for="username">Username:</label>
        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($userData['username']); ?>">
    </div>
    
    <!-- Background Color -->
    <div>
        <label for="background_color">Background Color:</label>
        <input type="color" name="background_color" id="background_color" value="<?php echo htmlspecialchars($profileSettings['background_color']); ?>">
    </div>

    <!-- Text Color -->
    <div>
        <label for="text_color">Text Color:</label>
        <input type="color" name="text_color" id="text_color" value="<?php echo htmlspecialchars($profileSettings['text_color']); ?>">
    </div>

    <!-- Link Color -->
    <div>
        <label for="link_color">Link Color:</label>
        <input type="color" name="link_color" id="link_color" value="<?php echo htmlspecialchars($profileSettings['link_color']); ?>">
    </div>

<div>
    <label for="font">Font:</label>
    <select name="font" id="font" onchange="updateFontPreview()">
        <option value="Arial" <?php echo $profileSettings['font'] === 'Arial' ? 'selected' : ''; ?>>Arial</option>
        <option value="Verdana" <?php echo $profileSettings['font'] === 'Verdana' ? 'selected' : ''; ?>>Verdana</option>
        <option value="Tahoma" <?php echo $profileSettings['font'] === 'Tahoma' ? 'selected' : ''; ?>>Tahoma</option>
        <option value="Helvetica" <?php echo $profileSettings['font'] === 'Helvetica' ? 'selected' : ''; ?>>Helvetica</option>
        <option value="Georgia" <?php echo $profileSettings['font'] === 'Georgia' ? 'selected' : ''; ?>>Georgia</option>
        <option value="Times New Roman" <?php echo $profileSettings['font'] === 'Times New Roman' ? 'selected' : ''; ?>>Times New Roman</option>
        <option value="Courier New" <?php echo $profileSettings['font'] === 'Courier New' ? 'selected' : ''; ?>>Courier New</option>
        <option value="Matrix Sans" <?php echo $profileSettings['font'] === 'Matrix Sans' ? 'selected' : ''; ?>>Matrix Sans</option>
    </select>
    <div id="fontPreview">This is a font preview.</div>
</div>



    <!-- Theme Selection -->
    <div>
        <label for="theme">Theme:</label>
        <select name="theme" id="theme">
            <option value="default" <?php echo $profileSettings['theme'] === 'default' ? 'selected' : ''; ?>>Default</option>
            <option value="light" <?php echo $profileSettings['theme'] === 'light' ? 'selected' : ''; ?>>Light</option>
            <option value="dark" <?php echo $profileSettings['theme'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
            <!-- Add more theme options as needed -->
        </select>
    </div>
	


    <button type="submit" class="btn btn-primary">Save Changes</button>
        
    </div>
          </form>
<script>
    function updateFontPreview() {
        var select = document.getElementById("font");
        var fontPreview = document.getElementById("fontPreview");
        var selectedFont = select.options[select.selectedIndex].value;
        fontPreview.style.fontFamily = selectedFont;
    }
    updateFontPreview(); // Initial update
async function connectWallet() {
    if (typeof window.ethereum !== 'undefined') {
        try {
            // Request account access using eth_requestAccounts
            const accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });

            if (accounts.length > 0) {
                const selectedAddress = accounts[0];

                // Send the wallet address to the PHP backend via AJAX
                const formData = new FormData();
                formData.append('wallet_address', selectedAddress);

                fetch('update_wallet.php', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.message) {
                        // Wallet successfully connected, you can redirect or show a success message
                        console.log(data.message);
                    } else {
                        // Handle any error messages from the backend
                        console.error(data.error);
                    }
                })
                .catch(error => {
                    console.error('AJAX request error:', error);
                });
            } else {
                // Handle if no accounts are available
                console.error('No Ethereum accounts available.');
            }
        } catch (error) {
            // Handle wallet connection error
            console.error(error);
        }
    } else {
        // MetaMask is not installed or not available
        console.error('MetaMask is not installed or not available.');
    }
}


</script>
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/5.1.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>