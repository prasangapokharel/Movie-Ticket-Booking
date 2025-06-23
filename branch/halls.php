<?php
include '../database/config.php';
session_start();

// Check if branch admin is logged in
if (!isset($_SESSION['branch_id'])) {
    header("Location: login.php");
    exit();
}

$branch_id = $_SESSION['branch_id'];
$success_message = '';
$error_message = '';

// Handle form submission for adding halls
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_hall') {
    $hall_name = trim($_POST['hall_name']);
    $total_rows = (int)$_POST['total_rows'];
    $seats_per_row = (int)$_POST['seats_per_row'];
    $hall_type = $_POST['hall_type'];
    
    if (empty($hall_name) || $total_rows <= 0 || $seats_per_row <= 0) {
        $error_message = "All fields are required and must be valid!";
    } else {
        try {
            $total_capacity = $total_rows * $seats_per_row;
            
            $stmt = $conn->prepare("
                INSERT INTO halls (branch_id, hall_name, total_rows, seats_per_row, total_capacity, hall_type) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$branch_id, $hall_name, $total_rows, $seats_per_row, $total_capacity, $hall_type])) {
                $success_message = "Hall added successfully with {$total_capacity} seats!";
            } else {
                $error_message = "Error adding hall.";
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch halls for this branch
try {
    $halls_stmt = $conn->prepare("SELECT * FROM halls WHERE branch_id = ? ORDER BY hall_name");
    $halls_stmt->execute([$branch_id]);
    $halls = $halls_stmt->fetchAll();
} catch (PDOException $e) {
    $halls = [];
}

// Get branch info
try {
    $branch_stmt = $conn->prepare("SELECT * FROM branches WHERE branch_id = ?");
    $branch_stmt->execute([$branch_id]);
    $branch_info = $branch_stmt->fetch();
} catch (PDOException $e) {
    $branch_info = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Halls - Branch Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #000000; color: #ffffff; }
        .seat { width: 30px; height: 30px; margin: 2px; }
        .seat.available { background-color: #10b981; }
        .seat.booked { background-color: #ef4444; }
        .seat.selected { background-color: #3b82f6; }
    </style>
</head>
<body>
    <?php include '../includes/branchnav.php'; ?>
    
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
                            <label class="block text-sm font-medium text-gray-300 mb-1">Hall Name *</label>
                            <input type="text" name="hall_name" required 
                                   placeholder="e.g., Hall A, Screen 1"
                                   class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Total Rows *</label>
                                <input type="number" name="total_rows" required min="1" max="26" id="total_rows"
                                       class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white"
                                       onchange="updatePreview()">
                                <p class="text-xs text-gray-400 mt-1">Max 26 rows (A-Z)</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-300 mb-1">Seats per Row *</label>
                                <input type="number" name="seats_per_row" required min="1" max="50" id="seats_per_row"
                                       class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white"
                                       onchange="updatePreview()">
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
                        
                        <div id="capacity_preview" class="text-sm text-gray-400"></div>
                        
                        <button type="submit" 
                                class="w-full bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-md font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Add Hall
                        </button>
                    </form>
                </div>
                
                <!-- Seat Layout Preview -->
                <div class="bg-gray-900 border border-gray-700 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-white mb-4">
                        <i class="fas fa-eye mr-2"></i>Seat Layout Preview
                    </h2>
                    
                    <div class="text-center mb-4">
                        <div class="bg-gray-600 text-white py-2 px-4 rounded-lg inline-block">
                            <i class="fas fa-tv mr-2"></i>SCREEN
                        </div>
                    </div>
                    
                    <div id="seat_preview" class="flex flex-col items-center space-y-2">
                        <p class="text-gray-400">Enter rows and seats to see preview</p>
                    </div>
                    
                    <div class="mt-4 flex justify-center space-x-4 text-xs">
                        <div class="flex items-center">
                            <div class="seat available rounded"></div>
                            <span class="ml-1 text-gray-400">Available</span>
                        </div>
                        <div class="flex items-center">
                            <div class="seat booked rounded"></div>
                            <span class="ml-1 text-gray-400">Booked</span>
                        </div>
                        <div class="flex items-center">
                            <div class="seat selected rounded"></div>
                            <span class="ml-1 text-gray-400">Selected</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Existing Halls -->
            <div class="mt-8 bg-gray-900 border border-gray-700 rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h2 class="text-xl font-semibold text-white">
                        <i class="fas fa-list mr-2"></i>Your Halls
                    </h2>
                </div>
                
                <?php if (empty($halls)): ?>
                    <div class="p-6 text-center text-gray-400">
                        No halls found. Add your first hall above.
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                        <?php foreach ($halls as $hall): ?>
                            <div class="bg-gray-800 border border-gray-600 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($hall['hall_name']); ?></h3>
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                        <?php echo ucfirst($hall['hall_type']); ?>
                                    </span>
                                </div>
                                
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Rows:</span>
                                        <span class="text-white"><?php echo $hall['total_rows']; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Seats per Row:</span>
                                        <span class="text-white"><?php echo $hall['seats_per_row']; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Total Capacity:</span>
                                        <span class="text-white font-semibold"><?php echo $hall['total_capacity']; ?></span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-400">Status:</span>
                                        <span class="text-white"><?php echo ucfirst($hall['status']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="mt-4 pt-3 border-t border-gray-600">
                                    <button onclick="showHallLayout(<?php echo $hall['hall_id']; ?>, '<?php echo htmlspecialchars($hall['hall_name']); ?>', <?php echo $hall['total_rows']; ?>, <?php echo $hall['seats_per_row']; ?>)"
                                            class="w-full bg-white text-black hover:bg-gray-200 px-3 py-2 rounded text-sm font-medium transition-colors">
                                        <i class="fas fa-eye mr-1"></i>View Layout
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Hall Layout Modal -->
    <div id="hallModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-gray-900 border border-gray-700 rounded-lg p-6 max-w-4xl w-full mx-4 max-h-screen overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 id="modalTitle" class="text-xl font-semibold text-white"></h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="text-center mb-4">
                <div class="bg-gray-600 text-white py-2 px-4 rounded-lg inline-block">
                    <i class="fas fa-tv mr-2"></i>SCREEN
                </div>
            </div>
            
            <div id="modalSeatLayout" class="flex flex-col items-center space-y-2">
                <!-- Seat layout will be generated here -->
            </div>
        </div>
    </div>
    
    <script>
        function updatePreview() {
            const rows = parseInt(document.getElementById('total_rows').value) || 0;
            const seatsPerRow = parseInt(document.getElementById('seats_per_row').value) || 0;
            const totalCapacity = rows * seatsPerRow;
            
            // Update capacity preview
            document.getElementById('capacity_preview').textContent = 
                totalCapacity > 0 ? `Total Capacity: ${totalCapacity} seats` : '';
            
            // Update seat preview
            const preview = document.getElementById('seat_preview');
            if (rows > 0 && seatsPerRow > 0 && rows <= 10 && seatsPerRow <= 20) {
                preview.innerHTML = '';
                for (let row = 1; row <= rows; row++) {
                    const rowDiv = document.createElement('div');
                    rowDiv.className = 'flex items-center space-x-1';
                    
                    const rowLabel = document.createElement('span');
                    rowLabel.textContent = String.fromCharCode(64 + row);
                    rowLabel.className = 'text-white text-sm w-6 text-center';
                    rowDiv.appendChild(rowLabel);
                    
                    for (let seat = 1; seat <= seatsPerRow; seat++) {
                        const seatDiv = document.createElement('div');
                        seatDiv.className = 'seat available rounded text-xs flex items-center justify-center text-white';
                        seatDiv.textContent = seat;
                        rowDiv.appendChild(seatDiv);
                    }
                    
                    preview.appendChild(rowDiv);
                }
            } else if (rows > 0 && seatsPerRow > 0) {
                preview.innerHTML = '<p class="text-gray-400">Preview available for halls up to 10 rows × 20 seats</p>';
            } else {
                preview.innerHTML = '<p class="text-gray-400">Enter rows and seats to see preview</p>';
            }
        }
        
        function showHallLayout(hallId, hallName, rows, seatsPerRow) {
            document.getElementById('modalTitle').textContent = `${hallName} - Seat Layout`;
            
            const layout = document.getElementById('modalSeatLayout');
            layout.innerHTML = '';
            
            for (let row = 1; row <= rows; row++) {
                const rowDiv = document.createElement('div');
                rowDiv.className = 'flex items-center space-x-1';
                
                const rowLabel = document.createElement('span');
                rowLabel.textContent = String.fromCharCode(64 + row);
                rowLabel.className = 'text-white text-sm w-8 text-center font-medium';
                rowDiv.appendChild(rowLabel);
                
                for (let seat = 1; seat <= seatsPerRow; seat++) {
                    const seatDiv = document.createElement('div');
                    seatDiv.className = 'seat available rounded text-xs flex items-center justify-center text-white font-medium';
                    seatDiv.textContent = seat;
                    rowDiv.appendChild(seatDiv);
                }
                
                layout.appendChild(rowDiv);
            }
            
            document.getElementById('hallModal').classList.remove('hidden');
            document.getElementById('hallModal').classList.add('flex');
        }
        
        function closeModal() {
            document.getElementById('hallModal').classList.add('hidden');
            document.getElementById('hallModal').classList.remove('flex');
        }
        
        // Close modal when clicking outside
        document.getElementById('hallModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
