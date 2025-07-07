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
    // Verify user exists
    $user_check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $user_check->execute([$user_id]);
    if (!$user_check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Invalid user session']);
        exit;
    }
    
    // Verify show exists
    $show_check = $conn->prepare("SELECT show_id FROM shows WHERE show_id = ?");
    $show_check->execute([$show_id]);
    if (!$show_check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Invalid show']);
        exit;
    }
    
    if ($action === 'select') {
        // Add temporary seat selection with 5-minute expiry
        $expires_at = date('Y-m-d H:i:s', time() + (5 * 60));
        
        $insert_temp = $conn->prepare("
            INSERT INTO temp_seat_selections (user_id, show_id, seat_number, expires_at, timestamp)
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
            expires_at = ?, timestamp = NOW()
        ");
        $insert_temp->execute([$user_id, $show_id, $seat_number, $expires_at, $expires_at]);
        
    } elseif ($action === 'deselect') {
        // Remove temporary seat selection
        $delete_temp = $conn->prepare("
            DELETE FROM temp_seat_selections 
            WHERE user_id = ? AND show_id = ? AND seat_number = ?
        ");
        $delete_temp->execute([$user_id, $show_id, $seat_number]);
    }
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    error_log("Temp seat update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
