<?php
// Check if branch admin is logged in
if (!isset($_SESSION['branch_id'])) {
    header("Location: ../branch_login.php");
    exit();
}

// Get branch information
try {
    $stmt = $conn->prepare("SELECT * FROM branches WHERE branch_id = ?");
    $stmt->execute([$_SESSION['branch_id']]);
    $branch_info = $stmt->fetch();
} catch (PDOException $e) {
    $branch_info = null;
}
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
                        <i class="fas fa-building mr-2"></i>
                        <?php echo $branch_info ? htmlspecialchars($branch_info['branch_name']) : 'Branch Admin'; ?>
                    </h1>
                </div>
                <div class="hidden md:block ml-10">
                    <div class="flex items-baseline space-x-4">
                        <a href="dashboard.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-tachometer-alt mr-1"></i> Dashboard
                        </a>
                        
                        <a href="movies.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-video mr-1"></i> Movies
                        </a>
                        
                        <a href="halls.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-chair mr-1"></i> Halls & Seats
                        </a>
                        
                        <a href="shows.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-calendar mr-1"></i> Shows
                        </a>
                        
                        <a href="bookings.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-ticket-alt mr-1"></i> Bookings
                        </a>
                        
                        <a href="reports.php" class="nav-link text-white hover:bg-gray-700 px-3 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-chart-bar mr-1"></i> Reports
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center">
                <div class="ml-3 relative">
                    <div class="flex items-center space-x-4">
                        <span class="text-white text-sm">
                            <?php echo $branch_info ? htmlspecialchars($branch_info['manager_name']) : 'Branch Manager'; ?>
                        </span>
                        <a href="logout.php" class="bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-md text-sm font-medium">
                            <i class="fas fa-sign-out-alt mr-1"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>
