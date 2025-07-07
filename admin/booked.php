<?php include 'model/Booked.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Management - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #000000;
            color: #ffffff;
        }
        .input-field {
            background-color: #111111;
            border: 1px solid #333333;
            color: #ffffff;
        }
        .input-field:focus {
            border-color: #666666;
            outline: none;
        }
        .submit-button {
            background-color: #ffffff;
            color: #000000;
        }
        .submit-button:hover {
            background-color: #e5e5e5;
        }
        table {
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #333333;
        }
        .status-confirmed {
            background-color: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }
        .status-cancelled {
            background-color: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
        .status-pending {
            background-color: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
        }
        .filter-form {
            transition: all 0.3s ease;
        }
        .filter-form:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .table-container {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        .table-container:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold">Booking Management</h1>
                <a href="dashboard.php" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-300">
                    Back to Dashboard
                </a>
            </div>
            
            <?php if (isset($error_message)): ?>
            <div class="bg-red-900 text-red-100 px-4 py-3 rounded-lg mb-6 flex items-start">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 mt-0.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <!-- Filter Form -->
            <div class="bg-gray-900 rounded-lg p-6 mb-8 filter-form">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                    </svg>
                    Filter Bookings
                </h2>
                
                <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="movie_id" class="block text-sm font-medium text-gray-300 mb-1">
                            Movie
                        </label>
                        <select 
                            name="movie_id" 
                            id="movie_id" 
                            class="input-field w-full px-4 py-2 rounded-lg"
                        >
                            <option value="">All Movies</option>
                            <?php foreach ($movies as $movie): ?>
                                <option value="<?php echo $movie['movie_id']; ?>" <?php echo ($movie_id == $movie['movie_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($movie['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="theater_id" class="block text-sm font-medium text-gray-300 mb-1">
                            Theater
                        </label>
                        <select 
                            name="theater_id" 
                            id="theater_id" 
                            class="input-field w-full px-4 py-2 rounded-lg"
                        >
                            <option value="">All Theaters</option>
                            <?php foreach ($theaters as $theater): ?>
                                <option value="<?php echo $theater['theater_id']; ?>" <?php echo ($theater_id == $theater['theater_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($theater['name'] . ' - ' . $theater['location']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-300 mb-1">
                            Booking Status
                        </label>
                        <select 
                            name="status" 
                            id="status" 
                            class="input-field w-full px-4 py-2 rounded-lg"
                        >
                            <option value="">All Statuses</option>
                            <option value="confirmed" <?php echo ($status == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo ($status == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-300 mb-1">
                            From Date
                        </label>
                        <input 
                            type="date" 
                            name="date_from" 
                            id="date_from" 
                            value="<?php echo $date_from; ?>"
                            class="input-field w-full px-4 py-2 rounded-lg"
                        >
                    </div>
                    
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-300 mb-1">
                            To Date
                        </label>
                        <input 
                            type="date" 
                            name="date_to" 
                            id="date_to" 
                            value="<?php echo $date_to; ?>"
                            class="input-field w-full px-4 py-2 rounded-lg"
                        >
                    </div>
                    
                    <div class="flex items-end gap-2">
                        <button 
                            type="submit" 
                            class="submit-button font-medium py-2 px-6 rounded-lg"
                        >
                            Apply Filters
                        </button>
                        
                        <?php if ($movie_id || $theater_id || $status || $date_from != date('Y-m-d', strtotime('-7 days')) || $date_to != date('Y-m-d')): ?>
                        <a href="booked.php" class="bg-gray-800 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition duration-300">
                            Reset
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Booking Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gray-900 rounded-lg p-6 stat-card">
                    <h3 class="text-gray-400 text-sm mb-1">Total Bookings</h3>
                    <p class="text-3xl font-bold"><?php echo number_format($stats['total_bookings']); ?></p>
                    <div class="mt-2 text-xs text-gray-500">
                        Based on your current filter
                    </div>
                </div>
                
                <div class="bg-gray-900 rounded-lg p-6 stat-card">
                    <h3 class="text-gray-400 text-sm mb-1">Total Revenue</h3>
                    <p class="text-3xl font-bold">Rs<?php echo number_format($stats['total_revenue'], 2); ?></p>
                    <div class="mt-2 text-xs text-gray-500">
                        From all filtered bookings
                    </div>
                </div>
                
                <div class="bg-gray-900 rounded-lg p-6 stat-card">
                    <h3 class="text-gray-400 text-sm mb-1">Unique Customers</h3>
                    <p class="text-3xl font-bold"><?php echo number_format($stats['unique_customers']); ?></p>
                    <div class="mt-2 text-xs text-gray-500">
                        Distinct users who booked
                    </div>
                </div>
                
                <div class="bg-gray-900 rounded-lg p-6 stat-card">
                    <h3 class="text-gray-400 text-sm mb-1">Total Seats Booked</h3>
                    <p class="text-3xl font-bold"><?php echo number_format($stats['total_seats']); ?></p>
                    <div class="mt-2 text-xs text-gray-500">
                        Across all filtered bookings
                    </div>
                </div>
            </div>
            
            <!-- Bookings Table -->
            <div class="bg-gray-900 rounded-lg p-6 table-container">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd" />
                        </svg>
                        Booking List
                    </h2>
                    
                    <?php if (!empty($bookings)): ?>
                    <a href="export_bookings.php?<?php echo http_build_query($_GET); ?>" class="bg-green-800 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition duration-300 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        Export to CSV
                    </a>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($bookings)): ?>
                    <div class="text-center py-16 bg-gray-800/50 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="text-xl font-medium text-gray-400">No bookings found</h3>
                        <p class="text-gray-500 mt-2">Try adjusting your filter criteria or check back later</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-800">
                                    <th class="px-4 py-2">ID</th>
                                    <th class="px-4 py-2">Customer</th>
                                    <th class="px-4 py-2">Movie</th>
                                    <th class="px-4 py-2">Theater</th>
                                    <th class="px-4 py-2">Show Time</th>
                                    <th class="px-4 py-2">Seats</th>
                                    <th class="px-4 py-2">Amount</th>
                                    <th class="px-4 py-2">Status</th>
                                    <th class="px-4 py-2">Booked On</th>
                                    <th class="px-4 py-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr class="border-t border-gray-800 hover:bg-gray-800/50 transition duration-150">
                                        <td class="px-4 py-2"><?php echo $booking['booking_id']; ?></td>
                                        <td class="px-4 py-2">
                                            <div class="font-medium"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                            <div class="text-sm text-gray-400"><?php echo htmlspecialchars($booking['user_email']); ?></div>
                                            <div class="text-sm text-gray-400"><?php echo htmlspecialchars($booking['user_phone'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($booking['movie_title']); ?></td>
                                        <td class="px-4 py-2">
                                            <?php echo htmlspecialchars($booking['theater_name']); ?>
                                            <?php if (isset($booking['screen'])): ?>
                                            <div class="text-sm text-gray-400">Screen <?php echo $booking['screen']; ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div><?php echo date('Y-m-d', strtotime($booking['show_time'])); ?></div>
                                            <div class="text-sm text-gray-400"><?php echo date('h:i A', strtotime($booking['show_time'])); ?></div>
                                        </td>
                                        <td class="px-4 py-2"><?php echo htmlspecialchars($booking['seats'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-2">Rs<?php echo number_format($booking['total_price'], 2); ?></td>
                                        <td class="px-4 py-2">
                                            <span class="px-2 py-1 rounded-full text-xs font-medium status-<?php echo strtolower($booking['booking_status']); ?>">
                                                <?php echo ucfirst($booking['booking_status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2"><?php echo date('Y-m-d H:i', strtotime($booking['created_at'])); ?></td>
                                        <td class="px-4 py-2">
                                            <div class="flex space-x-2">
                                                <a href="view_booking.php?id=<?php echo $booking['booking_id']; ?>" class="text-blue-400 hover:text-blue-300 transition duration-150" title="View Details">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                                
                                                <?php if (strtolower($booking['booking_status']) == 'confirmed'): ?>
                                                <a href="cancel_booking.php?id=<?php echo $booking['booking_id']; ?>" class="text-red-400 hover:text-red-300 transition duration-150" title="Cancel Booking" onclick="return confirm('Are you sure you want to cancel this booking?')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                                <?php endif; ?>
                                                
                                                <a href="print_ticket.php?id=<?php echo $booking['booking_id']; ?>" class="text-green-400 hover:text-green-300 transition duration-150" title="Print Ticket" target="_blank">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                            </div>
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
    
    <script>
        // Auto-submit form when certain filters change
        document.addEventListener('DOMContentLoaded', function() {
            const autoSubmitFilters = ['movie_id',   function() {
            const autoSubmitFilters = ['movie_id', 'theater_id', 'status'];
            
            autoSubmitFilters.forEach(function(filterId) {
                const element = document.getElementById(filterId);
                if (element) {
                    element.addEventListener('change', function() {
                        this.form.submit();
                    });
                }
            });
            
            // Date range validation
            const dateFrom = document.getElementById('date_from');
            const dateTo = document.getElementById('date_to');
            
            if (dateFrom && dateTo) {
                dateFrom.addEventListener('change', function() {
                    if (dateTo.value && this.value > dateTo.value) {
                        dateTo.value = this.value;
                    }
                });
                
                dateTo.addEventListener('change', function() {
                    if (dateFrom.value && this.value < dateFrom.value) {
                        dateFrom.value = this.value;
                    }
                });
            }
        });
    </script>
</body>
</html>