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

try {
    // Fetch show details
    $stmt = $conn->prepare("
        SELECT 
            s.show_id, 
            s.movie_id,
            s.theater_id,
            s.show_time, 
            s.price,
            s.screen,
            m.title AS movie_title,
            t.name AS theater_name,
            t.location AS theater_location,
            t.screens
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
    
    // Process form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $movie_id = $_POST['movie_id'];
        $theater_id = $_POST['theater_id'];
        $show_time = $_POST['show_time'];
        $price = (float)$_POST['price'];
        $screen = isset($_POST['screen']) ? (int)$_POST['screen'] : 1;
        
        // Validate inputs
        if (empty($movie_id) || empty($theater_id) || empty($show_time) || empty($price)) {
            $error_message = "All required fields must be filled out";
        } else {
            try {
                // If show time or theater or screen changed, check for conflicts
                if ($show_time != $show['show_time'] || $theater_id != $show['theater_id'] || $screen != $show['screen']) {
                    $check_stmt = $conn->prepare("
                        SELECT COUNT(*) FROM shows 
                        WHERE theater_id = ? 
                        AND DATE_FORMAT(show_time, '%Y-%m-%d %H:%i') = DATE_FORMAT(?, '%Y-%m-%d %H:%i') 
                        AND screen = ?
                        AND show_id != ?
                    ");
                    $check_stmt->execute([$theater_id, $show_time, $screen, $show_id]);
                    $show_exists = $check_stmt->fetchColumn() > 0;
                    
                    if ($show_exists) {
                        $error_message = "A show is already scheduled at this time for this theater and screen.";
                    }
                }
                
                if (empty($error_message)) {
                    // Update show
                    $update_stmt = $conn->prepare("
                        UPDATE shows 
                        SET movie_id = ?, theater_id = ?, show_time = ?, price = ?, screen = ?, updated_at = NOW()
                        WHERE show_id = ?
                    ");
                    
                    if ($update_stmt->execute([$movie_id, $theater_id, $show_time, $price, $screen, $show_id])) {
                        $success_message = "Show updated successfully!";
                        
                        // Refresh show data
                        $stmt->execute([$show_id]);
                        $show = $stmt->fetch();
                    } else {
                        $error_message = "Error updating show.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
    
    // Fetch movies
    $movies_stmt = $conn->prepare("SELECT movie_id, title FROM movies ORDER BY title");
    $movies_stmt->execute();
    $movies = $movies_stmt->fetchAll();
    
    // Fetch theaters
    $theaters_stmt = $conn->prepare("SELECT theater_id, name, location, screens FROM theaters ORDER BY name");
    $theaters_stmt->execute();
    $theaters = $theaters_stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Show - Admin Panel</title>
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
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-8">Edit Show</h1>
            
            <?php if (!empty($success_message)): ?>
            <div class="bg-green-900 text-green-100 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="bg-red-900 text-red-100 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($has_bookings): ?>
            <div class="bg-yellow-900/50 text-yellow-100 px-4 py-3 rounded-lg mb-6">
                <p class="font-medium">Warning: This show has existing bookings</p>
                <p class="mt-1">Changing the movie, date, or time may affect customers who have already booked tickets.</p>
            </div>
            <?php endif; ?>
            
            <div class="bg-gray-900 rounded-lg p-6">
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="movie_id" class="block text-sm font-medium text-gray-300 mb-1">
                                Movie*
                            </label>
                            <select 
                                name="movie_id" 
                                id="movie_id" 
                                class="input-field w-full px-4 py-2 rounded-lg" 
                                required
                            >
                                <?php foreach ($movies as $movie): ?>
                                    <option value="<?php echo $movie['movie_id']; ?>" <?php echo ($movie['movie_id'] == $show['movie_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="theater_id" class="block text-sm font-medium text-gray-300 mb-1">
                                Theater*
                            </label>
                            <select 
                                name="theater_id" 
                                id="theater_id" 
                                class="input-field w-full px-4 py-2 rounded-lg" 
                                required
                                onchange="updateScreenOptions()"
                            >
                                <?php foreach ($theaters as $theater): ?>
                                    <option value="<?php echo $theater['theater_id']; ?>" 
                                            data-screens="<?php echo $theater['screens']; ?>"
                                            <?php echo ($theater['theater_id'] == $show['theater_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($theater['name'] . ' - ' . $theater['location']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="screen" class="block text-sm font-medium text-gray-300 mb-1">
                                Screen Number
                            </label>
                            <select 
                                name="screen" 
                                id="screen" 
                                class="input-field w-full px-4 py-2 rounded-lg"
                            >
                                <?php for ($i = 1; $i <= $show['screens']; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($i == $show['screen']) ? 'selected' : ''; ?>>
                                        Screen <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="show_time" class="block text-sm font-medium text-gray-300 mb-1">
                                Show Time*
                            </label>
                            <input 
                                type="datetime-local" 
                                name="show_time" 
                                id="show_time" 
                                value="<?php echo date('Y-m-d\TH:i', strtotime($show['show_time'])); ?>"
                                class="input-field w-full px-4 py-2 rounded-lg" 
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-300 mb-1">
                                Ticket Price*
                            </label>
                            <input 
                                type="number" 
                                step="0.01" 
                                name="price" 
                                id="price" 
                                value="<?php echo $show['price']; ?>"
                                class="input-field w-full px-4 py-2 rounded-lg" 
                                required
                            >
                        </div>
                    </div>
                    
                    <div class="mt-8 flex justify-end space-x-4">
                        <a href="manage_shows.php" class="bg-gray-700 hover:bg-gray-600 text-white font-medium py-2 px-6 rounded-lg">
                            Cancel
                        </a>
                        <button 
                            type="submit" 
                            class="submit-button font-medium py-2 px-6 rounded-lg"
                        >
                            Update Show
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function updateScreenOptions() {
            const theaterSelect = document.getElementById('theater_id');
            const screenSelect = document.getElementById('screen');
            const selectedOption = theaterSelect.options[theaterSelect.selectedIndex];
            
            if (selectedOption.value) {
                const screens = parseInt(selectedOption.dataset.screens) || 1;
                
                // Clear existing options
                screenSelect.innerHTML = '';
                
                // Add new options
                for (let i = 1; i <= screens; i++) {
                    const option = document.createElement('option');
                    option.value = i;
                    option.textContent = `Screen ${i}`;
                    screenSelect.appendChild(option);
                }
            } else {
                screenSelect.innerHTML = '<option value="1">Screen 1</option>';
            }
        }
    </script>
</body>
</html>
