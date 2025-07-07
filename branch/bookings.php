<?php
include '../database/config.php';
session_start();

if (!isset($_SESSION['branch_id'])) {
    header("Location: login.php");
    exit();
}

$branch_id = $_SESSION['branch_id'];

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$movie_filter = $_GET['movie'] ?? '';

// Build query with filters
$where_conditions = ["t.branch_id = ?"];
$params = [$branch_id];

if (!empty($status_filter)) {
    $where_conditions[] = "b.booking_status = ?";
    $params[] = $status_filter;
}

if (!empty($date_filter)) {
    $where_conditions[] = "DATE(b.created_at) = ?";
    $params[] = $date_filter;
}

if (!empty($movie_filter)) {
    $where_conditions[] = "m.movie_id = ?";
    $params[] = $movie_filter;
}

$where_clause = implode(' AND ', $where_conditions);

// Fetch bookings
try {
    $bookings_stmt = $conn->prepare("
        SELECT b.booking_id, b.total_price, b.booking_status, b.payment_status, 
               b.payment_method, b.created_at, u.name as user_name, u.email as user_email,
               m.title as movie_title, t.name as theater_name, h.hall_name,
               s.show_time, s.price as ticket_price,
               GROUP_CONCAT(seats.seat_number ORDER BY seats.seat_number) as seat_numbers,
               COUNT(seats.seat_id) as total_seats
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN shows s ON b.show_id = s.show_id
        JOIN movies m ON s.movie_id = m.movie_id
        JOIN theaters t ON s.theater_id = t.theater_id
        LEFT JOIN halls h ON s.hall_id = h.hall_id
        LEFT JOIN seats ON b.booking_id = seats.booking_id
        WHERE {$where_clause}
        GROUP BY b.booking_id
        ORDER BY b.created_at DESC
        LIMIT 100
    ");
    $bookings_stmt->execute($params);
    $bookings = $bookings_stmt->fetchAll();
} catch (PDOException $e) {
    $bookings = [];
    $error_message = "Error fetching bookings: " . $e->getMessage();
}

// Get statistics
try {
    $stats_stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_bookings,
            SUM(CASE WHEN b.booking_status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed_bookings,
            SUM(CASE WHEN b.booking_status = 'Pending' THEN 1 ELSE 0 END) as pending_bookings,
            SUM(CASE WHEN b.booking_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled_bookings,
            SUM(CASE WHEN b.booking_status = 'Confirmed' THEN b.total_price ELSE 0 END) as total_revenue,
            SUM(CASE WHEN DATE(b.created_at) = CURDATE() AND b.booking_status = 'Confirmed' THEN b.total_price ELSE 0 END) as today_revenue
        FROM bookings b
        JOIN shows s ON b.show_id = s.show_id
        JOIN theaters t ON s.theater_id = t.theater_id
        WHERE t.branch_id = ?
    ");
    $stats_stmt->execute([$branch_id]);
    $stats = $stats_stmt->fetch();
} catch (PDOException $e) {
    $stats = [
        'total_bookings' => 0,
        'confirmed_bookings' => 0,
        'pending_bookings' => 0,
        'cancelled_bookings' => 0,
        'total_revenue' => 0,
        'today_revenue' => 0
    ];
}

// Get movies for filter
try {
    $movies_stmt = $conn->prepare("
        SELECT DISTINCT m.movie_id, m.title 
        FROM movies m
        JOIN shows s ON m.movie_id = s.movie_id
        JOIN theaters t ON s.theater_id = t.theater_id
        WHERE t.branch_id = ?
        ORDER BY m.title
    ");
    $movies_stmt->execute([$branch_id]);
    $movies = $movies_stmt->fetchAll();
} catch (PDOException $e) {
    $movies = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Branch Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #000000; color: #ffffff; }
    </style>
</head>
<body>
    <?php include '../includes/branchnav.php'; ?>
    
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-white mb-8">
                <i class="fas fa-ticket-alt mr-3"></i>Bookings Management
            </h1>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-ticket-alt text-white text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Total Bookings</p>
                            <p class="text-2xl font-semibold text-white"><?php echo $stats['total_bookings']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Confirmed</p>
                            <p class="text-2xl font-semibold text-white"><?php echo $stats['confirmed_bookings']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clock text-yellow-400 text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Pending</p>
                            <p class="text-2xl font-semibold text-white"><?php echo $stats['pending_bookings']; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-dollar-sign text-white text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Total Revenue</p>
                            <p class="text-2xl font-semibold text-white">Rs<?php echo number_format($stats['total_revenue'], 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-white mb-4">
                    <i class="fas fa-filter mr-2"></i>Filters
                </h2>
                
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                            <option value="">All Status</option>
                            <option value="Confirmed" <?php echo $status_filter == 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Date</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($date_filter); ?>"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Movie</label>
                        <select name="movie" class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                            <option value="">All Movies</option>
                            <?php foreach ($movies as $movie): ?>
                                <option value="<?php echo $movie['movie_id']; ?>" <?php echo $movie_filter == $movie['movie_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($movie['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-md font-medium transition-colors">
                            <i class="fas fa-search mr-2"></i>Filter
                        </button>
                        <a href="bookings.php" class="bg-gray-700 text-white hover:bg-gray-600 px-4 py-2 rounded-md font-medium transition-colors">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Bookings Table -->
            <div class="bg-gray-900 border border-gray-700 rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h2 class="text-xl font-semibold text-white">
                        <i class="fas fa-list mr-2"></i>Booking Details
                    </h2>
                </div>
                
                <?php if (empty($bookings)): ?>
                    <div class="p-6 text-center text-gray-400">
                        No bookings found with the current filters.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Booking ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Movie & Show</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Seats</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Payment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-900 divide-y divide-gray-700">
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-white">#<?php echo $booking['booking_id']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($booking['user_email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($booking['movie_title']); ?></div>
                                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($booking['theater_name']); ?></div>
                                            <?php if ($booking['hall_name']): ?>
                                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($booking['hall_name']); ?></div>
                                            <?php endif; ?>
                                            <div class="text-xs text-gray-400"><?php echo date('M j, Y H:i', strtotime($booking['show_time'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white"><?php echo $booking['total_seats']; ?> seats</div>
                                            <?php if ($booking['seat_numbers']): ?>
                                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($booking['seat_numbers']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-white">Rs<?php echo number_format($booking['total_price'], 2); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php 
                                                switch($booking['booking_status']) {
                                                    case 'Confirmed': echo 'bg-green-100 text-green-800'; break;
                                                    case 'Pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    case 'Cancelled': echo 'bg-red-100 text-red-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo $booking['booking_status']; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-xs text-gray-400">
                                                <?php echo ucfirst($booking['payment_status'] ?? 'N/A'); ?>
                                            </div>
                                            <?php if ($booking['payment_method']): ?>
                                                <div class="text-xs text-gray-400"><?php echo ucfirst($booking['payment_method']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white"><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></div>
                                            <div class="text-xs text-gray-400"><?php echo date('H:i', strtotime($booking['created_at'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="viewBookingDetails(<?php echo $booking['booking_id']; ?>)" 
                                                    class="text-white hover:text-gray-300 mr-3">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <!-- <?php if ($booking['booking_status'] == 'Pending'): ?>
                                                <button onclick="updateBookingStatus(<?php echo $booking['booking_id']; ?>, 'Confirmed')" 
                                                        class="text-green-400 hover:text-green-300 mr-3">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button onclick="updateBookingStatus(<?php echo $booking['booking_id']; ?>, 'Cancelled')" 
                                                        class="text-red-400 hover:text-red-300">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php endif; ?> -->
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Booking Details Modal -->
    <div id="bookingModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-gray-900 border border-gray-700 rounded-lg p-6 max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 id="modalTitle" class="text-xl font-semibold text-white">Booking Details</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="modalContent"></div>
        </div>
    </div>
    
    <script>
        function viewBookingDetails(bookingId) {
            const bookings = <?php echo json_encode($bookings); ?>;
            const booking = bookings.find(b => b.booking_id == bookingId);
            
            if (booking) {
                document.getElementById('modalContent').innerHTML = `
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-white font-semibold mb-2">Customer Information</h4>
                                <div class="space-y-1 text-sm">
                                    <div><span class="text-gray-400">Name:</span> <span class="text-white">${booking.user_name}</span></div>
                                    <div><span class="text-gray-400">Email:</span> <span class="text-white">${booking.user_email}</span></div>
                                </div>
                            </div>
                            
                            <div>
                                <h4 class="text-white font-semibold mb-2">Booking Information</h4>
                                <div class="space-y-1 text-sm">
                                    <div><span class="text-gray-400">Booking ID:</span> <span class="text-white">#${booking.booking_id}</span></div>
                                    <div><span class="text-gray-400">Status:</span> <span class="text-white">${booking.booking_status}</span></div>
                                    <div><span class="text-gray-400">Date:</span> <span class="text-white">${new Date(booking.created_at).toLocaleString()}</span></div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-white font-semibold mb-2">Show Details</h4>
                            <div class="space-y-1 text-sm">
                                <div><span class="text-gray-400">Movie:</span> <span class="text-white">${booking.movie_title}</span></div>
                                <div><span class="text-gray-400">Theater:</span> <span class="text-white">${booking.theater_name}</span></div>
                                ${booking.hall_name ? `<div><span class="text-gray-400">Hall:</span> <span class="text-white">${booking.hall_name}</span></div>` : ''}
                                <div><span class="text-gray-400">Show Time:</span> <span class="text-white">${new Date(booking.show_time).toLocaleString()}</span></div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="text-white font-semibold mb-2">Seat & Payment Details</h4>
                            <div class="space-y-1 text-sm">
                                <div><span class="text-gray-400">Seats:</span> <span class="text-white">${booking.seat_numbers || 'N/A'}</span></div>
                                <div><span class="text-gray-400">Total Seats:</span> <span class="text-white">${booking.total_seats}</span></div>
                                <div><span class="text-gray-400">Total Amount:</span> <span class="text-white">Rs${parseFloat(booking.total_price).toFixed(2)}</span></div>
                                <div><span class="text-gray-400">Payment Status:</span> <span class="text-white">${booking.payment_status || 'N/A'}</span></div>
                                <div><span class="text-gray-400">Payment Method:</span> <span class="text-white">${booking.payment_method || 'N/A'}</span></div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('bookingModal').classList.remove('hidden');
                document.getElementById('bookingModal').classList.add('flex');
            }
        }
        
        function closeModal() {
            document.getElementById('bookingModal').classList.add('hidden');
            document.getElementById('bookingModal').classList.remove('flex');
        }
        
        function updateBookingStatus(bookingId, status) {
            if (confirm(`Are you sure you want to ${status.toLowerCase()} this booking?`)) {
                window.location.href = `update_booking_status.php?booking_id=${bookingId}&status=${status}`;
            }
        }
        
        document.getElementById('bookingModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
