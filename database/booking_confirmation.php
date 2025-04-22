<?php
session_start();
include '../database/config.php';

if (!isset($_GET['booking_id'])) {
    header('Location: index.php');
    exit;
}

$booking_id = $_GET['booking_id'];

try {
    // Fetch booking details
    $stmt = $conn->prepare("
        SELECT 
            b.booking_id,
            b.total_price,
            b.created_at,
            m.title AS movie_title,
            m.poster_url,
            t.name AS theater_name,
            t.location AS theater_location,
            s.show_time,
            GROUP_CONCAT(st.seat_number ORDER BY st.seat_number) AS seats
        FROM 
            bookings b
        JOIN 
            shows sh ON b.show_id = sh.show_id
        JOIN 
            movies m ON sh.movie_id = m.movie_id
        JOIN 
            theaters t ON sh.theater_id = t.theater_id
        JOIN 
            seats st ON sh.show_id = st.show_id
        WHERE 
            b.booking_id = :booking_id
        GROUP BY 
            b.booking_id
    ");
    
    $stmt->bindParam(':booking_id', $booking_id);
    $stmt->execute();
    $booking = $stmt->fetch();
    
    if (!$booking) {
        header('Location: index.php');
        exit;
    }
    
} catch (PDOException $e) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - CineBook</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
        }
        
        .confirmation-card {
            background-color: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .success-icon {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .download-button {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            transition: all 0.3s ease;
        }
        
        .download-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <div class="container mx-auto px-4 py-16">
        <div class="max-w-2xl mx-auto">
            <div class="confirmation-card rounded-xl overflow-hidden">
                <div class="p-8 text-center">
                    <div class="success-icon w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    
                    <h1 class="text-3xl font-bold mb-2">Booking Confirmed!</h1>
                    <p class="text-gray-400 mb-8">Your tickets have been booked successfully.</p>
                    
                    <div class="bg-gray-800 bg-opacity-50 rounded-lg p-6 mb-8">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-gray-400">Booking ID</span>
                            <span class="font-medium">#<?php echo str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        
                        <div class="border-t border-gray-700 -mx-6 my-4"></div>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-gray-400">Movie</span>
                                <span class="font-medium"><?php echo htmlspecialchars($booking['movie_title']); ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-400">Theater</span>
                                <span class="text-right">
                                    <div class="font-medium"><?php echo htmlspecialchars($booking['theater_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['theater_location']); ?></div>
                                </span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-400">Date & Time</span>
                                <span class="text-right">
                                    <div class="font-medium"><?php echo date('l, F j, Y', strtotime($booking['show_time'])); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo date('g:i A', strtotime($booking['show_time'])); ?></div>
                                </span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-400">Seats</span>
                                <span class="font-medium"><?php echo htmlspecialchars($booking['seats']); ?></span>
                            </div>
                            
                            <div class="flex justify-between">
                                <span class="text-gray-400">Amount Paid</span>
                                <span class="font-medium">₨<?php echo number_format($booking['total_price'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button class="download-button px-6 py-3 rounded-lg text-white font-medium flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                            Download Ticket
                        </button>
                        
                        <a href="index.php" class="px-6 py-3 rounded-lg text-white font-medium border border-gray-600 hover:bg-gray-800 transition-colors flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                            </svg>
                            Back to Home
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</body>
</html>

