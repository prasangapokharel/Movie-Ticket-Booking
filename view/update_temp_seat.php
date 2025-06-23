<?php
session_start();
include '../database/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$show_id = $_POST['show_id'] ?? null;
$seat_number = $_POST['seat_number'] ?? null;
$action = $_POST['action'] ?? null;

if (!$show_id || !$seat_number || !$action) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

try {
    // Clean up expired selections first
    $cleanup = $conn->prepare("DELETE FROM temp_seat_selections WHERE expires_at < NOW()");
    $cleanup->execute();

    if ($action === 'select') {
        // Check if seat is already booked
        $check_booked = $conn->prepare("SELECT COUNT(*) FROM seats WHERE show_id = ? AND seat_number = ? AND status = 'booked'");
        $check_booked->execute([$show_id, $seat_number]);
        
        if ($check_booked->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Seat is already booked']);
            exit;
        }

        // Check if seat is temporarily selected by another user
        $check_temp = $conn->prepare("
            SELECT COUNT(*) FROM temp_seat_selections 
            WHERE show_id = ? AND seat_number = ? AND user_id != ? AND expires_at > NOW()
        ");
        $check_temp->execute([$show_id, $seat_number, $user_id]);
        
        if ($check_temp->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Seat is temporarily selected by another user']);
            exit;
        }

        // Add or update temporary seat selection with 10-minute expiration
        $stmt = $conn->prepare("
            INSERT INTO temp_seat_selections (user_id, show_id, seat_number, expires_at)
            VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
            ON DUPLICATE KEY UPDATE 
            expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
        ");
        $stmt->execute([$user_id, $show_id, $seat_number]);
        
    } elseif ($action === 'deselect') {
        // Remove temporary seat selection
        $stmt = $conn->prepare("
            DELETE FROM temp_seat_selections 
            WHERE user_id = ? AND show_id = ? AND seat_number = ?
        ");
        $stmt->execute([$user_id, $show_id, $seat_number]);
    }
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
