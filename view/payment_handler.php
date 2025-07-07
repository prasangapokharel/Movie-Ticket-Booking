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

// Khalti configuration - PRODUCTION KEYS
$secret_key = "live_secret_key_68791341fdd94846a146f0457ff7b455";
$khalti_api_url = "https://khalti.com/api/v2/epayment/"; // Production URL

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
        WHERE b.booking_id = ? AND b.user_id = ? AND b.booking_status = 'Pending'
    ");
    $booking_query->execute([$booking_id, $user_id]);
    $booking = $booking_query->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking not found or already processed']);
        exit();
    }
    
    // Check if temp seats are still valid
    $temp_seats_query = $conn->prepare("
        SELECT seat_number FROM temp_seat_selections 
        WHERE user_id = ? AND show_id = ? AND expires_at > NOW()
    ");
    $temp_seats_query->execute([$user_id, $booking['show_id']]);
    $temp_seats = $temp_seats_query->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($temp_seats)) {
        // Seats expired, cancel booking
        $cancel_booking = $conn->prepare("
            UPDATE bookings SET booking_status = 'Cancelled' WHERE booking_id = ?
        ");
        $cancel_booking->execute([$booking_id]);
        
        echo json_encode(['success' => false, 'message' => 'Seat selection expired. Please select seats again.']);
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
        CURLOPT_URL => $khalti_api_url . "initiate/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Key ' . $secret_key,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    // Log the request and response for debugging
    error_log("Khalti Payment Initiation - Booking ID: $booking_id");
    error_log("Request Data: " . json_encode($data));
    error_log("Response: " . $response);
    error_log("Status Code: " . $status_code);
    
    if ($curl_error) {
        error_log("Khalti cURL Error: " . $curl_error);
        echo json_encode(['success' => false, 'message' => 'Payment service unavailable']);
        exit();
    }
    
    if ($status_code == 200) {
        $response_data = json_decode($response, true);
        if (isset($response_data['payment_url'])) {
            // Update booking with payment info
            $update_query = $conn->prepare("
                UPDATE bookings 
                SET payment_method = 'khalti', payment_id = ? 
                WHERE booking_id = ?
            ");
            $update_query->execute([$purchase_order_id, $booking_id]);
            
            // Log payment initiation
            $log_query = $conn->prepare("
                INSERT INTO payment_logs 
                (booking_id, user_id, amount, payment_method, payment_id, response_data, created_at) 
                VALUES (?, ?, ?, 'khalti', ?, ?, NOW())
            ");
            $log_query->execute([
                $booking_id,
                $user_id,
                $booking['total_price'],
                $purchase_order_id,
                $response
            ]);
            
            echo json_encode([
                'success' => true,
                'payment_url' => $response_data['payment_url'],
                'pidx' => $response_data['pidx']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to get payment URL']);
        }
    } else {
        $error_response = json_decode($response, true);
        $error_message = $error_response['detail'] ?? 'Payment initiation failed';
        echo json_encode(['success' => false, 'message' => $error_message]);
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
        CURLOPT_URL => $khalti_api_url . "lookup/",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['pidx' => $pidx]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Key ' . $secret_key,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    error_log("Khalti Payment Status Check - PIDX: $pidx");
    error_log("Response: " . $response);
    error_log("Status Code: " . $status_code);
    
    if ($curl_error) {
        echo json_encode(['success' => false, 'message' => 'Payment verification failed']);
        exit();
    }
    
    if ($status_code == 200) {
        $response_data = json_decode($response, true);
        $payment_status = $response_data['status'] ?? 'Unknown';
        
        if ($payment_status == 'Completed') {
            // ONLY NOW CONFIRM THE BOOKING AND RESERVE SEATS
            try {
                $conn->beginTransaction();
                
                // Get temp seats for this booking
                $temp_seats_query = $conn->prepare("
                    SELECT seat_number FROM temp_seat_selections 
                    WHERE user_id = ? AND show_id = (SELECT show_id FROM bookings WHERE booking_id = ?)
                ");
                $temp_seats_query->execute([$user_id, $booking_id]);
                $temp_seats = $temp_seats_query->fetchAll(PDO::FETCH_COLUMN);
                
                if (empty($temp_seats)) {
                    throw new Exception("No seats found for this booking");
                }
                
                // Update booking status to CONFIRMED
                $update_booking = $conn->prepare("
                    UPDATE bookings 
                    SET payment_status = 'paid', booking_status = 'Confirmed', payment_id = ?
                    WHERE booking_id = ?
                ");
                $update_booking->execute([$pidx, $booking_id]);
                
                // Get booking details for seat update
                $booking_query = $conn->prepare("SELECT show_id FROM bookings WHERE booking_id = ?");
                $booking_query->execute([$booking_id]);
                $booking = $booking_query->fetch(PDO::FETCH_ASSOC);
                
                if ($booking) {
                    // NOW actually book the seats
                    foreach ($temp_seats as $seat) {
                        // Insert or update seat as BOOKED
                        $check_seat = $conn->prepare("
                            SELECT seat_id FROM seats 
                            WHERE show_id = ? AND seat_number = ?
                        ");
                        $check_seat->execute([$booking['show_id'], $seat]);
                        $existing_seat = $check_seat->fetch();
                        
                        if ($existing_seat) {
                            // Update existing seat
                            $update_seat = $conn->prepare("
                                UPDATE seats 
                                SET status = 'booked', booking_id = ? 
                                WHERE show_id = ? AND seat_number = ?
                            ");
                            $update_seat->execute([$booking_id, $booking['show_id'], $seat]);
                        } else {
                            // Insert new seat
                            $insert_seat = $conn->prepare("
                                INSERT INTO seats (show_id, seat_number, status, booking_id, created_at)
                                VALUES (?, ?, 'booked', ?, NOW())
                            ");
                            $insert_seat->execute([$booking['show_id'], $seat, $booking_id]);
                        }
                    }
                    
                    // Insert payment record
                    $payment_query = $conn->prepare("
                        INSERT INTO payment 
                        (user_id, booking_id, show_id, amount, payment_method, status, created_at) 
                        VALUES (?, ?, ?, ?, 'Khalti', 'Paid', NOW())
                        ON DUPLICATE KEY UPDATE status = 'Paid'
                    ");
                    $payment_query->execute([
                        $user_id,
                        $booking_id,
                        $booking['show_id'],
                        $response_data['amount'] / 100 // Convert from paisa to rupees
                    ]);
                    
                    // Clean up temp selections
                    $delete_temp = $conn->prepare("
                        DELETE FROM temp_seat_selections 
                        WHERE user_id = ? AND show_id = ?
                    ");
                    $delete_temp->execute([$user_id, $booking['show_id']]);
                    
                    // Log successful payment
                    $log_query = $conn->prepare("
                        INSERT INTO payment_logs 
                        (booking_id, user_id, amount, payment_method, payment_id, response_data, created_at) 
                        VALUES (?, ?, ?, 'khalti', ?, ?, NOW())
                    ");
                    $log_query->execute([
                        $booking_id,
                        $user_id,
                        $response_data['amount'] / 100,
                        $pidx,
                        $response
                    ]);
                }
                
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'status' => 'completed',
                    'message' => 'Payment successful! Your seats are now confirmed.'
                ]);
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Payment completion error: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment successful but booking confirmation failed'
                ]);
            }
        } else {
            echo json_encode([
                'success' => true,
                'status' => strtolower($payment_status)
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to verify payment status']);
    }
}
?>
