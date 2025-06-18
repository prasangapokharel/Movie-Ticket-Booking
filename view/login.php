<?php 
include '../model/Login.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CineBook</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="../assets/js/talwind.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .login-container {
            background: linear-gradient(to bottom right, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .form-input {
            background-color: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            background-color: rgba(30, 41, 59, 0.9);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25);
        }
        
        .login-button {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            transition: all 0.3s ease;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(182, 70, 70, 0.3);
        }

        

    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['alert'])): ?>
    <div class="fixed top-20 right-4 z-50 max-w-sm transition-all duration-500 ease-in-out transform" id="alertMessage">
        <div class="<?php echo $_SESSION['alert']['type'] === 'success' ? 'bg-green-900/80 text-green-200 border-green-800/50' : 'bg-yellow-900/80 text-yellow-200 border-yellow-800/50'; ?> backdrop-blur-md px-4 py-3 rounded-lg shadow-lg border">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <?php if ($_SESSION['alert']['type'] === 'success'): ?>
                    <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <?php else: ?>
                    <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <?php endif; ?>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo $_SESSION['alert']['message']; ?></p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" onclick="document.getElementById('alertMessage').remove()" class="inline-flex rounded-md p-1.5 <?php echo $_SESSION['alert']['type'] === 'success' ? 'text-green-300 hover:text-green-100' : 'text-yellow-300 hover:text-yellow-100'; ?> focus:outline-none">
                            <span class="sr-only">Dismiss</span>
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>
    
    <div class="flex-1 flex items-center justify-center px-4 py-12">
        <div class="login-container rounded-xl shadow-xl w-full max-w-md p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-2">Welcome Back</h1>
                <p class="text-gray-400">Sign in to continue to CineBook</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="bg-red-900/50 text-red-200 px-4 py-3 rounded-lg mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input w-full rounded-lg px-4 py-3" required>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="password" class="block text-sm font-medium text-gray-300">Password</label>
                            <a href="#" class="text-sm text-indigo-400 hover:text-indigo-300">Forgot password?</a>
                        </div>
                        <input type="password" id="password" name="password" class="form-input w-full rounded-lg px-4 py-3" required>
                    </div>
                    
                    <div class="flex items-center">
                        <input id="remember_me" name="remember_me" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-600 rounded bg-gray-700">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-300">Remember me</label>
                    </div>
                    
                    <button type="submit" class="login-button w-full py-3 rounded-lg text-white font-medium">
                        Sign In
                    </button>
                </div>
            </form>
            
            <div class="mt-8 text-center">
                <p class="text-gray-400">Don't have an account? <a href="register.php" class="text-indigo-400 hover:text-indigo-300 font-medium">Sign up</a></p>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alert = document.getElementById('alertMessage');
            if (alert) {
                alert.classList.add('opacity-0', 'translate-x-full');
                setTimeout(() => {
                    alert.remove();
                }, 500);
            }
        }, 5000);
    </script>
</body>
</html>
