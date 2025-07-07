<?php
// Test SMS functionality - UPDATED
include 'includes/sms_helper.php';

// Test phone number (replace with your number)
$test_phone = '9765470926'; // Your phone number

echo "Testing SMS functionality...\n";
echo "Phone: $test_phone\n";
echo "API Key: " . SMS_API_KEY . "\n";
echo "Route ID: " . SMS_ROUTE_ID . "\n";
echo "Campaign: " . SMS_CAMPAIGN . "\n";
echo "API URL: " . SMS_API_URL . "\n\n";

// Simple test message first
$simple_message = "Hello from CineBook! This is a test message.";
echo "Sending simple test message...\n";

$result = sendTicketSMS($test_phone, $simple_message);

if ($result === true) {
    echo "✅ Simple SMS sent successfully!\n\n";
} else {
    echo "❌ Simple SMS failed: " . $result . "\n\n";
}

// Test with ticket purchase message
$booking_data = [
    'booking_id' => 123,
    'movie_title' => 'Iron Man 4',
    'theater_name' => 'QFX Cinema',
    'show_time' => '2025-07-17 15:00:00'
];

$seats = ['A1', 'A2'];
$amount = 620.00;

$ticket_message = createTicketSMS($booking_data, $seats, $amount);
echo "Ticket SMS Message:\n";
echo $ticket_message . "\n\n";

echo "Sending ticket SMS...\n";
$ticket_result = sendTicketSMS($test_phone, $ticket_message);

if ($ticket_result === true) {
    echo "✅ Ticket SMS sent successfully!\n";
} else {
    echo "❌ Ticket SMS failed: " . $ticket_result . "\n";
}

// Debug information
echo "\n=== DEBUG INFO ===\n";
echo "Clean phone number: ";
$clean_phone = preg_replace('/[^0-9]/', '', $test_phone);
if (!str_starts_with($clean_phone, '977')) {
    if (str_starts_with($clean_phone, '9')) {
        $clean_phone = '977' . $clean_phone;
    } else {
        $clean_phone = '9779' . $clean_phone;
    }
}
echo $clean_phone . "\n";

echo "URL encoded message: " . urlencode($simple_message) . "\n";
echo "POST data would be: key=" . SMS_API_KEY . "&campaign=" . SMS_CAMPAIGN . "&contacts=" . $clean_phone . "&routeid=" . SMS_ROUTE_ID . "&msg=" . urlencode($simple_message) . "\n";
?>
