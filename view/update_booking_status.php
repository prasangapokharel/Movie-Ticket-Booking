<?php
include '../database/config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['booking_id']) || !isset($data['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$booking_id = $data['booking_id'];
$status = $data['status'];

// Verify the booking belongs to the user
$user_id = $_SESSION['user_id'];
$booking_query = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ? AND user_id = ?");
$booking_query->execute([$booking_id, $user_id]);
$booking = $booking_query->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid booking']);
    exit();
}

// Update booking status
$update_query = $conn->prepare("UPDATE bookings SET booking_status = ? WHERE booking_id = ?");
$update_query->execute([$status, $booking_id]);

// Return success response
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Booking status updated successfully']);
?>

