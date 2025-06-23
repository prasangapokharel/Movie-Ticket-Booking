<?php
include '../database/config.php';
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'add_hall') {
        $branch_id = $_POST['branch_id'];
        $hall_name = trim($_POST['hall_name']);
        $total_rows = (int)$_POST['total_rows'];
        $seats_per_row = (int)$_POST['seats_per_row'];
        $hall_type = $_POST['hall_type'];
        
        if (empty($branch_id) || empty($hall_name) || $total_rows <= 0 || $seats_per_row <= 0) {
            $error_message = "All fields are required and must be valid!";
        } else {
            try {
                $total_capacity = $total_rows * $seats_per_row;
                
                $stmt = $conn->prepare("
                    INSERT INTO halls (branch_id, hall_name, total_rows, seats_per_row, total_capacity, hall_type) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$branch_id, $hall_name, $total_rows, $seats_per_row, $total_capacity, $hall_type])) {
                    $hall_id = $conn->lastInsertId();
                    
                    // Generate seats for this hall
                    $seat_stmt = $conn->prepare("INSERT INTO seats (show_id, seat_number, status) VALUES (?, ?, 'available')");
                    
                    for ($row = 1; $row <= $total_rows; $row++) {
                        $row_letter = chr(64 + $row); // A, B, C, etc.
                        for ($seat = 1; $seat <= $seats_per_row; $seat++) {
                            $seat_number = $row_letter . $seat;
                            // Note: We'll generate seats for specific shows later
                        }
                    }
                    
                    $success_message = "Hall added successfully with {$total_capacity} seats!";
                } else {
                    $error_message = "Error adding hall.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'generate_seats') {
        $show_id = $_POST['show_id'];
        $hall_id = $_POST['hall_id'];
        
        try {
            // Get hall details
            $hall_stmt = $conn->prepare("SELECT * FROM halls WHERE hall_id = ?");
            $hall_stmt->execute([$hall_id]);
            $hall = $hall_stmt->fetch();
            
            if ($hall) {
                // Check if seats already exist for this show
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM seats WHERE show_id = ?");
                $check_stmt->execute([$show_id]);
                
                if ($check_stmt->fetchColumn() == 0) {
                    // Generate seats
                    $seat_stmt = $conn->prepare("INSERT INTO seats (show_id, seat_number, status) VALUES (?, ?, 'available')");
                    
                    for ($row = 1; $row <= $hall['total_rows']; $row++) {
                        $row_letter = chr(64 + $row); // A, B, C, etc.
                        for ($seat = 1; $seat <= $hall['seats_per_row']; $seat++) {
                            $seat_number = $row_letter . $seat;
                            $seat_stmt->execute([$show_id, $seat_number]);
                        }
                    }
                    
                    $success_message = "Seats generated successfully for the show!";
                } else {
                    $error_message = "Seats already exist for this show.";
                }
            } else {
                $error_message = "Hall not found.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch branches
try {
    $branches_stmt = $conn->query("SELECT * FROM branches WHERE status = 'active' ORDER BY branch_name");
    $branches = $branches_stmt->fetchAll();
} catch (PDOException $e) {
    $branches = [];
}

// Fetch halls
try {
    $halls_stmt = $conn->query("
        SELECT h.*, b.branch_name 
        FROM halls h 
        JOIN branches b ON h.branch_id = b.branch_id 
        ORDER BY b.branch_name, h.hall_name
    ");
    $halls = $halls_stmt->fetchAll();
} catch (PDOException $e) {
    $halls = [];
}

// Fetch shows for seat generation
try {
    $shows_stmt = $conn->query("
        SELECT s.show_id, s.show_time, m.title as movie_title, t.name as theater_name, h.hall_name
        FROM shows s
        JOIN movies m ON s.movie_id = m.movie_id
        JOIN theaters t ON s.theater_id = t.theater_id
        LEFT JOIN halls h ON s.hall_id = h.hall_id
        WHERE s.show_time >= NOW()
        ORDER BY s.show_time
    ");
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
    <title>Manage Halls - Admin Panel</title>
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
                <i class="fas fa-chair mr-3"></i>Manage Halls & Seats
            </h1>
            
            <?php if (!empty($success_message)): ?>
            <div class="bg-green-900 border border-green-700 text-green-100 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="bg-red-900 border border-red-700 text-red-100 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Add Hall Form -->
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">
                        <i class="fas fa-plus mr-2"></i>Add New Hall
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_hall">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Branch *</label>
                            <select name="branch_id" required 
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                                <option value="">Select Branch</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['branch_id']; ?>">
                                        <?php echo htmlspecialchars($branch['branch_name'] . ' - ' . $branch['location']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Hall Name *</label>
                            <input type="text" name="hall_name" required 
                                   placeholder="e.g., Hall A, Screen 1"
                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Total Rows *</label>
                                <input type="number" name="total_rows" required min="1" max="26"
                                       class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                                <p class="text-xs text-gray-400 mt-1">Max 26 rows (A-Z)</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Seats per Row *</label>
                                <input type="number" name="seats_per_row" required min="1" max="50"
                                       class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Hall Type</label>
                            <select name="hall_type" 
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                                <option value="standard">Standard</option>
                                <option value="premium">Premium</option>
                                <option value="imax">IMAX</option>
                            </select>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-md font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Add Hall
                        </button>
                    </form>
                </div>
                
                <!-- Generate Seats for Shows -->
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">
                        <i class="fas fa-cogs mr-2"></i>Generate Seats for Show
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="generate_seats">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Select Show *</label>
                            <select name="show_id" required onchange="updateHallId(this)"
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                                <option value="">Select Show</option>
                                <?php foreach ($shows as $show): ?>
                                    <option value="<?php echo $show['show_id']; ?>" data-hall-id="<?php echo $show['hall_id'] ?? ''; ?>">
                                        <?php echo htmlspecialchars($show['movie_title'] . ' - ' . $show['theater_name'] . ' - ' . date('M j, Y H:i', strtotime($show['show_time']))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <input type="hidden" name="hall_id" id="hall_id">
                        
                        <button type="submit" 
                                class="w-full bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-md font-medium transition-colors">
                            <i class="fas fa-cogs mr-2"></i>Generate Seats
                        </button>
                    </form>
                    
                    <div class="mt-6 p-4 bg-gray-800 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>How it works:
                        </h3>
                        <ul class="text-xs text-gray-400 space-y-1">
                            <li>• Seats are generated based on hall configuration</li>
                            <li>• Row letters: A, B, C... (up to Z)</li>
                            <li>• Seat numbers: 1, 2, 3... (per row)</li>
                            <li>• Example: A1, A2, B1, B2, etc.</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Halls List -->
            <div class="mt-8 bg-gray-900 border border-gray-700 rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h2 class="text-xl font-semibold text-white">
                        <i class="fas fa-list mr-2"></i>All Halls
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead class="bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Hall Info</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Branch</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Configuration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-900 divide-y divide-gray-700">
                            <?php if (empty($halls)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-400">No halls found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($halls as $hall): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($hall['hall_name']); ?></div>
                                            <div class="text-sm text-gray-400">ID: <?php echo $hall['hall_id']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white"><?php echo htmlspecialchars($hall['branch_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white"><?php echo $hall['total_rows']; ?> rows × <?php echo $hall['seats_per_row']; ?> seats</div>
                                            <div class="text-sm text-gray-400">Total: <?php echo $hall['total_capacity']; ?> seats</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                <?php echo ucfirst($hall['hall_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $hall['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($hall['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function updateHallId(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const hallId = selectedOption.getAttribute('data-hall-id');
            document.getElementById('hall_id').value = hallId || '';
        }
    </script>
</body>
</html>
<?php
include '../database/config.php';
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action']) && $_POST['action'] == 'add_hall') {
        $branch_id = $_POST['branch_id'];
        $hall_name = trim($_POST['hall_name']);
        $total_rows = (int)$_POST['total_rows'];
        $seats_per_row = (int)$_POST['seats_per_row'];
        $hall_type = $_POST['hall_type'];
        
        if (empty($branch_id) || empty($hall_name) || $total_rows <= 0 || $seats_per_row <= 0) {
            $error_message = "All fields are required and must be valid!";
        } else {
            try {
                $total_capacity = $total_rows * $seats_per_row;
                
                $stmt = $conn->prepare("
                    INSERT INTO halls (branch_id, hall_name, total_rows, seats_per_row, total_capacity, hall_type) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$branch_id, $hall_name, $total_rows, $seats_per_row, $total_capacity, $hall_type])) {
                    $hall_id = $conn->lastInsertId();
                    
                    // Generate seats for this hall
                    $seat_stmt = $conn->prepare("INSERT INTO seats (show_id, seat_number, status) VALUES (?, ?, 'available')");
                    
                    for ($row = 1; $row <= $total_rows; $row++) {
                        $row_letter = chr(64 + $row); // A, B, C, etc.
                        for ($seat = 1; $seat <= $seats_per_row; $seat++) {
                            $seat_number = $row_letter . $seat;
                            // Note: We'll generate seats for specific shows later
                        }
                    }
                    
                    $success_message = "Hall added successfully with {$total_capacity} seats!";
                } else {
                    $error_message = "Error adding hall.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'generate_seats') {
        $show_id = $_POST['show_id'];
        $hall_id = $_POST['hall_id'];
        
        try {
            // Get hall details
            $hall_stmt = $conn->prepare("SELECT * FROM halls WHERE hall_id = ?");
            $hall_stmt->execute([$hall_id]);
            $hall = $hall_stmt->fetch();
            
            if ($hall) {
                // Check if seats already exist for this show
                $check_stmt = $conn->prepare("SELECT COUNT(*) FROM seats WHERE show_id = ?");
                $check_stmt->execute([$show_id]);
                
                if ($check_stmt->fetchColumn() == 0) {
                    // Generate seats
                    $seat_stmt = $conn->prepare("INSERT INTO seats (show_id, seat_number, status) VALUES (?, ?, 'available')");
                    
                    for ($row = 1; $row <= $hall['total_rows']; $row++) {
                        $row_letter = chr(64 + $row); // A, B, C, etc.
                        for ($seat = 1; $seat <= $hall['seats_per_row']; $seat++) {
                            $seat_number = $row_letter . $seat;
                            $seat_stmt->execute([$show_id, $seat_number]);
                        }
                    }
                    
                    $success_message = "Seats generated successfully for the show!";
                } else {
                    $error_message = "Seats already exist for this show.";
                }
            } else {
                $error_message = "Hall not found.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch branches
try {
    $branches_stmt = $conn->query("SELECT * FROM branches WHERE status = 'active' ORDER BY branch_name");
    $branches = $branches_stmt->fetchAll();
} catch (PDOException $e) {
    $branches = [];
}

// Fetch halls
try {
    $halls_stmt = $conn->query("
        SELECT h.*, b.branch_name 
        FROM halls h 
        JOIN branches b ON h.branch_id = b.branch_id 
        ORDER BY b.branch_name, h.hall_name
    ");
    $halls = $halls_stmt->fetchAll();
} catch (PDOException $e) {
    $halls = [];
}

// Fetch shows for seat generation
try {
    $shows_stmt = $conn->query("
        SELECT s.show_id, s.show_time, m.title as movie_title, t.name as theater_name, h.hall_name
        FROM shows s
        JOIN movies m ON s.movie_id = m.movie_id
        JOIN theaters t ON s.theater_id = t.theater_id
        LEFT JOIN halls h ON s.hall_id = h.hall_id
        WHERE s.show_time >= NOW()
        ORDER BY s.show_time
    ");
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
    <title>Manage Halls - Admin Panel</title>
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
                <i class="fas fa-chair mr-3"></i>Manage Halls & Seats
            </h1>
            
            <?php if (!empty($success_message)): ?>
            <div class="bg-green-900 border border-green-700 text-green-100 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success_message); ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="bg-red-900 border border-red-700 text-red-100 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Add Hall Form -->
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">
                        <i class="fas fa-plus mr-2"></i>Add New Hall
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="add_hall">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Branch *</label>
                            <select name="branch_id" required 
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                                <option value="">Select Branch</option>
                                <?php foreach ($branches as $branch): ?>
                                    <option value="<?php echo $branch['branch_id']; ?>">
                                        <?php echo htmlspecialchars($branch['branch_name'] . ' - ' . $branch['location']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Hall Name *</label>
                            <input type="text" name="hall_name" required 
                                   placeholder="e.g., Hall A, Screen 1"
                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Total Rows *</label>
                                <input type="number" name="total_rows" required min="1" max="26"
                                       class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                                <p class="text-xs text-gray-400 mt-1">Max 26 rows (A-Z)</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Seats per Row *</label>
                                <input type="number" name="seats_per_row" required min="1" max="50"
                                       class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Hall Type</label>
                            <select name="hall_type" 
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                                <option value="standard">Standard</option>
                                <option value="premium">Premium</option>
                                <option value="imax">IMAX</option>
                            </select>
                        </div>
                        
                        <button type="submit" 
                                class="w-full bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-md font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Add Hall
                        </button>
                    </form>
                </div>
                
                <!-- Generate Seats for Shows -->
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">
                        <i class="fas fa-cogs mr-2"></i>Generate Seats for Show
                    </h2>
                    
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="action" value="generate_seats">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Select Show *</label>
                            <select name="show_id" required onchange="updateHallId(this)"
                                    class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                                <option value="">Select Show</option>
                                <?php foreach ($shows as $show): ?>
                                    <option value="<?php echo $show['show_id']; ?>" data-hall-id="<?php echo $show['hall_id'] ?? ''; ?>">
                                        <?php echo htmlspecialchars($show['movie_title'] . ' - ' . $show['theater_name'] . ' - ' . date('M j, Y H:i', strtotime($show['show_time']))); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <input type="hidden" name="hall_id" id="hall_id">
                        
                        <button type="submit" 
                                class="w-full bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-md font-medium transition-colors">
                            <i class="fas fa-cogs mr-2"></i>Generate Seats
                        </button>
                    </form>
                    
                    <div class="mt-6 p-4 bg-gray-800 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-info-circle mr-1"></i>How it works:
                        </h3>
                        <ul class="text-xs text-gray-400 space-y-1">
                            <li>• Seats are generated based on hall configuration</li>
                            <li>• Row letters: A, B, C... (up to Z)</li>
                            <li>• Seat numbers: 1, 2, 3... (per row)</li>
                            <li>• Example: A1, A2, B1, B2, etc.</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Halls List -->
            <div class="mt-8 bg-gray-900 border border-gray-700 rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h2 class="text-xl font-semibold text-white">
                        <i class="fas fa-list mr-2"></i>All Halls
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead class="bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Hall Info</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Branch</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Configuration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-900 divide-y divide-gray-700">
                            <?php if (empty($halls)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-400">No halls found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($halls as $hall): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($hall['hall_name']); ?></div>
                                            <div class="text-sm text-gray-400">ID: <?php echo $hall['hall_id']; ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white"><?php echo htmlspecialchars($hall['branch_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white"><?php echo $hall['total_rows']; ?> rows × <?php echo $hall['seats_per_row']; ?> seats</div>
                                            <div class="text-sm text-gray-400">Total: <?php echo $hall['total_capacity']; ?> seats</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                <?php echo ucfirst($hall['hall_type']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $hall['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($hall['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function updateHallId(selectElement) {
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const hallId = selectedOption.getAttribute('data-hall-id');
            document.getElementById('hall_id').value = hallId || '';
        }
    </script>
</body>
</html>
