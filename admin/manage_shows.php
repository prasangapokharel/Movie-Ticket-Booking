<?php
include '../database/config.php';
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Fetch all movies
try {
    $movies_stmt = $conn->query("SELECT movie_id, title FROM movies ORDER BY title ASC");
    $movies = $movies_stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching movies: " . $e->getMessage();
}

// Fetch all theaters
try {
    $theaters_stmt = $conn->query("SELECT theater_id, name, location FROM theaters ORDER BY location ASC, name ASC");
    $theaters = $theaters_stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching theaters: " . $e->getMessage();
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $movie_id = $_POST['movie_id'];
    $theater_id = $_POST['theater_id'];
    $show_date = $_POST['show_date'];
    $show_time = $_POST['show_time'];
    $price = floatval($_POST['price']);
    
    // Combine date and time
    $show_datetime = $show_date . ' ' . $show_time . ':00';

    // Validate inputs
    if (empty($movie_id) || empty($theater_id) || empty($show_date) || empty($show_time) || empty($price)) {
        $error_message = "All fields are required!";
    } elseif ($price <= 0) {
        $error_message = "Price must be a positive number";
    } else {
        try {
            // Check if there's already a show at the same time in the same theater
            $check_stmt = $conn->prepare("
                SELECT COUNT(*) FROM shows 
                WHERE theater_id = ? 
                AND show_time = ?
            ");
            $check_stmt->execute([$theater_id, $show_datetime]);
            $show_exists = $check_stmt->fetchColumn();
            
            if ($show_exists > 0) {
                $error_message = "There is already a show scheduled at this time in this theater.";
            } else {
                // Insert new show into the database
                $stmt = $conn->prepare("
                    INSERT INTO shows (
                        movie_id, theater_id, show_time, 
                        price, created_at
                        
                    ) VALUES (
                        ?, ?, ?, 
                        ?, NOW()
                    )
                ");
                
                if ($stmt->execute([
                    $movie_id, $theater_id, $show_datetime, 
                    $price
                ])) {
                    $success_message = "Show added successfully!";
                } else {
                    $error_message = "Error adding show.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch existing shows for display
try {
    $shows_stmt = $conn->prepare("
        SELECT 
            s.show_id,
            s.show_time,
            s.price,
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
            s.show_time >= NOW()
        ORDER BY 
            s.show_time ASC
        LIMIT 50
    ");
    $shows_stmt->execute();
    $shows = $shows_stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching shows: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shows - Admin Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #000000;
            color: #ffffff;
        }
        .form-card, .table-card {
            background-color: #111111;
            border: 1px solid #333333;
        }
        .input-field {
            background-color: #1a1a1a;
            border: 1px solid #333333;
            color: #ffffff;
        }
        .input-field:focus {
            border-color: #4b5563;
            box-shadow: 0 0 0 2px rgba(75, 85, 99, 0.2);
        }
        .submit-button {
            background-color: #ffffff;
            color: #000000;
        }
        .submit-button:hover {
            background-color: #e5e5e5;
        }
        .table-header {
            background-color: #1a1a1a;
        }
        .table-row:nth-child(even) {
            background-color: #1a1a1a;
        }
        .table-row:nth-child(odd) {
            background-color: #111111;
        }
        .table-row:hover {
            background-color: #262626;
        }
    </style>
</head>
<body class="min-h-screen">
    <?php include '../includes/header.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8 text-center">Manage Shows</h1>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Add Show Form -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Add New Show</h2>
                
                <?php if (!empty($success_message)): ?>
                <div class="bg-green-900 text-green-100 px-4 py-3 rounded-lg mb-6">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                <div class="bg-red-900 text-red-100 px-4 py-3 rounded-lg mb-6">
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="form-card rounded-lg overflow-hidden shadow-lg">
                    <div class="p-6">
                        <form method="POST" class="space-y-4">
                            <div>
                                <label for="movie_id" class="block text-sm font-medium text-gray-300 mb-1">
                                    Select Movie <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    name="movie_id" 
                                    id="movie_id" 
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none" 
                                    required
                                >
                                    <option value="">Select Movie</option>
                                    <?php foreach ($movies as $movie): ?>
                                        <option value="<?php echo $movie['movie_id']; ?>">
                                            <?php echo htmlspecialchars($movie['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="theater_id" class="block text-sm font-medium text-gray-300 mb-1">
                                    Select Theater <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    name="theater_id" 
                                    id="theater_id" 
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none" 
                                    required
                                >
                                    <option value="">Select Theater</option>
                                    <?php foreach ($theaters as $theater): ?>
                                        <option value="<?php echo $theater['theater_id']; ?>">
                                            <?php echo htmlspecialchars($theater['name'] . ' (' . $theater['location'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="show_date" class="block text-sm font-medium text-gray-300 mb-1">
                                    Show Date <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="date" 
                                    name="show_date" 
                                    id="show_date" 
                                    min="<?php echo date('Y-m-d'); ?>"
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none" 
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="show_time" class="block text-sm font-medium text-gray-300 mb-1">
                                    Show Time <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    name="show_time" 
                                    id="show_time" 
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none" 
                                    required
                                >
                                    <option value="">Select Time</option>
                                    <option value="09:00">09:00 AM</option>
                                    <option value="12:00">12:00 PM</option>
                                    <option value="15:00">03:00 PM</option>
                                    <option value="18:00">06:00 PM</option>
                                    <option value="21:00">09:00 PM</option>
                                    <option value="22:00">10:00 PM</option>

                                    <option value="23:30">11:30 PM</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="price" class="block text-sm font-medium text-gray-300 mb-1">
                                    Ticket Price (Rs) <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    name="price" 
                                    id="price" 
                                    min="1" 
                                    step="0.01"
                                    class="input-field w-full px-4 py-2 rounded-lg focus:outline-none" 
                                    required
                                >
                            </div>
                            
                            <div class="flex justify-end">
                                <button 
                                    type="submit" 
                                    class="submit-button font-medium py-2 px-6 rounded-lg transition-colors"
                                >
                                    Add Show
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Shows Table -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Upcoming Shows</h2>
                
                <div class="table-card rounded-lg overflow-hidden shadow-lg">
                    <?php if (empty($shows)): ?>
                        <div class="p-6 text-center text-gray-400">
                            No upcoming shows found.
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="table-header">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                            Movie
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                            Theater
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                            Date & Time
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                            Price
                                        </th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($shows as $show): ?>
                                        <tr class="table-row">
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <?php echo htmlspecialchars($show['movie_title']); ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div><?php echo htmlspecialchars($show['theater_name']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($show['theater_location']); ?></div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <div><?php echo date('d M Y', strtotime($show['show_time'])); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($show['show_time'])); ?></div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                Rs<?php echo number_format($show['price'], 2); ?>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                <a href="edit_show.php?id=<?php echo $show['show_id']; ?>" class="text-blue-400 hover:text-blue-300 mr-3">
                                                    Edit
                                                </a>
                                                <a href="delete_show.php?id=<?php echo $show['show_id']; ?>" class="text-red-400 hover:text-red-300" onclick="return confirm('Are you sure you want to delete this show?')">
                                                    Delete
                                                </a>
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
    </div>
    
    <script>
        // Set minimum date to today
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('show_date').min = today;
        });
    </script>
</body>
</html>