<?php
include '../database/config.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$show_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success_message = '';
$error_message = '';

if (!$show_id) {
    header("Location: manage_shows.php");
    exit();
}

// Fetch show details
try {
    $stmt = $conn->prepare("
        SELECT 
            s.show_id, 
            s.show_time, 
            s.price,
            s.screen,
            m.title AS movie_title,
            t.name AS theater_name,
            t.location AS theater_location
        FROM 
            shows s
        JOIN 
            movies m ON s.movie_id = m.movie_id
        JOIN 
            theaters t ON s.theater_id = t.theater_id
        WHERE 
            s.show_id = ?
    ");
    $stmt->execute([$show_id]);
    $show = $stmt->fetch();
    
    if (!$show) {
        header("Location: manage_shows.php");
        exit();
    }
    
    // Check if there are any bookings for this show
    $booking_check = $conn->prepare("
        SELECT COUNT(*) FROM bookings WHERE show_id = ?
    ");
    $booking_check->execute([$show_id]);
    $has_bookings = $booking_check->fetchColumn() > 0;
    
    // Process deletion if confirmed
    if (isset($_POST['confirm_delete'])) {
        try {
            $conn->beginTransaction();
            
            // Delete seats for this show
            $delete_seats = $conn->prepare("DELETE FROM seats WHERE show_id = ?");
            $delete_seats->execute([$show_id]);
            
            // If there are bookings, handle them
            if ($has_bookings) {
                if (isset($_POST['delete_bookings']) && $_POST['delete_bookings'] == 'yes') {
                    // Delete bookings for this show
                    $delete_bookings = $conn->prepare("DELETE FROM bookings WHERE show_id = ?");
                    $delete_bookings->execute([$show_id]);
                } else {
                    $conn->rollBack();
                    $error_message = "Cannot delete show with existing bookings. Please check the option to delete associated bookings.";
                    $has_bookings = true; // Ensure the checkbox is shown again
                }
            }
            
            if (empty($error_message)) {
                // Delete the show
                $delete_show = $conn->prepare("DELETE FROM shows WHERE show_id = ?");
                $delete_show->execute([$show_id]);
                
                $conn->commit();
                $success_message = "Show deleted successfully!";
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $error_message = "Database error: " . $e->getMessage();
        }
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Show - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #000000;
            color: #ffffff;
        }
        .submit-button {
            background-color: #ffffff;
            color: #000000;
        }
        .submit-button:hover {
            background-color: #e5e5e5;
        }
        .delete-button {
            background-color: #dc2626;
            color: #ffffff;
        }
        .delete-button:hover {
            background-color: #b91c1c;
        }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-8">Delete Show</h1>
            
            <?php if (!empty($success_message)): ?>
            <div class="bg-green-900 text-green-100 px-4 py-3 rounded-lg mb-6">
                <p><?php echo htmlspecialchars($success_message); ?></p>
                <p class="mt-2">
                    <a href="manage_shows.php" class="text-green-300 underline">Return to Manage Shows</a>
                </p>
            </div>
            <?php else: ?>
                
                <?php if (!empty($error_message)): ?>
                <div class="bg-red-900 text-red-100 px-4 py-3 rounded-lg mb-6">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
                <?php endif; ?>
                
                <div class="bg-gray-900 rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Confirm Deletion</h2>
                    
                    <div class="bg-gray-800 rounded-lg p-4 mb-6">
                        <h3 class="font-medium mb-2">Show Details:</h3>
                        <ul class="space-y-2 text-gray-300">
                            <li><strong>Movie:</strong> <?php echo htmlspecialchars($show['movie_title']); ?></li>
                            <li><strong>Theater:</strong> <?php echo htmlspecialchars($show['theater_name'] . ' - ' . $show['theater_location']); ?></li>
                            <li><strong>Screen:</strong> <?php echo $show['screen']; ?></li>
                            <li><strong>Date:</strong> <?php echo date('Y-m-d', strtotime($show['show_time'])); ?></li>
                            <li><strong>Time:</strong> <?php echo date('h:i A', strtotime($show['show_time'])); ?></li>
                            <li><strong>Price:</strong> ₹<?php echo number_format($show['price'], 2); ?></li>
                        </ul>
                    </div>
                    
                    <div class="bg-red-900/30 text-red-100 px-4 py-3 rounded-lg mb-6">
                        <p class="font-medium">Warning: This action cannot be undone!</p>
                        <p class="mt-1">Deleting this show will remove it from the system permanently.</p>
                    </div>
                    
                    <form method="POST">
                        <?php if ($has_bookings): ?>
                        <div class="mb-4">
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    name="delete_bookings" 
                                    id="delete_bookings" 
                                    value="yes" 
                                    class="mr-2"
                                >
                                <label for="delete_bookings" class="text-red-300">
                                    Also delete all associated bookings for this show
                                </label>
                            </div>
                            <p class="text-gray-400 text-sm mt-1">
                                This show has existing bookings. Checking this box will delete all customer bookings for this show.
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex space-x-4">
                            <a href="manage_shows.php" class="submit-button font-medium py-2 px-6 rounded-lg">
                                Cancel
                            </a>
                            <button 
                                type="submit" 
                                name="confirm_delete" 
                                
                                class="delete-button font-medium py-2 px-6 rounded-lg"
                            >
                                Delete Show
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>