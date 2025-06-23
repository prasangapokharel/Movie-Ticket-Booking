<?php
include '../database/config.php';
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
            m.movie_id,
            m.title AS movie_title,
            m.duration,
            m.poster_url,
            m.genre,
            m.language,
            m.certificate,
            t.theater_id,
            t.name AS theater_name,
            t.location AS theater_location,
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

    // Get booked and reserved seats for this show
    $booked_seats_query = $conn->prepare("
        SELECT seat_number, status, booking_id
        FROM seats
        WHERE show_id = ? AND status IN ('booked', 'reserved')
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
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    header('Location: index.php');
    exit;
}

// Process booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_tickets'])) {
    $selectedSeats = isset($_POST['seats']) ? $_POST['seats'] : [];
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

    // Validate inputs
    if (empty($selectedSeats)) {
        $errors[] = "Please select at least one seat.";
    }
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }

    // Verify seats are still available (prevent double booking)
    if (!empty($selectedSeats)) {
        $placeholders = str_repeat('?,', count($selectedSeats) - 1) . '?';
        
        // Check for already booked/reserved seats
        $seats_check = $conn->prepare("
            SELECT seat_number 
            FROM seats 
            WHERE show_id = ? 
            AND seat_number IN ($placeholders) 
            AND status IN ('booked', 'reserved')
        ");
        
        $params = array_merge([$show_id], $selectedSeats);
        $seats_check->execute($params);
        $unavailable_seats = $seats_check->fetchAll(PDO::FETCH_COLUMN);

        // Also check temporary selections by other users
        $temp_check = $conn->prepare("
            SELECT seat_number 
            FROM temp_seat_selections 
            WHERE show_id = ? 
            AND seat_number IN ($placeholders) 
            AND user_id != ?
            AND expires_at > NOW()
        ");
        
        $temp_params = array_merge([$show_id], $selectedSeats, [$user_id]);
        $temp_check->execute($temp_params);
        $temp_unavailable = $temp_check->fetchAll(PDO::FETCH_COLUMN);
        
        $all_unavailable = array_merge($unavailable_seats, $temp_unavailable);

        if (!empty($all_unavailable)) {
            $errors[] = "Seats " . implode(', ', $all_unavailable) . " are no longer available. Please refresh and select different seats.";
        }
    }

    if (empty($errors)) {
        try {
            $conn->beginTransaction();

            // Update user information if changed
            if ($user['name'] !== $name || $user['email'] !== $email || $user['phone'] !== $phone) {
                $update_user = $conn->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, phone = ? 
                    WHERE user_id = ?
                ");
                $update_user->execute([$name, $email, $phone, $user_id]);
            }

            // Calculate total price
            $seat_count = count($selectedSeats);
            $ticket_price = $seat_count * $show['price'];
            $convenience_fee = 20.00; // Fixed convenience fee
            $total_price = $ticket_price + $convenience_fee;

            // Create booking record with pending status
            $booking_query = $conn->prepare("
                INSERT INTO bookings (user_id, show_id, total_price, booking_status, payment_status, created_at)
                VALUES (?, ?, ?, 'Pending', 'pending', NOW())
            ");
            
            $booking_query->execute([$user_id, $show_id, $total_price]);
            $booking_id = $conn->lastInsertId();

            // Reserve seats temporarily
            foreach ($selectedSeats as $seat) {
                // First try to update existing seat record
                $update_seat = $conn->prepare("
                    UPDATE seats 
                    SET status = 'reserved', booking_id = ?, updated_at = NOW()
                    WHERE show_id = ? AND seat_number = ? AND status = 'available'
                ");
                $update_seat->execute([$booking_id, $show_id, $seat]);
                
                // If no rows were updated, insert new seat record
                if ($update_seat->rowCount() === 0) {
                    $insert_seat = $conn->prepare("
                        INSERT INTO seats (show_id, seat_number, status, booking_id, created_at)
                        VALUES (?, ?, 'reserved', ?, NOW())
                        ON DUPLICATE KEY UPDATE 
                        status = 'reserved', booking_id = ?, updated_at = NOW()
                    ");
                    $insert_seat->execute([$show_id, $seat, $booking_id, $booking_id]);
                }
            }
            
            // Store selected seats in session for payment page
            $_SESSION['selected_seats'] = $selectedSeats;
            $_SESSION['booking_timestamp'] = time(); // Add timestamp for expiration check
            
            // Remove temporary selections for this user
            $delete_temp = $conn->prepare("
                DELETE FROM temp_seat_selections
                WHERE user_id = ? AND show_id = ?
            ");
            $delete_temp->execute([$user_id, $show_id]);

            $conn->commit();

            // Set success message
            $_SESSION['alert'] = [
                'type' => 'success',
                'message' => 'Seats reserved successfully! Please complete payment within 15 minutes.'
            ];

            // Redirect to payment page
            header("Location: payment.php?booking_id=" . $booking_id);
            exit;

        } catch (PDOException $e) {
            $conn->rollBack();
            $errors[] = "Booking failed: " . $e->getMessage();
            error_log("Booking error: " . $e->getMessage());
        }
    }
}
?>
