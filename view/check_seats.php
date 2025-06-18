<?php
include '../database/config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_GET['show_id'])) {
    echo json_encode(['error' => 'Show ID is required']);
    exit;
}

$show_id = $_GET['show_id'];

try {
    // Get all seats for this show
    $stmt = $conn->prepare("
        SELECT seat_number, status
        FROM seats
        WHERE show_id = :show_id
    ");
    $stmt->bindParam(':show_id', $show_id);
    $stmt->execute();
    $bookedSeats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get temporary selected seats
    $temp_stmt = $conn->prepare("
        SELECT seat_number, user_id, 'temp_selected' as status
        FROM temp_seat_selections
        WHERE show_id = :show_id
        AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $temp_stmt->bindParam(':show_id', $show_id);
    $temp_stmt->execute();
    $tempSeats = $temp_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combine both results
    $allSeats = array_merge($bookedSeats, $tempSeats);
    
    // Format the response
    $response = [];
    foreach ($allSeats as $seat) {
        $response[] = [
            'seat_number' => $seat['seat_number'],
            'status' => $seat['status']
        ];
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

