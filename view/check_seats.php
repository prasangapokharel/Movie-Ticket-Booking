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
    // Clean up expired temporary selections
    $cleanup = $conn->prepare("DELETE FROM temp_seat_selections WHERE expires_at < NOW()");
    $cleanup->execute();
    
    // Get all seat statuses for this show
    $seats = [];
    
    // Get booked/reserved seats
    $booked_query = $conn->prepare("
        SELECT seat_number, 'booked' as status 
        FROM seats 
        WHERE show_id = ? AND status IN ('booked', 'reserved')
    ");
    $booked_query->execute([$show_id]);
    $booked_seats = $booked_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Get temporarily selected seats
    $temp_query = $conn->prepare("
        SELECT seat_number, 'temp_selected' as status 
        FROM temp_seat_selections 
        WHERE show_id = ? AND expires_at > NOW()
    ");
    $temp_query->execute([$show_id]);
    $temp_seats = $temp_query->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine results
    $all_seats = array_merge($booked_seats, $temp_seats);
    
    echo json_encode($all_seats);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
