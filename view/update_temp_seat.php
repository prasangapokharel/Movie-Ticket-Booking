<?php
include '../database/config.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

// Check if required parameters are provided
if (!isset($_POST['show_id']) || !isset($_POST['seat_number']) || !isset($_POST['action'])) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$show_id = $_POST['show_id'];
$seat_number = $_POST['seat_number'];
$action = $_POST['action'];
$user_id = $_SESSION['user_id'];

try {
    // Check if the seat is already booked
    $check_stmt = $conn->prepare("
        SELECT status 
        FROM seats 
        WHERE show_id = :show_id 
        AND seat_number = :seat_number
        AND status = 'booked'
    ");
    $check_stmt->bindParam(':show_id', $show_id);
    $check_stmt->bindParam(':seat_number', $seat_number);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        echo json_encode(['error' => 'Seat is already booked']);
        exit;
    }
    
    // Check if the seat is temporarily selected by another user
    if ($action === 'select') {
        $temp_check = $conn->prepare("
            SELECT user_id 
            FROM temp_seat_selections 
            WHERE show_id = :show_id 
            AND seat_number = :seat_number
            AND user_id != :user_id
            AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ");
        $temp_check->bindParam(':show_id', $show_id);
        $temp_check->bindParam(':seat_number', $seat_number);
        $temp_check->bindParam(':user_id', $user_id);
        $temp_check->execute();
        
        if ($temp_check->rowCount() > 0) {
            echo json_encode(['error' => 'Seat is temporarily selected by another user']);
            exit;
        }
    }
    
    if ($action === 'select') {
        // Insert or update temporary selection
        $stmt = $conn->prepare("
            INSERT INTO temp_seat_selections (user_id, show_id, seat_number, timestamp)
            VALUES (:user_id, :show_id, :seat_number, NOW())
            ON DUPLICATE KEY UPDATE timestamp = NOW()
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':show_id', $show_id);
        $stmt->bindParam(':seat_number', $seat_number);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Seat temporarily selected']);
    } else if ($action === 'deselect') {
        // Delete temporary selection
        $stmt = $conn->prepare("
            DELETE FROM temp_seat_selections
            WHERE user_id = :user_id
            AND show_id = :show_id
            AND seat_number = :seat_number
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':show_id', $show_id);
        $stmt->bindParam(':seat_number', $seat_number);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Seat deselected']);
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

