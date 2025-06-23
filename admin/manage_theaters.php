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
    if (isset($_POST['action']) && $_POST['action'] == 'add_theater') {
        $name = trim($_POST['name']);
        $branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
        $location = trim($_POST['location']);
        $capacity = (int)$_POST['capacity'];
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $screens = (int)($_POST['screens'] ?? 1);
        
        // Validation
        if (empty($name)) {
            $error_message = "Theater name is required!";
        } elseif (empty($location)) {
            $error_message = "Location is required!";
        } elseif ($capacity <= 0) {
            $error_message = "Capacity must be greater than 0!";
        } elseif ($branch_id === null) {
            $error_message = "Please select a branch!";
        } else {
            try {
                // Verify branch exists and is active
                $branch_check = $conn->prepare("SELECT COUNT(*) FROM branches WHERE branch_id = ? AND status = 'active'");
                $branch_check->execute([$branch_id]);
                
                if ($branch_check->fetchColumn() == 0) {
                    $error_message = "Selected branch is not valid or inactive!";
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO theaters (name, branch_id, location, capacity, address, city, state, screens, status, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
                    ");
                    
                    if ($stmt->execute([$name, $branch_id, $location, $capacity, $address, $city, $state, $screens])) {
                        $theater_id = $conn->lastInsertId();
                        $success_message = "Theater '{$name}' added successfully with ID: {$theater_id}!";
                    } else {
                        $error_message = "Error adding theater. Please try again.";
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'toggle_status') {
        $theater_id = (int)$_POST['theater_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status == 'active' ? 'inactive' : 'active';
        
        if ($theater_id <= 0) {
            $error_message = "Invalid theater ID!";
        } else {
            try {
                $stmt = $conn->prepare("UPDATE theaters SET status = ?, updated_at = NOW() WHERE theater_id = ?");
                if ($stmt->execute([$new_status, $theater_id])) {
                    $success_message = "Theater status updated successfully!";
                } else {
                    $error_message = "Error updating theater status.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'fix_theater_ids') {
        // Fix theaters with ID 0 or NULL branch_id
        try {
            // First, let's see what theaters have issues
            $problem_theaters = $conn->query("
                SELECT theater_id, name, branch_id 
                FROM theaters 
                WHERE theater_id = 0 OR branch_id IS NULL OR branch_id = 0
            ")->fetchAll();
            
            if (!empty($problem_theaters)) {
                // Delete theaters with ID 0 (they're corrupted)
                $conn->exec("DELETE FROM theaters WHERE theater_id = 0");
                
                // Update theaters with NULL or 0 branch_id to first active branch
                $first_branch = $conn->query("SELECT branch_id FROM branches WHERE status = 'active' LIMIT 1")->fetchColumn();
                if ($first_branch) {
                    $conn->prepare("UPDATE theaters SET branch_id = ? WHERE branch_id IS NULL OR branch_id = 0")->execute([$first_branch]);
                }
                
                $success_message = "Fixed " . count($problem_theaters) . " problematic theater records!";
            } else {
                $success_message = "No problematic theaters found.";
            }
        } catch (PDOException $e) {
            $error_message = "Error fixing theaters: " . $e->getMessage();
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

// Fetch theaters with branch information
try {
    $theaters_stmt = $conn->query("
        SELECT t.*, b.branch_name, b.location as branch_location
        FROM theaters t
        LEFT JOIN branches b ON t.branch_id = b.branch_id
        ORDER BY t.theater_id DESC, b.branch_name, t.name
    ");
    $theaters = $theaters_stmt->fetchAll();
} catch (PDOException $e) {
    $theaters = [];
}

// Check for problematic theaters
$problematic_theaters = [];
foreach ($theaters as $theater) {
    if ($theater['theater_id'] == 0 || empty($theater['branch_id']) || empty($theater['branch_name'])) {
        $problematic_theaters[] = $theater;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Theaters - Admin Panel</title>
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
                <i class="fas fa-theater-masks mr-3"></i>Manage Theaters
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
            
            <!-- Fix Problematic Theaters -->
            <?php if (!empty($problematic_theaters)): ?>
            <div class="bg-yellow-900 border border-yellow-700 text-yellow-100 px-4 py-3 rounded mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        <strong>Warning:</strong> Found <?php echo count($problematic_theaters); ?> theaters with invalid IDs or missing branch assignments.
                    </div>
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="fix_theater_ids">
                        <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded text-sm"
                                onclick="return confirm('This will fix problematic theater records. Continue?')">
                            <i class="fas fa-wrench mr-1"></i>Fix Now
                        </button>
                    </form>
                </div>
                <div class="mt-2 text-sm">
                    Problematic theaters: 
                    <?php foreach ($problematic_theaters as $pt): ?>
                        <span class="bg-yellow-800 px-2 py-1 rounded mr-1">
                            <?php echo htmlspecialchars($pt['name']); ?> (ID: <?php echo $pt['theater_id']; ?>)
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Add Theater Form -->
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-white mb-4">
                    <i class="fas fa-plus mr-2"></i>Add New Theater
                </h2>
                
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <input type="hidden" name="action" value="add_theater">
                    
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
                        <?php if (empty($branches)): ?>
                            <p class="text-red-400 text-xs mt-1">No active branches found. Please create a branch first.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Theater Name *</label>
                        <input type="text" name="name" required 
                               placeholder="e.g., Main Theater, Screen 1"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Location/Area *</label>
                        <input type="text" name="location" required 
                               placeholder="e.g., Downtown, Mall Area"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Capacity *</label>
                        <input type="number" name="capacity" required min="1" max="1000"
                               placeholder="Total seats"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Number of Screens</label>
                        <input type="number" name="screens" min="1" max="10" value="1"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">City</label>
                        <input type="text" name="city" 
                               placeholder="City name"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">State</label>
                        <input type="text" name="state" 
                               placeholder="State name"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-300 mb-1">Address</label>
                        <input type="text" name="address" 
                               placeholder="Full address"
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" 
                                class="w-full bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-md font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Add Theater
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Theaters List -->
            <div class="bg-gray-900 border border-gray-700 rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h2 class="text-xl font-semibold text-white">
                        <i class="fas fa-list mr-2"></i>All Theaters (<?php echo count($theaters); ?>)
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead class="bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Theater Info</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Branch</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Capacity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-900 divide-y divide-gray-700">
                            <?php if (empty($theaters)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-400">No theaters found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($theaters as $theater): ?>
                                    <tr class="<?php echo ($theater['theater_id'] == 0 || empty($theater['branch_name'])) ? 'bg-red-900 bg-opacity-20' : ''; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-white">
                                                <?php echo $theater['theater_id']; ?>
                                                <?php if ($theater['theater_id'] == 0): ?>
                                                    <span class="text-red-400 text-xs">(INVALID)</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($theater['name']); ?></div>
                                                <div class="text-sm text-gray-400"><?php echo $theater['screens']; ?> screen(s)</div>
                                                <?php if ($theater['address']): ?>
                                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($theater['address']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($theater['branch_name']): ?>
                                                <div class="text-sm text-white"><?php echo htmlspecialchars($theater['branch_name']); ?></div>
                                                <div class="text-sm text-gray-400"><?php echo htmlspecialchars($theater['branch_location']); ?></div>
                                            <?php else: ?>
                                                <span class="text-red-400">No Branch Assigned</span>
                                                <div class="text-xs text-gray-400">Branch ID: <?php echo $theater['branch_id'] ?? 'NULL'; ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white"><?php echo htmlspecialchars($theater['location']); ?></div>
                                            <?php if ($theater['city']): ?>
                                                <div class="text-sm text-gray-400"><?php echo htmlspecialchars($theater['city']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white"><?php echo number_format($theater['capacity']); ?> seats</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo ($theater['status'] ?? 'active') == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($theater['status'] ?? 'active'); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <?php if ($theater['theater_id'] > 0): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="theater_id" value="<?php echo $theater['theater_id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $theater['status'] ?? 'active'; ?>">
                                                    <button type="submit" 
                                                            class="text-white hover:text-gray-300 mr-3"
                                                            onclick="return confirm('Are you sure you want to <?php echo ($theater['status'] ?? 'active') == 'active' ? 'deactivate' : 'activate'; ?> this theater?')">
                                                        <i class="fas fa-<?php echo ($theater['status'] ?? 'active') == 'active' ? 'ban' : 'check'; ?>"></i>
                                                        <?php echo ($theater['status'] ?? 'active') == 'active' ? 'Deactivate' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-red-400 text-xs">Invalid Record</span>
                                            <?php endif; ?>
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
</body>
</html>
