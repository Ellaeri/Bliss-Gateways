<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    die(json_encode(["success" => false, "message" => "Error: User not logged in."]));
}

$admin_id = $_SESSION['admin_id'];

$conn = new mysqli("localhost", "root", "", "bliss_gateways");
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed."]));
}

// --- Handle avatar upload ---
$avatarFileName = null;
if (!empty($_FILES['profileUpload']['name']) && $_FILES['profileUpload']['error'] === UPLOAD_ERR_OK) {
    $targetDir = __DIR__ . "/uploads/avatars/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $fileName = time() . "_" . basename($_FILES["profileUpload"]["name"]);
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES["profileUpload"]["tmp_name"], $targetFile)) {
        $avatarFileName = $fileName; // store filename only
        $_SESSION['admin_avatar'] = "/BlissGateways/uploads/avatars/" . $fileName; // update session avatar path
    }
}

// --- Collect form data ---
$firstName  = $_POST['firstName'] ?? '';
$lastName   = $_POST['lastName'] ?? '';
$birthday   = $_POST['birthday'] ?? null;
$gender     = $_POST['gender'] ?? null;
$address    = $_POST['address'] ?? null;
$contact    = $_POST['contact'] ?? null;
$email      = $_POST['email'] ?? '';
$password   = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;
$employeeID = $_POST['employeeID'] ?? '';
$position   = $_POST['position'] ?? '';

// --- Fetch current user info (for email + notifications) ---
$sqlFetch = "SELECT email, first_name FROM users WHERE id=?";
$stmtFetch = $conn->prepare($sqlFetch);
$stmtFetch->bind_param("i", $admin_id);
$stmtFetch->execute();
$resultFetch = $stmtFetch->get_result();
$currentUser = $resultFetch->fetch_assoc();
$currentEmail = $currentUser['email'] ?? "";
$firstNameStored = $currentUser['first_name'] ?? "";
$stmtFetch->close();

// --- Update users table ---
if ($password) {
    $sqlUsers = "UPDATE users SET first_name=?, last_name=?, email=?, password=? WHERE id=?";
    $stmtUsers = $conn->prepare($sqlUsers);
    $stmtUsers->bind_param("ssssi", $firstName, $lastName, $email, $password, $admin_id);
} else {
    $sqlUsers = "UPDATE users SET first_name=?, last_name=?, email=? WHERE id=?";
    $stmtUsers = $conn->prepare($sqlUsers);
    $stmtUsers->bind_param("sssi", $firstName, $lastName, $email, $admin_id);
}
$stmtUsers->execute();
$stmtUsers->close();

// --- Update or insert admin_profile ---
$sqlCheck = "SELECT profile_id FROM admin_profile WHERE user_id=?";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("i", $admin_id);
$stmtCheck->execute();
$result = $stmtCheck->get_result();
$stmtCheck->close();

if ($result->num_rows > 0) {
    $sqlProfile = "UPDATE admin_profile 
                   SET birthday=?, gender=?, address=?, contact=?, avatar=IFNULL(?, avatar) 
                   WHERE user_id=?";
    $stmtProfile = $conn->prepare($sqlProfile);
    $stmtProfile->bind_param("sssssi", $birthday, $gender, $address, $contact, $avatarFileName, $admin_id);
} else {
    $sqlProfile = "INSERT INTO admin_profile (user_id, birthday, gender, address, contact, avatar) 
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmtProfile = $conn->prepare($sqlProfile);
    $stmtProfile->bind_param("isssss", $admin_id, $birthday, $gender, $address, $contact, $avatarFileName);
}
$stmtProfile->execute();
$stmtProfile->close();

// Update or insert employee info
$sqlEmpCheck = "SELECT id FROM admin_employees WHERE user_id=?";
$stmtEmpCheck = $conn->prepare($sqlEmpCheck);
$stmtEmpCheck->bind_param("i", $admin_id);
$stmtEmpCheck->execute();
$resEmpCheck = $stmtEmpCheck->get_result();
$stmtEmpCheck->close();

if ($resEmpCheck->num_rows > 0) {
    $sqlEmp = "UPDATE admin_employees SET employee_id=?, position=? WHERE user_id=?";
    $stmtEmp = $conn->prepare($sqlEmp);
    $stmtEmp->bind_param("ssi", $employeeID, $position, $admin_id);
} else {
    $sqlEmp = "INSERT INTO admin_employees (user_id, employee_id, position) VALUES (?, ?, ?)";
    $stmtEmp = $conn->prepare($sqlEmp);
    $stmtEmp->bind_param("iss", $admin_id, $employeeID, $position);
}
$stmtEmp->execute();
$stmtEmp->close();

// Notifications
$notifMsgProfile = "Your profile information has been updated.";
$stmtNotif = $conn->prepare("INSERT INTO notifications (user_id, category, title, message, type) 
                             VALUES (?, 'profile', 'Profile Updated', ?, 'update')");
$stmtNotif->bind_param("is", $admin_id, $notifMsgProfile);
$stmtNotif->execute();
$stmtNotif->close();

// Password change notification & email
if ($password) {
    $notifMsgPwd = "Your password has been successfully changed.";
    $stmtNotifPwd = $conn->prepare("INSERT INTO notifications (user_id, category, title, message, type) 
                                    VALUES (?, 'account', 'Password Changed', ?, 'password')");
    $stmtNotifPwd->bind_param("is", $admin_id, $notifMsgPwd);
    $stmtNotifPwd->execute();
    $stmtNotifPwd->close();

    // Send Email
    $subject = "Bliss Gateways - Password Changed";
    $body = "
    <html>
    <head><title>Password Changed</title></head>
    <body>
      <p>Hello $firstNameStored,</p>
      <p>Your account password has been successfully changed.</p>
      <p>If you did not perform this action, please contact support immediately.</p>
      <br><p>Regards,<br>Bliss Gateways</p>
    </body>
    </html>";
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: blissgatewaysph@gmail.com\r\n";
    if (!empty($currentEmail)) {
        mail($currentEmail, $subject, $body, $headers);
    }
}

// Build final avatar path for frontend
$finalAvatar = !empty($avatarFileName) 
    ? "/BlissGateways/uploads/avatars/" . $avatarFileName 
    : ($_SESSION['admin_avatar'] ?? "/BlissGateways/assets/icons/profile.png");

echo json_encode([
    "success"    => true,
    "message"    => "Profile updated successfully!",
    "avatar"     => $finalAvatar,
    "employeeID" => $employeeID,
    "position"   => $position
]);

$conn->close();
?>
