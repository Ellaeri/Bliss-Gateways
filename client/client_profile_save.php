<?php

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "bliss_gateways";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Default placeholder avatar
$avatarPath = "assets/icons/profile.png";

// Handle avatar upload
if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileName = time() . "_" . basename($_FILES["avatar"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFile)) {
        $avatarPath = $targetFile; // Use uploaded file
    }
}

// Collect form data safely
$firstName       = $_POST['firstName'] ?? '';
$lastName        = $_POST['lastName'] ?? '';
$birthday        = $_POST['birthday'] ?? '';
$address         = $_POST['address'] ?? '';
$state           = $_POST['state'] ?? '';
$country         = $_POST['country'] ?? '';
$passport        = $_POST['passport'] ?? '';
$issue           = $_POST['issue'] ?? '';
$gender          = $_POST['gender'] ?? '';
$city            = $_POST['city'] ?? '';
$postal          = $_POST['postal'] ?? '';
$nationality     = $_POST['nationality'] ?? '';
$passportExpiry  = $_POST['passportExpiry'] ?? '';
$contact         = $_POST['contact'] ?? '';
$username        = $_POST['username'] ?? '';
$password        = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : '';

// Insert into DB
$sql = "INSERT INTO users 
    (firstName, lastName, birthday, address, state, country, passport, issue, gender, city, postal, nationality, passportExpiry, contact, username, password, avatar) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssssssssssss", 
    $firstName, $lastName, $birthday, $address, $state, $country, $passport, $issue, 
    $gender, $city, $postal, $nationality, $passportExpiry, $contact, $username, $password, $avatarPath);

if ($stmt->execute()) {
    echo "<script>alert('Profile saved successfully!'); window.location.href='client_profile.html';</script>";
} else {
    echo "Error: " . $stmt->error;
}

$conn->close();
?>
