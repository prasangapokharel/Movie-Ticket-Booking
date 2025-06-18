<?php
include '../database/config.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize filters
$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
$theater_id = isset($_GET['theater_id']) ? (int)$_GET['theater_id'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-7 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$status = isset($_GET['status']) ? $_GET['status'] : '';

try {
    // Fetch movies for filter
    $movies_stmt = $conn->prepare("SELECT movie_id, title FROM movies ORDER BY title");
    $movies_stmt->execute();
    $movies = $movies_stmt->fetchAll();
    
    // Fetch theaters for filter
    $theaters_stmt = $conn->prepare("SELECT theater_id, name, location FROM theaters ORDER BY name");
    $theaters_stmt->execute();
    $theaters = $theaters_stmt->fetchAll();
    
    // Check if screen column exists in shows table
    $column_check = $conn->query("SHOW COLUMNS FROM shows LIKE 'screen'");
    $screen_column_exists = $column_check->rowCount() > 0;
    
    // Build query for bookings
    $query = "
        SELECT 
            b.booking_id,
            b.user_id,
            b.total_price,
            b.booking_status,
            b.created_at,
            u.name AS user_name,
            u.email AS user_email,
            u.phone AS user_phone,
            m.title AS movie_title,
            t.name AS theater_name,
            t.location AS theater_location,
            s.show_time";
    
    // Only include screen column if it exists
    if ($screen_column_exists) {
        $query .= ", s.screen";
    }
    
    $query .= ", (SELECT GROUP_CONCAT(seat_number) FROM seats WHERE booking_id = b.booking_id) AS seats
        FROM 
            bookings b
        JOIN 
            users u ON b.user_id = u.user_id
        JOIN 
            shows s ON b.show_id = s.show_id
        JOIN 
            movies m ON s.movie_id = m.movie_id
        JOIN 
            theaters t ON s.theater_id = t.theater_id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Add filters to query
    if ($movie_id) {
        $query .= " AND s.movie_id = ?";
        $params[] = $movie_id;
    }
    
    if ($theater_id) {
        $query .= " AND s.theater_id = ?";
        $params[] = $theater_id;
    }
    
    if ($date_from) {
        $query .= " AND DATE(s.show_time) >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $query .= " AND DATE(s.show_time) <= ?";
        $params[] = $date_to;
    }
    
    if ($status) {
        $query .= " AND b.booking_status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY s.show_time DESC, b.created_at DESC";
    
    // Execute query
    $bookings_stmt = $conn->prepare($query);
    $bookings_stmt->execute($params);
    $bookings = $bookings_stmt->fetchAll();
    
    // Get summary statistics
    $stats_query = "
        SELECT 
            COUNT(*) AS total_bookings,
            COALESCE(SUM(total_price), 0) AS total_revenue,
            COUNT(DISTINCT user_id) AS unique_customers,
            (SELECT COUNT(*) FROM seats WHERE booking_id IN (SELECT booking_id FROM bookings WHERE 1=1
    ";
    
    $stats_params = [];
    
    // Add the same filters to stats query
    if ($movie_id) {
        $stats_query .= " AND show_id IN (SELECT show_id FROM shows WHERE movie_id = ?)";
        $stats_params[] = $movie_id;
    }
    
    if ($theater_id) {
        $stats_query .= " AND show_id IN (SELECT show_id FROM shows WHERE theater_id = ?)";
        $stats_params[] = $theater_id;
    }
    
    if ($date_from) {
        $stats_query .= " AND show_id IN (SELECT show_id FROM shows WHERE DATE(show_time) >= ?)";
        $stats_params[] = $date_from;
    }
    
    if ($date_to) {
        $stats_query .= " AND show_id IN (SELECT show_id FROM shows WHERE DATE(show_time) <= ?)";
        $stats_params[] = $date_to;
    }
    
    if ($status) {
        $stats_query .= " AND booking_status = ?";
        $stats_params[] = $status;
    }
    
    $stats_query .= ")) AS total_seats FROM bookings WHERE 1=1";
    
    // Add the same filters to main stats query
    if ($movie_id) {
        $stats_query .= " AND show_id IN (SELECT show_id FROM shows WHERE movie_id = ?)";
        $stats_params[] = $movie_id;
    }
    
    if ($theater_id) {
        $stats_query .= " AND show_id IN (SELECT show_id FROM shows WHERE theater_id = ?)";
        $stats_params[] = $theater_id;
    }
    
    if ($date_from) {
        $stats_query .= " AND show_id IN (SELECT show_id FROM shows WHERE DATE(show_time) >= ?)";
        $stats_params[] = $date_from;
    }
    
    if ($date_to) {
        $stats_query .= " AND show_id IN (SELECT show_id FROM shows WHERE DATE(show_time) <= ?)";
        $stats_params[] = $date_to;
    }
    
    if ($status) {
        $stats_query .= " AND booking_status = ?";
        $stats_params[] = $status;
    }
    
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->execute($stats_params);
    $stats = $stats_stmt->fetch();
    
    // Initialize stats with default values if no results
    if (!$stats) {
        $stats = [
            'total_bookings' => 0,
            'total_revenue' => 0,
            'unique_customers' => 0,
            'total_seats' => 0
        ];
    }
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    // Initialize empty arrays to prevent undefined variable errors
    $bookings = [];
    $movies = [];
    $theaters = [];
    $stats = [
        'total_bookings' => 0,
        'total_revenue' => 0,
        'unique_customers' => 0,
        'total_seats' => 0
    ];
}
?>
