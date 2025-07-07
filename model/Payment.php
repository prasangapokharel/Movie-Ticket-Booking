<?php
include '../database/config.php';
session_start();

// BiraSMS API configuration
define('SMS_API_KEY', '3B853539856F3FD36823E959EF82ABF6');
define('SMS_ROUTE_ID', 'SI_Alert');
define('SMS_CAMPAIGN', 'Default');
define('SMS_API_URL', 'https://user.birasms.com/api/smsapi');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['booking_id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

$booking_query = $conn->prepare("
    SELECT b.booking_id, b.show_id, b.total_price, b.booking_status, b.payment_status, b.created_at,
           m.title, s.show_time, t.name as theater_name,
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
    header("Location: my_bookings.php");
    exit();
}

$is_paid = ($booking['payment_status'] === 'paid');

$booking_timestamp = $_SESSION['booking_timestamp'] ?? 0;
$current_time = time();
$timeout = 15 * 60;

if (!$is_paid && ($current_time - $booking_timestamp) > $timeout) {
    try {
        $conn->beginTransaction();
        
        $update_booking = $conn->prepare("
            UPDATE bookings 
            SET booking_status = 'Expired', payment_status = 'failed'
            WHERE booking_id = ? AND booking_status = 'Pending'
        ");
        $update_booking->execute([$booking_id]);
        
        $release_seats = $conn->prepare("
            UPDATE seats 
            SET status = 'available'
            WHERE show_id = ? AND status = 'reserved'
        ");
        $release_seats->execute([$booking['show_id']]);
        
        $conn->commit();
        
        $_SESSION['payment_error'] = "Your booking session has expired. Please try again.";
        header("Location: my_bookings.php");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error releasing expired seats: " . $e->getMessage());
    }
}

// SMS function using POST method with cURL
function sendTicketSMS($phone, $message) {
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
    
    // API parameters
    $api_key = SMS_API_KEY;
    $contacts = $clean_phone;
    $from = SMS_ROUTE_ID;
    $campaign = SMS_CAMPAIGN;
    
    error_log("SMS API Request - Phone: $clean_phone");
    error_log("SMS Message: " . $message);
    
    // Submit to server using cURL POST method
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SMS_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "key=".$api_key."&campaign=".$campaign."&contacts=".$contacts."&routeid=".$from."&msg=".$sms_text);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'CineBook SMS Service');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    error_log("SMS API Response: " . ($response ?: 'No response'));
    error_log("SMS API HTTP Code: " . $httpCode);
    
    if ($curl_error) {
        error_log("SMS API cURL Error: " . $curl_error);
        return "cURL Error: " . $curl_error;
    }
    
    if ($response === false) {
        error_log("SMS API Error: Failed to send request");
        return "Failed to send SMS request";
    }
    
    // Check if response indicates success
    if (stripos($response, 'success') !== false || 
        stripos($response, 'sent') !== false || 
        stripos($response, 'submitted') !== false ||
        stripos($response, 'ok') !== false) {
        return true;
    }
    
    return $response;
}

function getBookingSeats($conn, $booking_id) {
    $booking_query = $conn->prepare("SELECT show_id, total_price FROM bookings WHERE booking_id = ?");
    $booking_query->execute([$booking_id]);
    $booking = $booking_query->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        return [];
    }
    
    $show_id = $booking['show_id'];
    
    if (isset($_SESSION['selected_seats']) && !empty($_SESSION['selected_seats'])) {
        return $_SESSION['selected_seats'];
    }
    
    $seats_query = $conn->prepare("
        SELECT seat_number 
        FROM seats 
        WHERE show_id = ? AND (status = 'reserved' OR status = 'booked')
        ORDER BY seat_number
    ");
    $seats_query->execute([$show_id]);
    return $seats_query->fetchAll(PDO::FETCH_COLUMN);
}

$seats = getBookingSeats($conn, $booking_id);
$seats_text = implode(', ', $seats);

$show_date = date('D, d M Y', strtotime($booking['show_time']));
$show_time = date('h:i A', strtotime($booking['show_time']));

$amount_in_paisa = $booking['total_price'] * 100;
$purchase_order_id = 'CINE' . $booking_id . '_' . time();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'check_payment_status_db') {
        $status_query = $conn->prepare("
            SELECT payment_status, booking_status 
            FROM bookings 
            WHERE booking_id = ?
        ");
        $status_query->execute([$booking_id]);
        $status_data = $status_query->fetch(PDO::FETCH_ASSOC);
        
        if ($status_data && $status_data['payment_status'] === 'paid') {
            echo json_encode([
                'success' => true,
                'is_paid' => true,
                'booking_status' => $status_data['booking_status']
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'is_paid' => false,
                'booking_status' => $status_data['booking_status'] ?? 'Pending'
            ]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'initiate_payment') {
        $url = "https://dev.khalti.com/api/v2/epayment/initiate/";
        $secret_key = "live_secret_key_68791341fdd94846a146f0457ff7b455";
        
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'];
        $script_name = dirname($_SERVER['SCRIPT_NAME']);
        
        if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
            $base_path = $protocol . "://" . $host;
            
            if ($script_name !== '/' && $script_name !== '\\') {
                $script_name = rtrim($script_name, '/\\');
                $base_path .= $script_name;
            }
            
            $base_url = dirname($base_path);
        } else {
            $base_url = $protocol . "://" . $host . $script_name;
        }
        
        $base_url = rtrim($base_url, '/');
        
        $return_url = $base_url . "/templates/verify_khalti.php?booking_id=" . $booking_id;
        $website_url = $base_url;
        
        error_log("Return URL: " . $return_url);
        error_log("Website URL: " . $website_url);
        
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
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
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
        $curl_error = curl_error($ch);
        
        curl_close($ch);
        
        if ($curl_error) {
            error_log("Khalti cURL Error: " . $curl_error);
            echo json_encode([
                'success' => false,
                'message' => "cURL Error: " . $curl_error
            ]);
            exit;
        } else {
            $response_data = json_decode($response, true);
            error_log("Khalti Response: " . $response);
            
            if ($status_code == 200 && isset($response_data['payment_url'])) {
                $payment_url = $response_data['payment_url'];
                $pidx = $response_data['pidx'];
                
                $update_query = $conn->prepare("
                    UPDATE bookings 
                    SET payment_status = 'pending', payment_method = 'khalti', payment_id = ?
                    WHERE booking_id = ?
                ");
                $update_query->execute([$purchase_order_id, $booking_id]);
                
                $log_query = $conn->prepare("
                    INSERT INTO payment_logs 
                    (booking_id, user_id, amount, payment_method, payment_id, response_data, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $log_query->execute([
                    $booking_id,
                    $user_id,
                    $booking['total_price'],
                    'khalti',
                    $purchase_order_id,
                    $response
                ]);
                
                $_SESSION['khalti_pidx'] = $pidx;
                $_SESSION['khalti_purchase_order_id'] = $purchase_order_id;
                
                echo json_encode([
                    'success' => true,
                    'payment_url' => $payment_url,
                    'pidx' => $pidx
                ]);
                exit;
            } else {
                $error_message = "Payment initiation failed: " . ($response_data['detail'] ?? 'Unknown error');
                error_log("Khalti Error: " . $error_message);
                
                echo json_encode([
                    'success' => false,
                    'message' => $error_message
                ]);
                exit;
            }
        }
    }
    
    if ($_POST['action'] === 'check_payment_status') {
        $pidx = $_POST['pidx'] ?? '';
        
        if (empty($pidx)) {
            echo json_encode([
                'success' => false,
                'message' => 'Missing payment ID'
            ]);
            exit;
        }
        
        $secret_key = "live_secret_key_68791341fdd94846a146f0457ff7b455";
        $url = "https://dev.khalti.com/api/v2/epayment/lookup/";
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
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
        $curl_error = curl_error($ch);
        
        curl_close($ch);
        
        error_log("Khalti Verification Response: " . $response);
        
        if ($curl_error) {
            echo json_encode([
                'success' => false,
                'message' => 'Verification failed: ' . $curl_error
            ]);
            exit;
        }
        
        $response_data = json_decode($response, true);
        
        if ($status_code == 200) {
            $payment_status = $response_data['status'] ?? 'Unknown';
            
            if ($payment_status == 'Completed') {
                try {
                    $conn->beginTransaction();
                    
                    // Get user phone number from users table
                    $user_query = $conn->prepare("
                        SELECT u.phone, u.name, b.show_id, m.title as movie_title, t.name as theater_name, s.show_time
                        FROM users u
                        JOIN bookings b ON u.user_id = b.user_id
                        JOIN shows s ON b.show_id = s.show_id
                        JOIN movies m ON s.movie_id = m.movie_id
                        JOIN theaters t ON s.theater_id = t.theater_id
                        WHERE b.booking_id = ?
                    ");
                    $user_query->execute([$booking_id]);
                    $user_data = $user_query->fetch(PDO::FETCH_ASSOC);
                    
                    $update_query = $conn->prepare("
                        UPDATE bookings
                        SET payment_status = 'paid', booking_status = 'Confirmed'
                        WHERE booking_id = ?
                    ");
                    $update_query->execute([$booking_id]);
                    
                    $update_seats = $conn->prepare("
                        UPDATE seats
                        SET status = 'booked', booking_id = ?
                        WHERE show_id = ? AND status = 'reserved'
                    ");
                    $update_seats->execute([$booking_id, $booking['show_id']]);
                    
                    $payment_query = $conn->prepare("
                        INSERT INTO payment
                        (user_id, booking_id, show_id, amount, payment_method, status, created_at)
                        VALUES (?, ?, ?, ?, 'Khalti', 'Paid', NOW())
                    ");
                    $payment_query->execute([
                        $user_id,
                        $booking_id,
                        $booking['show_id'],
                        $booking['total_price']
                    ]);
                    
                    $log_query = $conn->prepare("
                        INSERT INTO payment_logs
                        (booking_id, user_id, amount, payment_method, payment_id, response_data, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $log_query->execute([
                        $booking_id,
                        $user_id,
                        $booking['total_price'],
                        'khalti',
                        $pidx,
                        $response
                    ]);
                    
                    $conn->commit();
                    
                    // SEND SMS NOTIFICATION - TICKET PURCHASED
                    if (!empty($user_data['phone'])) {
                        $show_date = date('d M Y', strtotime($user_data['show_time']));
                        $show_time_formatted = date('h:i A', strtotime($user_data['show_time']));
                        $booking_code = 'CB' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);
                        
                        // Create SMS message for ticket purchase
                        $sms_message = "TICKET PURCHASED!\n";
                        $sms_message .= "Booking: {$booking_code}\n";
                        $sms_message .= "Movie: {$user_data['movie_title']}\n";
                        $sms_message .= "Theater: {$user_data['theater_name']}\n";
                        $sms_message .= "Date: {$show_date}\n";
                        $sms_message .= "Time: {$show_time_formatted}\n";
                        $sms_message .= "Seats: {$seats_text}\n";
                        $sms_message .= "Amount: Rs. " . number_format($booking['total_price'], 2) . "\n";
                        $sms_message .= "Show this SMS at theater entrance. Thank you!";
                        
                        // Send SMS
                        $sms_result = sendTicketSMS($user_data['phone'], $sms_message);
                        
                        if ($sms_result === true) {
                            error_log("SMS sent successfully to {$user_data['phone']} for booking {$booking_id}");
                            $_SESSION['payment_success'] = "Payment successful! Your booking is confirmed. SMS confirmation sent to " . $user_data['phone'];
                        } else {
                            error_log("SMS sending failed to {$user_data['phone']} for booking {$booking_id}. Response: " . print_r($sms_result, true));
                            $_SESSION['payment_success'] = "Payment successful! Your booking is confirmed. (SMS notification failed - please check your booking details)";
                        }
                    } else {
                        $_SESSION['payment_success'] = "Payment successful! Your booking is confirmed.";
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'status' => 'completed',
                        'redirect' => 'booking_confirmation.php?booking_id=' . $booking_id
                    ]);
                } catch (Exception $e) {
                    $conn->rollBack();
                    error_log("Database error in payment status check: " . $e->getMessage());
                    
                    echo json_encode([
                        'success' => false,
                        'message' => 'Payment was successful, but there was an error updating your booking.'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => true,
                    'status' => strtolower($payment_status)
                ]);
            }
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to verify payment status'
            ]);
        }
        
        exit;
    }
}
?>