<?php
include '../database/config.php';
session_start();
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
    $conn->beginTransaction();
    
    // Verify user exists
    $user_check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $user_check->execute([$user_id]);
    if (!$user_check->fetch()) {
        throw new Exception('Invalid user session');
    }
    
    // Verify show exists
    $show_check = $conn->prepare("SELECT show_id FROM shows WHERE show_id = ?");
    $show_check->execute([$show_id]);
    if (!$show_check->fetch()) {
        throw new Exception('Invalid show');
    }
    
    // Check if seat is permanently booked
    $seat_check = $conn->prepare("
        SELECT status FROM seats 
        WHERE show_id = ? AND seat_number = ? AND status IN ('booked', 'reserved')
    ");
    $seat_check->execute([$show_id, $seat_number]);
    if ($seat_check->fetch()) {
        throw new Exception('Seat is already booked');
    }
    
    if ($action === 'select') {
        // Check if seat is temporarily selected by another user
        $temp_check = $conn->prepare("
            SELECT user_id FROM temp_seat_selections 
            WHERE show_id = ? AND seat_number = ? AND expires_at > NOW() AND user_id != ?
        ");
        $temp_check->execute([$show_id, $seat_number, $user_id]);
        if ($temp_check->fetch()) {
            throw new Exception('Seat is temporarily selected by another user');
        }
        
        // Add/update temporary seat selection with 5-minute expiry
        $expires_at = date('Y-m-d H:i:s', time() + (5 * 60));
        
        $insert_temp = $conn->prepare("
            INSERT INTO temp_seat_selections (user_id, show_id, seat_number, expires_at, timestamp)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                expires_at = VALUES(expires_at), 
                timestamp = NOW()
        ");
        $insert_temp->execute([$user_id, $show_id, $seat_number, $expires_at]);
        
    } elseif ($action === 'deselect') {
        // Remove temporary seat selection only if it belongs to current user
        $delete_temp = $conn->prepare("
            DELETE FROM temp_seat_selections 
            WHERE user_id = ? AND show_id = ? AND seat_number = ?
        ");
        $delete_temp->execute([$user_id, $show_id, $seat_number]);
    }
    
    $conn->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Temp seat update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
