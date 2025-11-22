<?php
session_start();
require_once "../db.php";

header("Content-Type: application/json");

$admin_id = $_SESSION['user_id'] ?? null;
if (!$admin_id) {
    echo json_encode(["success" => false, "message" => "Not logged in"]);
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Join users + admin_profile
    $sql = "SELECT 
                u.first_name, 
                u.last_name, 
                u.email,
                ap.employee_id, 
                ap.position, 
                ap.birthday, 
                ap.gender, 
                ap.address, 
                ap.contact, 
                ap.avatar
            FROM users u
            LEFT JOIN admin_profile ap ON ap.user_id = u.id
            WHERE u.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    if ($data) {
        echo json_encode([
            "success"    => true,
            "employeeID" => $data['employee_id'] ?? "",
            "position"   => $data['position'] ?? "",
            "firstName"  => $data['first_name'] ?? "",
            "lastName"   => $data['last_name'] ?? "",
            "birthday"   => $data['birthday'] ?? "",
            "gender"     => $data['gender'] ?? "",
            "address"    => $data['address'] ?? "",
            "contact"    => $data['contact'] ?? "",
            "email"      => $data['email'] ?? "",
            "avatar"     => $data['avatar'] ?: "/BlissGateways/assets/icons/profile.png"
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Profile not found"]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

$conn->close();
exit;
?>
