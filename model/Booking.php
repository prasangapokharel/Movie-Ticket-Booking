<?php
session_start();
include '../database/config.php';

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

try {
    // Fetch show details with movie and theater information
    $stmt = $conn->prepare("
        SELECT 
            s.show_id,
            s.show_time,
            s.price,
            m.movie_id,
            m.title AS movie_title,
            m.duration,
            m.poster_url,
            t.theater_id,
            t.name AS theater_name,
            t.location AS theater_location,
            t.capacity
        FROM 
            shows s
        JOIN 
            movies m ON s.movie_id = m.movie_id
        JOIN 
            theaters t ON s.theater_id = t.theater_id
        WHERE 
            s.show_id = :show_id
    ");
    $stmt->bindParam(':show_id', $show_id);
    $stmt->execute();
    $show = $stmt->fetch();

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

    // Fetch user details for pre-filling the form
    $user_stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE user_id = :user_id");
    $user_stmt->bindParam(':user_id', $user_id);
    $user_stmt->execute();
    $user = $user_stmt->fetch();

    // Process booking
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_tickets'])) {
        $selectedSeats = isset($_POST['seats']) ? $_POST['seats'] : [];
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

        $errors = [];

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
            $seats_check = $conn->prepare("
                SELECT seat_number 
                FROM seats 
                WHERE show_id = ? 
                AND seat_number IN ($placeholders) 
                AND status = 'booked'
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
                AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            
            $temp_params = array_merge([$show_id], $selectedSeats, [$user_id]);
            $temp_check->execute($temp_params);
            $temp_unavailable = $temp_check->fetchAll(PDO::FETCH_COLUMN);
            
            $all_unavailable = array_merge($unavailable_seats, $temp_unavailable);

            if (!empty($all_unavailable)) {
                $errors[] = "Seats " . implode(', ', $all_unavailable) . " have been booked by another user. Please refresh and try again.";
            }
        }

        if (empty($errors)) {
            try {
                $conn->beginTransaction();

                // Update user information if changed
                if ($user['name'] !== $name || $user['email'] !== $email || $user['phone'] !== $phone) {
                    $update_user = $conn->prepare("
                        UPDATE users 
                        SET name = :name, email = :email, phone = :phone 
                        WHERE user_id = :user_id
                    ");
                    $update_user->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':phone' => $phone,
                        ':user_id' => $user_id
                    ]);
                }

                // Calculate total price
                $totalPrice = (count($selectedSeats) * $show['price']) + 20.00; // Adding convenience fee

                // Create booking record with pending status
                $stmt = $conn->prepare("
                    INSERT INTO bookings (user_id, show_id, total_price, booking_status, payment_status, created_at)
                    VALUES (:user_id, :show_id, :total_price, 'Pending', 'pending', NOW())
                ");
                
                $stmt->execute([
                    ':user_id' => $user_id,
                    ':show_id' => $show_id,
                    ':total_price' => $totalPrice
                ]);

                $bookingId = $conn->lastInsertId();

                // Mark seats as temporarily reserved
                $updateSeatStmt = $conn->prepare("
                    INSERT INTO seats (show_id, seat_number, status)
                    VALUES (:show_id, :seat_number, 'reserved')
                    ON DUPLICATE KEY UPDATE status = 'reserved'
                ");

                foreach ($selectedSeats as $seat) {
                    $updateSeatStmt->execute([
                        ':show_id' => $show_id,
                        ':seat_number' => $seat
                    ]);
                }
                
                // Store selected seats in session for payment page
                $_SESSION['selected_seats'] = $selectedSeats;
                $_SESSION['booking_timestamp'] = time(); // Add timestamp for expiration check
                
                // Remove temporary selections for this user
                $delete_temp = $conn->prepare("
                    DELETE FROM temp_seat_selections
                    WHERE user_id = :user_id AND show_id = :show_id
                ");
                $delete_temp->execute([
                    ':user_id' => $user_id,
                    ':show_id' => $show_id
                ]);

                $conn->commit();

                // Redirect to payment page
                header("Location: payment.php?booking_id=" . $bookingId);
                exit;

            } catch (PDOException $e) {
                $conn->rollBack();
                $errors[] = "Booking failed: " . $e->getMessage();
            }
        }
    }

    // Fetch booked seats for this show
    $stmt = $conn->prepare("
        SELECT seat_number, status
        FROM seats
        WHERE show_id = :show_id
    ");
    $stmt->bindParam(':show_id', $show_id);
    $stmt->execute();
    $bookedSeats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Fetch temporary selected seats by other users
    $temp_stmt = $conn->prepare("
        SELECT seat_number
        FROM temp_seat_selections
        WHERE show_id = :show_id 
        AND user_id != :user_id
        AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $temp_stmt->bindParam(':show_id', $show_id);
    $temp_stmt->bindParam(':user_id', $user_id);
    $temp_stmt->execute();
    $tempSelectedSeats = $temp_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Fetch user's own temporary selections
    $user_temp_stmt = $conn->prepare("
        SELECT seat_number
        FROM temp_seat_selections
        WHERE show_id = :show_id 
        AND user_id = :user_id
        AND timestamp > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $user_temp_stmt->bindParam(':show_id', $show_id);
    $user_temp_stmt->bindParam(':user_id', $user_id);
    $user_temp_stmt->execute();
    $userTempSeats = $user_temp_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    header('Location: index.php');
    exit;
}
?>
