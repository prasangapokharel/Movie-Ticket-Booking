<?php
include '../database/config.php';
session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? null;

switch ($action) {
    case 'get_status':
        getSeatStatus();
        break;
    case 'extend_selection':
        extendSelection();
        break;
    case 'release_all':
        releaseAllUserSeats();
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
}

function getSeatStatus() {
    global $conn;
    
    $show_id = $_GET['show_id'] ?? null;
    if (!$show_id) {
        echo json_encode(['error' => 'Show ID required']);
        return;
    }
    
    try {
        // Clean expired selections
        $conn->prepare("DELETE FROM temp_seat_selections WHERE expires_at < NOW()")->execute();
        
        $query = $conn->prepare("
            SELECT 
                s.seat_number,
                s.status as permanent_status,
                tss.user_id as temp_user_id,
                tss.expires_at,
                CASE 
                    WHEN s.status IN ('booked', 'reserved') THEN s.status
                    WHEN tss.seat_number IS NOT NULL THEN 'temp_selected'
                    ELSE 'available'
                END as current_status
            FROM seats s
            LEFT JOIN temp_seat_selections tss ON s.show_id = tss.show_id 
                AND s.seat_number = tss.seat_number 
                AND tss.expires_at > NOW()
            WHERE s.show_id = ?
            ORDER BY s.seat_number
        ");
        
        $query->execute([$show_id]);
        $seats = $query->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'seats' => $seats]);
        
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function extendSelection() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $show_id = $_POST['show_id'] ?? null;
    $seat_number = $_POST['seat_number'] ?? null;
    
    if (!$show_id || !$seat_number) {
        echo json_encode(['success' => false, 'error' => 'Missing parameters']);
        return;
    }
    
    try {
        $expires_at = date('Y-m-d H:i:s', time() + (5 * 60));
        
        $stmt = $conn->prepare("
            UPDATE temp_seat_selections 
            SET expires_at = ?, timestamp = NOW() 
            WHERE user_id = ? AND show_id = ? AND seat_number = ?
        ");
        
        $stmt->execute([$expires_at, $user_id, $show_id, $seat_number]);
        
        echo json_encode(['success' => true]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function releaseAllUserSeats() {
    global $conn;
    
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        return;
    }
    
    $user_id = $_SESSION['user_id'];
    $show_id = $_POST['show_id'] ?? null;
    
    if (!$show_id) {
        echo json_encode(['success' => false, 'error' => 'Show ID required']);
        return;
    }
    
    try {
        $stmt = $conn->prepare("
            DELETE FROM temp_seat_selections 
            WHERE user_id = ? AND show_id = ?
        ");
        
        $stmt->execute([$user_id, $show_id]);
        
        echo json_encode(['success' => true]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>
