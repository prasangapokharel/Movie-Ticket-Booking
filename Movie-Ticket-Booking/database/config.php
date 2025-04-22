<?php

$host = 'localhost';
$dbname = 'movie_booking'; // Updated to match the database name from SQL dump
$username = 'root'; // Default username for XAMPP
$password = ''; // Default empty password for XAMPP

try {
  $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
  // Set the PDO error mode to exception
  $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  //echo "Connected successfully";
} catch(PDOException $e) {
  echo "Connection failed: " . $e->getMessage();
}

// Check if booking_id column exists in seats table, add it if not
try {
  $check_column = $conn->query("SHOW COLUMNS FROM seats LIKE 'booking_id'");
  if ($check_column->rowCount() == 0) {
      $conn->exec("ALTER TABLE seats ADD COLUMN booking_id INT DEFAULT NULL");
  }
  
  // Check if created_at column exists in seats table, add it if not
  $check_created_at = $conn->query("SHOW COLUMNS FROM seats LIKE 'created_at'");
  if ($check_created_at->rowCount() == 0) {
      $conn->exec("ALTER TABLE seats ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
  }
  
  // Fix any empty status values in the seats table
  $conn->exec("UPDATE seats SET status = 'available' WHERE status = '' OR status IS NULL");
  
} catch (PDOException $e) {
  // Log the error instead of displaying it
  error_log("Database schema check error: " . $e->getMessage());
}
?>

