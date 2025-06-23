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

try {
    // Fetch booking details with movie, show, theater details
    $booking_query = $conn->prepare("
        SELECT b.booking_id, b.show_id, b.total_price, b.booking_status, b.payment_status, b.created_at,
               m.movie_id, m.title, m.poster_url, m.language, m.genre, m.duration,
               s.show_time, s.price,
               t.name as theater_name, t.location,
               br.name as branch_name
        FROM bookings b
        JOIN shows s ON b.show_id = s.show_id
        JOIN movies m ON s.movie_id = m.movie_id
        LEFT JOIN theaters t ON s.theater_id = t.theater_id
        LEFT JOIN branches br ON t.branch_id = br.branch_id
        WHERE b.booking_id = ? AND b.user_id = ?
    ");
    $booking_query->execute([$booking_id, $user_id]);
    $booking = $booking_query->fetch(PDO::FETCH_ASSOC);

    // If booking not found or doesn't belong to user, redirect
    if (!$booking) {
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Booking not found'
        ];
        header("Location: my_bookings.php");
        exit();
    }

    // Get seats for this booking
    $seats_query = $conn->prepare("
        SELECT seat_number 
        FROM seats 
        WHERE show_id = ? AND booking_id = ? AND status = 'booked'
        ORDER BY seat_number
    ");
    $seats_query->execute([$booking['show_id'], $booking_id]);
    $seats = $seats_query->fetchAll(PDO::FETCH_COLUMN);
    
    // If no seats found with booking_id, try to find by session data
    if (empty($seats) && isset($_SESSION['selected_seats'])) {
        $seats = $_SESSION['selected_seats'];
    }
    
    $seats_text = !empty($seats) ? implode(', ', $seats) : 'N/A';

    // Format date & time
    $show_date = date('D, d M Y', strtotime($booking['show_time']));
    $show_time = date('h:i A', strtotime($booking['show_time']));

    // Generate booking code
    $booking_code = 'CB' . str_pad($booking_id, 6, '0', STR_PAD_LEFT);

} catch (PDOException $e) {
    $_SESSION['alert'] = [
        'type' => 'error',
        'message' => 'Database error: ' . $e->getMessage()
    ];
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - CineBook</title>
    <script src="../assets/js/talwind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
        }
        
        .confirmation-card {
            background: linear-gradient(to bottom right, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
        }
        
        .success-icon {
            background: linear-gradient(135deg, #10b981, #059669);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
        }
        
        .ticket-section {
            background-color: rgba(30, 41, 59, 0.5);
            border: 2px dashed rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .info-item {
            background-color: rgba(15, 23, 42, 0.6);
            padding: 1rem;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .info-label {
            font-size: 0.75rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-weight: 600;
            color: #f8fafc;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ef4444, #b91c1c);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(185, 28, 28, 0.3);
        }
        
        .btn-secondary {
            background-color: rgba(71, 85, 105, 0.8);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-secondary:hover {
            background-color: rgba(51, 65, 85, 0.9);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .status-confirmed {
            background-color: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .qr-code {
            background-color: white;
            padding: 1rem;
            border-radius: 8px;
            display: inline-block;
            margin: 1rem auto;
        }
        
        @media print {
            body {
                background: white;
                color: black;
            }
            .confirmation-card {
                background: white;
                border: 1px solid #ccc;
                box-shadow: none;
            }
            .btn, .print-hide {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Success Header -->
        <div class="text-center mb-8">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold mb-2">Booking Confirmed!</h1>
            <p class="text-gray-400">Your movie tickets have been successfully booked</p>
        </div>
        
        <!-- Booking Details Card -->
        <div class="max-w-4xl mx-auto">
            <div class="confirmation-card">
                <!-- Header with Status -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($booking['title']); ?></h2>
                        <p class="text-gray-400">Booking ID: <?php echo $booking_code; ?></p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <span class="status-badge status-confirmed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Confirmed
                        </span>
                    </div>
                </div>
                
                <!-- Movie Poster and Details -->
                <div class="flex flex-col lg:flex-row gap-6 mb-6">
                    <?php if (!empty($booking['poster_url'])): ?>
                    <div class="w-full lg:w-48 h-72 flex-shrink-0">
                        <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" 
                             alt="<?php echo htmlspecialchars($booking['title']); ?>" 
                             class="w-full h-full object-cover rounded-lg">
                    </div>
                    <?php endif; ?>
                    
                    <div class="flex-1">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Date</div>
                                <div class="info-value"><?php echo $show_date; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Time</div>
                                <div class="info-value"><?php echo $show_time; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Theater</div>
                                <div class="info-value"><?php echo htmlspecialchars($booking['theater_name']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Branch</div>
                                <div class="info-value"><?php echo htmlspecialchars($booking['branch_name'] ?? 'N/A'); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Seats</div>
                                <div class="info-value"><?php echo $seats_text; ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Total Amount</div>
                                <div class="info-value">₨<?php echo number_format($booking['total_price'], 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ticket Section -->
                <div class="ticket-section">
                    <div class="text-center">
                        <h3 class="text-lg font-semibold mb-4">Your E-Ticket</h3>
                        
                        <!-- QR Code Placeholder -->
                        <div class="qr-code">
                            <div class="w-32 h-32 bg-gray-200 flex items-center justify-center text-gray-600 text-xs">
                                QR Code<br>
                                <?php echo $booking_code; ?>
                            </div>
                        </div>
                        
                        <p class="text-sm text-gray-400 mt-2">
                            Show this QR code at the theater entrance
                        </p>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 mt-6 print-hide">
                    <button onclick="window.print()" class="btn btn-primary flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        Print Ticket
                    </button>
                    
                    <a href="my_bookings.php" class="btn btn-secondary flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                        </svg>
                        My Bookings
                    </a>
                    
                    <a href="index.php" class="btn btn-secondary flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" clip-rule="evenodd" />
                        </svg>
                        Back to Home
                    </a>
                </div>
            </div>
            
            <!-- Important Information -->
            <div class="confirmation-card mt-6 print-hide">
                <h3 class="text-lg font-semibold mb-4">Important Information</h3>
                <ul class="space-y-2 text-sm text-gray-300">
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-400 mr-2 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Please arrive at least 15 minutes before showtime
                    </li>
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-400 mr-2 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Present this ticket (printed or digital) at the entrance
                    </li>
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-400 mr-2 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Outside food and beverages are not allowed
                    </li>
                    <li class="flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-400 mr-2 mt-0.5 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Tickets once booked cannot be cancelled or refunded
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>
