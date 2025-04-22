<?php
// Database setup and enhancement script
// Run this script to fix database issues and add sample data

include 'config.php';

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // 1. Fix NULL theater_id in shows table
    $stmt = $conn->prepare("UPDATE shows SET theater_id = 1 WHERE theater_id IS NULL");
    $stmt->execute();
    $rowsUpdated = $stmt->rowCount();
    echo "Fixed NULL theater_id: $rowsUpdated rows updated<br>";
    
    // 2. Add NOT NULL constraint to theater_id in shows table
    $conn->exec("ALTER TABLE shows MODIFY COLUMN theater_id INT NOT NULL");
    echo "Added NOT NULL constraint to theater_id column<br>";
    
    // 3. Add indexes for better performance
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_theater_location ON theaters(location)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_shows_theater_movie ON shows(theater_id, movie_id)");
    $conn->exec("CREATE INDEX IF NOT EXISTS idx_shows_datetime ON shows(show_time)");
    echo "Added performance indexes<br>";
    
    // 4. Create a view for easier querying
    $conn->exec("
        CREATE OR REPLACE VIEW movie_showtimes AS
        SELECT 
            m.movie_id,
            m.title,
            m.genre,
            m.duration,
            m.release_date,
            m.description,
            m.rating,
            m.language,
            m.poster_url,
            m.trailer_url,
            t.theater_id,
            t.name AS theater_name,
            t.location AS theater_location,
            s.show_id,
            s.show_time,
            s.price
        FROM 
            movies m
        JOIN 
            shows s ON m.movie_id = s.movie_id
        JOIN 
            theaters t ON s.theater_id = t.theater_id
        ORDER BY 
            t.location, m.title, s.show_time
    ");
    echo "Created movie_showtimes view<br>";
    
    // 5. Add more theaters for testing
    $theaters = [
        ['name' => 'CineWorld', 'location' => 'Itahari', 'capacity' => 120],
        ['name' => 'MoviePlex', 'location' => 'Dharan', 'capacity' => 150],
        ['name' => 'FilmHouse', 'location' => 'Biratnagar', 'capacity' => 180]
    ];
    
    $theaterStmt = $conn->prepare("INSERT INTO theaters (name, location, capacity) VALUES (:name, :location, :capacity)");
    $theatersAdded = 0;
    
    foreach ($theaters as $theater) {
        // Check if theater already exists
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM theaters WHERE name = :name AND location = :location");
        $checkStmt->bindParam(':name', $theater['name']);
        $checkStmt->bindParam(':location', $theater['location']);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() == 0) {
            $theaterStmt->bindParam(':name', $theater['name']);
            $theaterStmt->bindParam(':location', $theater['location']);
            $theaterStmt->bindParam(':capacity', $theater['capacity']);
            $theaterStmt->execute();
            $theatersAdded++;
        }
    }
    echo "Added $theatersAdded new theaters<br>";
    
    // 6. Generate future showtimes for all movies in all theaters
    // Get all theaters
    $theaters = $conn->query("SELECT theater_id, name, location FROM theaters")->fetchAll();
    
    // Get all movies
    $movies = $conn->query("SELECT movie_id, title FROM movies")->fetchAll();
    
    // Generate showtimes for the next 7 days
    $showTimeStmt = $conn->prepare("
        INSERT INTO shows (movie_id, theater_id, show_time, price) 
        VALUES (:movie_id, :theater_id, :show_time, :price)
    ");
    
    $showtimesAdded = 0;
    $currentDate = date('Y-m-d');
    
    foreach ($movies as $movie) {
        foreach ($theaters as $theater) {
            // Add 3 showtimes per day for the next 7 days
            for ($day = 0; $day < 7; $day++) {
                $showDate = date('Y-m-d', strtotime("+$day days"));
                
                // Morning show (10:30 AM)
                $morningTime = "$showDate 10:30:00";
                
                // Afternoon show (2:45 PM)
                $afternoonTime = "$showDate 14:45:00";
                
                // Evening show (7:15 PM - prime time)
                $eveningTime = "$showDate 19:15:00";
                
                $showtimes = [$morningTime, $afternoonTime, $eveningTime];
                $prices = [120.00, 150.00, 180.00]; // Different prices for different times
                
                for ($i = 0; $i < count($showtimes); $i++) {
                    // Check if showtime already exists
                    $checkStmt = $conn->prepare("
                        SELECT COUNT(*) FROM shows 
                        WHERE movie_id = :movie_id 
                        AND theater_id = :theater_id 
                        AND show_time = :show_time
                    ");
                    $checkStmt->bindParam(':movie_id', $movie['movie_id']);
                    $checkStmt->bindParam(':theater_id', $theater['theater_id']);
                    $checkStmt->bindParam(':show_time', $showtimes[$i]);
                    $checkStmt->execute();
                    
                    if ($checkStmt->fetchColumn() == 0) {
                        $showTimeStmt->bindParam(':movie_id', $movie['movie_id']);
                        $showTimeStmt->bindParam(':theater_id', $theater['theater_id']);
                        $showTimeStmt->bindParam(':show_time', $showtimes[$i]);
                        $showTimeStmt->bindParam(':price', $prices[$i]);
                        $showTimeStmt->execute();
                        $showtimesAdded++;
                    }
                }
            }
        }
    }
    echo "Added $showtimesAdded new showtimes<br>";
    
    // Commit transaction
    $conn->commit();
    
    echo "<p>Database enhancement completed successfully!</p>";
    echo "<p><a href='../templates/index.php'>Return to homepage</a></p>";
    
    
} catch (PDOException $e) {
    // Rollback transaction on error
    $conn->rollBack();
    echo "Database enhancement failed: " . $e->getMessage();
}
?>

