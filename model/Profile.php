<?php
include '../database/config.php';
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details
$user_query = $conn->prepare("
    SELECT name, email, phone, created_at
    FROM users
    WHERE user_id = ?
");
$user_query->execute([$user_id]);
$user = $user_query->fetch(PDO::FETCH_ASSOC);

// Handle profile update
$success_message = '';
$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($name) || empty($email)) {
        $error_message = "Name and email are required fields.";
    } else {
        // Start transaction
        $conn->beginTransaction();
        try {
            // Check if email already exists for another user
            $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $check_email->execute([$email, $user_id]);
            if ($check_email->rowCount() > 0) {
                $error_message = "Email already in use by another account.";
            } else {
                // If changing password
                if (!empty($current_password)) {
                    // Verify current password
                    $verify_password = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
                    $verify_password->execute([$user_id]);
                    $stored_hash = $verify_password->fetchColumn();
                    
                    if (!password_verify($current_password, $stored_hash)) {
                        $error_message = "Current password is incorrect.";
                    } elseif (empty($new_password) || empty($confirm_password)) {
                        $error_message = "New password and confirmation are required.";
                    } elseif ($new_password !== $confirm_password) {
                        $error_message = "New passwords do not match.";
                    } else {
                        // Update profile with new password
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_user = $conn->prepare("
                            UPDATE users 
                            SET name = ?, email = ?, phone = ?, password_hash = ? 
                            WHERE user_id = ?
                        ");
                        $update_user->execute([$name, $email, $phone, $new_hash, $user_id]);
                        $success_message = "Profile updated successfully with new password.";
                    }
                } else {
                    // Update profile without changing password
                    $update_user = $conn->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, phone = ? 
                        WHERE user_id = ?
                    ");
                    $update_user->execute([$name, $email, $phone, $user_id]);
                    $success_message = "Profile updated successfully.";
                }
            }
            
            if (empty($error_message)) {
                $conn->commit();
                // Update session data
                $_SESSION['name'] = $name;
                // Refresh user data
                $user['name'] = $name;
                $user['email'] = $email;
                $user['phone'] = $phone;
            } else {
                $conn->rollBack();
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Get booking statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        SUM(total_price) as total_spent,
        COUNT(DISTINCT show_id) as unique_shows
    FROM bookings
    WHERE user_id = ?
");
$stats_query->execute([$user_id]);
$stats = $stats_query->fetch(PDO::FETCH_ASSOC);

// Get recent bookings
$recent_bookings_query = $conn->prepare("
    SELECT 
        b.booking_id,
        b.created_at,
        b.total_price,
        m.title as movie_title,
        m.poster_url,
        s.show_time,
        t.name as theater_name
    FROM 
        bookings b
    JOIN 
        shows s ON b.show_id = s.show_id
    JOIN 
        movies m ON s.movie_id = m.movie_id
    JOIN 
        theaters t ON s.theater_id = t.theater_id
    WHERE 
        b.user_id = ?
    ORDER BY 
        b.created_at DESC
    LIMIT 3
");
$recent_bookings_query->execute([$user_id]);
$recent_bookings = $recent_bookings_query->fetchAll(PDO::FETCH_ASSOC);
?>
