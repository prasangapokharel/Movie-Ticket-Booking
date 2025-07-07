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
    
    // Get all seat statuses for this show
    $seats_query = $conn->prepare("
        SELECT 
            seat_number,
            CASE 
                WHEN status IN ('booked', 'reserved') THEN status
                ELSE 'available'
            END as status
        FROM seats 
        WHERE show_id = ?
        
        UNION
        
        SELECT 
            seat_number,
            'temp_selected' as status
        FROM temp_seat_selections 
        WHERE show_id = ? AND expires_at > NOW()
        
        ORDER BY seat_number
    ");
    $seats_query->execute([$show_id, $show_id]);
    $seats = $seats_query->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($seats);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
