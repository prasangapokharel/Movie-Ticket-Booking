<?php
include '../database/config.php';
session_start();

error_log("Loading my_bookings.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$bookings_query = $conn->prepare("
    SELECT b.booking_id, b.show_id, b.total_price, b.booking_status, b.created_at,
           b.payment_status, b.payment_method, b.payment_id,
           m.movie_id, m.title, m.poster_url, m.language, m.genre, m.duration,
           s.show_time, s.price,
           t.name as theater_name, t.location
    FROM bookings b
    JOIN shows s ON b.show_id = s.show_id
    JOIN movies m ON s.movie_id = m.movie_id
    LEFT JOIN theaters t ON s.theater_id = t.theater_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");
$bookings_query->execute([$user_id]);
$bookings = $bookings_query->fetchAll(PDO::FETCH_ASSOC);

function getBookingSeats($conn, $booking_id, $show_id) {
    error_log("Fetching seats for booking ID: $booking_id, show ID: $show_id");
    
    // First try to get seats directly associated with this booking_id
    $seats_query = $conn->prepare("
        SELECT seat_number 
        FROM seats 
        WHERE booking_id = ? AND show_id = ? AND status = 'booked'
        ORDER BY seat_number
    ");
    $seats_query->execute([$booking_id, $show_id]);
    $seats = $seats_query->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($seats)) {
        error_log("Found " . count($seats) . " seats with booking_id: " . implode(', ', $seats));
        return $seats;
    }
    
    // If no seats found with booking_id, check payment logs for this booking
    error_log("No seats found with booking_id, checking payment logs");
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
        error_log("Found payment_id in logs: $payment_id");
        
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
            error_log("Payment time: $payment_time");
            
            // Look for seats booked around the same time (within 10 minutes)
            $seats_by_time_query = $conn->prepare("
                SELECT seat_number
                FROM seats
                WHERE show_id = ? AND status = 'booked'
                AND created_at BETWEEN DATE_SUB(?, INTERVAL 10 MINUTE) AND DATE_ADD(?, INTERVAL 10 MINUTE)
                AND booking_id IS NULL
                ORDER BY seat_number
            ");
            $seats_by_time_query->execute([$show_id, $payment_time, $payment_time]);
            $seats_by_time = $seats_by_time_query->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($seats_by_time)) {
                error_log("Found " . count($seats_by_time) . " seats by time: " . implode(', ', $seats_by_time));
                
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
    error_log("No seats found by payment time, checking booking creation time");
    $booking_query = $conn->prepare("SELECT created_at FROM bookings WHERE booking_id = ?");
    $booking_query->execute([$booking_id]);
    $booking_time = $booking_query->fetchColumn();
    
    if ($booking_time) {
        error_log("Booking time: $booking_time");
        
        // Look for seats booked around the same time (within 10 minutes)
        $seats_query = $conn->prepare("
            SELECT seat_number 
            FROM seats 
            WHERE show_id = ? AND status = 'booked'
            AND created_at BETWEEN DATE_SUB(?, INTERVAL 10 MINUTE) AND DATE_ADD(?, INTERVAL 10 MINUTE)
            AND booking_id IS NULL
            ORDER BY seat_number
        ");
        $seats_query->execute([$show_id, $booking_time, $booking_time]);
        $seats = $seats_query->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($seats)) {
            error_log("Found " . count($seats) . " seats by booking time: " . implode(', ', $seats));
            
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
    
    error_log("No seats found for booking ID: $booking_id");
    return [];
}

$booking_count = count($bookings);

$cancelable_count = 0;
foreach ($bookings as $booking) {
    if (strtotime($booking['show_time']) > time() && $booking['booking_status'] != 'Cancelled') {
        $cancelable_count++;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    $show_id = $_POST['show_id'];
    
    $conn->beginTransaction();
    try {
        $update_booking = $conn->prepare("UPDATE bookings SET booking_status = 'Cancelled' WHERE booking_id = ? AND user_id = ?");
        $update_booking->execute([$booking_id, $user_id]);
        
        $seats = getBookingSeats($conn, $booking_id, $show_id);
        
        $update_seat = $conn->prepare("UPDATE seats SET status = 'available', booking_id = NULL WHERE show_id = ? AND seat_number = ?");
        foreach ($seats as $seat) {
            $update_seat->execute([$show_id, $seat]);
        }
        
        $conn->commit();
        
        if (isset($booking['payment_status']) && $booking['payment_status'] == 'pending') {
            header('Location: payment.php?booking_id=' . $booking_id);
            exit();
        }
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Your booking has been successfully cancelled.'
        ];
        
        header('Location: my_bookings.php');
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        
        $_SESSION['alert'] = [
            'type' => 'error',
            'message' => 'Error cancelling booking: ' . $e->getMessage()
        ];
        
        header('Location: my_bookings.php');
        exit();
    }
}

function generateQRData($booking, $seats_text) {
    $data = [
        'id' => $booking['booking_id'],
        'movie' => $booking['title'],
        'theater' => $booking['theater_name'],
        'date' => date('Y-m-d', strtotime($booking['show_time'])),
        'time' => date('H:i', strtotime($booking['show_time'])),
        'seats' => $seats_text,
        'code' => strtoupper(substr(md5($booking['booking_id'] . $booking['created_at']), 0, 8))
    ];
    return json_encode($data);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - CineBook</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
        }
        
        .booking-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 16px;
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
            padding: 8px;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100px;
            height: 100px;
        }
        .qr-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }
        
        .modal-content {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            max-width: 90%;
            width: 400px;
            margin: 10% auto;
            padding: 20px;
        }
        
        .ticket-qr {
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 0 auto;
            width: fit-content;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            text-align: center;
            cursor: pointer;
        }
        
        .btn-cancel {
            background-color: #7f1d1d;
            color: #fca5a5;
        }
        
        .btn-view {
            background-color: #1e3a8a;
            color: #bfdbfe;
        }
        
        .btn-pay {
            background-color: #5C2D91;
            color: #c4b5fd;
        }
        
        .btn-print {
            background-color: #b91c1c;
            color: white;
        }
        
        .btn-close {
            background-color: #475569;
            color: white;
        }
        
        .stats-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 1rem;
        }
        
        .empty-state {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
        }
        
        .alert {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            padding: 1rem;
            border-radius: 8px;
            max-width: 400px;
        }
        
        .alert-success {
            background-color: #064e3b;
            border: 1px solid #065f46;
            color: #6ee7b7;
        }
        
        .alert-error {
            background-color: #7f1d1d;
            border: 1px solid #991b1b;
            color: #fca5a5;
        }
        
        .filter-tabs {
            display: flex;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding-bottom: 5px;
            margin-bottom: 16px;
        }
        
        .filter-tabs::-webkit-scrollbar {
            display: none;
        }
        
        .filter-tab {
            white-space: nowrap;
            padding: 8px 16px;
            border-radius: 8px;
            margin-right: 8px;
            background-color: #1e293b;
            color: #94a3b8;
            cursor: pointer;
        }
        
        .filter-tab.active {
            background-color: #b91c1c;
            color: white;
        }
        
        .confirmed-badge {
            background-color: #064e3b;
            color: #6ee7b7;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        
        @media print {
            body * {
                visibility: hidden;
            }
            .modal, .modal * {
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
    <?php include '../includes/loader.php'; ?>
    
    <?php include '../includes/nav.php'; ?>
    
    <?php if (isset($_SESSION['alert'])): ?>
    <div id="alert" class="alert <?php echo $_SESSION['alert']['type'] === 'success' ? 'alert-success' : 'alert-error'; ?>">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <?php if ($_SESSION['alert']['type'] === 'success'): ?>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <?php else: ?>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <?php endif; ?>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium"><?php echo $_SESSION['alert']['message']; ?></p>
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
    <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['payment_success'])): ?>
    <div id="payment-alert" class="alert alert-success">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium"><?php echo $_SESSION['payment_success']; ?></p>
            </div>
            <div class="ml-auto pl-3">
                <button type="button" onclick="dismissPaymentAlert()" class="inline-flex rounded-md p-1.5 hover:text-white focus:outline-none">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['payment_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['payment_error'])): ?>
    <div id="payment-alert" class="alert alert-error">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium"><?php echo $_SESSION['payment_error']; ?></p>
            </div>
            <div class="ml-auto pl-3">
                <button type="button" onclick="dismissPaymentAlert()" class="inline-flex rounded-md p-1.5 hover:text-white focus:outline-none">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['payment_error']); ?>
    <?php endif; ?>
    
    <div class="max-w-6xl mx-auto px-4 pt-8 pb-24">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold mb-2 text-white">My Bookings</h1>
                <p class="text-gray-400">Manage your movie tickets and bookings</p>
            </div>
            
            <a href="index.php" class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 rounded-lg text-white font-medium bg-red-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z" />
                    <path fill-rule="evenodd" d="M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z" clip-rule="evenodd" />
                </svg>
                Book New Ticket
            </a>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-8">
            <div class="stats-card flex flex-col items-center justify-center py-4">
                <span class="text-sm text-slate-400 mb-1">Total Bookings</span>
                <span class="text-3xl font-bold text-white"><?php echo $booking_count; ?></span>
            </div>
            <div class="stats-card flex flex-col items-center justify-center py-4">
                <span class="text-sm text-slate-400 mb-1">Active Bookings</span>
                <span class="text-3xl font-bold text-blue-400"><?php echo $cancelable_count; ?></span>
            </div>
            <div class="stats-card flex flex-col items-center justify-center py-4">
                <span class="text-sm text-slate-400 mb-1">Completed</span>
                <span class="text-3xl font-bold text-green-400"><?php echo count(array_filter($bookings, function($b) { return strtotime($b['show_time']) < time() && $b['booking_status'] != 'Cancelled'; })); ?></span>
            </div>
            <div class="stats-card flex flex-col items-center justify-center py-4">
                <span class="text-sm text-slate-400 mb-1">Cancelled</span>
                <span class="text-3xl font-bold text-red-400"><?php echo count(array_filter($bookings, function($b) { return $b['booking_status'] == 'Cancelled'; })); ?></span>
            </div>
        </div>
        
        <?php if (count($bookings) > 0): ?>
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">
                    All Bookings
                </button>
                <button class="filter-tab" data-filter="active">
                    Active
                </button>
                <button class="filter-tab" data-filter="completed">
                    Completed
                </button>
                <button class="filter-tab" data-filter="pending">
                    Pending Payment
                </button>
                <button class="filter-tab" data-filter="cancelled">
                    Cancelled
                </button>
            </div>
            
            <div class="space-y-6">
                <?php foreach ($bookings as $booking):
                    $seats = getBookingSeats($conn, $booking['booking_id'], $booking['show_id']);
                    $seats_text = !empty($seats) ? implode(', ', $seats) : 'Seats not assigned';
                    
                    $show_date = date('D, d M Y', strtotime($booking['show_time']));
                    $show_time = date('h:i A', strtotime($booking['show_time']));
                    
                    $is_past_show = strtotime($booking['show_time']) < time();
                    
                    $status_color = '';
                    $booking_status_class = '';
                    switch ($booking['booking_status']) {
                        case 'Confirmed':
                            $status_color = 'bg-green-900 text-green-400';
                            $booking_status_class = $is_past_show ? 'completed' : 'active';
                            break;
                        case 'Cancelled':
                            $status_color = 'bg-red-900 text-red-400';
                            $booking_status_class = 'cancelled';
                            break;
                        default:
                            $status_color = 'bg-yellow-900 text-yellow-400';
                            $booking_status_class = 'active';
                    }
                    
                    $payment_status_color = '';
                    switch ($booking['payment_status']) {
                        case 'paid':
                            $payment_status_color = 'bg-green-900 text-green-400';
                            break;
                        case 'failed':
                            $payment_status_color = 'bg-red-900 text-red-400';
                            break;
                        case 'refunded':
                            $payment_status_color = 'bg-blue-900 text-blue-400';
                            break;
                        default:
                            $payment_status_color = 'bg-yellow-900 text-yellow-400';
                            $booking_status_class = 'pending';
                    }
                    
                    $booking_code = strtoupper(substr(md5($booking['booking_id'] . $booking['created_at']), 0, 8));
                    $qr_data = generateQRData($booking, $seats_text);
                ?>
                
                <div class="booking-card booking-item" data-status="<?php echo $booking_status_class; ?>">
                    <div class="p-4 md:p-6 flex flex-col md:flex-row gap-4">
                        <div class="w-full md:w-1/4 aspect-[2/3] overflow-hidden rounded-lg bg-slate-800 flex-shrink-0">
                            <?php if (!empty($booking['poster_url'])): ?>
                            <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" alt="<?php echo htmlspecialchars($booking['title']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-slate-700">
                                <span class="text-slate-400">No Image</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <h2 class="text-xl font-bold mb-1"><?php echo htmlspecialchars($booking['title']); ?></h2>
                                <div class="flex space-x-2">
                                    <span class="status-badge <?php echo $status_color; ?>">
                                        <?php echo $booking['booking_status']; ?>
                                    </span>
                                    <?php if (isset($booking['payment_status'])): ?>
                                    <span class="status-badge <?php echo $payment_status_color; ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <?php if ($booking['payment_status'] == 'paid'): ?>
                            <div class="confirmed-badge mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                Payment Confirmed
                            </div>
                            <?php endif; ?>
                            
                            <div class="text-sm text-slate-400 mb-2">
                                <span><?php echo htmlspecialchars($booking['language']); ?></span>
                                <?php if (!empty($booking['genre'])): ?>
                                <span class="mx-2">•</span>
                                <span><?php echo htmlspecialchars($booking['genre']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($booking['duration'])): ?>
                                <span class="mx-2">•</span>
                                <span><?php echo $booking['duration']; ?> min</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="text-sm mb-1 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-400 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-white"><?php echo $show_date; ?></span>
                            </div>
                            
                            <div class="text-sm mb-1 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-400 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-white"><?php echo $show_time; ?></span>
                            </div>
                            
                            <div class="text-sm mb-1 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-400 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-white"><?php echo htmlspecialchars($booking['theater_name']); ?><?php echo !empty($booking['location']) ? ', ' . htmlspecialchars($booking['location']) : ''; ?></span>
                            </div>
                            
                            <div class="text-sm flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-400 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-white">Booking ID: <?php echo $booking_code; ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="divider"></div>
                    
                    <div class="px-4 md:px-6 pb-4 md:pb-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                            <div class="ticket-info">
                                <div class="text-xs text-slate-400">Seats</div>
                                <div class="text-sm font-medium text-white"><?php echo $seats_text; ?></div>
                            </div>
                            <div class="ticket-info">
                                <div class="text-xs text-slate-400">Amount Paid</div>
                                <div class="text-sm font-medium text-white">₨ <?php echo number_format($booking['total_price'], 2); ?></div>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row items-center gap-3">
                            <div class="qr-container">
                                <div id="qrcode-<?php echo $booking['booking_id']; ?>"></div>
                            </div>
                            
                            <div class="flex-1 flex flex-col gap-2">
                                <?php if ($booking['booking_status'] == 'Confirmed' && !$is_past_show): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this booking? This action cannot be undone.');">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                    <input type="hidden" name="show_id" value="<?php echo $booking['show_id']; ?>">
                                    <button type="submit" name="cancel_booking" class="w-full btn btn-cancel">
                                        <span class="flex items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                            Cancel Booking
                                        </span>
                                    </button>
                                </form>
                                <?php endif; ?>
                                
                                <?php if (isset($booking['payment_status']) && $booking['payment_status'] == 'pending'): ?>
                                <a href="payment.php?booking_id=<?php echo $booking['booking_id']; ?>" class="w-full btn btn-pay text-center">
                                    <span class="flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        </svg>
                                        Complete Payment
                                    </span>
                                </a>
                                <?php endif; ?>
                                
                                <button onclick="showTicket(<?php echo $booking['booking_id']; ?>)" class="w-full btn btn-view">
                                    <span class="flex items-center justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z" />
                                        </svg>
                                        View Ticket
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="ticketModal-<?php echo $booking['booking_id']; ?>" class="modal">
                    <div class="modal-content">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-xl font-bold">Movie Ticket</h3>
                            <button onclick="closeTicket(<?php echo $booking['booking_id']; ?>)" class="text-slate-400 hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <div id="printTicket" class="bg-slate-800 rounded-lg p-4 mb-4 print-border">
                            <div class="hidden print-logo">CineBook Ticket</div>
                            
                            <div class="flex justify-between items-start mb-4">
                                <h4 class="text-lg font-bold print-dark"><?php echo htmlspecialchars($booking['title']); ?></h4>
                                <span class="status-badge <?php echo $status_color; ?> print-dark">
                                    <?php echo $booking['booking_status']; ?>
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div>
                                    <p class="text-xs text-slate-400 print-dark">Date & Time</p>
                                    <p class="text-sm font-medium print-dark"><?php echo $show_date; ?> at <?php echo $show_time; ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 print-dark">Theater</p>
                                    <p class="text-sm font-medium print-dark"><?php echo htmlspecialchars($booking['theater_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 print-dark">Seats</p>
                                    <p class="text-sm font-medium print-dark"><?php echo $seats_text; ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 print-dark">Booking ID</p>
                                    <p class="text-sm font-medium print-dark"><?php echo $booking_code; ?></p>
                                </div>
                            </div>
                            
                            <div class="ticket-qr mx-auto mb-3">
                                <div id="ticket-qrcode-<?php echo $booking['booking_id']; ?>"></div>
                            </div>
                            
                            <div>
                                <p class="text-center text-xs text-slate-400 print-dark">Scan this QR code at the theater entrance</p>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 print-hide">
                            <button onclick="printTicket()" class="flex-1 btn btn-print">
                                <span class="flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                                    </svg>
                                    Print Ticket
                                </span>
                            </button>
                            <button onclick="closeTicket(<?php echo $booking['booking_id']; ?>)" class="flex-1 btn btn-close">
                                <span class="flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                    Close
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state flex flex-col items-center justify-center py-16">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-slate-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z" />
                </svg>
                <h3 class="text-xl font-bold mb-2">No Bookings Yet</h3>
                <p class="text-slate-400 mb-6 text-center max-w-md">Looks like you haven't booked any tickets yet. Explore movies and book your first ticket!</p>
                <a href="index.php" class="btn py-2 px-6 rounded-lg text-white font-medium bg-red-700">
                    <span class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z" />
                            <path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd" />
                        </svg>
                        Browse Movies
                    </span>
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.getElementById('alert');
            if (alert) {
                alert.style.display = 'block';
                
                setTimeout(() => {
                    dismissAlert();
                }, 5000);
            }
            
            const paymentAlert = document.getElementById('payment-alert');
            if (paymentAlert) {
                paymentAlert.style.display = 'block';
                
                setTimeout(() => {
                    dismissPaymentAlert();
                }, 5000);
            }
            
            <?php foreach ($bookings as $booking):
                $seats = getBookingSeats($conn, $booking['booking_id'], $booking['show_id']);
                $qr_data = generateQRData($booking, implode(', ', $seats));
            ?>
                generateQRCode('<?php echo $booking['booking_id']; ?>', <?php echo json_encode($qr_data); ?>, 100);
                generateQRCode('ticket-<?php echo $booking['booking_id']; ?>', <?php echo json_encode($qr_data); ?>, 150);
            <?php endforeach; ?>
            
            const filterTabs = document.querySelectorAll('.filter-tab');
            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    filterTabs.forEach(t => {
                        t.classList.remove('active');
                    });
                    
                    this.classList.add('active');
                    
                    const filter = this.getAttribute('data-filter');
                    const bookingItems = document.querySelectorAll('.booking-item');
                    
                    bookingItems.forEach(item => {
                        if (filter === 'all' || item.getAttribute('data-status') === filter) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        });
        
        function dismissAlert() {
            const alert = document.getElementById('alert');
            if (alert) {
                alert.style.display = 'none';
            }
        }
        
        function dismissPaymentAlert() {
            const alert = document.getElementById('payment-alert');
            if (alert) {
                alert.style.display = 'none';
            }
        }
        
        function generateQRCode(elementId, data, size = 100) {
            try {
                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=${size}x${size}&data=${encodeURIComponent(data)}`;
                document.getElementById('qrcode-' + elementId).innerHTML = `
                    <img src="${qrUrl}" alt="QR Code" class="w-full h-full object-contain">
                `;
            } catch (e) {
                console.error("QR Code generation error:", e);
                document.getElementById('qrcode-' + elementId).innerHTML =
                    '<div class="p-2 text-center text-xs text-gray-600">' +
                    data.substring(0, 20) + '...</div>';
            }
        }
        
        function showTicket(bookingId) {
            const modal = document.getElementById('ticketModal-' + bookingId);
            modal.style.display = 'block';
        }
        
        function closeTicket(bookingId) {
            const modal = document.getElementById('ticketModal-' + bookingId);
            modal.style.display = 'none';
        }
        
        function printTicket() {
            window.print();
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        };
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });
    </script>
</body>
</html>
