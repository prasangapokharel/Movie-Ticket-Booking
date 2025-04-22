<?php
include '../database/config.php';
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$show_id = $_GET['show_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_seat = $_POST['seat_number'];
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO Bookings (user_id, show_id, total_price, booking_status) VALUES (?, ?, ?, 'Confirmed')");
    if ($stmt->execute([$user_id, $show_id, 10])) {
        echo "Booking Successful for seat $selected_seat!";
    } else {
        echo "Error booking seat.";
    }
}
?>

<h1>Select a Seat</h1>
<form method="POST">
    <select name="seat_number">
        <option value="A1">A1</option>
        <option value="A2">A2</option>
        <option value="B1">B1</option>
        <option value="B2">B2</option>
    </select>
    <button type="submit">Book Seat</button>
</form>
