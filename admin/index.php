<?php
include '../database/config.php';
session_start();

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    // Check for super admin
    if ($username === 'admin' && $password === '123') {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_type'] = 'super';
        header("Location: dashboard.php");
        exit();
    }
    
    // Check for branch admin
    try {
        $stmt = $conn->prepare("SELECT * FROM branches WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $branch = $stmt->fetch();
        
        if ($branch && password_verify($password, $branch['password_hash'])) {
            $_SESSION['branch_id'] = $branch['branch_id'];
            $_SESSION['admin_type'] = 'branch';
            header("Location: ../branch/dashboard.php");
            exit();
        }
    } catch (PDOException $e) {
        $error_message = "Database error occurred.";
    }
    
    $error_message = "Invalid username or password!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Movie Booking System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-in-out',
                        'slide-up': 'slideUp 0.6s ease-out',
                        'float': 'float 6s ease-in-out infinite',
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-900 via-purple-900 to-pink-900 flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0">
        <div class="absolute top-1/4 left-1/4 w-64 h-64 bg-gradient-to-r from-blue-400/10 to-purple-400/10 rounded-full blur-3xl animate-float"></div>
        <div class="absolute top-3/4 right-1/4 w-96 h-96 bg-gradient-to-r from-purple-400/10 to-pink-400/10 rounded-full blur-3xl animate-float" style="animation-delay: -3s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-80 h-80 bg-gradient-to-r from-indigo-400/5 to-blue-400/5 rounded-full blur-3xl animate-float" style="animation-delay: -1.5s;"></div>
    </div>
    
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%23ffffff" fill-opacity="0.03"%3E%3Cpath d="M20 20c0-5.5-4.5-10-10-10s-10 4.5-10 10 4.5 10 10 10 10-4.5 10-10zm10 0c0-5.5-4.5-10-10-10s-10 4.5-10 10 4.5 10 10 10 10-4.5 10-10z"/%3E%3C/g%3E%3C/svg%3E')] opacity-30"></div>
    
    <!-- Main Container -->
    <div class="relative w-full max-w-md z-10">
        <!-- Login Card -->
        <div class="bg-white/5 backdrop-blur-2xl border border-white/10 rounded-3xl shadow-2xl p-8 space-y-8 relative">
            <!-- Decorative Border Gradient -->
            <div class="absolute inset-0 rounded-3xl bg-gradient-to-r from-blue-500/20 via-purple-500/20 to-pink-500/20 blur-sm -z-10"></div>
            
            <!-- Header Section -->
            <div class="text-center space-y-6">
                <!-- Logo/Icon with Animation -->
                <div class="relative mx-auto w-20 h-20">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full animate-pulse"></div>
                    <div class="relative w-full h-full bg-gradient-to-r from-blue-600 to-purple-700 rounded-full flex items-center justify-center shadow-2xl">
                        <i class="fas fa-film text-white text-3xl"></i>
                    </div>
                </div>
                
                <!-- Title and Subtitle -->
                <div class="space-y-3">
                    <h1 class="text-4xl font-bold bg-gradient-to-r from-white via-blue-100 to-purple-100 bg-clip-text text-transparent">
                        Admin Portal
                    </h1>
                    <p class="text-gray-300 text-base leading-relaxed">
                        Movie Booking System Administration
                    </p>
                    <div class="w-16 h-1 bg-gradient-to-r from-blue-500 to-purple-500 rounded-full mx-auto"></div>
                </div>
            </div>
            
            <!-- Error Message -->
            <?php if (!empty($error_message)): ?>
            <div class="bg-red-500/10 backdrop-blur-sm border border-red-400/30 rounded-xl p-4 flex items-start space-x-3 animate-fade-in">
                <div class="flex-shrink-0 mt-0.5">
                    <div class="w-8 h-8 bg-red-500/20 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-400 text-sm"></i>
                    </div>
                </div>
                <div class="text-red-300 text-sm leading-relaxed">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <form method="POST" class="space-y-6">
                <div class="space-y-5">
                    <!-- Username Field -->
                    <div class="space-y-2">
                        <label for="username" class="block text-sm font-semibold text-gray-200">
                            Username
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400 group-focus-within:text-blue-400 transition-colors duration-200"></i>
                            </div>
                            <input 
                                id="username" 
                                name="username" 
                                type="text" 
                                required
                                class="w-full pl-12 pr-4 py-4 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 focus:bg-white/10 transition-all duration-300 backdrop-blur-sm"
                                placeholder="Enter your username"
                                autocomplete="username">
                        </div>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="space-y-2">
                        <label for="password" class="block text-sm font-semibold text-gray-200">
                            Password
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400 group-focus-within:text-blue-400 transition-colors duration-200"></i>
                            </div>
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                required
                                class="w-full pl-12 pr-4 py-4 bg-white/5 border border-white/10 rounded-xl text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 focus:bg-white/10 transition-all duration-300 backdrop-blur-sm"
                                placeholder="Enter your password"
                                autocomplete="current-password">
                        </div>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 hover:from-blue-700 hover:via-purple-700 hover:to-pink-700 text-white font-bold py-4 px-6 rounded-xl transition-all duration-300 transform hover:scale-[1.02] hover:shadow-2xl focus:outline-none focus:ring-2 focus:ring-purple-500/50 focus:ring-offset-2 focus:ring-offset-transparent relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-r from-white/0 via-white/5 to-white/0 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
                    <span class="relative flex items-center justify-center space-x-3">
                        <i class="fas fa-sign-in-alt text-lg"></i>
                        <span class="text-lg">Sign In</span>
                    </span>
                </button>
            </form>
            
            <!-- Credentials Info -->
            <div class="pt-6 border-t border-white/10">
                <div class="bg-gradient-to-r from-blue-500/10 to-purple-500/10 backdrop-blur-sm rounded-xl p-4 space-y-3">
                    <div class="flex items-center space-x-2 mb-3">
                        <i class="fas fa-info-circle text-blue-400"></i>
                        <span class="text-sm font-semibold text-gray-200">Login Credentials</span>
                    </div>
                    <div class="space-y-2 text-sm">
                        <!-- <div class="flex items-center justify-between p-2 bg-white/5 rounded-lg">
                            <span class="text-gray-300">Super Admin:</span>
                            <span class="text-blue-300 font-mono">admin / 123</span>
                        </div> -->
                        <div class="flex items-center justify-between p-2 bg-white/5 rounded-lg">
                            <span class="text-gray-300">Branch Admin:</span>
                            <span class="text-purple-300">Use branch credentials</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Floating Decorative Elements -->
        <div class="absolute -top-6 -right-6 w-32 h-32 bg-gradient-to-r from-blue-500/10 to-purple-500/10 rounded-full blur-2xl animate-float"></div>
        <div class="absolute -bottom-6 -left-6 w-40 h-40 bg-gradient-to-r from-purple-500/10 to-pink-500/10 rounded-full blur-2xl animate-float" style="animation-delay: -2s;"></div>
    </div>
    
    <!-- Additional Ambient Elements -->
    <div class="fixed top-10 left-10 w-3 h-3 bg-blue-400/60 rounded-full animate-pulse"></div>
    <div class="fixed top-20 right-20 w-2 h-2 bg-purple-400/60 rounded-full animate-pulse" style="animation-delay: 1s;"></div>
    <div class="fixed bottom-20 left-20 w-2.5 h-2.5 bg-pink-400/60 rounded-full animate-pulse" style="animation-delay: 0.5s;"></div>
    <div class="fixed bottom-10 right-10 w-2 h-2 bg-blue-300/60 rounded-full animate-pulse" style="animation-delay: 1.5s;"></div>
    
    <!-- Custom CSS for float animation -->
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-10px) rotate(1deg); }
            66% { transform: translateY(5px) rotate(-1deg); }
        }
    </style>
</body>
</html>
