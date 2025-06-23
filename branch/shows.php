<?php
include '../database/config.php';
session_start();

if (!isset($_SESSION['branch_id'])) {
    header("Location: login.php");
    exit();
}

$branch_id = $_SESSION['branch_id'];
$success_message = '';
$error_message = '';
$debug = true; // Enable debugging to see what's happening
$debugInfo = [];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'add_show') {
        // Get and sanitize form data
        $movie_id = isset($_POST['movie_id']) ? trim($_POST['movie_id']) : '';
        $theater_id = isset($_POST['theater_id']) ? trim($_POST['theater_id']) : '';
        $hall_id = isset($_POST['hall_id']) && !empty(trim($_POST['hall_id'])) ? trim($_POST['hall_id']) : null;
        $show_date = isset($_POST['show_date']) ? trim($_POST['show_date']) : '';
        $show_time = isset($_POST['show_time']) ? trim($_POST['show_time']) : '';
        $price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
        
        // Debug information
        if ($debug) {
            $debugInfo['form_data'] = [
                'movie_id' => $movie_id,
                'theater_id' => $theater_id,
                'hall_id' => $hall_id,
                'show_date' => $show_date,
                'show_time' => $show_time,
                'price' => $price,
                'branch_id' => $branch_id
            ];
        }
        
        // Validation
        $validation_errors = [];
        
        if (empty($movie_id)) {
            $validation_errors[] = "Please select a movie";
        }
        
        if (empty($theater_id)) {
            $validation_errors[] = "Please select a theater";
        } else {
            // Verify that the selected theater belongs to this branch
            try {
                $theater_check = $conn->prepare("SELECT COUNT(*) FROM theaters WHERE theater_id = ? AND branch_id = ?");
                $theater_check->execute([$theater_id, $branch_id]);
                if ($theater_check->fetchColumn() == 0) {
                    $validation_errors[] = "Selected theater does not belong to your branch";
                    if ($debug) {
                        $debugInfo['theater_validation'] = "Theater ID $theater_id not found for branch ID $branch_id";
                    }
                }
            } catch (PDOException $e) {
                $validation_errors[] = "Error validating theater";
                if ($debug) {
                    $debugInfo['theater_validation_error'] = $e->getMessage();
                }
            }
        }
        
        if (empty($show_date)) {
            $validation_errors[] = "Please select a show date";
        }
        
        if (empty($show_time)) {
            $validation_errors[] = "Please select a show time";
        }
        
        if ($price <= 0) {
            $validation_errors[] = "Please enter a valid ticket price greater than 0";
        }
        
        if (!empty($validation_errors)) {
            $error_message = implode(", ", $validation_errors);
            if ($debug) {
                $error_message .= "<br><br><strong>Debug Info:</strong><br>" . print_r($debugInfo, true);
            }
        } else {
            // Proceed with adding the show
            $show_datetime = $show_date . ' ' . $show_time . ':00';

            try {
                // Check for conflicts
                $check_query = "
                    SELECT COUNT(*) FROM shows s
                    JOIN theaters t ON s.theater_id = t.theater_id
                    WHERE t.branch_id = ? AND s.show_time = ?";
                
                $check_params = [$branch_id, $show_datetime];
                
                if ($hall_id) {
                    $check_query .= " AND s.hall_id = ?";
                    $check_params[] = $hall_id;
                }
                
                $check_stmt = $conn->prepare($check_query);
                $check_stmt->execute($check_params);
                
                if ($check_stmt->fetchColumn() > 0) {
                    $error_message = "There is already a show scheduled at this time" . ($hall_id ? " in this hall" : "") . ".";
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO shows (movie_id, theater_id, hall_id, show_time, price, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    
                    if ($stmt->execute([$movie_id, $theater_id, $hall_id, $show_datetime, $price])) {
                        $show_id = $conn->lastInsertId();
                        
                        // Generate seats for this show if hall is selected
                        if ($hall_id) {
                            $hall_stmt = $conn->prepare("SELECT * FROM halls WHERE hall_id = ?");
                            $hall_stmt->execute([$hall_id]);
                            $hall = $hall_stmt->fetch();
                            
                            if ($hall) {
                                $seat_stmt = $conn->prepare("INSERT INTO seats (show_id, seat_number, status) VALUES (?, ?, 'available')");
                                
                                for ($row = 1; $row <= $hall['total_rows']; $row++) {
                                    $row_letter = chr(64 + $row);
                                    for ($seat = 1; $seat <= $hall['seats_per_row']; $seat++) {
                                        $seat_number = $row_letter . $seat;
                                        $seat_stmt->execute([$show_id, $seat_number]);
                                    }
                                }
                            }
                        }
                        
                        $success_message = "Show added successfully" . ($hall_id ? " with seats generated" : "") . "!";
                        
                        // Clear form data after successful submission
                        $_POST = [];
                    } else {
                        $error_message = "Error adding show. Please try again.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch movies
try {
    $movies_stmt = $conn->query("SELECT movie_id, title FROM movies WHERE status = 'active' ORDER BY title ASC");
    $movies = $movies_stmt->fetchAll();
} catch (PDOException $e) {
    $movies = [];
}

// Fetch branch theaters - Make sure we're only getting theaters for this branch
try {
    $theaters_stmt = $conn->prepare("SELECT theater_id, name FROM theaters WHERE branch_id = ? ORDER BY name ASC");
    $theaters_stmt->execute([$branch_id]);
    $theaters = $theaters_stmt->fetchAll();
    
    if ($debug) {
        $debugInfo['available_theaters'] = $theaters;
        $debugInfo['branch_id'] = $branch_id;
    }
} catch (PDOException $e) {
    $theaters = [];
    if ($debug) {
        $debugInfo['theater_fetch_error'] = $e->getMessage();
    }
}

// Fetch branch halls
try {
    $halls_stmt = $conn->prepare("SELECT * FROM halls WHERE branch_id = ? AND status = 'active' ORDER BY hall_name ASC");
    $halls_stmt->execute([$branch_id]);
    $halls = $halls_stmt->fetchAll();
} catch (PDOException $e) {
    $halls = [];
}

// Fetch existing shows
try {
    $shows_stmt = $conn->prepare("
        SELECT s.show_id, s.show_time, s.price, m.title as movie_title, 
               t.name as theater_name, h.hall_name,
               (SELECT COUNT(*) FROM seats WHERE show_id = s.show_id AND status = 'booked') as booked_seats,
               (SELECT COUNT(*) FROM seats WHERE show_id = s.show_id) as total_seats
        FROM shows s
        JOIN movies m ON s.movie_id = m.movie_id
        JOIN theaters t ON s.theater_id = t.theater_id
        LEFT JOIN halls h ON s.hall_id = h.hall_id
        WHERE t.branch_id = ? AND s.show_time >= NOW()
        ORDER BY s.show_time ASC
    ");
    $shows_stmt->execute([$branch_id]);
    $shows = $shows_stmt->fetchAll();
} catch (PDOException $e) {
    $shows = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Shows - Branch Admin</title>
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
                <i class="fas fa-calendar mr-3"></i>Manage Shows
            </h1>
            
            <?php if (!empty($success_message)): ?>
            <div class="bg-green-900 border border-green-700 text-green-100 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="bg-red-900 border border-red-700 text-red-100 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Debug Information -->
            <?php if ($debug && !empty($debugInfo)): ?>
            <div class="bg-yellow-900 border border-yellow-700 text-yellow-100 px-4 py-3 rounded mb-6">
                <h4 class="font-bold">Debug Information:</h4>
                <pre class="text-xs mt-2 overflow-auto"><?php echo print_r($debugInfo, true); ?></pre>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Add Show Form -->
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">
                        <i class="fas fa-plus mr-2"></i>Add New Show
                    </h2>
                    
                    <!-- Show available theaters for debugging -->
                    <?php if ($debug && empty($theaters)): ?>
                    <div class="bg-orange-900 border border-orange-700 text-orange-100 px-4 py-3 rounded mb-4">
                        <strong>Warning:</strong> No theaters found for your branch (ID: <?php echo $branch_id; ?>). 
                        Please contact admin to assign theaters to your branch.
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_show">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Movie *</label>
                            <select name="movie_id" required 
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                                <option value="">Select Movie</option>
                                <?php foreach ($movies as $movie): ?>
                                    <option value="<?php echo $movie['movie_id']; ?>" 
                                            <?php echo (isset($_POST['movie_id']) && $_POST['movie_id'] == $movie['movie_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($movie['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">
                                Theater * 
                                <?php if ($debug): ?>
                                    <span class="text-xs text-gray-400">(<?php echo count($theaters); ?> available)</span>
                                <?php endif; ?>
                            </label>
                            <select name="theater_id" required 
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                                <option value="">Select Theater</option>
                                <?php foreach ($theaters as $theater): ?>
                                    <option value="<?php echo $theater['theater_id']; ?>"
                                            <?php echo (isset($_POST['theater_id']) && $_POST['theater_id'] == $theater['theater_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($theater['name']); ?>
                                        <?php if ($debug): ?>
                                            (ID: <?php echo $theater['theater_id']; ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Hall</label>
                            <select name="hall_id" 
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                                <option value="">Select Hall (Optional)</option>
                                <?php foreach ($halls as $hall): ?>
                                    <option value="<?php echo $hall['hall_id']; ?>"
                                            <?php echo (isset($_POST['hall_id']) && $_POST['hall_id'] == $hall['hall_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($hall['hall_name']) . ' (' . $hall['total_capacity'] . ' seats)'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Show Date *</label>
                                <input type="date" name="show_date" required min="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo isset($_POST['show_date']) ? htmlspecialchars($_POST['show_date']) : ''; ?>"
                                       class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Show Time *</label>
                                <select name="show_time" required 
                                        class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                                    <option value="">Select Time</option>
                                    <option value="09:00" <?php echo (isset($_POST['show_time']) && $_POST['show_time'] == '09:00') ? 'selected' : ''; ?>>09:00 AM</option>
                                    <option value="12:00" <?php echo (isset($_POST['show_time']) && $_POST['show_time'] == '12:00') ? 'selected' : ''; ?>>12:00 PM</option>
                                    <option value="15:00" <?php echo (isset($_POST['show_time']) && $_POST['show_time'] == '15:00') ? 'selected' : ''; ?>>03:00 PM</option>
                                    <option value="18:00" <?php echo (isset($_POST['show_time']) && $_POST['show_time'] == '18:00') ? 'selected' : ''; ?>>06:00 PM</option>
                                    <option value="21:00" <?php echo (isset($_POST['show_time']) && $_POST['show_time'] == '21:00') ? 'selected' : ''; ?>>09:00 PM</option>
                                    <option value="22:00" <?php echo (isset($_POST['show_time']) && $_POST['show_time'] == '22:00') ? 'selected' : ''; ?>>10:00 PM</option>
                                    <option value="23:30" <?php echo (isset($_POST['show_time']) && $_POST['show_time'] == '23:30') ? 'selected' : ''; ?>>11:30 PM</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Ticket Price (₹) *</label>
                            <input type="number" name="price" required min="1" step="0.01"
                                   value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>"
                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-md font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Add Show
                        </button>
                    </form>
                </div>
                
                <!-- Show Statistics -->
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">
                        <i class="fas fa-chart-bar mr-2"></i>Show Statistics
                    </h2>
                    
                    <div class="space-y-4">
                        <div class="bg-gray-800 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-400">Total Shows</p>
                                    <p class="text-2xl font-semibold text-white"><?php echo count($shows); ?></p>
                                </div>
                                <i class="fas fa-calendar-alt text-2xl text-gray-400"></i>
                            </div>
                        </div>
                        
                        <div class="bg-gray-800 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-400">Available Theaters</p>
                                    <p class="text-2xl font-semibold text-white"><?php echo count($theaters); ?></p>
                                </div>
                                <i class="fas fa-theater-masks text-2xl text-gray-400"></i>
                            </div>
                        </div>
                        
                        <div class="bg-gray-800 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-400">Available Halls</p>
                                    <p class="text-2xl font-semibold text-white"><?php echo count($halls); ?></p>
                                </div>
                                <i class="fas fa-chair text-2xl text-gray-400"></i>
                            </div>
                        </div>
                        
                        <div class="bg-gray-800 p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-400">Active Movies</p>
                                    <p class="text-2xl font-semibold text-white"><?php echo count($movies); ?></p>
                                </div>
                                <i class="fas fa-film text-2xl text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Shows List -->
            <div class="mt-8 bg-gray-900 border border-gray-700 rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h2 class="text-xl font-semibold text-white">
                        <i class="fas fa-list mr-2"></i>Upcoming Shows
                    </h2>
                </div>
                
                <?php if (empty($shows)): ?>
                    <div class="p-6 text-center text-gray-400">
                        No upcoming shows found. Add your first show above.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Movie</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Theater/Hall</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Date & Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Bookings</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-900 divide-y divide-gray-700">
                                <?php foreach ($shows as $show): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($show['movie_title']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white"><?php echo htmlspecialchars($show['theater_name']); ?></div>
                                            <?php if ($show['hall_name']): ?>
                                                <div class="text-xs text-gray-400"><?php echo htmlspecialchars($show['hall_name']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white"><?php echo date('M j, Y', strtotime($show['show_time'])); ?></div>
                                            <div class="text-xs text-gray-400"><?php echo date('h:i A', strtotime($show['show_time'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white">₹<?php echo number_format($show['price'], 2); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white"><?php echo $show['booked_seats']; ?>/<?php echo $show['total_seats']; ?></div>
                                            <div class="w-full bg-gray-700 rounded-full h-2 mt-1">
                                                <?php 
                                                $percentage = $show['total_seats'] > 0 ? ($show['booked_seats'] / $show['total_seats']) * 100 : 0;
                                                ?>
                                                <div class="bg-white h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="viewSeats(<?php echo $show['show_id']; ?>)" 
                                                    class="text-white hover:text-gray-300 mr-3">
                                                <i class="fas fa-chair"></i> Seats
                                            </button>
                                            <button onclick="deleteShow(<?php echo $show['show_id']; ?>)" 
                                                    class="text-red-400 hover:text-red-300">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
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
        function viewSeats(showId) {
            window.open(`seat_management.php?show_id=${showId}`, '_blank');
        }
        
        function deleteShow(showId) {
            if (confirm('Are you sure you want to delete this show? This will also delete all associated seats and bookings.')) {
                window.location.href = `delete_show.php?show_id=${showId}`;
            }
        }
    </script>
</body>
</html>
