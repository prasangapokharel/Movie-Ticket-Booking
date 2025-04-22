<?php
include '../database/config.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the name from the form
    $username = $_POST['name'];  // Changed from 'username' to 'name'

    $defaultUsername = 'Admin';  // Default username
    $defaultPassword = '123';    // Default password

    // Check if the provided username matches the default username
    if ($username === $defaultUsername) {
        $_SESSION['admin_id'] = 1; // Assuming 'admin_id' is 1 for the first admin user
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Invalid username!";
    }
}
?>

<form method="POST">
    <input type="text" name="name" placeholder="Admin Username" required>  <!-- Name input field -->
    <button type="submit">Login</button>
</form>
