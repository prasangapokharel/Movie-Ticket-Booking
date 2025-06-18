<?php
include '../database/config.php';
session_start();

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the name from the form
    $username = trim($_POST['name']);
    $defaultUsername = 'Admin';
    $defaultPassword = '123';
    
    // Check if the provided username matches the default username
    if ($username === $defaultUsername) {
        $_SESSION['admin_id'] = 1;
        header("Location: dashboard.php");
        exit();
    } else {
        $error_message = "Invalid username! Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #000000;
            color: #ffffff;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background-color: #111111;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 400px;
            border: 1px solid #333333;
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h1 {
            color: #ffffff;
            font-size: 28px;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #888888;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #cccccc;
            font-size: 14px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background-color: #222222;
            border: 1px solid #444444;
            border-radius: 4px;
            color: #ffffff;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #666666;
            background-color: #2a2a2a;
            box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.1);
        }

        .form-control::placeholder {
            color: #666666;
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #ffffff;
            color: #000000;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            background-color: #f0f0f0;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.2);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .error-message {
            background-color: #2a1f1f;
            color: #ff6b6b;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #4a2c2c;
            font-size: 14px;
            text-align: center;
        }

        .divider {
            height: 1px;
            background-color: #333333;
            margin: 30px 0;
        }

        .footer-text {
            text-align: center;
            color: #666666;
            font-size: 12px;
            margin-top: 20px;
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
        }

        /* Loading animation for form submission */
        .btn-login:disabled {
            background-color: #888888;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Admin Login</h1>
            <p>Enter your credentials to access the dashboard</p>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label for="name">Username</label>
                <input 
                    type="text" 
                    id="name"
                    name="name" 
                    class="form-control"
                    placeholder="Enter admin username" 
                    required
                    autocomplete="username"
                >
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                Login
            </button>
        </form>

        <div class="divider"></div>
        
        <div class="footer-text">
            Secure Admin Access Only
        </div>
    </div>

    <script>
        // Simple form enhancement
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.textContent = 'Logging in...';
            btn.disabled = true;
        });

        // Auto-focus on username field
        document.getElementById('name').focus();
    </script>
</body>
</html>