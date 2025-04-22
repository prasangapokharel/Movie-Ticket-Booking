<?php
session_start();
include '../database/config.php';
 include '../includes/loader.php'; 
// Debug mode - set to false in production
$debug = false;
$debugInfo = [];

// Fetch available locations (theaters)
try {
    $stmt = $conn->query("SELECT DISTINCT location FROM theaters ORDER BY location");
    $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if ($debug && empty($locations)) {
        $debugInfo['locations_error'] = "No theater locations found in database";
    }
} catch (PDOException $e) {
    if ($debug) {
        $debugInfo['locations_query_error'] = $e->getMessage();
    }
    $locations = [];
}

// If location is selected, store it in session
if (isset($_POST['select_location'])) {
    $_SESSION['location'] = $_POST['location'];
    // Redirect to remove POST data
    header('Location: index.php');
    exit;
}

// Fetch movies and shows for the selected location
$movies = [];
if (isset($_SESSION['location'])) {
    $location = $_SESSION['location'];
    
    // Get current date and time in Y-m-d H:i:s format
    $currentDateTime = date('Y-m-d H:i:s');
    
    try {
        // Fetch movies with shows in the selected location
        $stmt = $conn->prepare("
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
                m.status,
                t.theater_id, 
                t.name AS theater_name, 
                t.location AS theater_location,
                s.show_id, 
                s.show_time, 
                s.price 
            FROM 
                movies m
            INNER JOIN 
                shows s ON m.movie_id = s.movie_id
            INNER JOIN 
                theaters t ON s.theater_id = t.theater_id
            WHERE 
                t.location = :location
                AND s.show_time >= :currentDateTime
            ORDER BY 
                m.title ASC, s.show_time ASC
        ");
        
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':currentDateTime', $currentDateTime);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($debug) {
            $debugInfo['query_results_count'] = count($results);
            
            if (empty($results)) {
                // Check if theaters exist for this location
                $theaterCheck = $conn->prepare("SELECT * FROM theaters WHERE location = :location");
                $theaterCheck->bindParam(':location', $location);
                $theaterCheck->execute();
                $theaters = $theaterCheck->fetchAll(PDO::FETCH_ASSOC);
                $debugInfo['theaters_in_location'] = $theaters;
                
                if (!empty($theaters)) {
                    // Check if shows exist for these theaters
                    $theaterIds = array_column($theaters, 'theater_id');
                    $theaterIdsStr = implode(',', $theaterIds);
                    $showCheck = $conn->query("
                        SELECT s.*, m.title, t.name AS theater_name 
                        FROM shows s
                        JOIN movies m ON s.movie_id = m.movie_id
                        JOIN theaters t ON s.theater_id = t.theater_id
                        WHERE s.theater_id IN ($theaterIdsStr) 
                        LIMIT 10
                    ");
                    $shows = $showCheck->fetchAll(PDO::FETCH_ASSOC);
                    $debugInfo['shows_for_theaters'] = $shows;
                    
                    // Check if any shows are in the future
                    $futureShowCheck = $conn->prepare("
                        SELECT COUNT(*) FROM shows 
                        WHERE theater_id IN ($theaterIdsStr) 
                        AND show_time >= :currentDateTime
                    ");
                    $futureShowCheck->bindParam(':currentDateTime', $currentDateTime);
                    $futureShowCheck->execute();
                    $futureShowCount = $futureShowCheck->fetchColumn();
                    $debugInfo['future_shows_count'] = $futureShowCount;
                }
            }
        }
        
        // Group shows by movie
        $movieShows = [];
        foreach ($results as $row) {
            $movieId = $row['movie_id'];
            if (!isset($movieShows[$movieId])) {
                $movieShows[$movieId] = [
                    'movie_id' => $movieId,
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
            
            // Add theater if not already in the list
            $theaterId = $row['theater_id'];
            $theaterName = $row['theater_name'];
            if (!isset($movieShows[$movieId]['theaters'][$theaterId])) {
                $movieShows[$movieId]['theaters'][$theaterId] = $theaterName;
            }
            
            // Add show
            $movieShows[$movieId]['shows'][] = [
                'show_id' => $row['show_id'],
                'theater_id' => $theaterId,
                'theater_name' => $theaterName,
                'show_time' => $row['show_time'],
                'price' => $row['price']
            ];
        }
        
        $movies = array_values($movieShows);
        
        // Sort movies by number of available showtimes (most showtimes first)
        usort($movies, function($a, $b) {
            return count($b['shows']) - count($a['shows']);
        });
        
    } catch (PDOException $e) {
        if ($debug) {
            $debugInfo['movies_query_error'] = $e->getMessage();
        }
    }
    
    // Fetch coming soon movies
    try {
        $comingSoonStmt = $conn->prepare("
            SELECT 
                movie_id, 
                title, 
                genre, 
                duration, 
                release_date, 
                description, 
                rating, 
                language, 
                poster_url, 
                trailer_url,
                status
            FROM 
                movies
            WHERE 
                status = 'Coming Soon'
            ORDER BY 
                release_date ASC
            LIMIT 6
        ");
        $comingSoonStmt->execute();
        $comingSoonMovies = $comingSoonStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if ($debug) {
            $debugInfo['coming_soon_query_error'] = $e->getMessage();
        }
        $comingSoonMovies = [];
    }
}

// Check if database needs setup
$needsSetup = false;
try {
    $checkStmt = $conn->query("
        SELECT COUNT(*) FROM shows s
        JOIN theaters t ON s.theater_id = t.theater_id
        WHERE s.show_time >= NOW()
    ");
    $futureShowsCount = $checkStmt->fetchColumn();
    
    if ($futureShowsCount < 5) {
        $needsSetup = true;
    }
} catch (PDOException $e) {
    $needsSetup = true;
    if ($debug) {
        $debugInfo['setup_check_error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CineBook - Movie Booking System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="../assets/js/talwind.js"></script>
    <script src="../assets/js/privacy.js"></script>

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #0f172a;
            color: #f1f5f9;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            letter-spacing: 0.015em;
        }
        
        .movie-card {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 600px; /* Ensure consistent height */
}

.movie-info {
    display: flex;
    flex-direction: column;
    flex: 1;
    padding: 1.25rem;
}

.movie-info-footer {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.book-button {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.book-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(220, 38, 38, 0.3);
}

.book-button:active {
    transform: translateY(0);
}

.trailer-button {
    background-color: rgba(55, 65, 81, 0.8);
    backdrop-filter: blur(4px);
    transition: all 0.3s ease;
}

.trailer-button:hover {
    background-color: rgba(75, 85, 99, 0.9);
    transform: translateY(-2px);
}

/* Ensure consistent spacing */
.movie-info > * {
    margin-bottom: 1rem;
}

.movie-info > *:last-child {
    margin-bottom: 0;
}

/* Responsive adjustments */
@media (max-width: 640px) {
    .movie-card {
        min-height: 550px;
    }
    
    .movie-info {
        padding: 1rem;
    }
    
    .movie-info-footer {
        padding-top: 0.75rem;
    }
}
        .video-container {
            position: relative;
            overflow: hidden;
            border-radius: 12px 12px 0 0;
        }
        
        .video-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0.7));
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        
        .video-container:hover .video-overlay {
            opacity: 1;
        }
        
        .play-button {
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(5px);
            border-radius: 50%;
            width: 70px;
            height: 70px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }
        
        .play-button:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: scale(1.1);
        }
        
        .genre-tag {
            background-color: rgba(99, 102, 241, 0.15);
            color: #c7d2fe;
            border: 1px solid rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
        }
        
        .genre-tag:hover {
            background-color: rgba(99, 102, 241, 0.25);
        }
        
        .book-button {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%);
            position: relative;
            overflow: hidden;
        }
        
        .book-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(185, 28, 28, 0.3);
        }
        
        .book-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 50%);
            transition: opacity 0.3s ease;
        }
        
        .book-button:hover::after {
            opacity: 0;
        }
        
        .modal {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .show-time-btn {
            transition: all 0.3s ease;
            background-color: rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.3);
        }
        
        .show-time-btn:hover {
            transform: scale(1.05);
            background-color: rgba(99, 102, 241, 0.25);
        }
        
        .show-time-btn.prime-time {
            background-color: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #fcd34d;
        }
        
        .show-time-btn.prime-time:hover {
            background-color: rgba(245, 158, 11, 0.25);
        }
        
        .hero-section {
            background-image: linear-gradient(to bottom, rgba(15, 23, 42, 0.7), rgba(15, 23, 42, 0.9)), url('../assets/image/image.png');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, rgba(99, 102, 241, 0.15), transparent 60%);
        }
        
        .location-modal {
            backdrop-filter: blur(15px);
            background-color: rgba(15, 23, 42, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .heading-gradient {
            background: linear-gradient(90deg, #f9fafb, #c7d2fe);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .search-input {
            backdrop-filter: blur(5px);
            background-color: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            background-color: rgba(30, 41, 59, 0.9);
            border-color: rgba(99, 102, 241, 0.5);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
        }
        
        .coming-soon-card {
            background-color: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .coming-soon-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .rating-star {
            filter: drop-shadow(0 0 2px rgba(250, 204, 21, 0.3));
        }
        
        .section-heading {
            position: relative;
            display: inline-block;
        }
        
        .section-heading::after {
            content: '';
            position: absolute;
            bottom: -5px;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #ef4444, #b91c1c);
            border-radius: 3px;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(90deg, #ef4444, #b91c1c);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(90deg, #ef4444, #b91c1c);
        }
        
        /* Image shimmer effect */
        .shimmer {
            position: relative;
            overflow: hidden;
        }
        
        .shimmer::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 50%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            to {
                left: 100%;
            }
        }
        
        /* Theater badge */
        .theater-badge {
            background-color: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            font-size: 0.7rem;
            padding: 0.1rem 0.5rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        /* Date separator */
        .date-separator {
            position: relative;
            text-align: center;
            margin: 1rem 0;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        
        .date-separator::before,
        .date-separator::after {
            content: '';
            position: absolute;
            top: 50%;
            width: calc(50% - 70px);
            height: 1px;
            background-color: rgba(148, 163, 184, 0.3);
        }
        
        .date-separator::before {
            left: 0;
        }
        
        .date-separator::after {
            right: 0;
        }
        
        /* Debug panel */
        .debug-panel {
            background-color: rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .debug-panel h3 {
            color: #f97316;
            margin-top: 0;
            margin-bottom: 10px;
        }
        
        .debug-panel pre {
            color: #a3e635;
            white-space: pre-wrap;
        }
        
        /* Setup alert */
        .setup-alert {
            background-color: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* Fix for equal height cards */
        .movie-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        /* Responsive poster height */
        .poster-container {
            aspect-ratio: 2/3;
            overflow: hidden;
        }
        
        /* Ensure footer stays at bottom */
        .page-content {
            flex: 1;
        }
        
       
    </style>
</head>
<body>
    <?php include '../includes/nav.php'; ?>
    
    <div class="page-content">
        <?php if ($needsSetup): ?>
        <div class="container mx-auto px-4 mt-4">
            
        </div>
        <?php endif; ?>
        
        <?php if ($debug && !empty($debugInfo)): ?>
        <div class="container mx-auto px-4 mt-4">
            <div class="debug-panel">
                <h3>Debug Information</h3>
                <pre><?php echo json_encode($debugInfo, JSON_PRETTY_PRINT); ?></pre>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Location Selection Modal -->
        <?php if (!isset($_SESSION['location'])): ?>
        <div id="locationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60">
            <div class="location-modal p-8 rounded-2xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold heading-gradient">Select Your Location</h2>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <p class="text-gray-300 mb-6 text-sm font-light leading-relaxed">Please select your location to see movie showtimes near you. We'll remember your preference for future visits.</p>
                
                <form method="post" action="">
                    <div class="mb-6">
                        <label for="location" class="block text-sm font-medium text-gray-300 mb-2">Your Location</label>
                        <div class="relative">
                            <select id="location" name="location" class="w-full search-input rounded-lg py-3 px-4 pl-10 text-white focus:outline-none appearance-none">
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>"><?php echo htmlspecialchars($loc); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-300 absolute left-3 top-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 absolute right-3 top-3.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                    </div>
                    
                    <button type="submit" name="select_location" class="w-full book-button py-3 rounded-lg font-medium text-white shadow-lg transition-all">
                        Explore Movies
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Hero Section -->
        <section class="hero-section py-24 mb-16 relative">
            <div class="container mx-auto px-4 text-center relative z-10">
                <h1 class="text-4xl md:text-6xl font-bold mb-6 heading-gradient">Book Your Perfect Movie Experience</h1>
                <p class="text-xl text-gray-300 mb-10 max-w-2xl mx-auto font-light">Discover the latest blockbusters and book your tickets with just a few clicks. Premium experiences await.</p>
                <a href="#now-showing" class="book-button px-10 py-4 rounded-lg font-medium text-white inline-flex items-center shadow-lg">
                    Explore Movies
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 ml-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            </div>
        </section>
        
        <!-- Now Showing Section -->
        <section id="now-showing" class="container mx-auto px-4 mb-20">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-12">
                <h2 class="text-3xl font-bold text-white mb-6 md:mb-0 section-heading">
                    Now Showing in 
                    <span class="text-indigo-300"><?php echo isset($_SESSION['location']) ? htmlspecialchars($_SESSION['location']) : ''; ?></span>
                </h2>
                <div class="flex items-center space-x-4">
                    <div class="relative w-full md:w-64">
                        <input type="text" id="searchInput" placeholder="Search movies..." class="w-full search-input rounded-full py-2.5 px-4 pl-10 text-white focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-300 absolute left-3 top-2.5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <?php if (isset($_SESSION['location'])): ?>
                    <form method="post" action="" class="hidden md:block">
                        <div class="relative">
                            <select id="change_location" name="location" class="search-input rounded-lg py-2 px-4 pl-10 text-white focus:outline-none appearance-none pr-10" onchange="this.form.submit()">
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $loc === $_SESSION['location'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-300 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.  stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 absolute right-3 top-2.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <input type="hidden" name="select_location" value="1">
                    </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Movie Grid -->
            <div class="movie-grid" id="movieGrid">
                <?php if (empty($movies)): ?>
                    <div class="col-span-full text-center py-16">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-600 mb-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                        </svg>
                        <h3 class="text-2xl font-semibold mb-3">No Movies Available</h3>
                        <p class="text-gray-400 max-w-lg mx-auto font-light">There are no movies showing in your selected location at this time. Please check back later or choose a different location.</p>
                        
                        <?php if (isset($_SESSION['location'])): ?>
                        <div class="mt-6">
                            <form method="post" action="">
                                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                                    <div class="relative w-full sm:w-64">
                                        <select id="change_location" name="location" class="w-full search-input rounded-lg py-2.5 px-4 pl-10 text-white focus:outline-none appearance-none">
                                            <?php foreach ($locations as $loc): ?>
                                                <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $loc === $_SESSION['location'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($loc); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-300 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <button type="submit" name="select_location" class="book-button py-2.5 px-6 rounded-lg font-medium text-white shadow-lg transition-all">
                                        Change Location
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($movies as $movie): ?>
                    <div class="movie-card rounded-xl overflow-hidden">
                        <div class="video-container shimmer poster-container">
                            <img src="<?php echo !empty($movie['poster_url']) ? htmlspecialchars($movie['poster_url']) : 'assets/images/movie-placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-full object-cover">
                            <?php if (!empty($movie['trailer_url'])): ?>
                            <div class="video-overlay">
                                <div class="play-button" onclick="playTrailer('<?php echo htmlspecialchars($movie['trailer_url']); ?>', '<?php echo htmlspecialchars($movie['title']); ?>')">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-5 movie-info">
                            <h3 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <?php if (isset($movie['rating'])): ?>
                            <div class="flex items-center mb-3">
                                <?php 
                                $rating = min(5, max(0, $movie['rating']));
                                $fullStars = floor($rating);
                                $halfStar = $rating - $fullStars >= 0.5;
                                $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                                
                                for ($i = 0; $i < $fullStars; $i++): 
                                ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400 rating-star" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                <?php endfor; ?>
                                
                                <?php if ($halfStar): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-yellow-400 rating-star" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                <?php endif; ?>
                                
                                <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                    </svg>
                                <?php endfor; ?>
                                <span class="ml-2 text-sm text-gray-400 font-light"><?php echo $movie['rating']; ?>/5</span>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($movie['genre'])): ?>
                            <div class="flex flex-wrap gap-2 mb-4">
                                <?php 
                                $genres = explode(',', $movie['genre']);
                                foreach ($genres as $genre): 
                                    $genre = trim($genre);
                                    if (!empty($genre)):
                                ?>
                                    <span class="genre-tag text-xs px-3 py-1 rounded-full"><?php echo htmlspecialchars($genre); ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center text-sm text-gray-400 mb-5 font-light">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-indigo-300" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                </svg>
                                <?php echo $movie['duration']; ?> min
                                <span class="mx-2 text-gray-600">•</span>
                                <span><?php echo htmlspecialchars($movie['language']); ?></span>
                            </div>
                            
                            <!-- Theaters -->
                            <?php if (!empty($movie['theaters'])): ?>
                            <div class="mb-3">
                                <h4 class="text-sm font-medium text-gray-300 mb-2">Available at:</h4>
                                <div class="flex flex-wrap">
                                    <?php foreach ($movie['theaters'] as $theaterId => $theaterName): ?>
                                        <span class="theater-badge"><?php echo htmlspecialchars($theaterName); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-5">
                                <h4 class="text-sm font-medium text-gray-300 mb-3">Available Showtimes:</h4>
                                <div class="flex flex-wrap gap-2">
                                    <?php 
                                    $showIndex = 0;
                                    $currentDate = date('Y-m-d');
                                    $currentDateTime = date('Y-m-d H:i:s');
                                    $displayedDates = [];
                                    
                                    // Sort shows by time
                                    usort($movie['shows'], function($a, $b) {
                                        return strtotime($a['show_time']) - strtotime($b['show_time']);
                                    });
                                    
                                    foreach ($movie['shows'] as $show): 
                                        $showDate = date('Y-m-d', strtotime($show['show_time']));
                                        $showTime = date('h:i A', strtotime($show['show_time']));
                                        $primeTime = (strtotime($show['show_time']) >= strtotime($showDate . ' 18:00:00') && 
                                                     strtotime($show['show_time']) <= strtotime($showDate . ' 21:00:00'));
                                        
                                        // Only show future showtimes
                                        if (strtotime($show['show_time']) > strtotime($currentDateTime)):
                                            
                                            // Add date separator if this is a new date
                                            if (!in_array($showDate, $displayedDates)):
                                                $displayedDates[] = $showDate;
                                                $dateDisplay = ($showDate === $currentDate) ? 'Today' : date('D, M j', strtotime($showDate));
                                                if ($showIndex > 0): // Don't add separator before the first date
                                                ?>
                                                    <div class="w-full date-separator"><?php echo $dateDisplay; ?></div>
                                                <?php 
                                                endif;
                                            endif;
                                            
                                            // Only show first 6 showtimes
                                            if ($showIndex < 6):
                                        ?>
                                            <button class="show-time-btn <?php echo $primeTime ? 'prime-time' : ''; ?> text-xs px-3 py-1.5 rounded-full flex items-center" 
                                                    onclick="window.location.href='booking.php?show_id=<?php echo $show['show_id']; ?>'">
                                                <span><?php echo $showTime; ?></span>
                                                <?php if($primeTime): ?>
                                                    <span class="ml-1 w-1.5 h-1.5 rounded-full bg-yellow-400"></span>
                                                <?php endif; ?>
                                            </button>
                                        <?php 
                                            $showIndex++;
                                            endif; 
                                        endif;
                                    endforeach; 
                                    
                                    // Count future shows
                                    $futureShows = 0;
                                    foreach ($movie['shows'] as $show) {
                                        if (strtotime($show['show_time']) > strtotime($currentDateTime)) {
                                            $futureShows++;
                                        }
                                    }
                                    
                                    // Show "more" button if needed
                                    if ($futureShows > 6): 
                                    ?>
                                        <button class="show-time-btn bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1.5 rounded-full" 
                                                onclick="window.location.href='movie_detail.php?id=<?php echo $movie['movie_id']; ?>'">
                                            +<?php echo $futureShows - 6; ?> more
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="movie-info-footer mt-auto pt-4 border-t border-gray-700">
    <div class="flex items-center justify-between mb-3">
        <div class="text-sm text-gray-400">
            <span class="font-medium text-indigo-300">From </span>
            <span class="text-lg font-semibold">₹<?php 
                $prices = array_column($movie['shows'], 'price');
                echo !empty($prices) ? number_format(min($prices), 2) : '0.00'; 
            ?></span>
        </div>
        <?php if (!empty($movie['shows'])): ?>
            <span class="text-xs text-green-400 bg-green-400/10 px-2 py-1 rounded-full">
                <?php echo count($movie['shows']); ?> shows available
            </span>
        <?php endif; ?>
    </div>
    
    <div class="flex gap-2">
        <a href="movie_detail.php?id=<?php echo $movie['movie_id']; ?>" 
           class="book-button flex-1 py-3 px-4 text-center text-white rounded-lg transition duration-300 hover:bg-red-600 bg-red-500 font-medium">
            Book Now
        </a>
        <?php if (!empty($movie['trailer_url'])): ?>
            <button onclick="playTrailer('<?php echo htmlspecialchars($movie['trailer_url']); ?>', '<?php echo htmlspecialchars($movie['title']); ?>')"
                    class="trailer-button px-4 rounded-lg bg-gray-700 hover:bg-gray-600 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                </svg>
            </button>
        <?php endif; ?>
    </div>
</div>

                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Coming Soon Section -->
        <section class="container mx-auto px-4 mb-20">
            <h2 class="text-3xl font-bold text-white mb-10 section-heading">Coming Soon</h2>
            
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-5">
                <?php if (isset($comingSoonMovies) && !empty($comingSoonMovies)): ?>
                    <?php foreach ($comingSoonMovies as $movie): ?>
                    <div class="coming-soon-card rounded-xl overflow-hidden shadow-md">
                        <div class="h-40 bg-gray-800 shimmer">
                            <?php if (!empty($movie['poster_url'])): ?>
                                <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <h3 class="text-sm font-medium text-white mb-1 truncate"><?php echo htmlspecialchars($movie['title']); ?></h3>
                            <p class="text-xs text-gray-400">
                                <?php echo !empty($movie['release_date']) ? date('M d, Y', strtotime($movie['release_date'])) : 'Coming Soon'; ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Placeholder for coming soon movies -->
                    <?php for ($i = 0; $i < 6; $i++): ?>
                    <div class="coming-soon-card rounded-xl overflow-hidden shadow-md">
                        <div class="h-40 bg-gray-800 flex items-center justify-center shimmer">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z" />
                            </svg>
                        </div>
                        <div class="p-4">
                            <div class="h-4 bg-gray-700 rounded mb-2 shimmer"></div>
                            <div class="h-3 bg-gray-700 rounded w-3/4 shimmer"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
    <!-- Video Modal -->
    <div id="trailerModal" class="fixed inset-0 z-50 flex items-center justify-center hidden modal">
        <div class="absolute inset-0 bg-black bg-opacity-80 backdrop-blur-sm" onclick="closeTrailerModal()"></div>
        <div class="relative bg-gray-900 rounded-xl overflow-hidden w-11/12 max-w-4xl border border-gray-800">
            <div class="absolute top-4 right-4 z-10">
                <button onclick="closeTrailerModal()" class="bg-gray-800 bg-opacity-70 hover:bg-gray-700 text-white p-2 rounded-full flex items-center justify-center transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div id="trailerTitleBar" class="bg-gray-900 bg-opacity-80 backdrop-blur-sm py-3 px-5 border-b border-gray-800">
                <h3 id="trailerTitle" class="text-lg font-medium text-white"></h3>
            </div>
            
            <div id="trailerContainer" class="relative pt-16:9 h-0 pb-[56.25%]">
                <iframe id="trailerPlayer" class="absolute top-0 left-0 w-full h-full" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
    
    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const movieGrid = document.getElementById('movieGrid');
        const movieCards = movieGrid ? movieGrid.querySelectorAll('.movie-card') : [];
        
        if (searchInput && movieCards.length > 0) {
            searchInput.addEventListener('keyup', function() {
                const searchTerm = this.value.toLowerCase();
                let foundMovies = false;
                
                movieCards.forEach(card => {
                    const title = card.querySelector('h3').textContent.toLowerCase();
                    const genreTags = card.querySelectorAll('.genre-tag');
                    const genres = Array.from(genreTags).map(tag => tag.textContent.toLowerCase());
                    const theaterBadges = card.querySelectorAll('.theater-badge');
                    const theaters = Array.from(theaterBadges).map(badge => badge.textContent.toLowerCase());
                    
                    if (title.includes(searchTerm) || 
                        genres.some(genre => genre.includes(searchTerm)) ||
                        theaters.some(theater => theater.includes(searchTerm))) {
                        card.style.display = 'block';
                        foundMovies = true;
                    } else {
                        card.style.display = 'none';
                    }
                });
                
                // Show no results message if needed
                let noResultsMsg = document.getElementById('noResultsMessage');
                if (!foundMovies && searchTerm) {
                    if (!noResultsMsg) {
                        noResultsMsg = document.createElement('div');
                        noResultsMsg.id = 'noResultsMessage';
                        noResultsMsg.className = 'col-span-full text-center py-8';
                        noResultsMsg.innerHTML = `
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-600 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <h3 class="text-xl font-medium text-gray-400 mb-2">No movies found</h3>
                            <p class="text-gray-500">No movies match your search for "${searchTerm}"</p>
                        `;
                        movieGrid.appendChild(noResultsMsg);
                    }
                } else if (noResultsMsg) {
                    noResultsMsg.remove();
                }
            });
        }
        
        // Trailer functionality
        const trailerModal = document.getElementById('trailerModal');
        const trailerPlayer = document.getElementById('trailerPlayer');
        const trailerTitle = document.getElementById('trailerTitle');
        
        function playTrailer(trailerUrl, movieTitle) {
            if (trailerModal && trailerPlayer) {
                trailerModal.classList.remove('hidden');
                
                // Set title
                if (trailerTitle) {
                    trailerTitle.textContent = movieTitle || 'Movie Trailer';
                }
                
                // Extract YouTube video ID
                let videoId = '';
                if (trailerUrl.includes('youtube.com')) {
                    videoId = new URL(trailerUrl).searchParams.get('v');
                } else if (trailerUrl.includes('youtu.be')) {
                    videoId = trailerUrl.split('/').pop();
                }
                
                if (videoId) {
                    trailerPlayer.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
                } else {
                    // Fallback for non-YouTube videos
                    trailerPlayer.src = trailerUrl;
                }
                
                // Prevent body scrolling
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeTrailerModal() {
            if (trailerModal && trailerPlayer) {
                trailerModal.classList.add('hidden');
                trailerPlayer.src = '';
                
                // Re-enable body scrolling
                document.body.style.overflow = '';
            }
        }
        
        // Close modal with escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeTrailerModal();
            }
        });
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        // Fix image loading errors
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    this.src = '../assets/image/kgf.png';
                });
            });
        });
    </script>
</body>
</html>
