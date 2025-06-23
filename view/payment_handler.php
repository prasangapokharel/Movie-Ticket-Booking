<?php
include '../database/config.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

// Khalti configuration
$secret_key = "live_secret_key_68791341fdd94846a146f0457ff7b455";

if ($action === 'initiate_payment') {
    $booking_id = $_POST['booking_id'] ?? '';
    
    if (empty($booking_id)) {
        echo json_encode(['success' => false, 'message' => 'Booking ID required']);
        exit();
    }
    
    // Fetch booking details
    $booking_query = $conn->prepare("
        SELECT b.*, m.title, s.show_time, t.name as theater_name,
               u.name as user_name, u.email as user_email, u.phone as user_phone
        FROM bookings b
        JOIN shows s ON b.show_id = s.show_id
        JOIN movies m ON s.movie_id = m.movie_id
        LEFT JOIN theaters t ON s.theater_id = t.theater_id
        JOIN users u ON b.user_id = u.user_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $booking_query->execute([$booking_id, $user_id]);
    $booking = $booking_query->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found']);
        exit();
    }
    
    // Generate unique purchase order ID
    $purchase_order_id = 'CINE' . $booking_id . '_' . time();
    $amount_in_paisa = $booking['total_price'] * 100;
    
    // Determine base URL
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $script_name = dirname($_SERVER['SCRIPT_NAME']);
    $base_url = $protocol . "://" . $host . rtrim($script_name, '/');
    
    $return_url = $base_url . "/verify_khalti.php?booking_id=" . $booking_id;
    $website_url = $base_url;
    
    // Prepare Khalti request
    $data = [
        "return_url" => $return_url,
        "website_url" => $website_url,
        "amount" => $amount_in_paisa,
        "purchase_order_id" => $purchase_order_id,
        "purchase_order_name" => "Movie Ticket: " . $booking['title'],
        "customer_info" => [
            "name" => $booking['user_name'] ?? "Customer",
            "email" => $booking['user_email'] ?? "customer@example.com",
            "phone" => $booking['user_phone'] ?? "9800000000"
        ]
    ];
    
    // Make API call to Khalti
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://dev.khalti.com/api/v2/epayment/initiate/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Key ' . $secret_key,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($status_code == 200) {
        $response_data = json_decode($response, true);
        if (isset($response_data['payment_url'])) {
            // Update booking with payment info
            $update_query = $conn->prepare("
                UPDATE bookings 
                SET payment_status = 'pending', payment_method = 'khalti', payment_id = ? 
                WHERE booking_id = ?
            ");
            $update_query->execute([$purchase_order_id, $booking_id]);
            
            echo json_encode([
                'success' => true,
                'payment_url' => $response_data['payment_url'],
                'pidx' => $response_data['pidx']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to get payment URL']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Payment initiation failed']);
    }
    
} elseif ($action === 'check_payment_status') {
    $pidx = $_POST['pidx'] ?? '';
    $booking_id = $_POST['booking_id'] ?? '';
    
    if (empty($pidx)) {
        echo json_encode(['success' => false, 'message' => 'Payment ID required']);
        exit();
    }
    
    // Verify payment with Khalti
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://dev.khalti.com/api/v2/epayment/lookup/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['pidx' => $pidx]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Key ' . $secret_key,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($status_code == 200) {
        $response_data = json_decode($response, true);
        $payment_status = $response_data['status'] ?? 'Unknown';
        
        if ($payment_status == 'Completed') {
            // Update booking and seats
            try {
                $conn->beginTransaction();
                
                // Update booking status
                $update_booking = $conn->prepare("
                    UPDATE bookings 
                    SET payment_status = 'paid', booking_status = 'Confirmed'
                    WHERE booking_id = ?
                ");
                $update_booking->execute([$booking_id]);
                
                // Get booking details for seat update
                $booking_query = $conn->prepare("SELECT show_id FROM bookings WHERE booking_id = ?");
                $booking_query->execute([$booking_id]);
                $booking = $booking_query->fetch(PDO::FETCH_ASSOC);
                
                if ($booking) {
                    // Update seats from reserved to booked
                    $update_seats = $conn->prepare("
                        UPDATE seats 
                        SET status = 'booked', booking_id = ? 
                        WHERE show_id = ? AND status = 'reserved'
                    ");
                    $update_seats->execute([$booking_id, $booking['show_id']]);
                    
                    // Clean up temp selections
                    $delete_temp = $conn->prepare("
                        DELETE FROM temp_seat_selections 
                        WHERE user_id = ? AND show_id = ?
                    ");
                    $delete_temp->execute([$user_id, $booking['show_id']]);
                }
                
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment successful!'
                ]);
            } catch (Exception $e) {
                $conn->rollBack();
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment successful but booking update failed'
                ]);
            }
        } else {
            echo json_encode([
                'success' => true,
                'status' => strtolower($payment_status)
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to verify payment']);
    }
}
?>
