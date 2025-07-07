<?php
include '../database/config.php';
session_start();

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM branches WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $branch = $stmt->fetch();
        
        if ($branch && password_verify($password, $branch['password_hash'])) {
            $_SESSION['branch_id'] = $branch['branch_id'];
            $_SESSION['branch_name'] = $branch['branch_name'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error_message = "Invalid username or password!";
        }
    } catch (PDOException $e) {
        $error_message = "Database error occurred.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branch Login - Movie Booking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-900 via-black to-gray-800 flex items-center justify-center p-4">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%23ffffff" fill-opacity="0.02"%3E%3Ccircle cx="30" cy="30" r="2"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>
    
    <!-- Main Container -->
    <div class="relative w-full max-w-md">
        <!-- Login Card -->
        <div class="bg-gray-900/80 backdrop-blur-xl border border-gray-700/50 rounded-2xl shadow-2xl p-8 space-y-8">
            <!-- Header Section -->
            <div class="text-center space-y-4">
                <!-- Logo/Icon -->
                <div class="mx-auto w-16 h-16 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg">
                    <i class="fas fa-building text-white text-2xl"></i>
                </div>
                
                <!-- Title -->
                <div class="space-y-2">
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">
                        Branch Login
                    </h1>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        Enter your branch credentials to access the movie booking system
                    </p>
                </div>
            </div>
            
            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
            <div class="bg-red-500/10 border border-red-500/20 rounded-lg p-4 flex items-center space-x-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-red-400 text-lg"></i>
                </div>
                <div class="text-red-300 text-sm">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" class="space-y-6">
                <div class="space-y-4">
                    <!-- Username Field -->
                    <div class="space-y-2">
                        <label for="username" class="block text-sm font-medium text-gray-300">
                            Branch Username
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input 
                                id="username" 
                                name="username" 
                                type="text" 
                                required
                                class="w-full pl-10 pr-4 py-3 bg-gray-800/50 border border-gray-600/50 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-200"
                                placeholder="Enter your username"
                                autocomplete="username">
                        </div>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-medium text-gray-300">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                required
                                class="w-full pl-10 pr-4 py-3 bg-gray-800/50 border border-gray-600/50 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-200"
                                placeholder="Enter your password"
                                autocomplete="current-password">
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:ring-offset-2 focus:ring-offset-gray-900 shadow-lg">
                    <span class="flex items-center justify-center space-x-2">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Sign in to Branch</span>
                    </span>
                </button>
            </form>
            
            <!-- Footer Links -->
            <div class="pt-6 border-t border-gray-700/50">
                <div class="text-center">
                    <a 
                        href="../admin/login.php" 
                        class="inline-flex items-center space-x-2 text-gray-400 hover:text-white transition-colors duration-200 text-sm group">
                        <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform duration-200"></i>
                        <span>Back to Admin Login</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Decorative Elements -->
        <div class="absolute -top-4 -right-4 w-24 h-24 bg-gradient-to-r from-blue-500/20 to-purple-500/20 rounded-full blur-xl"></div>
        <div class="absolute -bottom-4 -left-4 w-32 h-32 bg-gradient-to-r from-purple-500/20 to-pink-500/20 rounded-full blur-xl"></div>
    </div>
    
    <!-- Additional Background Elements -->
    <div class="fixed top-10 left-10 w-2 h-2 bg-blue-400 rounded-full animate-pulse"></div>
    <div class="fixed top-20 right-20 w-1 h-1 bg-purple-400 rounded-full animate-pulse delay-1000"></div>
    <div class="fixed bottom-20 left-20 w-1.5 h-1.5 bg-pink-400 rounded-full animate-pulse delay-500"></div>
    <div class="fixed bottom-10 right-10 w-1 h-1 bg-blue-300 rounded-full animate-pulse delay-700"></div>
</body>
</html>
