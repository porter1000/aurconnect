<?php

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $conn->real_escape_string(htmlspecialchars($_POST['username']));
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $user, $pass, $email);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "New account created successfully";

            // Send account creation email
            $to = $email;
            $subject = "Welcome to AurConnect!";
            // Prepare HTML content
            $message = "
            <html>
            <head>
            <title>Welcome to AurConnect!</title>
            </head>
            <body>
            <img src='https://example.com/path/to/your/logo_or_pfp.png' alt='AurConnect Logo' style='height: 100px;'>
            <h1>Hello " . $user . "!</h1>
            <p>Welcome to AurConnect! We're thrilled to have you join our community.</p>
            <p>Here are a few things you can do to get started:</p>
            <ul>
                <li>Complete your profile</li>
                <li>Connect with friends</li>
                <li>Share your first post</li>
            </ul>
            <p>Start exploring the community <a href='https://aurconnect.com/login.php'>here</a>.</p>
            </body>
            </html>
            ";
            // Headers
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: AurConnect <no-reply@aurconnect.com>" . "\r\n";

            if(mail($to, $subject, $message, $headers)) {
                echo "Account creation email sent.";
            } else {
                echo "Failed to send account creation email.";
            }
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>
