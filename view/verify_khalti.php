<?php
include '../database/config.php';
session_start();

// BiraSMS API configuration - UPDATED
define('API_KEY', '3B853539856F3FD36823E959EF82ABF6');
define('ROUTE_ID', 'SI_Alert');
define('CAMPAIGN', 'Default');
define('API_URL', 'https://user.birasms.com/api/smsapi');

$pidx = $_GET['pidx'] ?? '';
$booking_id = $_GET['booking_id'] ?? '';

if (empty($pidx) || empty($booking_id)) {
    header("Location: index.php");
    exit();
}

$secret_key = "live_secret_key_68791341fdd94846a146f0457ff7b455";
$khalti_api_url = "https://khalti.com/api/v2/epayment/";

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

error_log("Khalti Verification - PIDX: $pidx, Booking: $booking_id");
error_log("Response: " . $response);
error_log("Status Code: " . $status_code);

if ($curl_error) {
    error_log("Khalti Verification cURL Error: " . $curl_error);
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Payment verification failed. Please try again.'];
    header("Location: payment.php?booking_id=" . $booking_id);
    exit();
}

// FIXED SMS function using GET method as per BiraSMS API
function sendSMS($phone, $message) {
    // Clean phone number - ensure it starts with 977 for Nepal
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Add country code if not present
    if (!str_starts_with($clean_phone, '977')) {
        if (str_starts_with($clean_phone, '9')) {
            $clean_phone = '977' . $clean_phone;
        } else {
            $clean_phone = '9779' . $clean_phone;
        }
    }
    
    // URL encode the message
    $sms_text = urlencode($message);
    
    // Build API URL using GET method as per BiraSMS documentation
    $api_url = API_URL . "?key=" . API_KEY . 
               "&campaign=" . CAMPAIGN . 
               "&contacts=" . $clean_phone . 
               "&routeid=" . ROUTE_ID . 
               "&msg=" . $sms_text;
    
    error_log("SMS API URL: " . $api_url);
    error_log("SMS API Request - Phone: $clean_phone");
    
    // Submit to server using file_get_contents (GET method)
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'CineBook SMS Service'
        ]
    ]);
    
    $response = @file_get_contents($api_url, false, $context);
    
    error_log("SMS API Response: " . ($response ?: 'No response'));
    
    if ($response === false) {
        error_log("SMS API Error: Failed to send request");
        return false;
    }
    
    // Check if response indicates success
    // BiraSMS typically returns success message or error
    if (stripos($response, 'success') !== false || stripos($response, 'sent') !== false) {
        return true;
    }
    
    return $response;
}

