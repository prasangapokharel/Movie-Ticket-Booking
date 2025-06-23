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
    <style>
        body { background-color: #000000; color: #ffffff; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-white">
                <i class="fas fa-building text-black text-xl"></i>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                Branch Login
            </h2>
            <p class="mt-2 text-center text-sm text-gray-400">
                Enter your branch credentials to access the system
            </p>
        </div>
        
        <?php if (!empty($error_message)): ?>
        <div class="bg-red-900 border border-red-700 text-red-100 px-4 py-3 rounded">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>
        
        <form class="mt-8 space-y-6" method="POST">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="username" class="sr-only">Username</label>
                    <input id="username" name="username" type="text" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-700 placeholder-gray-500 text-white bg-gray-900 rounded-t-md focus:outline-none focus:ring-white focus:border-white focus:z-10 sm:text-sm" 
                           placeholder="Branch Username">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-700 placeholder-gray-500 text-white bg-gray-900 rounded-b-md focus:outline-none focus:ring-white focus:border-white focus:z-10 sm:text-sm" 
                           placeholder="Password">
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-black bg-white hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-white">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-lock text-black group-hover:text-gray-700"></i>
                    </span>
                    Sign in to Branch
                </button>
            </div>
            
            <div class="text-center">
                <a href="../admin/login.php" class="text-white hover:text-gray-300 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i>Back to Admin Login
                </a>
            </div>
        </form>
    </div>
</body>
</html>
