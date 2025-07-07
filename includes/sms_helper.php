<?php
// SMS Helper Functions for CineBook - FIXED VERSION

// BiraSMS API configuration
define('SMS_API_KEY', '3B853539856F3FD36823E959EF82ABF6');
define('SMS_ROUTE_ID', 'SI_Alert');
define('SMS_CAMPAIGN', 'Default');
define('SMS_API_URL', 'https://user.birasms.com/api/smsapi');

/**
 * Send SMS using BiraSMS API (POST method with cURL)
 * @param string $phone Phone number
 * @param string $message SMS message
 * @return bool|string True on success, error message on failure
 */
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
    // BiraSMS typically returns success message or error
    if (stripos($response, 'success') !== false || 
        stripos($response, 'sent') !== false || 
        stripos($response, 'submitted') !== false ||
        stripos($response, 'ok') !== false) {
        return true;
    }
    
    // Return the actual response for debugging
    return $response;
}

/**
 * Create ticket purchase SMS message
 * @param array $booking_data Booking information
 * @param array $seats Selected seats
 * @param float $amount Payment amount
 * @return string SMS message
 */
function createTicketSMS($booking_data, $seats, $amount) {
    $seats_text = !empty($seats) ? implode(', ', $seats) : 'N/A';
    $show_date = date('d M Y', strtotime($booking_data['show_time']));
    $show_time = date('h:i A', strtotime($booking_data['show_time']));
    $booking_code = 'CB' . str_pad($booking_data['booking_id'], 6, '0', STR_PAD_LEFT);
    
    $message = "TICKET PURCHASED!\n";
    $message .= "Booking: {$booking_code}\n";
    $message .= "Movie: {$booking_data['movie_title']}\n";
    $message .= "Theater: {$booking_data['theater_name']}\n";
    $message .= "Date: {$show_date}\n";
    $message .= "Time: {$show_time}\n";
    $message .= "Seats: {$seats_text}\n";
    $message .= "Amount: Rs. " . number_format($amount, 2) . "\n";
    $message .= "Show this SMS at theater entrance. Thank you!";
    
    return $message;
}

/**
 * Test SMS function
 * @param string $phone Phone number to test
 * @return bool|string Test result
 */
function testSMS($phone) {
    $test_message = "Test SMS from CineBook. Your SMS service is working correctly!";
    return sendTicketSMS($phone, $test_message);
}
?>
