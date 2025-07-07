<?php
include '../database/config.php';
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Get dashboard statistics
try {
    // Total movies
    $movies_count = $conn->query("SELECT COUNT(*) FROM movies WHERE status = 'active'")->fetchColumn();
    
    // Total theaters
    $theaters_count = $conn->query("SELECT COUNT(*) FROM theaters")->fetchColumn();
    
    // Total branches
    $branches_count = $conn->query("SELECT COUNT(*) FROM branches WHERE status = 'active'")->fetchColumn();
    
    // Total bookings today
    $today_bookings = $conn->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
    // Total revenue today
    $today_revenue = $conn->query("SELECT COALESCE(SUM(total_price), 0) FROM bookings WHERE DATE(created_at) = CURDATE() AND booking_status = 'Confirmed'")->fetchColumn();
    
    // Recent bookings
    $recent_bookings_stmt = $conn->prepare("
        SELECT b.booking_id, b.total_price, b.booking_status, b.created_at,
               u.name as user_name, m.title as movie_title, t.name as theater_name
        FROM bookings b
        JOIN users u ON b.user_id = u.user_id
        JOIN shows s ON b.show_id = s.show_id
        JOIN movies m ON s.movie_id = m.movie_id
        JOIN theaters t ON s.theater_id = t.theater_id
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $recent_bookings_stmt->execute();
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
    <title>Admin Dashboard - Movie Booking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #000000; color: #ffffff; }
    </style>
</head>
<body>
    <?php include '../includes/adminnav.php'; ?>
    
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-white mb-8">
                <i class="fas fa-tachometer-alt mr-3"></i>Super Admin Dashboard
            </h1>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-video text-white text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Total Movies</p>
                            <p class="text-2xl font-semibold text-white"><?php echo $movies_count; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-building text-white text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-400">Total Branches</p>
                            <p class="text-2xl font-semibold text-white"><?php echo $branches_count; ?></p>
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
                            <p class="text-2xl font-semibold text-white"><?php echo $today_bookings; ?></p>
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
                            <p class="text-2xl font-semibold text-white">Rs<?php echo number_format($today_revenue, 2); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">
                        <i class="fas fa-bolt mr-2"></i>Quick Actions
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <a href="manage_branches.php" class="bg-white text-black hover:bg-gray-200 p-4 rounded-lg text-center transition-colors">
                            <i class="fas fa-building text-2xl mb-2"></i>
                            <p class="font-medium">Manage Branches</p>
                        </a>
                        <a href="add_movie.php" class="bg-white text-black hover:bg-gray-200 p-4 rounded-lg text-center transition-colors">
                            <i class="fas fa-plus text-2xl mb-2"></i>
                            <p class="font-medium">Add Movie</p>
                        </a>
                        <a href="manage_theaters.php" class="bg-white text-black hover:bg-gray-200 p-4 rounded-lg text-center transition-colors">
                            <i class="fas fa-theater-masks text-2xl mb-2"></i>
                            <p class="font-medium">Manage Theaters</p>
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
                                        <p class="text-white">Rs<?php echo number_format($booking['total_price'], 2); ?></p>
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
