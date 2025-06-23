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
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            // Add new branch
            $branch_name = trim($_POST['branch_name']);
            $branch_code = trim($_POST['branch_code']);
            $location = trim($_POST['location']);
            $manager_name = trim($_POST['manager_name']);
            $contact_phone = trim($_POST['contact_phone']);
            $email = trim($_POST['email']);
            $username = trim($_POST['username']);
            $password = trim($_POST['password']);
            
            if (empty($branch_name) || empty($branch_code) || empty($location) || empty($manager_name) || empty($username) || empty($password)) {
                $error_message = "All required fields must be filled!";
            } else {
                try {
                    // Check if branch code or username already exists
                    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM branches WHERE branch_code = ? OR username = ?");
                    $check_stmt->execute([$branch_code, $username]);
                    
                    if ($check_stmt->fetchColumn() > 0) {
                        $error_message = "Branch code or username already exists!";
                    } else {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        
                        $stmt = $conn->prepare("
                            INSERT INTO branches (branch_name, branch_code, location, manager_name, contact_phone, email, username, password_hash) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        
                        if ($stmt->execute([$branch_name, $branch_code, $location, $manager_name, $contact_phone, $email, $username, $password_hash])) {
                            $success_message = "Branch added successfully!";
                        } else {
                            $error_message = "Error adding branch.";
                        }
                    }
                } catch (PDOException $e) {
                    $error_message = "Database error: " . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] == 'toggle_status') {
            // Toggle branch status
            $branch_id = $_POST['branch_id'];
            $new_status = $_POST['current_status'] == 'active' ? 'inactive' : 'active';
            
            try {
                $stmt = $conn->prepare("UPDATE branches SET status = ? WHERE branch_id = ?");
                if ($stmt->execute([$new_status, $branch_id])) {
                    $success_message = "Branch status updated successfully!";
                } else {
                    $error_message = "Error updating branch status.";
                }
            } catch (PDOException $e) {
                $error_message = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch all branches
try {
    $branches_stmt = $conn->query("SELECT * FROM branches ORDER BY created_at DESC");
    $branches = $branches_stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching branches: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Branches - Admin Panel</title>
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
                <i class="fas fa-building mr-3"></i>Manage Branches
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
            
            <!-- Add Branch Form -->
            <div class="bg-gray-900 border border-gray-700 rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold text-white mb-4">
                    <i class="fas fa-plus mr-2"></i>Add New Branch
                </h2>
                
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <input type="hidden" name="action" value="add">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Branch Name *</label>
                        <input type="text" name="branch_name" required 
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Branch Code *</label>
                        <input type="text" name="branch_code" required 
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Location *</label>
                        <input type="text" name="location" required 
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Manager Name *</label>
                        <input type="text" name="manager_name" required 
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Contact Phone</label>
                        <input type="tel" name="contact_phone" 
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Email</label>
                        <input type="email" name="email" 
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Username *</label>
                        <input type="text" name="username" required 
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Password *</label>
                        <input type="password" name="password" required 
                               class="w-full px-3 py-2 bg-gray-800 border border-gray-600 rounded-md text-white focus:outline-none focus:border-white">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" 
                                class="w-full bg-white text-black hover:bg-gray-200 px-4 py-2 rounded-md font-medium transition-colors">
                            <i class="fas fa-plus mr-2"></i>Add Branch
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Branches List -->
            <div class="bg-gray-900 border border-gray-700 rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-700">
                    <h2 class="text-xl font-semibold text-white">
                        <i class="fas fa-list mr-2"></i>All Branches
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-700">
                        <thead class="bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Branch Info</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Manager</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Login Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-gray-900 divide-y divide-gray-700">
                            <?php if (empty($branches)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-400">No branches found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($branches as $branch): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div>
                                                <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($branch['branch_name']); ?></div>
                                                <div class="text-sm text-gray-400"><?php echo htmlspecialchars($branch['branch_code']); ?></div>
                                                <div class="text-sm text-gray-400"><?php echo htmlspecialchars($branch['location']); ?></div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white"><?php echo htmlspecialchars($branch['manager_name']); ?></div>
                                            <?php if ($branch['contact_phone']): ?>
                                                <div class="text-sm text-gray-400"><?php echo htmlspecialchars($branch['contact_phone']); ?></div>
                                            <?php endif; ?>
                                            <?php if ($branch['email']): ?>
                                                <div class="text-sm text-gray-400"><?php echo htmlspecialchars($branch['email']); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white">Username: <?php echo htmlspecialchars($branch['username']); ?></div>
                                            <div class="text-sm text-gray-400">Created: <?php echo date('M j, Y', strtotime($branch['created_at'])); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $branch['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($branch['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="branch_id" value="<?php echo $branch['branch_id']; ?>">
                                                <input type="hidden" name="current_status" value="<?php echo $branch['status']; ?>">
                                                <button type="submit" 
                                                        class="text-white hover:text-gray-300 mr-3"
                                                        onclick="return confirm('Are you sure you want to <?php echo $branch['status'] == 'active' ? 'deactivate' : 'activate'; ?> this branch?')">
                                                    <i class="fas fa-<?php echo $branch['status'] == 'active' ? 'ban' : 'check'; ?>"></i>
                                                    <?php echo $branch['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </form>
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
