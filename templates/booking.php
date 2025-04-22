<?php
session_start();
include '../database/config.php';
include '../includes/loader.php'; 

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tickets - <?php echo htmlspecialchars($show['movie_title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="../assets/js/talwind.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
        }
        
        .booking-container {
            background: linear-gradient(to bottom right, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .screen {
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transform: perspective(500px) rotateX(-30deg);
            box-shadow: 0 3px 10px rgba(255, 255, 255, 0.1);
        }
        
        .seat {
            width: 35px;
            height: 35px;
            margin: 3px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background-color: rgba(22, 4, 100, 0.77);
            border: 1px solid rgba(0, 0, 0, 0.4);
        }

        .seat:not(.booked):not(.temp-selected):hover {
            transform: scale(1.1);
            background-color: rgba(250, 250, 255, 0.3);
        }

        .seat.selected {
            background-color: rgba(16, 185, 64, 0.53);
            border: 1px solid rgba(16, 185, 129, 0.6);
            animation: pulse 1.5s infinite;
        }

        .seat.booked {
            background-color: rgba(239, 68, 68, 0.73);
            border: 1px solid rgba(239, 68, 68, 0.4);
            cursor: not-allowed;
            opacity: 0.5;
        }
        
        .seat.temp-selected {
            background-color: rgba(234, 179, 8, 0.6);
            border: 1px solid rgba(234, 179, 8, 0.4);
            cursor: not-allowed;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4);
            }
            70% {
                box-shadow: 0 0 0 5px rgba(16, 185, 129, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
            }
        }

        .seat::before {
            content: '';
            position: absolute;
            top: 5px;
            left: 5px;
            right: 5px;
            height: 12px;
            background-color: currentColor;
            opacity: 0.2;
            border-radius: 4px 4px 0 0;
        }

        .seat::after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 8px;
            right: 8px;
            height: 4px;
            background-color: currentColor;
            opacity: 0.2;
            border-radius: 2px;
        }

        .seat span {
            position: absolute;
            bottom: 2px;
            font-size: 10px;
            color: rgba(255, 255, 255, 0.8);
        }
        
        .movie-card {
            background: linear-gradient(to bottom, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-input {
            background-color: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            background-color: rgba(30, 41, 59, 0.9);
            border-color: rgba(0, 0, 0, 0.5);
            box-shadow: 0 0 0 2px rgba(4, 5, 61, 0.25);
        }
        
        .book-button {
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            transition: all 0.3s ease;
        }
        
        .book-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(185, 28, 28, 0.3);
        }
        
        .book-button:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            margin-right: 1rem;
        }
        
        .legend-box {
            width: 20px;
            height: 20px;
            border-radius: 4px;
            margin-right: 0.5rem;
        }
        
        /* Toast notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            background-color: rgba(15, 23, 42, 0.9);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            transform: translateX(120%);
            transition: transform 0.3s ease-out;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast.error {
            border-left: 4px solid #ef4444;
        }
        
        .toast.warning {
            border-left: 4px solid #f59e0b;
        }
        
        .toast.info {
            border-left: 4px solid #3b82f6;
        }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span id="toastMessage"></span>
        </div>
    </div>
    
    <!-- Alert Messages -->
    <?php if (!empty($errors)): ?>
    <div class="fixed top-20 right-4 z-50 max-w-sm">
        <div class="bg-red-900/80 backdrop-blur-md text-red-200 px-4 py-3 rounded-lg shadow-lg border border-red-800/50">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-300">Please fix the following errors:</h3>
                    <ul class="mt-2 text-sm text-red-200 list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex rounded-md p-1.5 text-red-300 hover:text-red-100 focus:outline-none">
                            <span class="sr-only">Dismiss</span>
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Movie and Show Info -->
            <div class="lg:w-1/3">
                <div class="movie-card rounded-xl overflow-hidden sticky top-20">
                    <div class="relative">
                        <img src="<?php echo htmlspecialchars($show['poster_url']); ?>" 
                             alt="<?php echo htmlspecialchars($show['movie_title']); ?>" 
                             class="w-full h-80 object-cover">
                        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 to-transparent"></div>
                        <div class="absolute bottom-0 left-0 right-0 p-6">
                            <h1 class="text-2xl font-bold mb-2 text-white"><?php echo htmlspecialchars($show['movie_title']); ?></h1>
                            
                            <div class="flex items-center text-sm text-gray-300 mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <?php echo $show['duration']; ?> min
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 space-y-5">
                        <div class="flex items-start space-x-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <div>
                                <p class="font-medium text-gray-300">Theater</p>
                                <p class="text-gray-400"><?php echo htmlspecialchars($show['theater_name']); ?></p>
                                <p class="text-gray-500"><?php echo htmlspecialchars($show['theater_location']); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="font-medium text-gray-300">Show Date & Time</p>
                                <p class="text-gray-400"><?php echo date('l, F j, Y', strtotime($show['show_time'])); ?></p>
                                <p class="text-gray-500"><?php echo date('g:i A', strtotime($show['show_time'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-start space-x-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 9a2 2 0 10-4 0v5a2 2 0 01-2 2h6m-6-4h4m8 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="font-medium text-gray-300">Ticket Price</p>
                                <p class="text-gray-400">₨<?php echo number_format($show['price'], 2); ?> per ticket</p>
                            </div>
                        </div>
                        
                        <div class="pt-4 border-t border-gray-800">
                            <div class="text-sm text-gray-400">
                                <p class="mb-2">• Please arrive at least 15 minutes before showtime</p>
                                <p class="mb-2">• Outside food and beverages are not allowed</p>
                                <p>• Tickets once booked cannot be cancelled</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Booking Section -->
            <div class="lg:w-2/3">
                <div class="booking-container rounded-xl shadow-xl p-6 mb-8">
                    <h2 class="text-2xl font-bold mb-6 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Select Your Seats
                    </h2>
                    
                    <!-- Seat Selection Legend -->
                    <div class="flex flex-wrap gap-4 mb-6">
                        <div class="legend-item">
                            <div class="legend-box seat available"></div>
                            <span class="text-sm">Available</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box seat selected"></div>
                            <span class="text-sm">Selected</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box seat booked"></div>
                            <span class="text-sm">Booked</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box seat temp-selected"></div>
                            <span class="text-sm">Selected by others</span>
                        </div>
                    </div>
                    
                    <!-- Screen -->
                    <div class="mb-10 relative">
                        <div class="screen h-3 w-full mb-8 rounded"></div>
                        <p class="text-center text-sm text-gray-400 -mt-4">Screen</p>
                        <div class="absolute left-0 right-0 -bottom-6 text-center">
                            <span class="text-xs text-gray-500">FRONT</span>
                        </div>
                    </div>
                    
                    <!-- Seat Grid -->
                    <form method="post" id="bookingForm" class="space-y-8">
                        <div class="flex flex-wrap justify-center gap-2 max-w-3xl mx-auto">
                            <?php
                            $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                            $seatsPerRow = 10;
                            
                            foreach ($rows as $row) {
                                echo "<div class='flex items-center mb-2'>";
                                echo "<div class='w-6 text-center text-sm text-gray-400 mr-2'>{$row}</div>";
                                
                                for ($seat = 1; $seat <= $seatsPerRow; $seat++) {
                                    $seatNumber = $row . $seat;
                                    $isBooked = isset($bookedSeats[$seatNumber]) && $bookedSeats[$seatNumber] === 'booked';
                                    $isTempSelected = in_array($seatNumber, $tempSelectedSeats);
                                    $isUserSelected = in_array($seatNumber, $userTempSeats);
                                    
                                    if ($isBooked) {
                                        $class = 'booked';
                                        $disabled = 'disabled';
                                    } elseif ($isTempSelected) {
                                        $class = 'temp-selected';
                                        $disabled = 'disabled';
                                    } elseif ($isUserSelected) {
                                        $class = 'selected';
                                        $disabled = '';
                                    } else {
                                        $class = 'available';
                                        $disabled = '';
                                    }
                                    
                                    echo "
                                        <div class='seat {$class}' 
                                             data-seat='{$seatNumber}'>
                                            <input type='checkbox' 
                                                   name='seats[]' 
                                                   value='{$seatNumber}' 
                                                   class='hidden' 
                                                   " . ($isUserSelected ? 'checked' : '') . "
                                                   " . ($disabled ? 'disabled' : '') . ">
                                            <span>{$seat}</span>
                                        </div>
                                    ";
                                }
                                
                                echo "</div>";
                            }
                            ?>
                        </div>
                        
                        <div class="text-right text-xs text-gray-500 -mt-2">BACK</div>
                        
                        <!-- Booking Details -->
                        <div class="max-w-xl mx-auto mt-10">
                            <div class="bg-gray-800/50 rounded-lg p-6">
                                <h3 class="text-xl font-bold mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Your Details
                                </h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Name</label>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" class="form-input w-full rounded-lg px-4 py-2.5" required>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="form-input w-full rounded-lg px-4 py-2.5" required>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Phone</label>
                                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="form-input w-full rounded-lg px-4 py-2.5" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gray-800/50 rounded-lg p-6 mt-6">
                                <h3 class="text-xl font-bold mb-4 flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                    </svg>
                                    Booking Summary
                                </h3>
                                
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400">Movie:</span>
                                        <span class="font-medium"><?php echo htmlspecialchars($show['movie_title']); ?></span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400">Date & Time:</span>
                                        <span class="font-medium"><?php echo date('d M, g:i A', strtotime($show['show_time'])); ?></span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400">Selected Seats:</span>
                                        <span id="selectedSeatsDisplay" class="font-medium text-red-400">None</span>
                                    </div>
                                    
                                    <div class="border-t border-gray-700 my-3"></div>
                                    
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400">Ticket Price:</span>
                                        <span class="font-medium">₨<?php echo number_format($show['price'], 2); ?> × <span id="seatCount">0</span></span>
                                    </div>
                                    
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400">Convenience Fee:</span>
                                        <span class="font-medium">₨20.00</span>
                                    </div>
                                    
                                    <div class="border-t border-gray-700 my-3"></div>
                                    
                                    <div class="flex justify-between items-center text-lg">
                                        <span class="font-medium">Total Amount:</span>
                                        <span id="totalAmount" class="font-bold text-red-400">₨20.00</span>
                                    </div>
                                    
                                    <button type="submit" 
                                            name="book_tickets" 
                                            class="book-button w-full py-3 rounded-lg text-white font-medium mt-4 disabled:opacity-50" 
                                            id="bookButton" 
                                            disabled>
                                        <span class="flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                            </svg>
                                            Continue to Payment
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
    const ticketPrice = <?php echo $show['price']; ?>;
    const convenienceFee = 20.00;
    const showId = <?php echo $show_id; ?>;
    const bookButton = document.getElementById('bookButton');
    const selectedSeatsDisplay = document.getElementById('selectedSeatsDisplay');
    const totalAmountDisplay = document.getElementById('totalAmount');
    const seatCountDisplay = document.getElementById('seatCount');
    const bookingForm = document.getElementById('bookingForm');
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    
    // Initialize selected seats from PHP
    let selectedSeats = <?php echo json_encode($userTempSeats); ?> || [];
    
    // Update booking details on page load
    updateBookingDetails();
    
    // Real-time seat status checking
    function checkSeatStatus() {
        console.log("Checking seat status...");
        fetch(`check_seats.php?show_id=${showId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log("Received seat data:", data);
                if (!data || !Array.isArray(data)) {
                    console.error("Invalid seat data received:", data);
                    return;
                }
                
                data.forEach(seat => {
                    const seatElement = document.querySelector(`[data-seat="${seat.seat_number}"]`);
                    if (seatElement) {
                        // Skip updating if this is a seat the current user has selected
                        if (selectedSeats.includes(seat.seat_number)) {
                            return;
                        }
                        
                        // Update seat status
                        if (seat.status === 'booked') {
                            if (!seatElement.classList.contains('booked')) {
                                seatElement.classList.remove('available', 'selected', 'temp-selected');
                                seatElement.classList.add('booked');
                                seatElement.setAttribute('disabled', '');
                                const checkbox = seatElement.querySelector('input[type="checkbox"]');
                                if (checkbox) {
                                    checkbox.disabled = true;
                                    checkbox.checked = false;
                                }
                            }
                        } else if (seat.status === 'temp_selected') {
                            if (!seatElement.classList.contains('temp-selected')) {
                                seatElement.classList.remove('available', 'selected', 'booked');
                                seatElement.classList.add('temp-selected');
                                seatElement.setAttribute('disabled', '');
                                const checkbox = seatElement.querySelector('input[type="checkbox"]');
                                if (checkbox) {
                                    checkbox.disabled = true;
                                    checkbox.checked = false;
                                }
                            }
                        } else if (!seatElement.classList.contains('selected')) {
                            seatElement.classList.remove('booked', 'temp-selected');
                            seatElement.classList.add('available');
                            seatElement.removeAttribute('disabled');
                            const checkbox = seatElement.querySelector('input[type="checkbox"]');
                            if (checkbox) {
                                checkbox.disabled = false;
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('Error checking seat status:', error);
            });
    }

    // Check seat status every 1 seconds
    setInterval(checkSeatStatus, 500);
    
    // Initial check
    setTimeout(checkSeatStatus, 500);

    function toggleSeat(seatElement) {
        console.log("Toggling seat:", seatElement);
        
        // Debug: Log the current class list
        console.log("Current classes:", seatElement.className);
        
        if (seatElement.classList.contains('booked') || seatElement.classList.contains('temp-selected')) {
            console.log("Seat is booked or temp-selected, cannot toggle");
            return;
        }
        
        const seatNumber = seatElement.dataset.seat;
        const checkbox = seatElement.querySelector('input[type="checkbox"]');
        
        console.log("Seat number:", seatNumber);
        console.log("Is currently selected:", seatElement.classList.contains('selected'));
        
        if (seatElement.classList.contains('selected')) {
            // Deselect seat
            console.log("Deselecting seat:", seatNumber);
            seatElement.classList.remove('selected');
            seatElement.classList.add('available');
            if (checkbox) checkbox.checked = false;
            
            // Remove from selected seats array
            const index = selectedSeats.indexOf(seatNumber);
            if (index > -1) {
                selectedSeats.splice(index, 1);
            }
            
            // Update temporary selection in database
            updateTempSeatSelection(seatNumber, 'deselect');
            
        } else {
            // Select seat
            console.log("Selecting seat:", seatNumber);
            seatElement.classList.remove('available');
            seatElement.classList.add('selected');
            if (checkbox) checkbox.checked = true;
            
            // Add to selected seats array
            if (!selectedSeats.includes(seatNumber)) {
                selectedSeats.push(seatNumber);
            }
            
            // Update temporary selection in database
            updateTempSeatSelection(seatNumber, 'select');
        }
        
        updateBookingDetails();
    }
    
    function updateTempSeatSelection(seatNumber, action) {
        console.log(`Updating temp seat: ${seatNumber}, action: ${action}`);
        
        const formData = new FormData();
        formData.append('show_id', showId);
        formData.append('seat_number', seatNumber);
        formData.append('action', action);
        
        fetch('update_temp_seat.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.error || 'Failed to update seat selection');
                });
            }
            return response.json();
        })
        .then(data => {
            console.log("Seat update successful:", data);
        })
        .catch(error => {
            console.error('Error updating seat selection:', error);
            showToast('Error updating seat selection. Please try again.', 'error');
        });
    }

    function updateBookingDetails() {
        const seatCount = selectedSeats.length;
        
        console.log("Updating booking details. Selected seats:", selectedSeats);
        
        selectedSeatsDisplay.textContent = seatCount > 0 ? selectedSeats.join(', ') : 'None';
        seatCountDisplay.textContent = seatCount;
        
        const subtotal = seatCount * ticketPrice;
        const total = subtotal + convenienceFee;
        
        totalAmountDisplay.textContent = '₨' + total.toFixed(2);
        
        bookButton.disabled = seatCount === 0;
    }
    
    function showToast(message, type = 'info') {
        toastMessage.textContent = message;
        toast.className = 'toast ' + type;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Form submission validation
    bookingForm.addEventListener('submit', function(e) {
        if (selectedSeats.length === 0) {
            e.preventDefault();
            showToast('Please select at least one seat', 'error');
        }
    });

    // Auto-dismiss alerts
    setTimeout(() => {
        const alerts = document.querySelectorAll('.fixed.top-20.right-4 > div');
        alerts.forEach(alert => {
            alert.classList.add('opacity-0', 'translate-x-full');
            setTimeout(() => {
                alert.remove();
            }, 500);
        });
    }, 5000);
    
    // Keep temporary seat selections alive
    setInterval(() => {
        if (selectedSeats.length > 0) {
            console.log("Refreshing temporary seat selections");
            selectedSeats.forEach(seat => {
                updateTempSeatSelection(seat, 'select');
            });
        }
    }, 60000); // Every minute
    
    // Add click event listeners to all seats
    document.addEventListener('DOMContentLoaded', function() {
        console.log("DOM loaded, initializing seat click handlers");
        const allSeats = document.querySelectorAll('.seat');
        console.log(`Found ${allSeats.length} seats`);
        
        allSeats.forEach(seat => {
            seat.addEventListener('click', function(e) {
                console.log("Seat clicked:", this.dataset.seat);
                toggleSeat(this);
            });
        });
    });
</script>

</body>
</html>

