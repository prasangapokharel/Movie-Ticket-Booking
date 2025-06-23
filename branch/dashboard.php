<?php
include '../database/config.php';
session_start();

// Check if branch admin is logged in
if (!isset($_SESSION['branch_id'])) {
    header("Location: login.php");
    exit();
}

$branch_id = $_SESSION['branch_id'];

// Get branch information
try {
    $branch_stmt = $conn->prepare("SELECT * FROM branches WHERE branch_id = ?");
    $branch_stmt->execute([$branch_id]);
    $branch_info = $branch_stmt->fetch();
} catch (PDOException $e) {
    $branch_info = null;
}

// Get dashboard statistics for this branch
try {
    // Total halls in this branch
    $halls_count = $conn->prepare("SELECT COUNT(*) FROM halls WHERE branch_id = ? AND status = 'active'");
    $halls_count->execute([$branch_id]);
    $total_halls = $halls_count->fetchColumn();
    
    // Total theaters linked to this branch
    $theaters_count = $conn->prepare("SELECT COUNT(*) FROM theaters WHERE branch_id = ?");
    $theaters_count->execute([$branch_id]);
    $total_theaters = $theaters_count->fetchColumn();
    
    // Today's bookings for this branch
    $today_bookings = $conn->prepare("
        SELECT COUNT(*) FROM bookings b
        JOIN shows s ON b.show_id = s.show_id
        JOIN theaters t ON s.theater_id = t.theater_id
        WHERE t.branch_id = ? AND DATE(b.created_at) = CURDATE()
    ");
    $today_bookings->execute([$branch_id]);
    $total_today_bookings = $today_bookings->fetchColumn();
    
    // Today's revenue for this branch
    $today_revenue = $conn->prepare("
        SELECT COALESCE(SUM(b.total_price), 0) FROM bookings b
        JOIN shows s ON b.show_id = s.show_id
        JOIN theaters t ON s.theater_id = t.theater_id
        WHERE t.branch_id = ? AND DATE(b.created_at) = CURDATE() AND b.booking_status = 'Confirmed'
    ");
    $today_revenue->execute([$branch_id]);
    $total_today_revenue = $today_revenue->fetchColumn();
    
    // Recent bookings for this branch
    $recent_bookings_stmt = $conn->prepare("
        SELECT b.booking_id, b.total_price, b.booking_status, b.created_at,
               u.name as user_name, m.title as movie_title, t.name as theater_name
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN shows s ON b.show_id = s.show_id
        JOIN movies m ON s.movie_id = m.movie_id
        JOIN theaters t ON s.theater_id = t.theater_id
        WHERE t.branch_id = ?
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $recent_bookings_stmt->execute([$branch_id]);
    $recent_bookings = $recent_bookings_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Error fetching dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Dashboard - <?php echo $branch_info ? htmlspecialchars($branch_info['branch_name']) : 'Branch Admin'; ?></title>
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
                <i class="fas fa-tachometer-alt mr-3"></i>Branch Dashboard
                <?php if ($branch_info): ?>
                    <span class="text-xl text-gray-400 ml-2">- <?php echo htmlspecialchars($branch_info['branch_name']); ?></span>
                <?php endif; ?>
            </h1>
            
            <!-- Branch Info Card -->
            <?php if ($branch_info): ?>
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-white mb-4">
                    <i class="fas fa-info-circle mr-2"></i>Branch Information
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-400">Branch Code</p>
                        <p class="text-white font-medium"><?php echo htmlspecialchars($branch_info['branch_code']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Location</p>
                        <p class="text-white font-medium"><?php echo htmlspecialchars($branch_info['location']); ?></p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">Manager</p>
                        <p class="text-white font-medium"><?php echo htmlspecialchars($branch_info['manager_name']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chair text-white text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Total Halls</p>
                            <p class="text-2xl font-semibold text-white"><?php echo $total_halls; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-theater-masks text-white text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Total Theaters</p>
                            <p class="text-2xl font-semibold text-white"><?php echo $total_theaters; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-ticket-alt text-white text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Today's Bookings</p>
                            <p class="text-2xl font-semibold text-white"><?php echo $total_today_bookings; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-dollar-sign text-white text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Today's Revenue</p>
                            <p class="text-2xl font-semibold text-white">₹<?php echo number_format($total_today_revenue, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions and Recent Bookings -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Quick Actions -->
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">
                        <i class="fas fa-bolt mr-2"></i>Quick Actions
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="halls.php" class="bg-white text-black hover:bg-gray-200 p-4 rounded-lg text-center transition-colors">
                            <i class="fas fa-chair text-2xl mb-2"></i>
                            <p class="font-medium">Manage Halls</p>
                        </a>
                        <a href="shows.php" class="bg-white text-black hover:bg-gray-200 p-4 rounded-lg text-center transition-colors">
                            <i class="fas fa-calendar text-2xl mb-2"></i>
                            <p class="font-medium">Manage Shows</p>
                        </a>
                        <a href="bookings.php" class="bg-white text-black hover:bg-gray-200 p-4 rounded-lg text-center transition-colors">
                            <i class="fas fa-ticket-alt text-2xl mb-2"></i>
                            <p class="font-medium">View Bookings</p>
                        </a>
                        <a href="reports.php" class="bg-white text-black hover:bg-gray-200 p-4 rounded-lg text-center transition-colors">
                            <i class="fas fa-chart-bar text-2xl mb-2"></i>
                            <p class="font-medium">View Reports</p>
                        </a>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">
                        <i class="fas fa-clock mr-2"></i>Recent Bookings
                    </h2>
                    <div class="space-y-3">
                        <?php if (empty($recent_bookings)): ?>
                            <p class="text-gray-400">No recent bookings found.</p>
                        <?php else: ?>
                            <?php foreach (array_slice($recent_bookings, 0, 5) as $booking): ?>
                                <div class="flex justify-between items-center py-2 border-b border-gray-700">
                                    <div>
                                        <p class="text-white font-medium"><?php echo htmlspecialchars($booking['movie_title']); ?></p>
                                        <p class="text-sm text-gray-400"><?php echo htmlspecialchars($booking['user_name']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-white">₹<?php echo number_format($booking['total_price'], 2); ?></p>
                                        <p class="text-xs text-gray-400"><?php echo date('M j, H:i', strtotime($booking['created_at'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
