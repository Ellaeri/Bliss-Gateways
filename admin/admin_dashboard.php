<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "bliss_gateways";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    echo json_encode(["error" => "DB connection failed: " . $conn->connect_error]);
    exit;
}

// Total Users
$result = $conn->query("SELECT COUNT(*) AS total_users FROM users WHERE role='client'");
$total_users = $result ? ($result->fetch_assoc()['total_users'] ?? 0) : 0;

// Fetching bookings + payments
function fetchBookingsWithPayments($conn, $table) {
    $data = [];
    $sql = "
        SELECT b.id, b.booking_id, b.customer_name, b.destination, b.date, b.amount, b.status,
               p.amount_due, p.amount_paid, p.status AS payment_status
        FROM {$table} b
        LEFT JOIN payments p ON b.booking_id = p.booking_id
        ORDER BY b.id DESC
    ";
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}

// Fetch all bookings
$flights      = fetchBookingsWithPayments($conn, "flight_bookings");
$tours        = fetchBookingsWithPayments($conn, "tour_bookings");
$kabayans     = fetchBookingsWithPayments($conn, "kabayan_bookings");
$itineraries  = fetchBookingsWithPayments($conn, "itinerary_bookings");

// Total Revenue (sum of amount_paid from verified payments)
$result = $conn->query("SELECT SUM(amount_paid) AS total_revenue FROM payments WHERE status='Verified'");
$total_revenue = $result ? ($result->fetch_assoc()['total_revenue'] ?? 0) : 0;

// Final response
echo json_encode([
    "total_users"       => $total_users,
    "flight_bookings"   => $flights,
    "tour_bookings"     => $tours,
    "kabayan_bookings"  => $kabayans,
    "itinerary_bookings"=> $itineraries,
    "total_revenue"     => $total_revenue
]);

$conn->close();
?>
