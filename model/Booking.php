<?php
include '../database/config.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    $_SESSION['alert'] = [
        'type' => 'warning',
        'message' => 'Please login to book tickets'
    ];
    header('Location: login.php');
    exit;
}

// Check if show_id is provided
if (!isset($_GET['show_id'])) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Show ID is required'
    ];
    header('Location: index.php');
    exit;
}

$show_id = $_GET['show_id'];
$user_id = $_SESSION['user_id'];

// Verify user exists in database
try {
    $user_check = $conn->prepare("SELECT user_id, name, email, phone FROM users WHERE user_id = ?");
    $user_check->execute([$user_id]);
    $user = $user_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // User doesn't exist, clear session and redirect to login
        session_destroy();
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'User session invalid. Please login again.'
        ];
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("User verification error: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Database error. Please try again.'
    ];
    header('Location: index.php');
    exit;
}

// Initialize variables
$errors = [];
$success_message = '';

try {
    // Clean up expired temporary seat selections first
    $cleanup_stmt = $conn->prepare("
        DELETE FROM temp_seat_selections 
        WHERE expires_at < NOW()
    ");
    $cleanup_stmt->execute();

    // Fetch show details with movie and theater information
    $show_query = $conn->prepare("
        SELECT 
            s.show_id,
            s.show_time,
            s.price,
            s.theater_id,
            s.hall_id,
            m.movie_id,
            m.title AS movie_title,
            m.duration,
            m.poster_url,
            m.genre,
            m.language,
            m.certificate,
            m.description,
            m.rating,
            t.name AS theater_name,
            t.location AS theater_location,
            t.capacity,
            h.hall_name,
            h.total_capacity
        FROM 
            shows s
        JOIN 
            movies m ON s.movie_id = m.movie_id
        LEFT JOIN 
            theaters t ON s.theater_id = t.theater_id
        LEFT JOIN
            halls h ON s.hall_id = h.hall_id
        WHERE 
            s.show_id = ?
    ");
    $show_query->execute([$show_id]);
    $show = $show_query->fetch(PDO::FETCH_ASSOC);

    if (!$show) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Show not found'
        ];
        header('Location: index.php');
        exit;
    }

    // Check if show time is in the past
    if (strtotime($show['show_time']) < time()) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'This show has already started. Please select a future show.'
        ];
        header('Location: movie_detail.php?id=' . $show['movie_id']);
        exit;
    }

    // Fetch user details for auto-filling the form
    $user_query = $conn->prepare("SELECT name, email, phone FROM users WHERE user_id = ?");
    $user_query->execute([$user_id]);
    $user = $user_query->fetch(PDO::FETCH_ASSOC);

    // Get ONLY actually booked seats (not reserved) for this show
    $booked_seats_query = $conn->prepare("
        SELECT seat_number, status, booking_id
        FROM seats
        WHERE show_id = ? AND status = 'booked'
        ORDER BY seat_number
    ");
    $booked_seats_query->execute([$show_id]);
    $booked_seats_result = $booked_seats_query->fetchAll(PDO::FETCH_ASSOC);
    
    $bookedSeats = [];
    foreach ($booked_seats_result as $seat) {
        $bookedSeats[$seat['seat_number']] = $seat['status'];
    }

    // Get temporary selected seats by other users (not expired)
    $temp_seats_query = $conn->prepare("
        SELECT seat_number, user_id
        FROM temp_seat_selections
        WHERE show_id = ? AND expires_at > NOW()
        ORDER BY seat_number
    ");
    $temp_seats_query->execute([$show_id]);
    $temp_seats_result = $temp_seats_query->fetchAll(PDO::FETCH_ASSOC);
    
    $tempSelectedSeats = [];
    $userTempSeats = [];
    
    foreach ($temp_seats_result as $temp) {
        if ($temp['user_id'] == $user_id) {
            $userTempSeats[] = $temp['seat_number'];
        } else {
            $tempSelectedSeats[] = $temp['seat_number'];
        }
    }

} catch (PDOException $e) {
    error_log("Database error in booking: " . $e->getMessage());
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    header('Location: index.php');
    exit;
}

// Handle AJAX booking request - CREATE PENDING BOOKING ONLY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_booking') {
    header('Content-Type: application/json');
    
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $seats = $_POST['seats'] ?? [];
        
        // Log the incoming data
        error_log("Booking attempt - User: $user_id, Show: $show_id, Seats: " . implode(',', $seats));
        
        // Double-check user exists
        $user_verify = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $user_verify->execute([$user_id]);
        if (!$user_verify->fetch()) {
            throw new Exception("User session invalid. Please login again.");
        }
        
        // Validation
        $errors = [];
        if (empty($name)) $errors[] = 'Name is required';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
        if (empty($phone)) $errors[] = 'Phone number is required';
        if (empty($seats)) $errors[] = 'Please select at least one seat';
        
        if (!empty($errors)) {
            error_log("Booking validation errors: " . implode(', ', $errors));
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        $conn->beginTransaction();
        
        // Check if seats are available (not booked by others)
        foreach ($seats as $seat) {
            $check_seat = $conn->prepare("
                SELECT COUNT(*) as count
                FROM seats 
                WHERE show_id = ? AND seat_number = ? AND status = 'booked'
            ");
            $check_seat->execute([$show_id, $seat]);
            $seat_count = $check_seat->fetch(PDO::FETCH_ASSOC);
            
            if ($seat_count['count'] > 0) {
                throw new Exception("Seat $seat is already booked");
            }
            
            // Check if seat is temporarily selected by another user
            $check_temp = $conn->prepare("
                SELECT COUNT(*) as count
                FROM temp_seat_selections 
                WHERE show_id = ? AND seat_number = ? AND user_id != ? AND expires_at > NOW()
            ");
            $check_temp->execute([$show_id, $seat, $user_id]);
            $temp_count = $check_temp->fetch(PDO::FETCH_ASSOC);
            
            if ($temp_count['count'] > 0) {
                throw new Exception("Seat $seat is currently being selected by another user");
            }
        }
        
        // Calculate total price
        $ticket_price = floatval($show['price']);
        $convenience_fee = 20.00;
        $total_price = (count($seats) * $ticket_price) + $convenience_fee;
        
        error_log("Calculated total price: $total_price for " . count($seats) . " seats");
        
        // Create PENDING booking (NOT confirmed yet)
        $booking_query = $conn->prepare("
            INSERT INTO bookings (user_id, show_id, total_price, booking_status, payment_status, created_at)
            VALUES (?, ?, ?, 'Pending', 'pending', NOW())
        ");
        $booking_result = $booking_query->execute([$user_id, $show_id, $total_price]);
        
        if (!$booking_result) {
            throw new Exception("Failed to create booking: " . implode(', ', $booking_query->errorInfo()));
        }
        
        $booking_id = $conn->lastInsertId();
        error_log("Created PENDING booking ID: $booking_id");
        
        // Store seats in temp_seat_selections (15 minutes expiry)
        $expires_at = date('Y-m-d H:i:s', time() + (15 * 60)); // 15 minutes from now
        
        foreach ($seats as $seat) {
            // Remove any existing temp selection for this seat by this user
            $delete_temp = $conn->prepare("
                DELETE FROM temp_seat_selections 
                WHERE show_id = ? AND seat_number = ? AND user_id = ?
            ");
            $delete_temp->execute([$show_id, $seat, $user_id]);
            
            // Add new temp selection with explicit column names
            $insert_temp = $conn->prepare("
                INSERT INTO temp_seat_selections (user_id, show_id, seat_number, expires_at, timestamp)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $insert_result = $insert_temp->execute([$user_id, $show_id, $seat, $expires_at]);
            
            if (!$insert_result) {
                $error_info = $insert_temp->errorInfo();
                error_log("Failed to insert temp seat: " . implode(', ', $error_info));
                throw new Exception("Failed to temporarily reserve seat $seat: " . $error_info[2]);
            }
        }
        
        // Update user details
        $update_user = $conn->prepare("
            UPDATE users SET name = ?, email = ?, phone = ? WHERE user_id = ?
        ");
        $update_user->execute([$name, $email, $phone, $user_id]);
        
        $conn->commit();
        
        // Store booking info in session
        $_SESSION['booking_timestamp'] = time();
        $_SESSION['selected_seats'] = $seats;
        $_SESSION['pending_booking_id'] = $booking_id;
        
        error_log("Pending booking created successfully: $booking_id");
        
        echo json_encode([
            'success' => true,
            'booking_id' => $booking_id,
            'message' => 'Seats temporarily reserved. Complete payment within 15 minutes.',
            'redirect' => 'payment.php?booking_id=' . $booking_id
        ]);
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Booking creation failed: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>
