<?php
session_start();
header("Content-Type: application/json");

$defaultAvatar = "/BlissGateways/assets/icons/profile.png";

if (isset($_SESSION['admin_avatar']) && !empty($_SESSION['admin_avatar'])) {
    echo json_encode(["avatar_url" => $_SESSION['admin_avatar']]);
    exit;
}

echo json_encode(["avatar_url" => $defaultAvatar]);
