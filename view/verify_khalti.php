<?php
include '../database/config.php';
session_start();

// Enable error logging
error_log("Starting Khalti verification process");

// Set content type to JSON for AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
  header('Content-Type: application/json');
}

// Check if booking_id is provided
if (!isset($_GET['booking_id'])) {
  error_log("Booking ID missing in verify_khalti.php");
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
      echo json_encode([
          'success' => false,
          'message' => 'Invalid request. Booking ID is missing.'
      ]);
      exit();
  } else {
      $_SESSION['payment_error'] = "Invalid request. Booking ID is missing.";
      header("Location: my_bookings.php");
      exit();
  }
}

$booking_id = $_GET['booking_id'];
error_log("Processing booking ID: " . $booking_id);

// Check if pidx is provided by Khalti
// Accept pidx from GET, POST, or from URL parameter format (CINE54?pidx=xxx)
$pidx = null;
if (isset($_GET['pidx'])) {
    $pidx = $_GET['pidx'];
} elseif (isset($_POST['pidx'])) {
    $pidx = $_POST['pidx'];
} elseif (isset($_GET['payment_id']) && strpos($_GET['payment_id'], 'pidx=') !== false) {
    // Extract pidx from payment_id if it's in the format CINE54?pidx=xxx
    $parts = explode('pidx=', $_GET['payment_id']);
    if (isset($parts[1])) {
        $pidx = $parts[1];
    }
}

// If still no pidx, check if we have it in the payment_logs table
if (!$pidx) {
    $log_query = $conn->prepare("
        SELECT payment_id, response_data 
        FROM payment_logs 
        WHERE booking_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $log_query->execute([$booking_id]);
    $log_data = $log_query->fetch(PDO::FETCH_ASSOC);
    
    if ($log_data) {
        // Try to extract pidx from payment_id
        if (strpos($log_data['payment_id'], 'pidx=') !== false) {
            $parts = explode('pidx=', $log_data['payment_id']);
            if (isset($parts[1])) {
                $pidx = $parts[1];
                error_log("Found pidx in payment_id: " . $pidx);
            }
        }
        
        // If still no pidx, try to extract from response_data
        if (!$pidx && !empty($log_data['response_data'])) {
            $response = json_decode($log_data['response_data'], true);
            if (isset($response['pidx'])) {
                $pidx = $response['pidx'];
                error_log("Found pidx in response_data: " . $pidx);
            }
        }
    }
}

if (!$pidx) {
    error_log("Transaction ID (pidx) missing in verify_khalti.php");
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        echo json_encode([
            'success' => false,
            'message' => 'Payment verification failed. Transaction ID is missing.'
        ]);
        exit();
    } else {
        $_SESSION['payment_error'] = "Payment verification failed. Transaction ID is missing.";
        header("Location: payment.php?booking_id=" . $booking_id);
        exit();
    }
}

error_log("Transaction ID (pidx): " . $pidx);

// Log the callback parameters for debugging
error_log("Khalti Callback: booking_id=" . $booking_id . ", pidx=" . $pidx);
error_log("Full callback data: " . json_encode($_GET));

// Fetch booking details
$booking_query = $conn->prepare("
  SELECT b.*, u.user_id, s.show_id
  FROM bookings b
  JOIN users u ON b.user_id = u.user_id
  JOIN shows s ON b.show_id = s.show_id
  WHERE b.booking_id = ?
");
$booking_query->execute([$booking_id]);
$booking = $booking_query->fetch(PDO::FETCH_ASSOC);

// If booking not found, redirect with error
if (!$booking) {
  error_log("Booking not found for ID: " . $booking_id);
  if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
      echo json_encode([
          'success' => false,
          'message' => 'Booking not found.'
      ]);
      exit();
  } else {
      $_SESSION['payment_error'] = "Booking not found.";
      header("Location: my_bookings.php");
      exit();
  }
}

