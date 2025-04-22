<?php
include '../database/config.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    $_SESSION['alert'] = [
        'type' => 'warning',
        'message' => 'Please login to access support'
    ];
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$user_query = $conn->prepare("SELECT name, email FROM users WHERE user_id = ?");
$user_query->execute([$user_id]);
$user = $user_query->fetch(PDO::FETCH_ASSOC);

// Fetch support categories
$categories_query = $conn->query("SELECT * FROM support_categories ORDER BY name");
$categories = $categories_query->fetchAll(PDO::FETCH_ASSOC);

// Handle new ticket submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $category_id = $_POST['category_id'];
    $priority = $_POST['priority'];
    
    // Validate inputs
    if (empty($subject) || empty($message)) {
        $error_message = "Subject and message are required.";
    } else {
        try {
            $conn->beginTransaction();
            
            // Create ticket
            $ticket_query = $conn->prepare("
                INSERT INTO support_tickets (user_id, subject, category_id, priority, status) 
                VALUES (?, ?, ?, ?, 'Open')
            ");
            $ticket_query->execute([$user_id, $subject, $category_id, $priority]);
            $ticket_id = $conn->lastInsertId();
            
            // Add first message
            $message_query = $conn->prepare("
                INSERT INTO support_messages (ticket_id, sender_id, is_admin, message) 
                VALUES (?, ?, 0, ?)
            ");
            $message_query->execute([$ticket_id, $user_id, $message]);
            
            $conn->commit();
            
            $success_message = "Your support ticket has been created successfully. Ticket ID: #" . $ticket_id;
            
            // Redirect to prevent form resubmission
            header("Location: support.php?ticket_id=" . $ticket_id);
            exit();
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $error_message = "Error creating ticket: " . $e->getMessage();
        }
    }
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $ticket_id = $_POST['ticket_id'];
    $message = trim($_POST['message']);
    
    // Validate inputs
    if (empty($message)) {
        $error_message = "Message cannot be empty.";
    } else {
        try {
            // Verify ticket belongs to user
            $verify_query = $conn->prepare("SELECT ticket_id FROM support_tickets WHERE ticket_id = ? AND user_id = ?");
            $verify_query->execute([$ticket_id, $user_id]);
            
            if ($verify_query->rowCount() > 0) {
                // Add message
                $message_query = $conn->prepare("
                    INSERT INTO support_messages (ticket_id, sender_id, is_admin, message) 
                    VALUES (?, ?, 0, ?)
                ");
                $message_query->execute([$ticket_id, $user_id, $message]);
                
                // Update ticket status if it was closed
                $update_query = $conn->prepare("
                    UPDATE support_tickets 
                    SET status = CASE WHEN status = 'Closed' THEN 'Open' ELSE status END,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE ticket_id = ?
                ");
                $update_query->execute([$ticket_id]);
                
                $success_message = "Your message has been sent.";
                
                // Redirect to prevent form resubmission
                header("Location: support.php?ticket_id=" . $ticket_id);
                exit();
                
            } else {
                $error_message = "Invalid ticket.";
            }
            
        } catch (PDOException $e) {
            $error_message = "Error sending message: " . $e->getMessage();
        }
    }
}

// Fetch user's tickets
$tickets_query = $conn->prepare("
    SELECT t.*, c.name as category_name,
           (SELECT COUNT(*) FROM support_messages WHERE ticket_id = t.ticket_id AND is_admin = 1 AND is_read = 0) as unread_count
    FROM support_tickets t
    LEFT JOIN support_categories c ON t.category_id = c.category_id
    WHERE t.user_id = ?
    ORDER BY 
        CASE 
            WHEN t.status = 'Open' THEN 1
            WHEN t.status = 'In Progress' THEN 2
            WHEN t.status = 'Resolved' THEN 3
            WHEN t.status = 'Closed' THEN 4
        END,
        t.updated_at DESC
");
$tickets_query->execute([$user_id]);
$tickets = $tickets_query->fetchAll(PDO::FETCH_ASSOC);

// View specific ticket
$current_ticket = null;
$messages = [];

if (isset($_GET['ticket_id'])) {
    $ticket_id = $_GET['ticket_id'];
    
    // Verify ticket belongs to user
    $ticket_query = $conn->prepare("
        SELECT t.*, c.name as category_name
        FROM support_tickets t
        LEFT JOIN support_categories c ON t.category_id = c.category_id
        WHERE t.ticket_id = ? AND t.user_id = ?
    ");
    $ticket_query->execute([$ticket_id, $user_id]);
    $current_ticket = $ticket_query->fetch(PDO::FETCH_ASSOC);
    
    if ($current_ticket) {
        // Fetch messages
        $messages_query = $conn->prepare("
            SELECT m.*, u.name as sender_name
            FROM support_messages m
            JOIN users u ON m.sender_id = u.user_id
            WHERE m.ticket_id = ?
            ORDER BY m.created_at ASC
        ");
        $messages_query->execute([$ticket_id]);
        $messages = $messages_query->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark admin messages as read
        $mark_read_query = $conn->prepare("
            UPDATE support_messages
            SET is_read = 1
            WHERE ticket_id = ? AND is_admin = 1 AND is_read = 0
        ");
        $mark_read_query->execute([$ticket_id]);
    }
}

// Close ticket
if (isset($_GET['action']) && $_GET['action'] === 'close' && isset($_GET['ticket_id'])) {
    $ticket_id = $_GET['ticket_id'];
    
    // Verify ticket belongs to user
    $verify_query = $conn->prepare("SELECT ticket_id FROM support_tickets WHERE ticket_id = ? AND user_id = ?");
    $verify_query->execute([$ticket_id, $user_id]);
    
    if ($verify_query->rowCount() > 0) {
        $update_query = $conn->prepare("UPDATE support_tickets SET status = 'Closed' WHERE ticket_id = ?");
        $update_query->execute([$ticket_id]);
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Ticket has been closed.'
        ];
        
        header("Location: support.php?ticket_id=" . $ticket_id);
        exit();
    }
}

// Reopen ticket
if (isset($_GET['action']) && $_GET['action'] === 'reopen' && isset($_GET['ticket_id'])) {
    $ticket_id = $_GET['ticket_id'];
    
    // Verify ticket belongs to user
    $verify_query = $conn->prepare("SELECT ticket_id FROM support_tickets WHERE ticket_id = ? AND user_id = ?");
    $verify_query->execute([$ticket_id, $user_id]);
    
    if ($verify_query->rowCount() > 0) {
        $update_query = $conn->prepare("UPDATE support_tickets SET status = 'Open' WHERE ticket_id = ?");
        $update_query->execute([$ticket_id]);
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Ticket has been reopened.'
        ];
        
        header("Location: support.php?ticket_id=" . $ticket_id);
        exit();
    }
}

// Function to get status badge color
function getStatusColor($status) {
    switch ($status) {
        case 'Open':
            return 'bg-blue-900/50 text-blue-400';
        case 'In Progress':
            return 'bg-yellow-900/50 text-yellow-400';
        case 'Resolved':
            return 'bg-green-900/50 text-green-400';
        case 'Closed':
            return 'bg-gray-900/50 text-gray-400';
        default:
            return 'bg-gray-900/50 text-gray-400';
    }
}

// Function to get priority badge color
function getPriorityColor($priority) {
    switch ($priority) {
        case 'Low':
            return 'bg-gray-900/50 text-gray-400';
        case 'Medium':
            return 'bg-blue-900/50 text-blue-400';
        case 'High':
            return 'bg-yellow-900/50 text-yellow-400';
        case 'Urgent':
            return 'bg-red-900/50 text-red-400';
        default:
            return 'bg-gray-900/50 text-gray-400';
    }
}

// Function to format date
function formatDate($date) {
    $timestamp = strtotime($date);
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return "Just now";
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . " min" . ($mins > 1 ? "s" : "") . " ago";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . " hour" . ($hours > 1 ? "s" : "") . " ago";
    } elseif ($diff < 172800) {
        return "Yesterday at " . date('g:i A', $timestamp);
    } else {
        return date('M j, Y g:i A', $timestamp);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support - CineBook</title>
    <script src="../assets/js/talwind.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
        }
        
        .support-card {
            background: linear-gradient(to bottom right, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        
        .support-card:hover {
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 20px -8px rgba(0, 0, 0, 0.5);
        }
        
        .ticket-item {
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .ticket-item:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 12px -4px rgba(0, 0, 0, 0.3);
        }
        
        .ticket-item.active {
            border-color: rgba(239, 68, 68, 0.3);
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.05) 0%, rgba(220, 38, 38, 0.05) 100%);
        }
        
        .status-badge, .priority-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 9999px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .message-container {
            max-height: 500px;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
        }
        
        .message-container::-webkit-scrollbar {
            width: 6px;
        }
        
        .message-container::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .message-container::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }
        
        .message-bubble {
            max-width: 80%;
            border-radius: 1rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .message-bubble.user {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.2) 100%);
            border: 1px solid rgba(239, 68, 68, 0.2);
            margin-left: auto;
            border-bottom-right-radius: 0.25rem;
        }
        
        .message-bubble.admin {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(79, 70, 229, 0.2) 100%);
            border: 1px solid rgba(99, 102, 241, 0.2);
            margin-right: auto;
            border-bottom-left-radius: 0.25rem;
        }
        
        .message-time {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 0.25rem;
            text-align: right;
        }
        
        .message-form {
            background: rgba(30, 41, 59, 0.5);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 0 0 0.5rem 0.5rem;
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
        
        .btn {
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .btn-primary:hover {
            box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3);
        }
        
        .btn-secondary {
            background-color: rgba(71, 85, 105, 0.8);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: rgba(71, 85, 105, 1);
        }
        
        .alert {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 1000;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-width: 400px;
            transform: translateX(120%);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        
        .alert.show {
            transform: translateX(0);
            opacity: 1;
        }
        
        .alert-success {
            background-color: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #6ee7b7;
        }
        
        .alert-error {
            background-color: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #f87171;
        }
        
        .alert-warning {
            background-color: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fcd34d;
        }
        
        .unread-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background-color: #ef4444;
            color: white;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            min-width: 1.5rem;
            height: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 0.25rem;
        }
        
        /* Shimmer effect */
        @keyframes shimmer {
            0% {
                background-position: -100% 0;
            }
            100% {
                background-position: 100% 0;
            }
        }
        
        .shimmer {
            position: relative;
            overflow: hidden;
        }
        
        .shimmer::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.05) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
    </style>
</head>
<body>
    <!-- Include loader -->
    <?php include '../includes/loader.php'; ?>
    
    <!-- Include navigation -->
    <?php include '../includes/nav.php'; ?>
    
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['alert'])): ?>
    <div id="alert" class="alert <?php echo 'alert-' . $_SESSION['alert']['type']; ?>">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <?php if ($_SESSION['alert']['type'] === 'success'): ?>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <?php elseif ($_SESSION['alert']['type'] === 'error'): ?>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
                <?php else: ?>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <?php endif; ?>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium"><?php echo $_SESSION['alert']['message']; ?></p>
            </div>
            <div class="ml-auto pl-3">
                <button type="button" onclick="dismissAlert()" class="inline-flex rounded-md p-1.5 hover:text-white focus:outline-none">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div id="error-alert" class="alert alert-error show">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium"><?php echo $error_message; ?></p>
            </div>
            <div class="ml-auto pl-3">
                <button type="button" onclick="document.getElementById('error-alert').classList.remove('show')" class="inline-flex rounded-md p-1.5 hover:text-white focus:outline-none">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (isset($success_message)): ?>
    <div id="success-alert" class="alert alert-success show">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium"><?php echo $success_message; ?></p>
            </div>
            <div class="ml-auto pl-3">
                <button type="button" onclick="document.getElementById('success-alert').classList.remove('show')" class="inline-flex rounded-md p-1.5 hover:text-white focus:outline-none">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 pt-8 pb-24">
        <!-- Header section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold mb-2 text-white">Customer Support</h1>
                <p class="text-gray-400">Get help with your bookings and account</p>
            </div>
            
            <?php if (!isset($_GET['ticket_id']) && !isset($_GET['new'])): ?>
            <a href="support.php?new=1" class="mt-4 md:mt-0 inline-flex items-center px-4 py-2 rounded-lg text-white font-medium transition btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Create New Ticket
            </a>
            <?php endif; ?>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Tickets List -->
            <div class="lg:col-span-1">
                <div class="support-card rounded-xl overflow-hidden mb-6">
                    <div class="p-4 border-b border-gray-700 flex justify-between items-center">
                        <h2 class="text-lg font-semibold">Your Tickets</h2>
                        <a href="support.php?new=1" class="text-sm text-red-400 hover:text-red-300 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    </div>
                    
                    <div class="p-2 max-h-[600px] overflow-y-auto">
                        <?php if (empty($tickets)): ?>
                            <div class="text-center py-8 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <p class="text-sm">You don't have any support tickets yet.</p>
                                <a href="support.php?new=1" class="mt-3 inline-block text-sm text-red-400 hover:text-red-300">Create your first ticket</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <div class="ticket-item rounded-lg p-3 mb-2 relative <?php echo (isset($_GET['ticket_id']) && $_GET['ticket_id'] == $ticket['ticket_id']) ? 'active' : ''; ?>">
                                    <a href="support.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="block">
                                        <div class="flex justify-between items-start mb-2">
                                            <h3 class="font-medium text-white"><?php echo htmlspecialchars($ticket['subject']); ?></h3>
                                            <span class="status-badge <?php echo getStatusColor($ticket['status']); ?>"><?php echo $ticket['status']; ?></span>
                                        </div>
                                        
                                        <div class="flex items-center text-xs text-gray-400 mb-1">
                                            <span class="mr-2">ID: #<?php echo $ticket['ticket_id']; ?></span>
                                            <span class="mr-2">•</span>
                                            <span><?php echo $ticket['category_name'] ?? 'General'; ?></span>
                                        </div>
                                        
                                        <div class="flex justify-between items-center text-xs">
                                            <span class="priority-badge <?php echo getPriorityColor($ticket['priority']); ?>"><?php echo $ticket['priority']; ?></span>
                                            <span class="text-gray-500"><?php echo formatDate($ticket['updated_at']); ?></span>
                                        </div>
                                        
                                        <?php if ($ticket['unread_count'] > 0): ?>
                                            <div class="unread-badge"><?php echo $ticket['unread_count']; ?></div>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Help Section -->
                <div class="support-card rounded-xl overflow-hidden">
                    <div class="p-4 border-b border-gray-700">
                        <h2 class="text-lg font-semibold">Need Help?</h2>
                    </div>
                    
                    <div class="p-4">
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-400 mt-0.5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <h3 class="text-sm font-medium text-white mb-1">How long does it take to get a response?</h3>
                                    <p class="text-xs text-gray-400">We typically respond within 24 hours. For urgent issues, please mark your ticket as "Urgent".</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-400 mt-0.5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                                <div>
                                    <h3 class="text-sm font-medium text-white mb-1">Can I cancel my ticket?</h3>
                                    <p class="text-xs text-gray-400">Yes, you can close your ticket at any time if your issue has been resolved.</p>
                                </div>
                            </div>
                            
                            <div class="flex items-start">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-400 mt-0.5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                                </svg>
                                <div>
                                    <h3 class="text-sm font-medium text-white mb-1">Contact Us</h3>
                                    <p class="text-xs text-gray-400">For immediate assistance, call our support line at +977-01-4123456</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Area -->
            <div class="lg:col-span-2">
                <?php if (isset($_GET['new'])): ?>
                    <!-- New Ticket Form -->
                    <div class="support-card rounded-xl overflow-hidden">
                        <div class="p-4 border-b border-gray-700">
                            <h2 class="text-lg font-semibold">Create New Support Ticket</h2>
                        </div>
                        
                        <div class="p-6">
                            <form method="post" action="support.php">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Subject</label>
                                        <input type="text" name="subject" class="form-input w-full rounded-lg px-4 py-2.5" required placeholder="Brief description of your issue">
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-1">Category</label>
                                            <select name="category_id" class="form-input w-full rounded-lg px-4 py-2.5 bg-slate-800">
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-300 mb-1">Priority</label>
                                            <select name="priority" class="form-input w-full rounded-lg px-4 py-2.5 bg-slate-800">
                                                <option value="Low">Low</option>
                                                <option value="Medium" selected>Medium</option>
                                                <option value="High">High</option>
                                                <option value="Urgent">Urgent</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-1">Message</label>
                                        <textarea name="message" rows="6" class="form-input w-full rounded-lg px-4 py-2.5" required placeholder="Please describe your issue in detail"></textarea>
                                    </div>
                                    
                                    <div class="flex justify-end space-x-3 pt-4">
                                        <a href="support.php" class="px-4 py-2 rounded-lg text-gray-300 hover:text-white bg-gray-700 hover:bg-gray-600 transition">
                                            Cancel
                                        </a>
                                        <button type="submit" name="create_ticket" class="px-6 py-2 rounded-lg text-white font-medium btn-primary">
                                            Submit Ticket
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php elseif ($current_ticket): ?>
                    <!-- Ticket View -->
                    <div class="support-card rounded-xl overflow-hidden">
                        <div class="p-4 border-b border-gray-700 flex justify-between items-center">
                            <div>
                                <h2 class="text-lg font-semibold"><?php echo htmlspecialchars($current_ticket['subject']); ?></h2>
                                <div class="flex items-center text-xs text-gray-400 mt-1">
                                    <span class="mr-2">Ticket #<?php echo $current_ticket['ticket_id']; ?></span>
                                    <span class="mr-2">•</span>
                                    <span class="mr-2"><?php echo $current_ticket['category_name'] ?? 'General'; ?></span>
                                    <span class="mr-2">•</span>
                                    <span>Created <?php echo formatDate($current_ticket['created_at']); ?></span>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-2">
                                <span class="status-badge <?php echo getStatusColor($current_ticket['status']); ?>"><?php echo $current_ticket['status']; ?></span>
                                <span class="priority-badge <?php echo getPriorityColor($current_ticket['priority']); ?>"><?php echo $current_ticket['priority']; ?></span>
                            </div>
                        </div>
                        
                        <!-- Messages -->
                        <div class="message-container p-4">
                            <?php foreach ($messages as $message): ?>
                                <div class="message-bubble <?php echo $message['is_admin'] ? 'admin' : 'user'; ?>">
                                    <div class="flex items-center mb-1">
                                        <span class="font-medium text-sm"><?php echo htmlspecialchars($message['sender_name']); ?></span>
                                        <span class="text-xs text-gray-500 ml-2"><?php echo formatDate($message['created_at']); ?></span>
                                    </div>
                                    <div class="text-sm whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($message['message'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Reply Form -->
                        <?php if ($current_ticket['status'] !== 'Closed'): ?>
                            <div class="message-form">
                                <form method="post" action="support.php">
                                    <input type="hidden" name="ticket_id" value="<?php echo $current_ticket['ticket_id']; ?>">
                                    <div class="flex">
                                        <textarea name="message" rows="2" class="form-input w-full rounded-lg px-4 py-2.5 mr-2" required placeholder="Type your reply..."></textarea>
                                        <button type="submit" name="send_message" class="px-4 py-2 rounded-lg text-white font-medium btn-primary flex-shrink-0">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                            </svg>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="p-4 bg-gray-800/50 text-center">
                                <p class="text-gray-400 text-sm mb-2">This ticket is closed. You cannot send any more messages.</p>
                                <a href="support.php?action=reopen&ticket_id=<?php echo $current_ticket['ticket_id']; ?>" class="inline-block px-4 py-2 rounded-lg text-sm text-white bg-indigo-600 hover:bg-indigo-700 transition">
                                    Reopen Ticket
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Ticket Actions -->
                        <div class="p-4 border-t border-gray-700 flex justify-between">
                            <a href="support.php" class="text-gray-400 hover:text-white transition flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                                </svg>
                                Back to Tickets
                            </a>
                            
                            <?php if ($current_ticket['status'] !== 'Closed'): ?>
                                <a href="support.php?action=close&ticket_id=<?php echo $current_ticket['ticket_id']; ?>" class="text-red-400 hover:text-red-300 transition flex items-center" onclick="return confirm('Are you sure you want to close this ticket?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    Close Ticket
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Welcome / Help Screen -->
                    <div class="support-card rounded-xl overflow-hidden">
                        <div class="p-4 border-b border-gray-700">
                            <h2 class="text-lg font-semibold">Welcome to Customer Support</h2>
                        </div>
                        
                        <div class="p-6">
                            <div class="text-center mb-8">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-red-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <h3 class="text-xl font-bold mb-2">How can we help you?</h3>
                                <p class="text-gray-400 max-w-lg mx-auto">Our support team is here to assist you with any questions or issues you may have with your bookings or account.</p>
                            </div>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                                <div class="bg-slate-800/50 rounded-lg p-4 hover:bg-slate-800/80 transition">
                                    <h3 class="font-semibold mb-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z" />
                                            <path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z" />
                                        </svg>
                                        Booking Issues
                                    </h3>
                                    <p class="text-sm text-gray-400">Problems with booking tickets, seat selection, or payment processing.</p>
                                </div>
                                
                                <div class="bg-slate-800/50 rounded-lg p-4 hover:bg-slate-800/80 transition">
                                    <h3 class="font-semibold mb-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
                                        </svg>
                                        Account Issues
                                    </h3>
                                    <p class="text-sm text-gray-400">Help with login problems, account recovery, or profile updates.</p>
                                </div>
                                
                                <div class="bg-slate-800/50 rounded-lg p-4 hover:bg-slate-800/80 transition">
                                    <h3 class="font-semibold mb-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5 2a2 2 0 00-2 2v14l3.5-2 3.5 2 3.5-2 3.5 2V4a2 2 0 00-2-2H5zm4.707 3.707a1 1 0 00-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L8.414 9H10a3 3 0 013 3v1a1 1 0 102 0v-1a5 5 0 00-5-5H8.414l1.293-1.293z" clip-rule="evenodd" />
                                        </svg>
                                        Refunds & Cancellations
                                    </h3>
                                    <p class="text-sm text-gray-400">Questions about refund policies or help with cancelling bookings.</p>
                                </div>
                                
                                <div class="bg-slate-800/50 rounded-lg p-4 hover:bg-slate-800/80 transition">
                                    <h3 class="font-semibold mb-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                        </svg>
                                        General Inquiries
                                    </h3>
                                    <p class="text-sm text-gray-400">Any other questions about our services or website.</p>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <a href="support.php?new=1" class="inline-flex items-center px-6 py-3 rounded-lg text-white font-medium transition btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                    </svg>
                                    Create New Support Ticket
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Include footer -->
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Show alert on page load
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.getElementById('alert');
            if (alert) {
                setTimeout(() => {
                    alert.classList.add('show');
                }, 100);
                
                setTimeout(() => {
                    dismissAlert();
                }, 5000);
            }
            
            // Scroll to bottom of message container
            const messageContainer = document.querySelector('.message-container');
            if (messageContainer) {
                messageContainer.scrollTop = messageContainer.scrollHeight;
            }
        });
        
        function dismissAlert() {
            const alert = document.getElementById('alert');
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => {
                    alert.remove();
                }, 500);
            }
        }
    </script>
</body>
</html>
