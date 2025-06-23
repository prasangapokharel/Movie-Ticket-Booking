<?php
session_start();
include '../database/config.php';

// Debug mode - set to false in production
$debug = false;
$debugInfo = [];

class MovieService {
    private $conn;
    private $debug;
    private $debugInfo;
    
    public function __construct($conn, $debug = false) {
        $this->conn = $conn;
        $this->debug = $debug;
        $this->debugInfo = [];
    }
    
    public function getDebugInfo() {
        return $this->debugInfo;
    }
    
    // Fetch locations from branches
    public function fetchLocations() {
        try {
            $stmt = $this->conn->query("
                SELECT DISTINCT b.location 
                FROM branches b 
                WHERE b.status = 'active' 
                ORDER BY b.location
            ");
            $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if ($this->debug && empty($locations)) {
                $this->debugInfo['locations_error'] = "No branch locations found in database";
            }
            return $locations;
        } catch (PDOException $e) {
            if ($this->debug) {
                $this->debugInfo['locations_query_error'] = $e->getMessage();
            }
            return [];
        }
    }
    
    // Fetch movies by location (branch location)
    public function fetchMoviesByLocation($location) {
        $currentDateTime = date('Y-m-d H:i:s');
        
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    m.movie_id, m.title, m.genre, m.duration, m.release_date, 
                    m.description, m.rating, m.language, m.poster_url, m.trailer_url,
                    m.status, t.theater_id, t.name AS theater_name, 
                    b.branch_name, b.location AS branch_location,
                    s.show_id, s.show_time, s.price 
                FROM movies m
                INNER JOIN shows s ON m.movie_id = s.movie_id
                INNER JOIN theaters t ON s.theater_id = t.theater_id
                INNER JOIN branches b ON t.branch_id = b.branch_id
                WHERE b.location = :location 
                AND s.show_time >= :currentDateTime
                AND m.status = 'active'
                AND b.status = 'active'
                ORDER BY m.title ASC, s.show_time ASC
            ");
            
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':currentDateTime', $currentDateTime);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($this->debug) {
                $this->debugInfo['query_results_count'] = count($results);
                $this->checkEmptyResults($results, $location, $currentDateTime);
            }
            
            return $this->processMovieResults($results);
            
        } catch (PDOException $e) {
            if ($this->debug) {
                $this->debugInfo['movies_query_error'] = $e->getMessage();
            }
            return [];
        }
    }
    
    private function checkEmptyResults($results, $location, $currentDateTime) {
        if (empty($results)) {
            // Check branches in location
            $branchCheck = $this->conn->prepare("SELECT * FROM branches WHERE location = :location AND status = 'active'");
            $branchCheck->bindParam(':location', $location);
            $branchCheck->execute();
            $branches = $branchCheck->fetchAll(PDO::FETCH_ASSOC);
            $this->debugInfo['branches_in_location'] = $branches;
            
            if (!empty($branches)) {
                $branchIds = array_column($branches, 'branch_id');
                $branchIdsStr = implode(',', $branchIds);
                
                // Check theaters in these branches
                $theaterCheck = $this->conn->query("
                    SELECT t.*, b.branch_name 
                    FROM theaters t
                    JOIN branches b ON t.branch_id = b.branch_id
                    WHERE t.branch_id IN ($branchIdsStr)
                ");
                $this->debugInfo['theaters_for_branches'] = $theaterCheck->fetchAll(PDO::FETCH_ASSOC);
                
                // Check shows for these theaters
                $showCheck = $this->conn->prepare("
                    SELECT s.*, m.title, t.name AS theater_name, b.branch_name
                    FROM shows s
                    JOIN movies m ON s.movie_id = m.movie_id
                    JOIN theaters t ON s.theater_id = t.theater_id
                    JOIN branches b ON t.branch_id = b.branch_id
                    WHERE t.branch_id IN ($branchIdsStr) 
                    AND s.show_time >= :currentDateTime
                    LIMIT 10
                ");
                $showCheck->bindParam(':currentDateTime', $currentDateTime);
                $showCheck->execute();
                $this->debugInfo['shows_for_branches'] = $showCheck->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
    
    private function processMovieResults($results) {
        $movieShows = [];
        
        foreach ($results as $row) {
            $movieId = $row['movie_id'];
            
            if (!isset($movieShows[$movieId])) {
                $movieShows[$movieId] = $this->createMovieArray($row);
            }
            
            $this->addTheaterAndShow($movieShows[$movieId], $row);
        }
        
        $movies = array_values($movieShows);
        usort($movies, function($a, $b) {
            return count($b['shows']) - count($a['shows']);
        });
        
        return $movies;
    }
    
    private function createMovieArray($row) {
        return [
            'movie_id' => $row['movie_id'],
            'title' => $row['title'],
            'genre' => $row['genre'],
            'duration' => $row['duration'],
            'release_date' => $row['release_date'],
            'description' => $row['description'],
            'rating' => $row['rating'],
            'language' => $row['language'],
            'poster_url' => $row['poster_url'],
            'trailer_url' => $row['trailer_url'],
            'status' => $row['status'] ?? 'Now Showing',
            'theaters' => [],
            'shows' => []
        ];
    }
    
    private function addTheaterAndShow(&$movie, $row) {
        $theaterId = $row['theater_id'];
        $theaterName = $row['theater_name'];
        
        if (!isset($movie['theaters'][$theaterId])) {
            $movie['theaters'][$theaterId] = $theaterName;
        }
        
        $movie['shows'][] = [
            'show_id' => $row['show_id'],
            'theater_id' => $theaterId,
            'theater_name' => $theaterName,
            'branch_name' => $row['branch_name'],
            'show_time' => $row['show_time'],
            'price' => $row['price']
        ];
    }
    
    // Fetch coming soon movies
    public function fetchComingSoonMovies() {
        try {
            $stmt = $this->conn->prepare("
                SELECT movie_id, title, genre, duration, release_date, 
                       description, rating, language, poster_url, trailer_url, status
                FROM movies
                WHERE status = 'Coming Soon'
                ORDER BY release_date ASC
                LIMIT 6
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if ($this->debug) {
                $this->debugInfo['coming_soon_query_error'] = $e->getMessage();
            }
            return [];
        }
    }
    
    // Check system status
    public function checkSystemStatus() {
        try {
            $stmt = $this->conn->query("
                SELECT COUNT(*) FROM shows s
                JOIN theaters t ON s.theater_id = t.theater_id
                JOIN branches b ON t.branch_id = b.branch_id
                WHERE s.show_time >= NOW() AND b.status = 'active'
            ");
            $futureShowsCount = $stmt->fetchColumn();
            return $futureShowsCount < 5;
        } catch (PDOException $e) {
            if ($this->debug) {
                $this->debugInfo['setup_check_error'] = $e->getMessage();
            }
            return true;
        }
    }
}

// Initialize service
$movieService = new MovieService($conn, $debug);

// Fetch available locations
$locations = $movieService->fetchLocations();

// Handle location selection
if (isset($_POST['select_location'])) {
    $_SESSION['location'] = $_POST['location'];
    header('Location: index.php');
    exit;
}

// Initialize variables
$movies = [];
$comingSoonMovies = [];
$needsSetup = false;

// Fetch data if location is set
if (isset($_SESSION['location'])) {
    $location = $_SESSION['location'];
    
    // Fetch movies and coming soon movies
    $movies = $movieService->fetchMoviesByLocation($location);
    $comingSoonMovies = $movieService->fetchComingSoonMovies();
    
    // Check system status
    $needsSetup = $movieService->checkSystemStatus();
}

// Get debug info if needed
if ($debug) {
    $debugInfo = $movieService->getDebugInfo();
}
?>