if ($status_code == 200) {
    $response_data = json_decode($response, true);
    $payment_status = $response_data['status'] ?? 'Unknown';
    
    if ($payment_status == 'Completed') {
        try {
            $conn->beginTransaction();
            
            // Get booking details with user info
            $booking_query = $conn->prepare("
                SELECT b.*, u.user_id, u.name, u.phone, s.show_id, m.title as movie_title,
                        t.name as theater_name, s.show_time
                FROM bookings b
                JOIN users u ON b.user_id = u.user_id
                JOIN shows s ON b.show_id = s.show_id
                JOIN movies m ON s.movie_id = m.movie_id
                JOIN theaters t ON s.theater_id = t.theater_id
                WHERE b.booking_id = ?
            ");
            $booking_query->execute([$booking_id]);
            $booking = $booking_query->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            // Get selected seats from session or temp table
            $selected_seats = [];
            if (isset($_SESSION['selected_seats']) && !empty($_SESSION['selected_seats'])) {
                $selected_seats = $_SESSION['selected_seats'];
            } else {
                // Try to get from temp_seat_selections
                $temp_seats_query = $conn->prepare("
                    SELECT seat_number 
                    FROM temp_seat_selections 
                    WHERE user_id = ? AND show_id = ?
                ");
                $temp_seats_query->execute([$booking['user_id'], $booking['show_id']]);
                $selected_seats = $temp_seats_query->fetchAll(PDO::FETCH_COLUMN);
            }
            
            // Update booking status
            $update_booking = $conn->prepare("
                UPDATE bookings
                SET payment_status = 'paid', booking_status = 'Confirmed', payment_id = ?
                WHERE booking_id = ?
            ");
            $update_booking->execute([$pidx, $booking_id]);
            
            // Update or insert seats with booking_id
            if (!empty($selected_seats)) {
                foreach ($selected_seats as $seat) {
                    // Check if seat exists
                    $check_seat = $conn->prepare("
                        SELECT seat_id FROM seats 
                        WHERE show_id = ? AND seat_number = ?
                    ");
                    $check_seat->execute([$booking['show_id'], $seat]);
                    
                    if ($check_seat->rowCount() > 0) {
                        // Update existing seat
                        $update_seat = $conn->prepare("
                            UPDATE seats 
                            SET status = 'booked', booking_id = ?
                            WHERE show_id = ? AND seat_number = ?
                        ");
                        $update_seat->execute([$booking_id, $booking['show_id'], $seat]);
                        error_log("Updated seat $seat for booking $booking_id");
                    } else {
                        // Insert new seat record
                        $insert_seat = $conn->prepare("
                            INSERT INTO seats (show_id, seat_number, status, booking_id, created_at)
                            VALUES (?, ?, 'booked', ?, NOW())
                        ");
                        $insert_seat->execute([$booking['show_id'], $seat, $booking_id]);
                        error_log("Inserted new seat $seat for booking $booking_id");
                    }
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
                $booking['user_id'],
                $booking_id,
                $booking['show_id'],
                $response_data['amount'] / 100
            ]);
            
            // Log payment
            $log_query = $conn->prepare("
                INSERT INTO payment_logs
                (booking_id, user_id, amount, payment_method, payment_id, response_data, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $log_query->execute([
                $booking_id,
                $booking['user_id'],
                $response_data['amount'] / 100,
                'khalti',
                $pidx,
                $response
            ]);
            
            // Clean up temp selections
            $delete_temp = $conn->prepare("
                DELETE FROM temp_seat_selections
                WHERE user_id = ? AND show_id = ?
            ");
            $delete_temp->execute([$booking['user_id'], $booking['show_id']]);
            
            $conn->commit();
            
            // SEND SMS NOTIFICATION - TICKET PURCHASED
            if (!empty($booking['phone'])) {
                $seats_text = !empty($selected_seats) ? implode(', ', $selected_seats) : 'N/A';
                $show_date = date('d M Y', strtotime($booking['show_time']));
                $show_time = date('h:i A', strtotime($booking['show_time']));
                $booking_code = 'CB' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
                
                // Create SMS message for ticket purchase
                $sms_message = "🎬 TICKET PURCHASED!\n";
                $sms_message .= "Booking: {$booking_code}\n";
                $sms_message .= "Movie: {$booking['movie_title']}\n";
                $sms_message .= "Theater: {$booking['theater_name']}\n";
                $sms_message .= "Date: {$show_date}\n";
                $sms_message .= "Time: {$show_time}\n";
                $sms_message .= "Seats: {$seats_text}\n";
                $sms_message .= "Amount: Rs. " . number_format($response_data['amount'] / 100, 2) . "\n";
                $sms_message .= "Show this SMS at theater entrance. Thank you!";
                
                // Send SMS
                $sms_result = sendSMS($booking['phone'], $sms_message);
                
                if ($sms_result === true) {
                    error_log("SMS sent successfully to {$booking['phone']} for booking {$booking_id}");
                    $_SESSION['payment_success'] = "Payment successful! Your booking is confirmed. SMS confirmation sent to " . $booking['phone'];
                } else {
                    error_log("SMS sending failed to {$booking['phone']} for booking {$booking_id}. Response: " . print_r($sms_result, true));
                    $_SESSION['payment_success'] = "Payment successful! Your booking is confirmed. (SMS notification failed - please check your booking details)";
                }
            } else {
                $_SESSION['payment_success'] = "Payment successful! Your booking is confirmed.";
            }
            
            // Ensure redirect to booking_confirmation.php
            header("Location: booking_confirmation.php?booking_id=" . $booking_id);
            exit();
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Payment verification error: " . $e->getMessage());
            $_SESSION['alert'] = ['type' => 'error', 'message' => 'Payment was successful, but there was an error updating your booking. Please contact support.'];
            header("Location: payment.php?booking_id=" . $booking_id);
            exit();
        }
    } else {
        $status_message = match($payment_status) {
            'Pending' => 'Your payment is still processing. We\'ll update your booking once it\'s complete.',
            'Failed' => 'Payment failed. Please try again or use a different payment method.',
            default => 'Payment status: ' . $payment_status
        };
        
        $_SESSION['alert'] = ['type' => 'warning', 'message' => $status_message];
        header("Location: payment.php?booking_id=" . $booking_id);
        exit();
    }
} else {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Payment verification failed. Please try again.'];
    header("Location: payment.php?booking_id=" . $booking_id);
    exit();
}
?>
