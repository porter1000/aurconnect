<?php
session_start();

// Check if the user is logged in, otherwise redirect to login page
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the user has already completed onboarding
$checkOnboardingStatusQuery = "SELECT onboarding_completed FROM users WHERE user_id = ?";
$stmt = $conn->prepare($checkOnboardingStatusQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['onboarding_completed']) {
    header("Location: home.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Process Interests
    if (isset($_POST['interests'])) {
        $interests = $_POST['interests'];
        foreach ($interests as $interest) {
            $query = "INSERT INTO user_interests (user_id, interest) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("is", $user_id, $interest);
            $stmt->execute();
        }
    }

    // Process Profile Picture Upload
    if (isset($_FILES["profile_picture"]) && $_FILES["profile_picture"]["error"] == 0) {
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

    $updateQuery = "UPDATE users SET onboarding_completed = TRUE WHERE user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    header("Location: home.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AurConnect - Onboarding</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="Aureus.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1 class="mt-5">Welcome to AurConnect!</h1>
        <form method="post" enctype="multipart/form-data">

            <div class="my-3">
    <p>Select your interests:</p>
    <div class="form-check">
        <input class="form-check-input" type="checkbox" name="interests[]" value="Technology" id="tech">
        <label class="form-check-label" for="tech">Technology</label><br>
        
        <input class="form-check-input" type="checkbox" name="interests[]" value="Art" id="art">
        <label class="form-check-label" for="art">Art</label><br>

        <input class="form-check-input" type="checkbox" name="interests[]" value="Music" id="music">
        <label class="form-check-label" for="music">Music</label><br>

        <input class="form-check-input" type="checkbox" name="interests[]" value="Science" id="science">
        <label class="form-check-label" for="science">Science</label><br>
      
		<input class="form-check-input" type="checkbox" name="interests[]" value="Crypto" id="crypto">
        <label class="form-check-label" for="crypto">Crypto</label><br>

        <input class="form-check-input" type="checkbox" name="interests[]" value="Sports" id="sports">
        <label class="form-check-label" for="sports">Sports</label><br>

        <input class="form-check-input" type="checkbox" name="interests[]" value="Gaming" id="gaming">
        <label class="form-check-label" for="gaming">Gaming</label><br>

        <input class="form-check-input" type="checkbox" name="interests[]" value="Literature" id="literature">
        <label class="form-check-label" for="literature">Literature</label><br>

        <input class="form-check-input" type="checkbox" name="interests[]" value="Travel" id="travel">
        <label class="form-check-label" for="travel">Travel</label><br>

        <input class="form-check-input" type="checkbox" name="interests[]" value="Fashion" id="fashion">
        <label class="form-check-label" for="fashion">Fashion</label><br>

        <input class="form-check-input" type="checkbox" name="interests[]" value="HealthWellness" id="healthWellness">
        <label class="form-check-label" for="healthWellness">Health & Wellness</label><br>

        <input class="form-check-input" type="checkbox" name="interests[]" value="Cooking" id="cooking">
        <label class="form-check-label" for="cooking">Cooking</label><br>

        <input class="form-check-input" type="checkbox" name="interests[]" value="Photography" id="photography">
        <label class="form-check-label" for="photography">Photography</label><br>

		<input class="form-check-input" type="checkbox" name="interests[]" value="Web3" id="Web3">
        <label class="form-check-label" for="web3">Web3</label><br>
        <!-- Add more interests as needed -->
    </div>
</div>


            <!-- Profile Picture Upload Section -->
            <div class="my-3">
                <p>Upload a profile picture:</p>
                <input type="file" class="form-control" name="profile_picture" id="profile_picture">
            </div>


            <button type="submit" class="btn btn-primary">Complete Onboarding</button>
        </form>
    </div>
</body>
</html>
