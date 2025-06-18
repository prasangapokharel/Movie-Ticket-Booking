<?php 
include '../model/Profile.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - CineBook</title>
    <script src="../assets/js/talwind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
        }
        
        .profile-card {
            background: linear-gradient(to bottom right, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        
        .profile-card:hover {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .form-input {
            background-color: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            background-color: rgba(30, 41, 59, 0.9);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25);
        }
        
        .divider {
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(255, 255, 255, 0.1), transparent);
            margin: 20px 0;
        }
        
        .gradient-button {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            transition: all 0.3s ease;
        }
        
        .gradient-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3);
        }
        
        .stat-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .booking-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        
        .booking-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.1);
        }
        
        .tab-button {
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        @keyframes pulse-border {
            0%, 100% {
                border-color: rgba(239, 68, 68, 0.3);
            }
            50% {
                border-color: rgba(239, 68, 68, 0.6);
            }
        }
        
        .pulse-border {
            animation: pulse-border 2s infinite;
        }
    </style>
</head>
<body>
    <!-- Include loader -->
    
    <!-- Include navigation -->
    <?php include '../includes/nav.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">My Profile</h1>
            <p class="text-gray-400">Manage your account and view your booking history</p>
        </div>
        
        <!-- Profile Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-12">
            <!-- Left Column: User Info & Stats -->
            <div class="lg:col-span-1">
                <!-- User Card -->
                <div class="profile-card rounded-2xl overflow-hidden mb-6">
                    <div class="p-6">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-20 h-20 rounded-full flex items-center justify-center text-3xl font-bold" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);">
                                <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h2>
                                <p class="text-gray-400">Member since <?php echo date('M Y', strtotime($user['created_at'] ?? 'now')); ?></p>
                            </div>
                        </div>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                <span class="text-gray-300"><?php echo htmlspecialchars($user['email'] ?? 'email@example.com'); ?></span>
                            </div>
                            <?php if (!empty($user['phone'])): ?>
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                <span class="text-gray-300"><?php echo htmlspecialchars($user['phone']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <a href="#edit-profile" class="block w-full py-2.5 px-4 rounded-lg text-center text-white font-medium transition gradient-button">
                            Edit Profile
                        </a>
                    </div>
                </div>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-1 gap-4">
                    <div class="stat-card rounded-xl p-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center mr-4" style="background: rgba(239, 68, 68, 0.1);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">Total Bookings</p>
                                <p class="text-2xl font-bold"><?php echo number_format($stats['total_bookings'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card rounded-xl p-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center mr-4" style="background: rgba(99, 102, 241, 0.1);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h18M3 16h18" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">Movies Watched</p>
                                <p class="text-2xl font-bold"><?php echo number_format($stats['unique_shows'] ?? 0); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="stat-card rounded-xl p-4">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center mr-4" style="background: rgba(16, 185, 129, 0.1);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm text-gray-400">Total Spent</p>
                                <p class="text-2xl font-bold">₨<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Tabs for Profile Edit & Bookings -->
            <div class="lg:col-span-2">
                <!-- Tab Navigation -->
                <div class="flex border-b border-gray-700 mb-6">
                    <button id="tab-profile" class="tab-button active px-4 py-2 border-b-2 border-red-500 text-white font-medium">
                        Profile Details
                    </button>
                    <button id="tab-bookings" class="tab-button px-4 py-2 border-b-2 border-transparent text-gray-400 font-medium">
                        Recent Bookings
                    </button>
                    <button id="tab-preferences" class="tab-button px-4 py-2 border-b-2 border-transparent text-gray-400 font-medium">
                        Preferences
                    </button>
                </div>
                
                <!-- Tab Content: Profile Edit -->
                <div id="content-profile" class="tab-content">
                    <?php if (!empty($success_message)): ?>
                    <div class="bg-green-900/50 text-green-400 px-4 py-3 rounded-lg mb-6 flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?php echo $success_message; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="bg-red-900/50 text-red-400 px-4 py-3 rounded-lg mb-6 flex items-start">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?php echo $error_message; ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div id="edit-profile" class="profile-card rounded-xl overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-6 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit Profile
                            </h3>
                            
                            <form method="POST" action="">
                                <div class="space-y-5">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-1">Full Name</label>
                                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" class="form-input w-full rounded-lg px-4 py-2.5" required>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-1">Email Address</label>
                                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" class="form-input w-full rounded-lg px-4 py-2.5" required>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Phone Number</label>
                                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" class="form-input w-full rounded-lg px-4 py-2.5" placeholder="Optional">
                                    </div>
                                    
                                    <div class="divider"></div>
                                    
                                    <div>
                                        <h4 class="text-lg font-semibold mb-4 flex items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                            Change Password
                                        </h4>
                                        <p class="text-sm text-gray-400 mb-4">Leave these fields empty if you don't want to change your password</p>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-300 mb-1">Current Password</label>
                                                <input type="password" name="current_password" class="form-input w-full rounded-lg px-4 py-2.5">
                                            </div>
                                        </div>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-300 mb-1">New Password</label>
                                                <input type="password" name="new_password" class="form-input w-full rounded-lg px-4 py-2.5">
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-300 mb-1">Confirm New Password</label>
                                                <input type="password" name="confirm_password" class="form-input w-full rounded-lg px-4 py-2.5">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex justify-end pt-4">
                                        <button type="submit" name="update_profile" class="px-6 py-2.5 rounded-lg text-white font-medium gradient-button">
                                            Save Changes
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Content: Recent Bookings -->
                <div id="content-bookings" class="tab-content hidden">
                    <div class="profile-card rounded-xl overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-6 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z" />
                                </svg>
                                Recent Bookings
                            </h3>
                            
                            <?php if (empty($recent_bookings)): ?>
                                <div class="text-center py-8">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-500 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2H5z" />
                                    </svg>
                                    <h4 class="text-lg font-medium text-gray-400 mb-2">No bookings yet</h4>
                                    <p class="text-gray-500 mb-4">You haven't made any bookings yet.</p>
                                    <a href="index.php" class="inline-block px-4 py-2 rounded-lg text-white font-medium gradient-button">
                                        Browse Movies
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="space-y-4">
                                    <?php foreach ($recent_bookings as $booking): ?>
                                        <div class="booking-card rounded-lg overflow-hidden flex flex-col md:flex-row">
                                            <div class="w-full md:w-1/4 h-40 md:h-auto">
                                                <img src="<?php echo htmlspecialchars($booking['poster_url']); ?>" alt="<?php echo htmlspecialchars($booking['movie_title']); ?>" class="w-full h-full object-cover">
                                            </div>
                                            <div class="p-4 flex-1">
                                                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-2">
                                                    <h4 class="text-lg font-semibold"><?php echo htmlspecialchars($booking['movie_title']); ?></h4>
                                                    <div class="text-sm text-gray-400">
                                                        Booked on <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                                                    </div>
                                                </div>
                                                <div class="flex flex-col md:flex-row md:items-center text-sm text-gray-300 mb-4">
                                                    <div class="flex items-center mr-4">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                        <?php echo date('D, M d, Y', strtotime($booking['show_time'])); ?>
                                                    </div>
                                                    <div class="flex items-center mr-4">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <?php echo date('g:i A', strtotime($booking['show_time'])); ?>
                                                    </div>
                                                    <div class="flex items-center">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        </svg>
                                                        <?php echo htmlspecialchars($booking['theater_name']); ?>
                                                    </div>
                                                </div>
                                                <div class="flex items-center justify-between">
                                                    <span class="text-lg font-bold text-red-400">₨<?php echo number_format($booking['total_price'], 2); ?></span>
                                                    <a href="booking_detail.php?id=<?php echo $booking['booking_id']; ?>" class="px-4 py-1.5 rounded-lg text-sm text-white font-medium gradient-button">
                                                        View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <div class="text-center pt-4">
                                        <a href="my_bookings.php" class="inline-block px-4 py-2 rounded-lg text-white font-medium border border-red-500 hover:bg-red-500/10 transition">
                                            View All Bookings
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Tab Content: Preferences -->
                <div id="content-preferences" class="tab-content hidden">
                    <div class="profile-card rounded-xl overflow-hidden">
                        <div class="p-6">
                            <h3 class="text-xl font-bold mb-6 flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Preferences
                            </h3>
                            
                            <div class="space-y-6">
                                <div>
                                    <h4 class="text-lg font-medium mb-3">Notification Settings</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium">Email Notifications</p>
                                                <p class="text-sm text-gray-400">Receive booking confirmations and updates</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" value="" class="sr-only peer" checked>
                                                <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                            </label>
                                        </div>
                                        
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium">SMS Notifications</p>
                                                <p class="text-sm text-gray-400">Receive text messages for important updates</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" value="" class="sr-only peer">
                                                <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                            </label>
                                        </div>
                                        
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="font-medium">Marketing Communications</p>
                                                <p class="text-sm text-gray-400">Receive offers and promotions</p>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" value="" class="sr-only peer" checked>
                                                <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600"></div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="divider"></div>
                                
                                <div>
                                    <h4 class="text-lg font-medium mb-3">Movie Preferences</h4>
                                    <p class="text-sm text-gray-400 mb-4">Select your favorite genres to get personalized recommendations</p>
                                    
                                    <div class="flex flex-wrap gap-2">
                                        <div class="px-3 py-1.5 rounded-full bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                                            Action
                                        </div>
                                        <div class="px-3 py-1.5 rounded-full bg-gray-700/50 border border-gray-600 text-gray-300 text-sm">
                                            Comedy
                                        </div>
                                        <div class="px-3 py-1.5 rounded-full bg-gray-700/50 border border-gray-600 text-gray-300 text-sm">
                                            Drama
                                        </div>
                                        <div class="px-3 py-1.5 rounded-full bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                                            Sci-Fi
                                        </div>
                                        <div class="px-3 py-1.5 rounded-full bg-gray-700/50 border border-gray-600 text-gray-300 text-sm">
                                            Horror
                                        </div>
                                        <div class="px-3 py-1.5 rounded-full bg-gray-700/50 border border-gray-600 text-gray-300 text-sm">
                                            Romance
                                        </div>
                                        <div class="px-3 py-1.5 rounded-full bg-red-500/10 border border-red-500/30 text-red-400 text-sm">
                                            Thriller
                                        </div>
                                        <div class="px-3 py-1.5 rounded-full bg-gray-700/50 border border-gray-600 text-gray-300 text-sm">
                                            Animation
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end pt-4">
                                    <button type="button" class="px-6 py-2.5 rounded-lg text-white font-medium gradient-button">
                                        Save Preferences
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Logout button -->
        <div class="text-center">
            <a href="logout.php" class="inline-block px-6 py-3 rounded-lg text-red-400 font-medium border border-red-900/30 hover:bg-red-900/20 transition">
                <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </span>
            </a>
        </div>
    </div>
    
    <!-- Include footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching functionality
        const tabs = {
            'tab-profile': 'content-profile',
            'tab-bookings': 'content-bookings',
            'tab-preferences': 'content-preferences'
        };
        
        Object.keys(tabs).forEach(tabId => {
            const tab = document.getElementById(tabId);
            if (tab) {
                tab.addEventListener('click', function() {
                    // Hide all content
                    Object.values(tabs).forEach(contentId => {
                        document.getElementById(contentId).classList.add('hidden');
                    });
                    
                    // Show selected content
                    document.getElementById(tabs[tabId]).classList.remove('hidden');
                    
                    // Update active tab styling
                    Object.keys(tabs).forEach(id => {
                        document.getElementById(id).classList.remove('active');
                        document.getElementById(id).classList.add('text-gray-400');
                        document.getElementById(id).classList.remove('text-white');
                        document.getElementById(id).classList.add('border-transparent');
                        document.getElementById(id).classList.remove('border-red-500');
                    });
                    
                    this.classList.add('active');
                    this.classList.remove('text-gray-400');
                    this.classList.add('text-white');
                    this.classList.remove('border-transparent');
                    this.classList.add('border-red-500');
                });
            }
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId !== '#') {
                    e.preventDefault();
                    
                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    });
    </script>
</body>
</html>

