<?php
include '../database/config.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

echo "<h1>Admin Dashboard</h1>";
echo "<a href='add_movie.php'>Add Movie</a> | ";
echo "<a href='manage_shows.php'>Manage Shows</a> | ";
echo "<a href='view_bookings.php'>View Bookings</a> | ";
echo "<a href='logout.php'>Logout</a>";
?>
