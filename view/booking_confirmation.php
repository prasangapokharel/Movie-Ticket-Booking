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
               br.branch_name as branch_name,
               u.name as user_name, u.phone as user_phone
        FROM bookings b
        JOIN shows s ON b.show_id = s.show_id
        JOIN movies m ON s.movie_id = m.movie_id
        LEFT JOIN theaters t ON s.theater_id = t.theater_id
        LEFT JOIN branches br ON t.branch_id = br.branch_id
        JOIN users u ON b.user_id = u.user_id
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

    // Get seats for this booking - FIXED QUERY
    $seats_query = $conn->prepare("
        SELECT seat_number 
        FROM seats 
        WHERE show_id = ? AND booking_id = ? AND status IN ('booked', 'reserved')
        ORDER BY seat_number
    ");
    $seats_query->execute([$booking['show_id'], $booking_id]);
    $seats = $seats_query->fetchAll(PDO::FETCH_COLUMN);
    
    // If no seats found with booking_id, try alternative methods
    if (empty($seats)) {
        // Try to get from session if still available
        if (isset($_SESSION['selected_seats']) && !empty($_SESSION['selected_seats'])) {
            $seats = $_SESSION['selected_seats'];
            
            // Update seats table with booking_id if payment is confirmed
            if ($booking['payment_status'] === 'paid' && $booking['booking_status'] === 'Confirmed') {
                foreach ($seats as $seat) {
                    $update_seat = $conn->prepare("
                        UPDATE seats 
                        SET booking_id = ?, status = 'booked'
                        WHERE show_id = ? AND seat_number = ?
                    ");
                    $update_seat->execute([$booking_id, $booking['show_id'], $seat]);
                }
            }
        } else {
            // Try to get from temp_seat_selections
            $temp_seats_query = $conn->prepare("
                SELECT seat_number 
                FROM temp_seat_selections 
                WHERE user_id = ? AND show_id = ?
                ORDER BY seat_number
            ");
            $temp_seats_query->execute([$user_id, $booking['show_id']]);
            $temp_seats = $temp_seats_query->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($temp_seats)) {
                $seats = $temp_seats;
                
                // Update seats table with booking_id if payment is confirmed
                if ($booking['payment_status'] === 'paid' && $booking['booking_status'] === 'Confirmed') {
                    foreach ($seats as $seat) {
                        $update_seat = $conn->prepare("
                            UPDATE seats 
                            SET booking_id = ?, status = 'booked'
                            WHERE show_id = ? AND seat_number = ?
                        ");
                        $update_seat->execute([$booking_id, $booking['show_id'], $seat]);
                    }
                }
            }
        }
    }
    
    $seats_text = !empty($seats) ? implode(', ', $seats) : 'Not Available';

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
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - CineBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
        }
        
        .confirmation-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-confirmed {
            background-color: #10b981;
            color: #ffffff;
        }
        
        .status-paid {
            background-color: #3b82f6;
            color: #ffffff;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .info-item {
            background: #334155;
            padding: 1rem;
            border-radius: 8px;
        }
        
        .info-label {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .info-value {
            font-size: 0.875rem;
            font-weight: 500;
            color: #f8fafc;
        }
        
        .ticket-section {
            background: #334155;
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background-color: #dc2626;
            color: #ffffff;
        }
        
        .btn-primary:hover {
            background-color: #b91c1c;
        }
        
        .btn-secondary {
            background-color: #475569;
            color: #ffffff;
        }
        
        .btn-secondary:hover {
            background-color: #334155;
        }
        
        .print-hide {
            display: block;
        }
        
        @media print {
            .print-hide {
                display: none !important;
            }
            
            body {
                background: white;
                color: black;
            }
            
            .confirmation-card {
                background: white;
                border: 1px solid #ccc;
                box-shadow: none;
            }
        }
        
        #qrcode {
            display: flex;
            justify-content: center;
            margin: 1rem 0;
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
                    <div class="mt-4 md:mt-0 flex gap-2">
                        <span class="status-badge status-confirmed">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <?php echo ucfirst($booking['booking_status']); ?>
                        </span>
                        <span class="status-badge status-paid">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                            </svg>
                            <?php echo ucfirst($booking['payment_status']); ?>
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
                        
                        <!-- QR Code -->
                        <div id="qrcode"></div>
                        
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
            <div class="confirmation-card print-hide">
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
    
    <script>
        // Generate QR Code
        document.addEventListener('DOMContentLoaded', function() {
            const qrData = {
                bookingId: '<?php echo $booking_code; ?>',
                movie: '<?php echo addslashes($booking['title']); ?>',
                theater: '<?php echo addslashes($booking['theater_name']); ?>',
                date: '<?php echo $show_date; ?>',
                time: '<?php echo $show_time; ?>',
                seats: '<?php echo $seats_text; ?>',
                amount: '₨<?php echo number_format($booking['total_price'], 2); ?>'
            };
            
            const qrString = `Booking: ${qrData.bookingId}\nMovie: ${qrData.movie}\nTheater: ${qrData.theater}\nDate: ${qrData.date}\nTime: ${qrData.time}\nSeats: ${qrData.seats}\nAmount: ${qrData.amount}`;
            
            QRCode.toCanvas(document.createElement('canvas'), qrString, {
                width: 200,
                height: 200,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            }, function (error, canvas) {
                if (error) {
                    console.error('QR Code generation failed:', error);
                    document.getElementById('qrcode').innerHTML = '<div class="w-32 h-32 bg-gray-200 flex items-center justify-center text-gray-600 text-xs">QR Code<br><?php echo $booking_code; ?></div>';
                } else {
                    document.getElementById('qrcode').appendChild(canvas);
                }
            });
        });
    </script>
</body>
</html>
