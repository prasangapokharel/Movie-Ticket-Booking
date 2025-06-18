<?php 
include '../model/Register.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - CineBook</title>
    <script src="../assets/js/talwind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
        }
        .form-card {
            background: linear-gradient(to bottom right, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .input-field {
            background-color: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(100, 116, 139, 0.5);
            color: #f1f5f9;
        }
        .input-field:focus {
            border-color: rgba(99, 102, 241, 0.8);
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
        }
        .submit-button {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        .submit-button:hover {
            background: linear-gradient(135deg,rgb(174, 71, 71) 0%,rgb(106, 17, 17) 100%);
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <?php include '../includes/nav.php'; ?>
    
    <div class="flex-grow flex items-center justify-center px-4 py-12">
        <div class="form-card w-full max-w-md rounded-xl shadow-2xl overflow-hidden">
            <div class="p-8">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold mb-2">Create an Account</h1>
                    <p class="text-gray-400">Join CineBook to book movie tickets</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                <div class="bg-red-900/50 text-red-200 px-4 py-3 rounded-lg mb-6">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success_message)): ?>
                <div class="bg-green-900/50 text-green-200 px-4 py-3 rounded-lg mb-6">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                    <p class="mt-2">
                        <a href="login.php" class="text-green-300 underline">Click here to login</a>
                    </p>
                </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-300 mb-1">Full Name</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                            class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none transition-colors" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-300 mb-1">Email Address</label>
                        <input 
                            type="email" 
                            name="email" 
                            id="email" 
                            value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                            class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none transition-colors" 
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-300 mb-1">Phone Number</label>
                        <input 
                            type="tel" 
                            name="phone" 
                            id="phone" 
                            value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>"
                            class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none transition-colors" 
                            placeholder="10-digit number"
                            pattern="[0-9]{10}"
                            required
                        >
                    </div>
                    
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-300 mb-1">Password</label>
                        <input 
                            type="password" 
                            name="password" 
                            id="password" 
                            class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none transition-colors" 
                            minlength="8"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters</p>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-300 mb-1">Confirm Password</label>
                        <input 
                            type="password" 
                            name="confirm_password" 
                            id="confirm_password" 
                            class="input-field w-full px-4 py-2.5 rounded-lg focus:outline-none transition-colors" 
                            required
                        >
                    </div>
                    
                    <div>
                        <button 
                            type="submit" 
                            class="submit-button w-full text-white font-medium py-2.5 px-4 rounded-lg transition-colors"
                        >
                            Register
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center text-sm">
                    <p class="text-gray-400">
                        Already have an account? 
                        <a href="login.php" class="text-indigo-400 hover:text-indigo-300">Login here</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Client-side validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            form.addEventListener('submit', function(event) {
                if (password.value !== confirmPassword.value) {
                    event.preventDefault();
                    alert('Passwords do not match!');
                }
            });
        });
    </script>
</body>
</html>