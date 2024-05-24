<?php
session_start();


// Create database connection
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';

// Handle account creation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register'])) {
    $user = htmlspecialchars(strtolower($_POST['reg_username'])); // Normalize username to lowercase
    $pass = password_hash($_POST['reg_password'], PASSWORD_DEFAULT);
    $email = filter_var($_POST['reg_email'], FILTER_SANITIZE_EMAIL);

    // Check if username already exists
    $checkStmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $user);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows > 0) {
        $message = "Username already taken.";
    } else {
        // SQL to create a new user
        $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $user, $pass, $email);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $message = "New account created successfully. Please log in.";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
    $checkStmt->close();
}

// Handle user login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    $user = htmlspecialchars($_POST['login_username']);
    $pass = $_POST['login_password'];

    $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($pass, $row['password'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            header("Location: home.php");
            exit;
        } else {
            $message = "Invalid username or password";
        }
    } else {
        $message = "Invalid username or password";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AurConnect - Login and Register</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="Aureus.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark ">
    <div class="container-fluid">
            <a class="navbar-brand" href="#">AurConnect</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="#">Home</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-5">
        <!-- Account Creation Form -->
        <div class="row mb-5">
            <div class="col-lg-6 mx-auto">
                <h2>Account Creation</h2>
                <form method="POST" class="mb-3">
                    <div class="mb-3">
                        <input type="text" class="form-control" name="reg_username" placeholder="Username" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" class="form-control" name="reg_email" placeholder="Email" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control" name="reg_password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn btn-primary" name="register">Create Account</button>
                </form>
            </div>
        </div>

        <!-- User Login Form -->
        <div class="row">
            <div class="col-lg-6 mx-auto">
                <h2>User Login</h2>
                <form method="POST">
                    <div class="mb-3">
                        <input type="text" class="form-control" name="login_username" placeholder="Username" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control" name="login_password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn btn-primary" name="login">Login</button>
                </form>
            </div>
        </div>

        <?php if (!empty($message)) echo "<div class='alert alert-info'>$message</div>"; ?>
    </div>

    <!-- Footer -->
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