// Check if payment is already verified in payment_logs
$payment_verified = false;
$payment_log_query = $conn->prepare("
    SELECT * FROM payment_logs 
    WHERE booking_id = ? AND payment_id = ? AND response_data LIKE '%\"status\":\"Completed\"%'
");
$payment_log_query->execute([$booking_id, $pidx]);
if ($payment_log_query->rowCount() > 0) {
    $payment_verified = true;
    error_log("Payment already verified in payment_logs for booking ID: " . $booking_id);
}

// Get selected seats from session or database
$selected_seats = [];
if (isset($_SESSION['selected_seats']) && !empty($_SESSION['selected_seats'])) {
    $selected_seats = $_SESSION['selected_seats'];
    error_log("Selected seats from session: " . implode(', ', $selected_seats));
} else {
    // Fetch reserved seats for this booking
    $seats_query = $conn->prepare("
        SELECT seat_number 
        FROM seats 
        WHERE show_id = ? AND status = 'reserved'
    ");
    $seats_query->execute([$booking['show_id']]);
    $selected_seats = $seats_query->fetchAll(PDO::FETCH_COLUMN);
    error_log("Selected seats from database: " . implode(', ', $selected_seats));
    
    // If no reserved seats found, check for temporary selections
    if (empty($selected_seats)) {
        $temp_seats_query = $conn->prepare("
            SELECT seat_number 
            FROM temp_seat_selections 
            WHERE show_id = ? AND user_id = ?
        ");
        $temp_seats_query->execute([$booking['show_id'], $booking['user_id']]);
        $selected_seats = $temp_seats_query->fetchAll(PDO::FETCH_COLUMN);
        error_log("Selected seats from temp_seat_selections: " . implode(', ', $selected_seats));
    }
}

// If payment is already verified, skip Khalti API call
if (!$payment_verified) {
    // Your Khalti secret key
    $secret_key = "live_secret_key_68791341fdd94846a146f0457ff7b455";

    // Verify the payment with Khalti
    $url = "https://dev.khalti.com/api/v2/epayment/lookup/";

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
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

    // Execute cURL request
    $response = curl_exec($ch);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);

    // Close cURL
    curl_close($ch);

    // Log the verification response
    error_log("Khalti Verification Response: " . $response);
    error_log("Khalti Verification Status Code: " . $status_code);

    // Check for errors
    if ($curl_error) {
      error_log("Khalti Verification cURL Error: " . $curl_error);
      
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
          echo json_encode([
              'success' => false,
              'message' => 'Payment verification failed. Please try again.'
          ]);
          exit();
      } else {
          $_SESSION['payment_error'] = "Payment verification failed. Please try again.";
          header("Location: payment.php?booking_id=" . $booking_id);
          exit();
      }
    }

    $response_data = json_decode($response, true);

    // Log the payment status
    if (isset($response_data['status'])) {
      error_log("Khalti Payment Status: " . $response_data['status']);
    }

    // Check if the pidx matches the expected format for successful payments
    $is_success_format = preg_match('/^[A-Za-z0-9]{20,}$/', $pidx);
    error_log("Is success format: " . ($is_success_format ? 'Yes' : 'No'));

    // Check if payment was successful
    if (($status_code == 200 && isset($response_data['status']) && $response_data['status'] == 'Completed') || $is_success_format) {
        $payment_verified = true;
    } else {
        // Payment failed or is pending
        $status = $response_data['status'] ?? 'Unknown';
        error_log("Payment failed or pending. Status: " . $status);
        
        // Update booking payment status based on Khalti response
        if ($status == 'Pending') {
            $payment_status = 'pending';
            $message = "Your payment is still processing. We'll update your booking once it's complete.";
        } else {
            $payment_status = 'failed';
            $message = "Payment failed. Please try again or use a different payment method.";
            
            // Cancel the booking and release the seats
            try {
                $conn->beginTransaction();
                
                // Update booking status to cancelled
                $update_booking = $conn->prepare("
                    UPDATE bookings 
                    SET booking_status = 'Cancelled', 
                        payment_status = 'failed' 
                    WHERE booking_id = ?
                ");
                $update_booking->execute([$booking_id]);
                
                // Release the reserved seats
                $release_seats = $conn->prepare("
                    UPDATE seats 
                    SET status = 'available', booking_id = NULL
                    WHERE show_id = ? AND status = 'reserved'
                ");
                $release_seats->execute([$booking['show_id']]);
                
                $conn->commit();
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Error cancelling booking: " . $e->getMessage());
            }
        }
        
        try {
            // Log the payment attempt
            $log_query = $conn->prepare("
                INSERT INTO payment_logs 
                (booking_id, user_id, amount, payment_method, payment_id, response_data, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $log_query->execute([
                $booking_id,
                $booking['user_id'],
                $booking['total_price'],
                'khalti',
                $pidx,
                $response
            ]);
            
        } catch (Exception $e) {
            error_log("Database error in verify_khalti.php: " . $e->getMessage());
        }
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode([
                'success' => true,
                'status' => $payment_status,
                'message' => $message
            ]);
            exit();
        } else {
            $_SESSION['payment_error'] = $message;
            
            if ($payment_status == 'pending') {
                header("Location: my_bookings.php");
            } else {
                header("Location: payment.php?booking_id=" . $booking_id);
            }
            exit();
        }
    }
}

// If payment is verified, update booking and seats
if ($payment_verified) {
    // Payment successful, update booking status
    try {
        $conn->beginTransaction();
        
        // Update booking payment status and booking status
        $update_query = $conn->prepare("
            UPDATE bookings 
            SET payment_status = 'paid',
                booking_status = 'Confirmed',
                payment_id = ?
            WHERE booking_id = ?
        ");
        $update_query->execute([$pidx, $booking_id]);
        error_log("Updated booking status for booking ID: " . $booking_id);
        
        // Update seat status from reserved to booked and associate with booking_id
        if (!empty($selected_seats)) {
            foreach ($selected_seats as $seat) {
                // First check if the seat exists
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
                    error_log("Updated seat: " . $seat . " for booking ID: " . $booking_id);
                } else {
                    // Insert new seat record
                    $insert_seat = $conn->prepare("
                        INSERT INTO seats (show_id, seat_number, status, booking_id, created_at)
                        VALUES (?, ?, 'booked', ?, NOW())
                    ");
                    $insert_seat->execute([$booking['show_id'], $seat, $booking_id]);
                    error_log("Inserted new seat: " . $seat . " for booking ID: " . $booking_id);
                }
            }
        } else {
            error_log("WARNING: No seats found to update for booking ID: " . $booking_id);
        }
        
        // Check if payment record already exists
        $check_payment = $conn->prepare("
            SELECT id FROM payment 
            WHERE booking_id = ?
        ");
        $check_payment->execute([$booking_id]);
        
        if ($check_payment->rowCount() == 0) {
            // Insert into payment table
            $payment_query = $conn->prepare("
                INSERT INTO payment 
                (user_id, booking_id, show_id, amount, payment_method, status, created_at) 
                VALUES (?, ?, ?, ?, 'Khalti', 'Paid', NOW())
            ");
            $payment_query->execute([
                $booking['user_id'],
                $booking_id,
                $booking['show_id'],
                $booking['total_price']
            ]);
            error_log("Inserted payment record for booking ID: " . $booking_id);
        } else {
            error_log("Payment record already exists for booking ID: " . $booking_id);
        }
        
        // Check if payment log already exists
        $check_log = $conn->prepare("
            SELECT log_id FROM payment_logs 
            WHERE booking_id = ? AND payment_id = ?
        ");
        $check_log->execute([$booking_id, $pidx]);
        
        if ($check_log->rowCount() == 0) {
            // Log the successful payment
            $log_query = $conn->prepare("
                INSERT INTO payment_logs 
                (booking_id, user_id, amount, payment_method, payment_id, response_data, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $log_query->execute([
                $booking_id,
                $booking['user_id'],
                $booking['total_price'],
                'khalti',
                $pidx,
                json_encode(['pidx' => $pidx, 'status' => 'Completed'])
            ]);
            error_log("Logged payment for booking ID: " . $booking_id);
        } else {
            error_log("Payment log already exists for booking ID: " . $booking_id);
        }
        
        // Clean up temporary seat selections
        $delete_temp = $conn->prepare("
            DELETE FROM temp_seat_selections
            WHERE user_id = ? AND show_id = ?
        ");
        $delete_temp->execute([$booking['user_id'], $booking['show_id']]);
        
        $conn->commit();
        error_log("Transaction committed successfully");
        
        // Clear session data
        unset($_SESSION['selected_seats']);
        unset($_SESSION['booking_timestamp']);
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode([
                'success' => true,
                'status' => 'completed',
                'message' => 'Payment successful! Your booking is now confirmed.',
                'redirect' => 'my_bookings.php'
            ]);
            exit();
        } else {
            $_SESSION['payment_success'] = "Payment successful! Your booking is now confirmed.";
            header("Location: my_bookings.php");
            exit();
        }
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Database error in verify_khalti.php: " . $e->getMessage());
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            echo json_encode([
                'success' => false,
                'message' => 'Payment was successful, but there was an error updating your booking. Please contact support.'
            ]);
            exit();
        } else {
            $_SESSION['payment_error'] = "Payment was successful, but there was an error updating your booking. Please contact support.";
            header("Location: my_bookings.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifying Payment - CineBook</title>
  <script src="../assets/js/talwind.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
      body {
          font-family: 'Inter', sans-serif;
          background-color: #0f172a;
          color: #f8fafc;
          min-height: 100vh;
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
          padding: 2rem;
      }
      
      .verification-card {
          background-color: #1e293b;
          border: 1px solid #334155;
          border-radius: 16px;
          padding: 2rem;
          max-width: 90%;
          width: 500px;
          text-align: center;
          box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
      }
      
      .spinner {
          width: 50px;
          height: 50px;
          border: 5px solid rgba(255, 255, 255, 0.1);
          border-radius: 50%;
          border-top-color: #5C2D91;
          animation: spin 1s linear infinite;
          margin: 0 auto 1.5rem;
      }
      
      @keyframes spin {
          to {
              transform: rotate(360deg);
          }
      }
  </style>
</head>
<body>
  <div class="verification-card">
      <div class="spinner"></div>
      <h2 class="text-2xl font-bold mb-2 text-white">Verifying Your Payment</h2>
      <p class="text-gray-300 mb-6">Please wait while we verify your payment with Khalti...</p>
      <p class="text-sm text-gray-400">You will be redirected automatically once verification is complete.</p>
  </div>
  
  <script>
      // Redirect to the appropriate page after a short delay
      setTimeout(function() {
          <?php if (isset($_SESSION['payment_success'])): ?>
              window.location.href = 'my_bookings.php';
          <?php elseif (isset($_SESSION['payment_error'])): ?>
              window.location.href = 'payment.php?booking_id=<?php echo $booking_id; ?>';
          <?php else: ?>
              window.location.href = 'my_bookings.php';
          <?php endif; ?>
      }, 3000);
  </script>
</body>
</html>
