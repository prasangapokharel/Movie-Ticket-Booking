<?php
include '../database/config.php';
session_start();
header('Content-Type: application/json');

$show_id = $_GET['show_id'] ?? null;

if (!$show_id) {
    echo json_encode(['error' => 'Show ID required']);
    exit;
}

try {
    // Clean up expired temp selections first
    $cleanup_stmt = $conn->prepare("
        DELETE FROM temp_seat_selections 
        WHERE expires_at < NOW()
    ");
    $cleanup_stmt->execute();
    
    // Get current user ID for comparison
    $current_user_id = $_SESSION['user_id'] ?? null;
    
    // Get all seat statuses for this show
    $seats_query = $conn->prepare("
        SELECT 
            s.seat_number,
            CASE 
                WHEN s.status IN ('booked', 'reserved') THEN s.status
                WHEN tss.seat_number IS NOT NULL AND tss.user_id != ? THEN 'temp_selected'
                WHEN tss.seat_number IS NOT NULL AND tss.user_id = ? THEN 'user_selected'
                ELSE 'available'
            END as status,
            tss.user_id as temp_user_id,
            tss.expires_at
        FROM seats s
        LEFT JOIN temp_seat_selections tss ON s.show_id = tss.show_id 
            AND s.seat_number = tss.seat_number 
            AND tss.expires_at > NOW()
        WHERE s.show_id = ?
        ORDER BY s.seat_number
    ");
    
    $seats_query->execute([$current_user_id, $current_user_id, $show_id]);
    $seats = $seats_query->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'seats' => $seats,
        'current_user' => $current_user_id
    ]);
    
} catch (PDOException $e) {
    error_log("Check seats error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
