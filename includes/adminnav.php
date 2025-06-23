<?php
// Check if admin is logged in
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['branch_id'])) {
    header("Location: ../login.php");
    exit();
}

$is_super_admin = isset($_SESSION['admin_id']);
$is_branch_admin = isset($_SESSION['branch_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #000000; color: #ffffff; }
        .nav-link:hover { background-color: #333333; }
        .nav-link.active { background-color: #ffffff; color: #000000; }
    </style>
</head>
<body>

<nav class="bg-black border-b border-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <h1 class="text-xl font-bold text-white">
                        <i class="fas fa-film mr-2"></i>
                        <?php echo $is_super_admin ? 'Super Admin' : 'Branch Admin'; ?>
                    </h1>
                </div>
                <div class="hidden md:block ml-10">
                    <div class="flex items-baseline space-x-4">
                        <a href="dashboard.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                        </a>
                        
                        <?php if ($is_super_admin): ?>
                        <a href="manage_branches.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-building mr-1"></i> Branches
                        </a>
                        <?php endif; ?>
                        
                        <a href="manage_movies.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-video mr-1"></i> Movies
                        </a>
                        
                        <a href="manage_theaters.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-theater-masks mr-1"></i> Theaters
                        </a>
                        
                        <a href="manage_halls.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-chair mr-1"></i> Halls
                        </a>
                        
                        <a href="manage_shows.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-calendar mr-1"></i> Shows
                        </a>
                        
                        <a href="bookings.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-ticket-alt mr-1"></i> Bookings
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center">
                <div class="ml-3 relative">
                    <div class="flex items-center space-x-4">
                        <span class="text-white text-sm">
                            Welcome, <?php echo $is_super_admin ? 'Super Admin' : 'Branch Admin'; ?>
                        </span>
                        <a href="logout.php" class="bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Mobile menu -->
    <div class="md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="dashboard.php" class="nav-link text-white hover:bg-gray-700 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
            </a>
            
            <?php if ($is_super_admin): ?>
            <a href="manage_branches.php" class="nav-link text-white hover:bg-gray-700 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-building mr-1"></i> Branches
            </a>
            <?php endif; ?>
            
            <a href="manage_movies.php" class="nav-link text-white hover:bg-gray-700 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-video mr-1"></i> Movies
            </a>
            
            <a href="manage_theaters.php" class="nav-link text-white hover:bg-gray-700 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-theater-masks mr-1"></i> Theaters
            </a>
            
            <a href="manage_halls.php" class="nav-link text-white hover:bg-gray-700 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-chair mr-1"></i> Halls
            </a>
            
            <a href="manage_shows.php" class="nav-link text-white hover:bg-gray-700 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-calendar mr-1"></i> Shows
            </a>
            
            <a href="bookings.php" class="nav-link text-white hover:bg-gray-700 block px-3 py-2 rounded-md text-base font-medium">
                <i class="fas fa-ticket-alt mr-1"></i> Bookings
            </a>
        </div>
    </div>
</nav>
