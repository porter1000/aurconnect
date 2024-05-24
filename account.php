<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);


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

// Fetch user data including the profile picture
$userDataQuery = $conn->query("SELECT email, creation_date, profile_picture FROM users WHERE user_id = $user_id");
$userData = $userDataQuery->fetch_assoc();

$message = ""; // Initialize the message variable

// Handle profile picture upload
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_picture"])) {
        $targetDirectory = "uploads/profile_pictures/";
        $targetFile = $targetDirectory . basename($_FILES["profile_picture"]["name"]);

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile)) {
            // Profile picture uploaded successfully
            // Update the user's profile_picture column in the database with $targetFile
            $updatePictureQuery = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
            $updatePictureQuery->bind_param("si", $targetFile, $user_id);
            if ($updatePictureQuery->execute()) {
                $message = "Profile picture updated successfully.";
            } else {
                $message = "Error updating profile picture in the database.";
            }
            $updatePictureQuery->close();
        } 
		elseif ($_FILES["profile_picture"]["size"] > 500000) { // Example size limit: 500KB
            $message = "File is too large.";
        } 
        elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            $message = "Only JPG, JPEG, PNG & GIF files are allowed.";
        } 
      	else {
            $message = "Error uploading profile picture.";
        }
    }

// Handle form submission for account updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email']) && isset($_POST['password'])) {
    $new_email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $new_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Update user data in the database
    $updateStmt = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE user_id = ?");
    $updateStmt->bind_param("ssi", $new_email, $new_password, $user_id);
    if ($updateStmt->execute()) {
        $message = "Account updated successfully";
    } else {
        $message = "Error updating account or no changes made";
    }

    $updateStmt->close();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AurConnect</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="Aureus.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark md-3">
    <div class="container-fluid">
            <a class="navbar-brand" href="#">AurConnect</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="home.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Profile</a>
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

    <!-- Main Content -->
    <div class="container mt-4">
        <h1>Welcome, <?php echo htmlspecialchars($username); ?></h1>
        <p>Manage your account settings.</p>

        <div class="mb-3">
            <h3>Profile Information</h3>
<!-- Display User Profile Picture -->
<div class="mb-3 profile-picture-container">
    <h3>Profile Picture</h3>
    <?php if (!empty($userData['profile_picture'])): ?>
        <img src="<?php echo htmlspecialchars($userData['profile_picture']); ?>" alt="User Profile Picture" class="img-fluid profile-picture">
    <?php else: ?>
        <p class="no-picture-message">No profile picture available.</p>
    <?php endif; ?>
</div>


            <p>Email: <?php echo htmlspecialchars($userData['email']); ?></p>
            <p>Account Created On: <?php echo htmlspecialchars($userData['creation_date']); ?></p>
        </div>
          


        <!-- Display message after form submission -->
        <?php if (!empty($message)) echo "<div class='alert alert-info'>$message</div>"; ?>

        <!-- Account Management Form -->
        <!-- Account Management Form -->
<h2>Update Your Account</h2>
<form method="post">
    <div class="mb-3">
        <label for="email" class="form-label">New Email:</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="New Email" required>
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">New Password:</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="New Password" required>
    </div>
    <button type="submit" class="btn btn-primary">Update Account</button>
</form>

<!-- Profile Picture Upload Form -->
<h2>Upload Profile Picture</h2>
<form method="post" enctype="multipart/form-data">
    <div class="mb-3">
        <label for="profile_picture" class="form-label">Upload Profile Picture:</label>
        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
    </div>
    <button type="submit" class="btn btn-primary">Upload Profile Picture</button>
</form>



        <div class="mt-4">
            <h3>Privacy and Security</h3>
            <p>For additional security settings and privacy options, please contact our support team.</p>
        </div>
<div class="mt-4">
            <h3>Account Deactivation/Deletion</h3>
            <p>Contact support for options to deactivate or delete your account.</p>
        </div>

        
      <form action="logout.php" method="post">
        <button type="submit" class="btn btn-primary">Logout</button>
    </form>
    </div>

    <!-- Footer -->
    <footer class=" text-white text-center text-lg-start mt-3">
    <div class="container p-4">
        <!-- Grid row -->
        <div class="row">
            <!-- Grid column -->
            <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                <h5 class="text-uppercase">AurConnect</h5>
                <p>
                    AurConnect is a platform dedicated to fostering collaboration and community engagement. Join us to connect, share, and grow.
                </p>
            </div>
            <!-- Grid column -->

            <!-- Grid column -->
            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h5 class="text-uppercase">Links</h5>

                <ul class="list-unstyled mb-0">
                    <li>
                        <a href="about.html" class="text-white">About Us</a>
                    </li>
                    <li>
                        <a href="services.html" class="text-white">Services</a>
                    </li>
                    <li>
                        <a href="contact.html" class="text-white">Contact</a>
                    </li>
                    <li>
                        <a href="signup.html" class="text-white">Sign Up</a>
                    </li>
                  <li>
                        <a href="privacy_policy.html" class="text-white">Privacy</a>
                    </li>
                    <li>
                        <a href="terms_of_service.html" class="text-white">Terms of Service</a>
                    </li>
                </ul>
            </div>
            <!-- Grid column -->

            <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                <h5 class="text-uppercase">Contact</h5>

                <ul class="list-unstyled">
                    <li>
                        <span class="text-white">Email: info@aurconnect.com</span>
                    </li>
                    <li>
                        <span class="text-white">Phone: 678-848-8745</span>
                    </li>
                </ul>
            </div>
            <!-- Grid column -->
        </div>
        <!-- Grid row -->
    </div>

    <div class="text-center p-3">
        © 2024 AurConnect. All rights reserved.
		© 2024 AurMob. All rights reserved.
		© 2024 AurForge. All rights reserved.
    </div>
</footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

