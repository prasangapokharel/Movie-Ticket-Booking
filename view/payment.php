<?php
include '../database/config.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

// Check if booking_id is provided
if (!isset($_GET['booking_id'])) {
  header("Location: index.php");
  exit();
}

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Fetch booking details with movie, show, theater details
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

// If booking not found or doesn't belong to user, redirect
if (!$booking) {
  header("Location: my_bookings.php");
  exit();
}

// Check if the booking is already paid
$is_paid = ($booking['payment_status'] === 'paid');

// Check if the booking is expired (15 minutes timeout)
$booking_timestamp = $_SESSION['booking_timestamp'] ?? 0;
$current_time = time();
$timeout = 15 * 60; // 15 minutes in seconds

if (!$is_paid && ($current_time - $booking_timestamp) > $timeout) {
  // Booking has expired, release the seats
  try {
      $conn->beginTransaction();
      
      // Update booking status to expired
      $update_booking = $conn->prepare("
          UPDATE bookings 
          SET booking_status = 'Expired', 
              payment_status = 'failed' 
          WHERE booking_id = ? AND booking_status = 'Pending'
      ");
      $update_booking->execute([$booking_id]);
      
      // Release the reserved seats
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

// Function to fetch seats for this booking
function getBookingSeats($conn, $booking_id) {
  // Get the show_id for this booking
  $booking_query = $conn->prepare("SELECT show_id, total_price FROM bookings WHERE booking_id = ?");
  $booking_query->execute([$booking_id]);
  $booking = $booking_query->fetch(PDO::FETCH_ASSOC);
  
  if (!$booking) {
      return [];
  }
  
  $show_id = $booking['show_id'];
  
  // Check if we have seats in session from the booking page
  if (isset($_SESSION['selected_seats']) && !empty($_SESSION['selected_seats'])) {
      return $_SESSION['selected_seats'];
  }
  
  // Fallback: Get seats from the database
  $seats_query = $conn->prepare("
      SELECT seat_number 
      FROM seats 
      WHERE show_id = ? AND (status = 'reserved' OR status = 'booked')
      ORDER BY seat_number
  ");
  $seats_query->execute([$show_id]);
  return $seats_query->fetchAll(PDO::FETCH_COLUMN);
}

// Get seats for this booking
$seats = getBookingSeats($conn, $booking_id);
$seats_text = implode(', ', $seats);

// Format date & time
$show_date = date('D, d M Y', strtotime($booking['show_time']));
$show_time = date('h:i A', strtotime($booking['show_time']));

// Convert price to paisa (Khalti uses paisa as the smallest unit)
$amount_in_paisa = $booking['total_price'] * 100;

// Generate a unique purchase order ID
$purchase_order_id = 'CINE' . $booking_id . '_' . time();

// AJAX endpoint to check payment status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_payment_status_db') {
    header('Content-Type: application/json');
    
    // Check if the booking is paid in the database
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

// Handle AJAX request for payment initiation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'initiate_payment') {
  header('Content-Type: application/json');
  
  // Khalti API endpoint for initiating payment
  $url = "https://dev.khalti.com/api/v2/epayment/initiate/";
  
  // Your Khalti secret key
  $secret_key = "live_secret_key_68791341fdd94846a146f0457ff7b455";
  
  // Determine the base URL dynamically
  $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
  $host = $_SERVER['HTTP_HOST'];
  $script_name = dirname($_SERVER['SCRIPT_NAME']);
  
  // Fix for localhost - ensure the path is correct
  if ($host === 'localhost' || strpos($host, '127.0.0.1') !== false) {
      // For localhost, construct the path based on the current directory structure
      $base_path = $protocol . "://" . $host;
      
      // If script_name is just a slash, don't add it to avoid double slashes
      if ($script_name !== '/' && $script_name !== '\\') {
          // Remove any trailing slashes
          $script_name = rtrim($script_name, '/\\');
          $base_path .= $script_name;
      }
      
      // Go up one directory from templates to get to the root
      $base_url = dirname($base_path);
  } else {
      // For production servers
      $base_url = $protocol . "://" . $host . $script_name;
  }
  
  // Ensure base_url doesn't have a trailing slash
  $base_url = rtrim($base_url, '/');
  
  // Construct the return URL and website URL
  $return_url = $base_url . "/templates/verify_khalti.php?booking_id=" . $booking_id;
  $website_url = $base_url;
  
  // Log the URLs for debugging
  error_log("Return URL: " . $return_url);
  error_log("Website URL: " . $website_url);
  
  // Prepare the request data
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
  
  // Initialize cURL
  $ch = curl_init();
  
  // Set cURL options
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
  
  // Execute cURL request
  $response = curl_exec($ch);
  $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  $curl_error = curl_error($ch);
  
  // Close cURL
  curl_close($ch);
  
  // Check for errors
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
          // Payment initiated successfully
          $payment_url = $response_data['payment_url'];
          $pidx = $response_data['pidx'];
          
          // Update booking with payment information
          $update_query = $conn->prepare("
              UPDATE bookings 
              SET payment_status = 'pending', 
                  payment_method = 'khalti', 
                  payment_id = ? 
              WHERE booking_id = ?
          ");
          $update_query->execute([$purchase_order_id, $booking_id]);
          
          // Log the payment initiation
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
          
          // Store the pidx in session for verification
          $_SESSION['khalti_pidx'] = $pidx;
          $_SESSION['khalti_purchase_order_id'] = $purchase_order_id;
          
          echo json_encode([
              'success' => true,
              'payment_url' => $payment_url,
              'pidx' => $pidx
          ]);
          exit;
      } else {
          // Payment initiation failed
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

// Handle AJAX request for payment status check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_payment_status') {
    header('Content-Type: application/json');
    
    $pidx = $_POST['pidx'] ?? '';
    
    if (empty($pidx)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing payment ID'
        ]);
        exit;
    }
    
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
            // Payment successful, update booking status
            try {
                $conn->beginTransaction();
                
                // Update booking payment status
                $update_query = $conn->prepare("
                    UPDATE bookings 
                    SET payment_status = 'paid',
                        booking_status = 'Confirmed'
                    WHERE booking_id = ?
                ");
                $update_query->execute([$booking_id]);
                
                // Update seat status from reserved to booked and associate with booking_id
                $update_seats = $conn->prepare("
                    UPDATE seats
                    SET status = 'booked', booking_id = ?
                    WHERE show_id = ? AND status = 'reserved'
                ");
                $update_seats->execute([$booking_id, $booking['show_id']]);
                
                // Insert into payment table
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
                
                // Log the successful payment
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
                
                // Set success message in session
                $_SESSION['payment_success'] = "Payment successful! Your booking is now confirmed.";
                
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment - CineBook</title>
  <script src="../assets/js/talwind.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
      body {
          font-family: 'Inter', sans-serif;
          background-color: #0f172a;
          color: #f8fafc;
          min-height: 100vh;
      }
      
      .payment-card {
          background-color: #1e293b;
          border: 1px solid #334155;
          border-radius: 12px;
          padding: 1.5rem;
      }
      
      .divider {
          height: 1px;
          background-color: #334155;
          margin: 12px 0;
      }
      
      .ticket-info {
          background-color: #1e293b;
          padding: 10px;
          border-radius: 8px;
          margin-bottom: 8px;
          border: 1px solid #334155;
      }
      
      .btn {
          display: inline-block;
          padding: 10px 16px;
          border-radius: 8px;
          font-weight: 500;
          text-align: center;
          cursor: pointer;
          transition: background-color 0.2s;
      }
      
      .btn-primary {
          background-color: #b91c1c;
          color: white;
      }
      
      .btn-primary:hover {
          background-color: #991b1b;
      }
      
      .btn-secondary {
          background-color: #475569;
          color: white;
      }
      
      .btn-secondary:hover {
          background-color: #334155;
      }
      
      .btn-khalti {
          background-color: #5C2D91;
          color: white;
      }
      
      .btn-khalti:hover {
          background-color: #4A2275;
      }
      
      .payment-method {
          border: 2px solid transparent;
          cursor: pointer;
      }
      
      .payment-method.selected {
          border-color: #5C2D91;
          background-color: rgba(92, 45, 145, 0.1);
      }
      
      .alert {
          padding: 1rem;
          border-radius: 8px;
          margin-bottom: 1rem;
      }
      
      .alert-error {
          background-color: rgba(239, 68, 68, 0.2);
          border: 1px solid rgba(239, 68, 68, 0.4);
          color: #f87171;
      }
      
      .alert-success {
          background-color: rgba(16, 185, 129, 0.2);
          border: 1px solid rgba(16, 185, 129, 0.4);
          color: #6ee7b7;
      }
      
      /* Loading overlay */
      .loading-overlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(15, 23, 42, 0.9);
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
          z-index: 9999;
          opacity: 0;
          visibility: hidden;
          transition: opacity 0.3s ease;
      }
      
      .loading-overlay.active {
          opacity: 1;
          visibility: visible;
      }
      
      .spinner {
          width: 50px;
          height: 50px;
          border: 5px solid rgba(255, 255, 255, 0.1);
          border-radius: 50%;
          border-top-color: #5C2D91;
          animation: spin 1s linear infinite;
          margin-bottom: 1rem;
      }
      
      @keyframes spin {
          to {
              transform: rotate(360deg);
          }
      }
      
      /* Payment status modal */
      .modal {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(15, 23, 42, 0.9);
          display: flex;
          justify-content: center;
          align-items: center;
          z-index: 9999;
          opacity: 0;
          visibility: hidden;
          transition: opacity 0.3s ease;
      }
      
      .modal.active {
          opacity: 1;
          visibility: visible;
      }
      
      .modal-content {
          background-color: #1e293b;
          border-radius: 12px;
          padding: 2rem;
          max-width: 90%;
          width: 400px;
          text-align: center;
          box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
      }
      
      .status-icon {
          width: 80px;
          height: 80px;
          border-radius: 50%;
          display: flex;
          justify-content: center;
          align-items: center;
          margin: 0 auto 1.5rem;
      }
      
      .status-icon.success {
          background-color: rgba(16, 185, 129, 0.2);
          color: #6ee7b7;
      }
      
      .status-icon.error {
          background-color: rgba(239, 68, 68, 0.2);
          color: #f87171;
      }
      
      .status-icon.pending {
          background-color: rgba(245, 158, 11, 0.2);
          color: #fbbf24;
      }

      /* Payment success animation */
      .payment-success-animation {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(15, 23, 42, 0.9);
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
          z-index: 9999;
          opacity: 0;
          visibility: hidden;
          transition: opacity 0.3s ease;
      }
      
      .payment-success-animation.active {
          opacity: 1;
          visibility: visible;
      }
      
      .success-checkmark {
          width: 80px;
          height: 80px;
          border-radius: 50%;
          background-color: rgba(16, 185, 129, 0.2);
          display: flex;
          justify-content: center;
          align-items: center;
          margin-bottom: 1.5rem;
      }
      
      /* Order paid banner */
      .order-paid-banner {
          background-color: #10b981;
          color: white;
          padding: 1rem;
          border-radius: 8px;
          margin-bottom: 1rem;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: 500;
      }
      
      .order-paid-banner svg {
          margin-right: 0.5rem;
      }
  </style>
</head>
<body>
  <!-- Include loader -->
  <?php include '../includes/loader.php'; ?>
  
  <!-- Include navigation -->
  <?php include '../includes/nav.php'; ?>
  
  <!-- Loading overlay -->
  <div id="loadingOverlay" class="loading-overlay">
      <div class="spinner"></div>
      <p class="text-white font-medium">Processing payment...</p>
  </div>
  
  <!-- Payment success animation -->
  <div id="paymentSuccessAnimation" class="payment-success-animation">
      <div class="success-checkmark">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-12 h-12 text-green-500">
              <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
          </svg>
      </div>
      <h2 class="text-2xl font-bold text-white mb-2">Payment Confirmed!</h2>
      <p class="text-gray-300">Your booking has been successfully completed.</p>
  </div>
  
  <!-- Payment status modal -->
  <div id="statusModal" class="modal">
      <div class="modal-content">
          <div id="statusIcon" class="status-icon">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
              </svg>
          </div>
          <h3 id="statusTitle" class="text-xl font-bold mb-2 text-white">Payment Successful</h3>
          <p id="statusMessage" class="text-gray-300 mb-6">Your payment has been processed successfully.</p>
          <div class="flex justify-center">
              <button id="statusButton" class="btn btn-primary py-2 px-6 rounded-lg text-sm font-medium">
                  Continue
              </button>
          </div>
      </div>
  </div>
  
  <div class="max-w-4xl mx-auto px-4 pt-8 pb-24">
      <!-- Header section -->
      <div class="text-center mb-8">
          <h1 class="text-3xl font-bold mb-2 text-white">Complete Your Payment</h1>
          <p class="text-gray-400 max-w-lg mx-auto">Please review your booking details and complete the payment to confirm your tickets.</p>
      </div>
      
      <!-- Order paid banner (hidden by default) -->
      <div id="orderPaidBanner" class="order-paid-banner <?php echo $is_paid ? '' : 'hidden'; ?>">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
              <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
          </svg>
          Order Paid - Your booking is confirmed!
      </div>
      
      <!-- Alert container -->
      <div id="alertContainer" class="hidden">
          <div id="alert" class="alert">
              <div class="flex items-start">
                  <div class="flex-shrink-0" id="alertIcon">
                      <!-- Icon will be inserted here -->
                  </div>
                  <div class="ml-3">
                      <p class="text-sm font-medium" id="alertMessage"></p>
                  </div>
                  <div class="ml-auto pl-3">
                      <button type="button" onclick="dismissAlert()" class="inline-flex rounded-md p-1.5 hover:text-white focus:outline-none">
                          <span class="sr-only">Dismiss</span>
                          <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                          </svg>
                      </button>
                  </div>
              </div>
          </div>
      </div>
      
      <!-- Payment section -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Booking details -->
          <div class="md:col-span-2">
              <div class="payment-card mb-6">
                  <h2 class="text-xl font-bold mb-4">Booking Details</h2>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                      <div class="ticket-info">
                          <div class="text-xs text-slate-400">Movie</div>
                          <div class="text-sm font-medium text-white"><?php echo $booking['title']; ?></div>
                      </div>
                      <div class="ticket-info">
                          <div class="text-xs text-slate-400">Theater</div>
                          <div class="text-sm font-medium text-white"><?php echo $booking['theater_name']; ?></div>
                      </div>
                      <div class="ticket-info">
                          <div class="text-xs text-slate-400">Date & Time</div>
                          <div class="text-sm font-medium text-white"><?php echo $show_date; ?> at <?php echo $show_time; ?></div>
                      </div>
                      <div class="ticket-info">
                          <div class="text-xs text-slate-400">Seats</div>
                          <div class="text-sm font-medium text-white"><?php echo $seats_text; ?></div>
                      </div>
                  </div>
                  
                  <div class="divider"></div>
                  
                  <div class="flex justify-between items-center">
                      <span class="text-slate-400">Total Amount</span>
                      <span class="text-xl font-bold text-white">₨ <?php echo number_format($booking['total_price'], 2); ?></span>
                  </div>
              </div>
              
              <!-- Payment methods -->
              <div class="payment-card">
                  <h2 class="text-xl font-bold mb-4">Payment Method</h2>
                  
                  <div class="space-y-4">
                      <div class="payment-method selected rounded-lg p-4 flex items-center">
                          <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center mr-4">
                              <img src="../assets/images/khalti-logo.png" alt="Khalti" class="w-8 h-8" onerror="this.src='https://khalti.s3.ap-south-1.amazonaws.com/KPG/dist/2020.12.22.0.0.0/images/khalti-logo.png'">
                          </div>
                          <div>
                              <h3 class="font-medium text-white">Khalti Digital Wallet</h3>
                              <p class="text-xs text-slate-400">Pay securely using Khalti</p>
                          </div>
                      </div>
                  </div>
                  
                  <div class="mt-6">
                      <button id="payWithKhalti" class="w-full btn btn-khalti py-3 px-4 rounded-lg text-sm font-medium" <?php echo $is_paid ? 'disabled' : ''; ?>>
                          <span class="flex items-center justify-center">
                              <?php if ($is_paid): ?>
                                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                                      <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                  </svg>
                                  Payment Completed
                              <?php else: ?>
                                  Pay with Khalti
                              <?php endif; ?>
                          </span>
                      </button>
                  </div>
                  
                  <?php if ($is_paid): ?>
                  <div class="mt-4 text-center">
                      <a href="booking_confirmation.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">
                          View Booking Confirmation
                      </a>
                  </div>
                  <?php endif; ?>
              </div>
          </div>
          
          <!-- Order summary -->
          <div class="md:col-span-1">
              <div class="payment-card sticky top-20">
                  <h2 class="text-xl font-bold mb-4">Order Summary</h2>
                  
                  <!-- Add countdown timer (only show if not paid) -->
                  <?php if (!$is_paid): ?>
                  <div class="mb-4 p-3 bg-gray-800/50 rounded-lg text-center">
                      <p class="text-sm text-gray-400 mb-1">Time remaining to complete payment:</p>
                      <div id="countdown" class="text-xl font-bold text-red-400">15:00</div>
                  </div>
                  <?php endif; ?>
                  
                  <div class="space-y-3 mb-4">
                      <div class="flex justify-between">
                          <span class="text-slate-400">Ticket Price:</span>
                          <span class="text-white">₨ <?php echo number_format($booking['total_price'] - 20, 2); ?> × <span id="seatCount"><?php echo count($seats); ?></span></span>
                      </div>
                      
                      <div class="flex justify-between">
                          <span class="text-slate-400">Convenience Fee:</span>
                          <span class="text-white">₨ 20.00</span>
                      </div>
                  </div>
                  
                  <div class="divider"></div>
                  
                  <div class="flex justify-between items-center mb-6">
                      <span class="font-medium">Total:</span>
                      <span class="text-xl font-bold text-white">₨ <?php echo number_format($booking['total_price'], 2); ?></span>
                  </div>
                  
                  <div class="text-xs text-slate-400">
                      <p class="mb-2">By completing this payment, you agree to our <a href="#" class="text-purple-400 hover:underline">Terms of Service</a> and <a href="#" class="text-purple-400 hover:underline">Privacy Policy</a>.</p>
                      <p>All transactions are secure and encrypted.</p>
                  </div>
              </div>
          </div>
      </div>
  </div>
  
  <!-- Include footer -->
  <?php include '../includes/footer.php'; ?>
  
  <script>
      // Variables to store payment information
      let paymentPidx = '';
      let paymentCheckInterval = null;
      let paymentStatusCheckInterval = null;
      
      // DOM elements
      const loadingOverlay = document.getElementById('loadingOverlay');
      const paymentSuccessAnimation = document.getElementById('paymentSuccessAnimation');
      const statusModal = document.getElementById('statusModal');
      const statusIcon = document.getElementById('statusIcon');
      const statusTitle = document.getElementById('statusTitle');
      const statusMessage = document.getElementById('statusMessage');
      const statusButton = document.getElementById('statusButton');
      const alertContainer = document.getElementById('alertContainer');
      const alert = document.getElementById('alert');
      const alertIcon = document.getElementById('alertIcon');
      const alertMessage = document.getElementById('alertMessage');
      const countdownElement = document.getElementById('countdown');
      const orderPaidBanner = document.getElementById('orderPaidBanner');
      const payWithKhaltiButton = document.getElementById('payWithKhalti');
      
      // Check if the booking is already paid
      const isPaid = <?php echo $is_paid ? 'true' : 'false'; ?>;
      
      // If not paid, start checking payment status periodically
      if (!isPaid) {
          // Start checking payment status every 5 seconds
          paymentStatusCheckInterval = setInterval(checkPaymentStatusFromDB, 5000);
          
          // Check immediately
          checkPaymentStatusFromDB();
      }
      
      // Function to check payment status from database
      function checkPaymentStatusFromDB() {
          fetch('payment.php?booking_id=<?php echo $booking_id; ?>', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
              },
              body: 'action=check_payment_status_db'
          })
          .then(response => response.json())
          .then(data => {
              if (data.success && data.is_paid) {
                  // Payment is now paid, show success UI
                  clearInterval(paymentStatusCheckInterval);
                  
                  // Show order paid banner
                  orderPaidBanner.classList.remove('hidden');
                  
                  // Disable payment button and update text
                  payWithKhaltiButton.disabled = true;
                  payWithKhaltiButton.innerHTML = `
                      <span class="flex items-center justify-center">
                          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 mr-2">
                              <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                          </svg>
                          Payment Completed
                      </span>
                  `;
                  
                  // Add confirmation link
                  const paymentCard = payWithKhaltiButton.closest('.payment-card');
                  if (!paymentCard.querySelector('a.btn-primary')) {
                      const confirmationLink = document.createElement('div');
                      confirmationLink.className = 'mt-4 text-center';
                      confirmationLink.innerHTML = `
                          <a href="booking_confirmation.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">
                              View Booking Confirmation
                          </a>
                      `;
                      paymentCard.appendChild(confirmationLink);
                  }
                  
                  // Hide countdown if it exists
                  if (countdownElement) {
                      const countdownContainer = countdownElement.closest('div.mb-4');
                      if (countdownContainer) {
                          countdownContainer.style.display = 'none';
                      }
                  }
                  
                  // Show success message
                  showAlert('success', 'Payment successful! Your booking is now confirmed.');
              }
          })
          .catch(error => {
              console.error('Error checking payment status:', error);
          });
      }
      
      // Initialize payment process
      if (payWithKhaltiButton) {
          payWithKhaltiButton.addEventListener('click', function() {
              if (!this.disabled) {
                  initiatePayment();
              }
          });
      }
      
      // Function to initiate payment
      function initiatePayment() {
          // Show loading overlay
          loadingOverlay.classList.add('active');
          
          // Send AJAX request to initiate payment
          fetch('payment.php?booking_id=<?php echo $booking_id; ?>', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
              },
              body: 'action=initiate_payment'
          })
          .then(response => response.json())
          .then(data => {
              // Hide loading overlay
              loadingOverlay.classList.remove('active');
              
              if (data.success) {
                  // Store payment ID for status checking
                  paymentPidx = data.pidx;
                  
                  // Open Khalti payment page in a new window
                  const paymentWindow = window.open(data.payment_url, 'khaltiPayment', 'width=800,height=600');
                  
                  // Start checking payment status
                  startPaymentStatusCheck();
                  
                  // Show message to user
                  showAlert('success', 'Payment initiated. Please complete the payment in the opened window.');
              } else {
                  // Show error message
                  showAlert('error', data.message || 'Failed to initiate payment. Please try again.');
              }
          })
          .catch(error => {
              // Hide loading overlay
              loadingOverlay.classList.remove('active');
              
              // Show error message
              showAlert('error', 'An error occurred. Please try again.');
              console.error('Error:', error);
          });
      }
      
      // Function to start checking payment status
      function startPaymentStatusCheck() {
          // Clear any existing interval
          if (paymentCheckInterval) {
              clearInterval(paymentCheckInterval);
          }
          
          // Check status immediately
          checkPaymentStatus();
          
          // Set interval to check status every 5 seconds
          paymentCheckInterval = setInterval(checkPaymentStatus, 5000);
      }
      
      // Function to check payment status
      function checkPaymentStatus() {
          if (!paymentPidx) return;
          
          fetch('payment.php?booking_id=<?php echo $booking_id; ?>', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/x-www-form-urlencoded',
              },
              body: 'action=check_payment_status&pidx=' + paymentPidx
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  if (data.status === 'completed') {
                      // Payment successful
                      clearInterval(paymentCheckInterval);
                      
                      // Show success animation
                      showSuccessAnimation();
                      
                      // Then redirect to booking confirmation page after animation
                      setTimeout(() => {
                          window.location.href = 'booking_confirmation.php?booking_id=<?php echo $booking_id; ?>';
                      }, 2000);
                  } else if (data.status === 'pending') {
                      // Payment still pending, continue checking
                      console.log('Payment still pending...');
                  } else if (data.status === 'failed') {
                      // Payment failed
                      clearInterval(paymentCheckInterval);
                      showPaymentStatus('error', 'Payment Failed', 'Your payment could not be processed. Please try again.', 'Try Again');
                  }
              } else {
                  // Error checking status
                  console.error('Error checking payment status:', data.message);
              }
          })
          .catch(error => {
              console.error('Error checking payment status:', error);
          });
      }
      
      // Function to show success animation
      function showSuccessAnimation() {
          paymentSuccessAnimation.classList.add('active');
      }
      
      // Function to show payment status modal
      function showPaymentStatus(type, title, message, buttonText, redirectUrl = null) {
          // Set modal content based on status type
          if (type === 'success') {
              statusIcon.className = 'status-icon success';
              statusIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-10 w-10"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>';
          } else if (type === 'error') {
              statusIcon.className = 'status-icon error';
              statusIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>';
          } else if (type === 'pending') {
              statusIcon.className = 'status-icon pending';
              statusIcon.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
          }
          
          // Set text content
          statusTitle.textContent = title;
          statusMessage.textContent = message;
          statusButton.textContent = buttonText;
          
          // Set button action
          statusButton.onclick = function() {
              statusModal.classList.remove('active');
              if (redirectUrl) {
                  window.location.href = redirectUrl;
              } else if (type === 'success') {
                  // Default redirect for success if no specific URL provided
                  window.location.href = 'booking_confirmation.php?booking_id=<?php echo $booking_id; ?>';
              }
          };
          
          // Show modal
          statusModal.classList.add('active');
      }
      
      // Function to show alert
      function showAlert(type, message) {
          // Set alert type class
          alert.className = 'alert';
          alert.classList.add(type === 'success' ? 'alert-success' : 'alert-error');
          
          // Set icon based on type
          if (type === 'success') {
              alertIcon.innerHTML = '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>';
          } else {
              alertIcon.innerHTML = '<svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>';
          }
          
          // Set message
          alertMessage.textContent = message;
          
          // Show alert
          alertContainer.classList.remove('hidden');
          
          // Auto-dismiss after 5 seconds
          setTimeout(dismissAlert, 5000);
      }
      
      // Function to dismiss alert
      function dismissAlert() {
          alertContainer.classList.add('hidden');
      }
      
      // Countdown timer (only if not paid)
      <?php if (!$is_paid && isset($countdownElement)): ?>
      let timeLeft = <?php echo max(0, ($booking_timestamp + $timeout) - $current_time); ?>;
      
      function updateCountdown() {
          if (!countdownElement) return;
          
          const minutes = Math.floor(timeLeft / 60);
          const seconds = timeLeft % 60;
          
          countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
          
          if (timeLeft <= 0) {
              clearInterval(countdownInterval);
              countdownElement.textContent = '00:00';
              showPaymentStatus('error', 'Session Expired', 'Your booking session has expired. Please try again.', 'Return to Bookings', 'my_bookings.php');
          } else {
              timeLeft--;
          }
      }
      
      // Update countdown every second
      const countdownInterval = setInterval(updateCountdown, 1000);
      updateCountdown(); // Initial update
      <?php endif; ?>
  </script>
</body>
</html>

