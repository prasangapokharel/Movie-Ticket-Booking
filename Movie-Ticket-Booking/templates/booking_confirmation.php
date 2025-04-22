<?php
include '../database/config.php';
session_start();

// Check if Composer autoloader exists
if (!file_exists('../vendor/autoload.php')) {
    die("Error: ../vendor/autoload.php not found. Please install Composer dependencies.");
}

require_once '../vendor/autoload.php';

// Import all necessary classes
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Writer;

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
    SELECT b.booking_id, b.show_id, b.total_price, b.booking_status, b.created_at,
           m.movie_id, m.title, m.poster_url, m.language, m.genre, m.duration,
           s.show_time, s.price,
           t.name as theater_name, t.location
    FROM bookings b
    JOIN shows s ON b.show_id = s.show_id
    JOIN movies m ON s.movie_id = m.movie_id
    LEFT JOIN theaters t ON s.theater_id = t.theater_id
    WHERE b.booking_id = ? AND b.user_id = ?
");
$booking_query->execute([$booking_id, $user_id]);
$booking = $booking_query->fetch(PDO::FETCH_ASSOC);

// If booking not found or doesn't belong to user, redirect
if (!$booking) {
    header("Location: my_bookings.php");
    exit();
}

// Function to fetch seats for a specific booking
function getBookingSeats($conn, $booking_id, $show_id) {
    // First try to get seats directly associated with this booking_id
    $seats_query = $conn->prepare("
        SELECT seat_number 
        FROM seats 
        WHERE show_id = ? AND booking_id = ? AND status = 'booked'
        ORDER BY seat_number
    ");
    $seats_query->execute([$show_id, $booking_id]);
    $seats = $seats_query->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($seats)) {
        return $seats;
    }
    
    // If no seats found with booking_id, check payment logs for this booking
    $payment_log_query = $conn->prepare("
        SELECT payment_id 
        FROM payment_logs 
        WHERE booking_id = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $payment_log_query->execute([$booking_id]);
    $payment_id = $payment_log_query->fetchColumn();
    
    if ($payment_id) {
        // Try to find seats that were booked around the same time as the payment
        $payment_log_time_query = $conn->prepare("
            SELECT created_at 
            FROM payment_logs 
            WHERE booking_id = ? AND payment_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $payment_log_time_query->execute([$booking_id, $payment_id]);
        $payment_time = $payment_log_time_query->fetchColumn();
        
        if ($payment_time) {
            // Look for seats booked around the same time (within 10 minutes)
            $seats_by_time_query = $conn->prepare("
                SELECT seat_number 
                FROM seats 
                WHERE show_id = ? AND status = 'booked' 
                AND created_at BETWEEN DATE_SUB(?, INTERVAL 10 MINUTE) AND DATE_ADD(?, INTERVAL 10 MINUTE)
                ORDER BY seat_number
            ");
            $seats_by_time_query->execute([$show_id, $payment_time, $payment_time]);
            $seats_by_time = $seats_by_time_query->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($seats_by_time)) {
                // Update these seats to associate them with the booking_id
                $update_seats = $conn->prepare("
                    UPDATE seats 
                    SET booking_id = ? 
                    WHERE show_id = ? AND seat_number = ? AND status = 'booked'
                ");
                
                foreach ($seats_by_time as $seat) {
                    $update_seats->execute([$booking_id, $show_id, $seat]);
                }
                
                return $seats_by_time;
            }
        }
    }
    
    // If still no seats found, try to find by booking creation time
    $booking_query = $conn->prepare("SELECT created_at FROM bookings WHERE booking_id = ?");
    $booking_query->execute([$booking_id]);
    $booking_time = $booking_query->fetchColumn();
    
    if ($booking_time) {
        // Look for seats booked around the same time (within 10 minutes)
        $seats_query = $conn->prepare("
            SELECT seat_number 
            FROM seats 
            WHERE show_id = ? AND status = 'booked' 
            AND created_at BETWEEN DATE_SUB(?, INTERVAL 10 MINUTE) AND DATE_ADD(?, INTERVAL 10 MINUTE)
            ORDER BY seat_number
        ");
        $seats_query->execute([$show_id, $booking_time, $booking_time]);
        $seats = $seats_query->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($seats)) {
            // Update these seats to associate them with the booking_id
            $update_seats = $conn->prepare("
                UPDATE seats 
                SET booking_id = ? 
                WHERE show_id = ? AND seat_number = ? AND status = 'booked'
            ");
            
            foreach ($seats as $seat) {
                $update_seats->execute([$booking_id, $show_id, $seat]);
            }
            
            return $seats;
        }
    }
    
    return [];
}

// Get seats for this booking
$seats = getBookingSeats($conn, $booking_id, $booking['show_id']);
$seats_text = implode(', ', $seats);

// Format date & time
$show_date = date('D, d M Y', strtotime($booking['show_time']));
$show_time = date('h:i A', strtotime($booking['show_time']));

// Generate a simple booking code
$booking_code = strtoupper(substr(md5($booking['booking_id'] . $booking['created_at']), 0, 8));

// Generate QR code data
$qr_data = [
    'id' => $booking['booking_id'],
    'movie' => $booking['title'],
    'theater' => $booking['theater_name'],
    'date' => date('Y-m-d', strtotime($booking['show_time'])),
    'time' => date('H:i', strtotime($booking['show_time'])),
    'seats' => $seats_text,
    'code' => $booking_code
];
$qr_json = json_encode($qr_data);

// Generate QR code
$qrCodePath = '';
$qrCodeDataUri = '';

try {
    // Create directory for QR codes if it doesn't exist
    $qrDir = '../uploads/qrcodes';
    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0755, true);
    }
    
    // Define QR code file path
    $qrCodePath = $qrDir . '/booking_' . $booking_id . '_' . time() . '.png';
    
    // Check if we should use Imagick or SVG
    if (extension_loaded('imagick')) {
        // Use Imagick backend with custom colors and improved settings
        $renderer = new ImageRenderer(
            new RendererStyle(
                400,                  // Size increased for better quality
                3,                    // Margin reduced for cleaner look
                null,                 // Default foreground color
                null                  // Default background color
            ),
            new ImagickImageBackEnd()
        );
    } else {
        // Fallback to SVG if Imagick is not available
        $renderer = new ImageRenderer(
            new RendererStyle(
                400,                  // Size increased for better quality
                3,                    // Margin reduced for cleaner look
                null,                 // Default foreground color
                null                  // Default background color
            ),
            new SvgImageBackEnd()
        );
    }
    
    $writer = new Writer($renderer);
    
    // Generate the QR code and save to file
    $writer->writeFile($qr_json, $qrCodePath);
    
    // Convert to data URI for display
    $imageType = (extension_loaded('imagick')) ? 'png' : 'svg+xml';
    $imageData = file_get_contents($qrCodePath);
    $base64 = base64_encode($imageData);
    $qrCodeDataUri = 'data:image/' . $imageType . ';base64,' . $base64;
    
} catch (Exception $e) {
    // Fallback to QR Server API if bacon/bacon-qr-code fails
    $qrCodeDataUri = 'https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=' . urlencode($qr_json) . '&margin=3';
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
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .status-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 9999px;
            font-weight: 600;
            text-transform: uppercase;
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
        
        .qr-container {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 200px;
            height: 200px;
            margin: 0 auto;
        }

        .qr-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
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
        
        .success-icon {
            background-color: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 1rem;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            #printTicket, #printTicket * {
                visibility: visible;
            }
            #printTicket {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white;
                color: black;
                padding: 20px;
            }
            .print-dark {
                color: #1e293b !important;
            }
            .print-hide {
                display: none !important;
            }
            .print-border {
                border: 1px dashed #1e293b !important;
                padding: 1rem !important;
                border-radius: 8px !important;
            }
            .print-logo {
                display: block !important;
                text-align: center !important;
                margin-bottom: 1rem !important;
                font-size: 1.5rem !important;
                font-weight: bold !important;
            }
        }
    </style>
</head>
<body>
    <!-- Include loader -->
    <?php include '../includes/loader.php'; ?>
    
    <!-- Include navigation -->
    <?php include '../includes/nav.php'; ?>
    
    <div class="max-w-4xl mx-auto px-4 pt-8 pb-24">
        <!-- Success message -->
        <div class="text-center mb-8">
            <div class="success-icon">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <h1 class="text-3xl font-bold mb-2 text-white">Booking Confirmed!</h1>
            <p class="text-gray-400 max-w-lg mx-auto">Your movie tickets have been successfully booked. You can find your booking details below.</p>
        </div>
        
        <!-- Ticket section -->
        <div id="printTicket" class="confirmation-card mb-8 print-border">
            <div class="hidden print-logo">CineBook Ticket</div>
            
            <!-- Top section with movie info -->
            <div class="p-6 flex flex-col md:flex-row gap-6">
                <div class="w-full md:w-1/3 aspect-[2/3] overflow-hidden rounded-lg bg-slate-800 flex-shrink-0">
                    <?php if (!empty($booking['poster_url'])): ?>
                    <img src="<?php echo $booking['poster_url']; ?>" alt="<?php echo $booking['title']; ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-slate-700">
                        <span class="text-slate-400">No Image</span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <div class="flex justify-between items-start">
                        <h2 class="text-2xl font-bold mb-2 print-dark"><?php echo $booking['title']; ?></h2>
                        <span class="status-badge bg-green-900 text-green-400 print-dark">
                            CONFIRMED
                        </span>
                    </div>
                    <div class="text-sm text-slate-400 mb-4 print-dark">
                        <span><?php echo $booking['language']; ?></span>
                        <?php if (!empty($booking['genre'])): ?>
                        <span class="mx-2">•</span>
                        <span><?php echo $booking['genre']; ?></span>
                        <?php endif; ?>
                        <?php if (!empty($booking['duration'])): ?>
                        <span class="mx-2">•</span>
                        <span><?php echo $booking['duration']; ?> min</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="ticket-info">
                            <div class="text-xs text-slate-400 print-dark">Date</div>
                            <div class="text-sm font-medium text-white print-dark"><?php echo $show_date; ?></div>
                        </div>
                        <div class="ticket-info">
                            <div class="text-xs text-slate-400 print-dark">Time</div>
                            <div class="text-sm font-medium text-white print-dark"><?php echo $show_time; ?></div>
                        </div>
                        <div class="ticket-info">
                            <div class="text-xs text-slate-400 print-dark">Theater</div>
                            <div class="text-sm font-medium text-white print-dark"><?php echo $booking['theater_name']; ?><?php echo !empty($booking['location']) ? ', ' . $booking['location'] : ''; ?></div>
                        </div>
                        <div class="ticket-info">
                            <div class="text-xs text-slate-400 print-dark">Booking ID</div>
                            <div class="text-sm font-medium text-white print-dark"><?php echo $booking_code; ?></div>
                        </div>
                    </div>
                    
                    <div class="ticket-info mb-4">
                        <div class="text-xs text-slate-400 print-dark">Seats</div>
                        <div class="text-sm font-medium text-white print-dark"><?php echo $seats_text; ?></div>
                    </div>
                    
                    <div class="ticket-info">
                        <div class="text-xs text-slate-400 print-dark">Amount Paid</div>
                        <div class="text-sm font-medium text-white print-dark">₨ <?php echo number_format($booking['total_price'], 2); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="divider print-hide"></div>
            
            <!-- QR Code section -->
            <div class="p-6 text-center">
                <div class="qr-container mx-auto mb-4">
                    <?php if (!empty($qrCodeDataUri)): ?>
                    <img src="<?php echo $qrCodeDataUri; ?>" alt="Booking QR Code" class="w-full h-full object-contain">
                    <?php else: ?>
                    <div id="qrcode"></div>
                    <?php endif; ?>
                </div>
                
                <p class="text-center text-xs text-slate-400 print-dark">Scan this QR code at the theater entrance</p>
            </div>
        </div>
        
        <!-- Action buttons -->
        <div class="flex flex-col sm:flex-row gap-4 print-hide">
            <button onclick="printTicket()" class="flex-1 btn btn-primary">
                <span class="flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                    </svg>
                    Print Ticket
                </span>
            </button>
            <a href="my_bookings.php" class="flex-1 btn btn-secondary text-center">
                <span class="flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.293 15.707a1 1 0 010-1.414L14.586 10l-4.293-4.293a1 1 0 111.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M4.293 15.707a1 1 0 010-1.414L8.586 10 4.293 5.707a1 1 0 011.414-1.414l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                    View All Bookings
                </span>
            </a>
            <a href="index.php" class="flex-1 btn btn-secondary text-center">
                <span class="flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" clip-rule="evenodd" />
                    </svg>
                    Back to Home
                </span>
            </a>
        </div>
        
        <!-- Additional information -->
        <div class="mt-8 p-6 confirmation-card print-hide">
            <h3 class="text-lg font-bold mb-4">Important Information</h3>
            <ul class="space-y-2 text-sm text-slate-300">
                <li class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400 mr-2 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Please arrive at least 15 minutes before the show time.</span>
                </li>
                <li class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400 mr-2 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Present this ticket (printed or digital) at the theater entrance.</span>
                </li>
                <li class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400 mr-2 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Outside food and beverages are not allowed in the theater.</span>
                </li>
                <li class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400 mr-2 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Recording of movies is strictly prohibited and is punishable by law.</span>
                </li>
                <li class="flex items-start">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-400 mr-2 flex-shrink-0 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>Cancellations are allowed up to 4 hours before the show time.</span>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Include footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Function to print ticket
        function printTicket() {
            window.print();
        }
        
        // Generate QR code
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (empty($qrCodeDataUri)): ?>
            // Fallback to API-based QR code generation if the library isn't available
            try {
                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(<?php echo json_encode($qr_json); ?>)}&margin=3`;
                document.getElementById('qrcode').innerHTML = `
                    <img src="${qrUrl}" alt="QR Code" class="w-full h-full object-contain">
                `;
            } catch (e) {
                console.error("QR Code generation error:", e);
                document.getElementById('qrcode').innerHTML = 
                    '<div class="p-2 text-center text-xs text-gray-600">Booking ID: <?php echo $booking_code; ?></div>';
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>

