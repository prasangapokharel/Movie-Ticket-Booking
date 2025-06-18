<?php
include '../database/config.php';
session_start();

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Fetch all tickets with user details
$tickets_query = $conn->prepare("
    SELECT t.*, c.name as category_name, u.name as user_name, u.email as user_email,
           (SELECT COUNT(*) FROM support_messages WHERE ticket_id = t.ticket_id AND is_admin = 0 AND is_read = 0) as unread_count
    FROM support_tickets t
    LEFT JOIN support_categories c ON t.category_id = c.category_id
    LEFT JOIN users u ON t.user_id = u.user_id
    ORDER BY 
        CASE 
            WHEN t.status = 'Open' THEN 1
            WHEN t.status = 'In Progress' THEN 2
            WHEN t.status = 'Resolved' THEN 3
            WHEN t.status = 'Closed' THEN 4
        END,
        t.updated_at DESC
");
$tickets_query->execute();
$tickets = $tickets_query->fetchAll(PDO::FETCH_ASSOC);

// Handle status change
if (isset($_GET['action']) && isset($_GET['ticket_id'])) {
    $ticket_id = $_GET['ticket_id'];
    $action = $_GET['action'];
    
    $allowed_statuses = ['Open', 'In Progress', 'Resolved', 'Closed'];
    
    if (in_array($action, $allowed_statuses)) {
        $update_query = $conn->prepare("UPDATE support_tickets SET status = ? WHERE ticket_id = ?");
        $update_query->execute([$action, $ticket_id]);
        
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => "Ticket status updated to {$action}."
        ];
        
        header("Location: viewsupport.php?ticket_id=" . $ticket_id);
        exit();
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
            // Add message
            $message_query = $conn->prepare("
                INSERT INTO support_messages (ticket_id, sender_id, is_admin, message) 
                VALUES (?, ?, 1, ?)
            ");
            $message_query->execute([$ticket_id, $admin_id, $message]);
            
            // Update ticket status if it was closed
            $update_query = $conn->prepare("
                UPDATE support_tickets 
                SET status = CASE WHEN status = 'Closed' THEN 'In Progress' ELSE status END,
                    updated_at = CURRENT_TIMESTAMP
                WHERE ticket_id = ?
            ");
            $update_query->execute([$ticket_id]);
            
            $success_message = "Your response has been sent.";
            
            // Redirect to prevent form resubmission
            header("Location: viewsupport.php?ticket_id=" . $ticket_id);
            exit();
            
        } catch (PDOException $e) {
            $error_message = "Error sending message: " . $e->getMessage();
        }
    }
}

// View specific ticket
$current_ticket = null;
$messages = [];

if (isset($_GET['ticket_id'])) {
    $ticket_id = $_GET['ticket_id'];
    
    // Fetch ticket details
    $ticket_query = $conn->prepare("
        SELECT t.*, c.name as category_name, u.name as user_name, u.email as user_email
        FROM support_tickets t
        LEFT JOIN support_categories c ON t.category_id = c.category_id
        LEFT JOIN users u ON t.user_id = u.user_id
        WHERE t.ticket_id = ?
    ");
    $ticket_query->execute([$ticket_id]);
    $current_ticket = $ticket_query->fetch(PDO::FETCH_ASSOC);
    
    if ($current_ticket) {
        // Fetch messages
        $messages_query = $conn->prepare("
            SELECT m.*, 
                   CASE WHEN m.is_admin = 1 THEN a.name ELSE u.name END as sender_name
            FROM support_messages m
            LEFT JOIN users u ON m.sender_id = u.user_id AND m.is_admin = 0
            LEFT JOIN users a ON m.sender_id = a.user_id AND m.is_admin = 1
            WHERE m.ticket_id = ?
            ORDER BY m.created_at ASC
        ");
        $messages_query->execute([$ticket_id]);
        $messages = $messages_query->fetchAll(PDO::FETCH_ASSOC);
        
        // Mark user messages as read
        $mark_read_query = $conn->prepare("
            UPDATE support_messages
            SET is_read = 1
            WHERE ticket_id = ? AND is_admin = 0 AND is_read = 0
        ");
        $mark_read_query->execute([$ticket_id]);
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

// Get ticket counts by status
$status_counts = [
    'Open' => 0,
    'In Progress' => 0,
    'Resolved' => 0,
    'Closed' => 0
];

foreach ($tickets as $ticket) {
    if (isset($status_counts[$ticket['status']])) {
        $status_counts[$ticket['status']]++;
    }
}

$total_tickets = count($tickets);
$total_unread = array_sum(array_column($tickets, 'unread_count'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Tickets - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
        }
        
        .admin-card {
            background: linear-gradient(to bottom right, rgba(30, 41, 59, 0.7), rgba(15, 23, 42, 0.9));
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        
        .admin-card:hover {
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
        
        .message-bubble.admin {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.2) 100%);
            border: 1px solid rgba(239, 68, 68, 0.2);
            margin-left: auto;
            border-bottom-right-radius: 0.25rem;
        }
        
        .message-bubble.user {
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
        
        .filter-tabs {
            display: flex;
            overflow-x: auto;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
            padding-bottom: 5px;
        }
        
        .filter-tabs::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        .filter-tab {
            white-space: nowrap;
            transition: all 0.3s ease;
        }
        
        .filter-tab.active {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        
        .status-dropdown {
            position: relative;
        }
        
        .status-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            min-width: 160px;
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            z-index: 10;
        }
        
        .status-dropdown:hover .status-dropdown-content {
            display: block;
        }
        
        .status-option {
            padding: 0.5rem 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .status-option:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }
        
        .status-option:first-child {
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }
        
        .status-option:last-child {
            border-bottom-left-radius: 0.5rem;
            border-bottom-right-radius: 0.5rem;
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
    <!-- Include admin navigation -->
    <?php include '../includes/header.php'; ?>
    
    <!-- Alert Messages -->
    <?php if (isset($_SESSION['alert'])): ?>
    <div id="alert" class="alert <?php echo 'alert-' . $_SESSION['alert']['type']; ?>">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <?php if ($_SESSION['alert']['type'] === 'success'): ?>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <?php else: ?>
                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
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

    <div class="max-w-7xl mx-auto px-4 pt-8 pb-24">
        <!-- Header section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold mb-2 text-white">Support Tickets</h1>
                <p class="text-gray-400">Manage customer support tickets</p>
            </div>
            
            <div class="mt-4 md:mt-0 flex items-center space-x-4">
                <?php if ($total_unread > 0): ?>
                <div class="bg-red-900/30 text-red-400 px-3 py-1 rounded-full text-sm font-medium">
                    <?php echo $total_unread; ?> unread message<?php echo $total_unread !== 1 ? 's' : ''; ?>
                </div>
                <?php endif; ?>
                
                <a href="categories.php" class="inline-flex items-center px-4 py-2 rounded-lg text-white font-medium transition bg-slate-700 hover:bg-slate-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd" />
                        <path d="M15 7h1a2 2 0 012 2v5.5a1.5 1.5 0 01-3 0V7z" />
                    </svg>
                    Manage Categories
                </a>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
            <div class="admin-card rounded-xl p-4 flex flex-col items-center justify-center">
                <span class="text-sm text-slate-400 mb-1">Total Tickets</span>
                <span class="text-3xl font-bold text-white"><?php echo $total_tickets; ?></span>
            </div>
            
            <div class="admin-card rounded-xl p-4 flex flex-col items-center justify-center">
                <span class="text-sm text-slate-400 mb-1">Open</span>
                <span class="text-3xl font-bold text-blue-400"><?php echo $status_counts['Open']; ?></span>
            </div>
            
            <div class="admin-card rounded-xl p-4 flex flex-col items-center justify-center">
                <span class="text-sm text-slate-400 mb-1">In Progress</span>
                <span class="text-3xl font-bold text-yellow-400"><?php echo $status_counts['In Progress']; ?></span>
            </div>
            
            <div class="admin-card rounded-xl p-4 flex flex-col items-center justify-center">
                <span class="text-sm text-slate-400 mb-1">Resolved</span>
                <span class="text-3xl font-bold text-green-400"><?php echo $status_counts['Resolved']; ?></span>
            </div>
            
            <div class="admin-card rounded-xl p-4 flex flex-col items-center justify-center">
                <span class="text-sm text-slate-400 mb-1">Closed</span>
                <span class="text-3xl font-bold text-gray-400"><?php echo $status_counts['Closed']; ?></span>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Tickets List -->
            <div class="lg:col-span-1">
                <div class="admin-card rounded-xl overflow-hidden mb-6">
                    <div class="p-4 border-b border-gray-700 flex justify-between items-center">
                        <h2 class="text-lg font-semibold">All Tickets</h2>
                        
                        <div class="relative">
                            <button id="filterButton" class="text-sm text-gray-400 hover:text-white transition flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd" />
                                </svg>
                                Filter
                            </button>
                            
                            <div id="filterDropdown" class="hidden absolute right-0 mt-2 w-48 bg-slate-800 rounded-md shadow-lg z-10 py-1">
                                <div class="py-1">
                                    <a href="viewsupport.php" class="block px-4 py-2 text-sm text-gray-300 hover:bg-slate-700 hover:text-white">All Tickets</a>
                                    <a href="viewsupport.php?filter=Open" class="block px-4 py-2 text-sm text-gray-300 hover:bg-slate-700 hover:text-white">Open</a>
                                    <a href="viewsupport.php?filter=In Progress" class="block px-4 py-2 text-sm text-gray-300 hover:bg-slate-700 hover:text-white">In Progress</a>
                                    <a href="viewsupport.php?filter=Resolved" class="block px-4 py-2 text-sm text-gray-300 hover:bg-slate-700 hover:text-white">Resolved</a>
                                    <a href="viewsupport.php?filter=Closed" class="block px-4 py-2 text-sm text-gray-300 hover:bg-slate-700 hover:text-white">Closed</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-2 max-h-[600px] overflow-y-auto">
                        <?php if (empty($tickets)): ?>
                            <div class="text-center py-8 text-gray-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <p class="text-sm">No support tickets found.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                                <div class="ticket-item rounded-lg p-3 mb-2 relative <?php echo (isset($_GET['ticket_id']) && $_GET['ticket_id'] == $ticket['ticket_id']) ? 'active' : ''; ?>">
                                    <a href="viewsupport.php?ticket_id=<?php echo $ticket['ticket_id']; ?>" class="block">
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
                                        
                                        <div class="text-xs text-gray-400 mt-2 truncate">
                                            <span class="font-medium">User:</span> <?php echo htmlspecialchars($ticket['user_name']); ?>
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
            </div>
            
            <!-- Main Content Area -->
            <div class="lg:col-span-2">
                <?php if ($current_ticket): ?>
                    <!-- Ticket View -->
                    <div class="admin-card rounded-xl overflow-hidden">
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
                            
                            <div class="status-dropdown">
                                <button class="status-badge <?php echo getStatusColor($current_ticket['status']); ?> cursor-pointer">
                                    <?php echo $current_ticket['status']; ?> ▼
                                </button>
                                <div class="status-dropdown-content">
                                    <a href="viewsupport.php?action=Open&ticket_id=<?php echo $current_ticket['ticket_id']; ?>" class="status-option text-blue-400">Open</a>
                                    <a href="viewsupport.php?action=In Progress&ticket_id=<?php echo $current_ticket['ticket_id']; ?>" class="status-option text-yellow-400">In Progress</a>
                                    <a href="viewsupport.php?action=Resolved&ticket_id=<?php echo $current_ticket['ticket_id']; ?>" class="status-option text-green-400">Resolved</a>
                                    <a href="viewsupport.php?action=Closed&ticket_id=<?php echo $current_ticket['ticket_id']; ?>" class="status-option text-gray-400">Closed</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Info -->
                        <div class="p-4 bg-slate-800/50 border-b border-gray-700">
                            <div class="flex items-start">
                                <div class="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                                    <?php echo strtoupper(substr($current_ticket['user_name'] ?? 'U', 0, 1)); ?>
                                </div>
                                <div class="ml-3">
                                    <h3 class="font-medium"><?php echo htmlspecialchars($current_ticket['user_name']); ?></h3>
                                    <p class="text-sm text-gray-400"><?php echo htmlspecialchars($current_ticket['user_email']); ?></p>
                                </div>
                                <div class="ml-auto">
                                    <span class="priority-badge <?php echo getPriorityColor($current_ticket['priority']); ?>"><?php echo $current_ticket['priority']; ?></span>
                                </div>
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
                                <form method="post" action="viewsupport.php">
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
                                <p class="text-gray-400 text-sm mb-2">This ticket is closed.</p>
                                <a href="viewsupport.php?action=Open&ticket_id=<?php echo $current_ticket['ticket_id']; ?>" class="inline-block px-4 py-2 rounded-lg text-sm text-white bg-indigo-600 hover:bg-indigo-700 transition">
                                    Reopen Ticket
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Ticket Actions -->
                        <div class="p-4 border-t border-gray-700 flex justify-between">
                            <a href="viewsupport.php" class="text-gray-400 hover:text-white transition flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                                </svg>
                                Back to All Tickets
                            </a>
                            
                            <div class="flex space-x-2">
                                <a href="mailto:<?php echo $current_ticket['user_email']; ?>" class="text-indigo-400 hover:text-indigo-300 transition flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                    </svg>
                                    Email User
                                </a>
                                
                                <?php if ($current_ticket['status'] !== 'Closed'): ?>
                                    <a href="viewsupport.php?action=Closed&ticket_id=<?php echo $current_ticket['ticket_id']; ?>" class="text-red-400 hover:text-red-300 transition flex items-center" onclick="return confirm('Are you sure you want to close this ticket?')">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                        Close Ticket
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Welcome / Dashboard -->
                    <div class="admin-card rounded-xl overflow-hidden">
                        <div class="p-4 border-b border-gray-700">
                            <h2 class="text-lg font-semibold">Support Dashboard</h2>
                        </div>
                        
                        <div class="p-6">
                            <div class="text-center mb-8">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-red-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <h3 class="text-xl font-bold mb-2">Customer Support Management</h3>
                                <p class="text-gray-400 max-w-lg mx-auto">Select a ticket from the list to view details and respond to customer inquiries.</p>
                            </div>
                            
                            <?php if ($total_unread > 0): ?>
                                <div class="bg-red-900/20 border border-red-900/30 rounded-lg p-4 mb-6">
                                    <div class="flex items-start">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-400 mt-0.5 mr-3" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        <div>
                                            <h4 class="font-medium text-red-400">Attention Required</h4>
                                            <p class="text-sm text-gray-300 mt-1">You have <?php echo $total_unread; ?> unread message<?php echo $total_unread !== 1 ? 's' : ''; ?> from customers waiting for a response.</p>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                                <div class="bg-slate-800/50 rounded-lg p-4 hover:bg-slate-800/80 transition">
                                    <h3 class="font-semibold mb-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                        </svg>
                                        Support Guidelines
                                    </h3>
                                    <p class="text-sm text-gray-400">Respond to all tickets within 24 hours. Mark urgent tickets as "In Progress" immediately.</p>
                                </div>
                                
                                <div class="bg-slate-800/50 rounded-lg p-4 hover:bg-slate-800/80 transition">
                                    <h3 class="font-semibold mb-2 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H4a2 2 0 01-2-2V5zm3 1h6v4H5V6zm6 6H5v2h6v-2z" clip-rule="evenodd" />
                                            <path d="M15 7h1a2 2 0 012 2v5.5a1.5 1.5 0 01-3 0V7z" />
                                        </svg>
                                        Common Responses
                                    </h3>
                                    <p class="text-sm text-gray-400">Use the template responses for common issues to ensure consistency in communication.</p>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <a href="categories.php" class="inline-flex items-center px-6 py-3 rounded-lg text-white font-medium transition btn-primary">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                    </svg>
                                    Manage Support Categories
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
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
            
            // Filter dropdown
            const filterButton = document.getElementById('filterButton');
            const filterDropdown = document.getElementById('filterDropdown');
            
            if (filterButton && filterDropdown) {
                filterButton.addEventListener('click', function() {
                    filterDropdown.classList.toggle('hidden');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(event) {
                    if (!filterButton.contains(event.target) && !filterDropdown.contains(event.target)) {
                        filterDropdown.classList.add('hidden');
                    }
                });
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
