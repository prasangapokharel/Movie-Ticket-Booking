<?php
$host = "localhost";
$username = "root";  // Change if needed
$password = "";  // Change if needed
$dbname = "movie_booking";

try {
    // Connect to MySQL
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create Database
    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "Database '$dbname' created successfully.<br>";

    // Connect to the newly created database
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // SQL queries to create tables
    $sql = "
    CREATE TABLE IF NOT EXISTS Users (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS Movies (
        movie_id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        genre VARCHAR(100),
        duration INT,
        release_date DATE,
        description TEXT,
        rating DECIMAL(3,1),
        language VARCHAR(50),
        poster_url VARCHAR(255),
        trailer_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS Theaters (
        theater_id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        location VARCHAR(255) NOT NULL,
        capacity INT NOT NULL
    );

    CREATE TABLE IF NOT EXISTS Shows (
        show_id INT PRIMARY KEY AUTO_INCREMENT,
        movie_id INT,
        theater_id INT,
        show_time DATETIME NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (movie_id) REFERENCES Movies(movie_id) ON DELETE CASCADE,
        FOREIGN KEY (theater_id) REFERENCES Theaters(theater_id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS Bookings (
        booking_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        show_id INT,
        total_price DECIMAL(10,2) NOT NULL,
        booking_status ENUM('Pending', 'Confirmed', 'Cancelled') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES Users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (show_id) REFERENCES Shows(show_id) ON DELETE CASCADE
    );
    ";

    // Execute queries
    $conn->exec($sql);
    echo "Tables created successfully.<br>";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
